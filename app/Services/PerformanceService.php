<?php

namespace App\Services;

use App\Models\Performance;
use App\Models\PerformanceAttachment;
use App\Models\PerformanceStaff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PerformanceService
{
    public function getFilteredPerformances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Performance::with(['phases.location'])
            ->where('performances.is_active', 1);

        // 検索フィルターを適用
        $this->applyFilters($query, $filters);

        return $query->leftJoin('phases', 'performances.id', '=', 'phases.performance_id')
            ->select('performances.*')
            ->withCount('phases')
            ->orderByRaw('COALESCE(MAX(phases.start_date), performances.created_at) DESC')
            ->groupBy('performances.id')
            ->paginate($perPage);
    }

    public function createPerformance(array $data): Performance
    {
        return DB::transaction(function () use ($data) {
            $performance = Performance::create($this->extractPerformanceData($data));

            $this->syncProductions($performance, $data['production_ids'] ?? []);
            $this->syncStaff($performance, $data);
            $this->handleFileUploads($performance, $data);

            return $performance->load(['phases.location', 'staff.user', 'staff.position', 'productions', 'attachments']);
        });
    }

    public function updatePerformance(Performance $performance, array $data): Performance
    {
        return DB::transaction(function () use ($performance, $data) {
            $performance->update($this->extractPerformanceData($data));

            $this->syncProductions($performance, $data['production_ids'] ?? []);
            $this->syncStaff($performance, $data);
            $this->handleFileUploads($performance, $data);
            $this->handleFileDeletions($performance, $data);

            return $performance->load(['phases.location', 'staff.user', 'staff.position', 'productions', 'attachments']);
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->where('performances.title', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['performance_type'])) {
            $query->where('performances.performance_type', $filters['performance_type']);
        }

        if (! empty($filters['phase_status'])) {
            $today = now()->toDateString();

            if ($filters['phase_status'] === 'completed') {
                // 完了：すべてのフェーズが完了している公演のみ
                $query->whereHas('phases', function ($q) use ($today) {
                    $q->where('end_date', '<', $today);
                })->whereDoesntHave('phases', function ($q) use ($today) {
                    $q->where('end_date', '>=', $today);
                });
            } else {
                $query->whereHas('phases', function ($q) use ($filters, $today) {
                    match ($filters['phase_status']) {
                        'in_progress' => $q->where('start_date', '<=', $today)->where('end_date', '>=', $today),
                        'upcoming' => $q->where('start_date', '>', $today),
                        default => null,
                    };
                });
            }
        }
    }

    private function extractPerformanceData(array $data): array
    {
        return [
            'title' => $data['title'],
            'short_name' => $data['short_name'] ?? null,
            'performance_type' => $data['performance_type'],
            'director' => $data['director'] ?? null,
            'status' => $data['status'] ?? 'in_progress',
            'note' => $data['note'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }

    private function syncProductions(Performance $performance, array $productionIds): void
    {
        $performance->productions()->sync($productionIds);
    }

    private function syncStaff(Performance $performance, array $data): void
    {
        // 既存のスタッフを削除
        $performance->staff()->delete();

        // 通常のスタッフを追加
        if (! empty($data['staff'])) {
            foreach ($data['staff'] as $staffData) {
                if (! empty($staffData['user_id']) && ! empty($staffData['position_id'])) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $staffData['user_id'],
                        'position_id' => $staffData['position_id'],
                    ]);
                }
            }
        }

        // サウンドデザイナーを追加
        if (! empty($data['sound_designers'])) {
            foreach ($data['sound_designers'] as $designerId) {
                if (! empty($designerId)) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $designerId,
                        'position_id' => 1, // サウンドデザインのポジションID
                    ]);
                }
            }
        }
    }

    /**
     * ファイルアップロード処理
     */
    private function handleFileUploads(Performance $performance, array $data): void
    {
        Log::info('ファイルアップロード処理開始', ['performance_id' => $performance->id, 'has_attachments' => ! empty($data['attachments'])]);

        if (! empty($data['attachments'])) {
            Log::info('添付ファイル数', ['count' => count($data['attachments'])]);

            foreach ($data['attachments'] as $index => $file) {
                Log::info('ファイル処理中', ['index' => $index, 'is_valid' => $file && $file->isValid()]);

                if ($file && $file->isValid()) {
                    // ファイルを保存
                    $filename = time().'_'.$file->getClientOriginalName();
                    $path = $file->storeAs('performances/'.$performance->id, $filename, 'public');

                    Log::info('ファイル保存完了', [
                        'filename' => $filename,
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);

                    // データベースに記録
                    $attachment = PerformanceAttachment::create([
                        'performance_id' => $performance->id,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);

                    Log::info('DB記録完了', ['attachment_id' => $attachment->id]);

                    // 画像の場合はサムネイル生成
                    if ($attachment->isImage()) {
                        $thumbnailResult = $attachment->generateThumbnail();
                        Log::info('サムネイル生成結果', ['result' => $thumbnailResult]);
                    }
                }
            }
        }
    }

    /**
     * ファイル削除処理（編集時）
     */
    private function handleFileDeletions(Performance $performance, array $data): void
    {
        if (! empty($data['delete_attachments'])) {
            $deleteIds = $data['delete_attachments'];
            $attachments = $performance->attachments()->whereIn('id', $deleteIds)->get();

            foreach ($attachments as $attachment) {
                // ファイルを削除
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }

                // サムネイルも削除
                if ($attachment->thumbnail_path && Storage::disk('public')->exists($attachment->thumbnail_path)) {
                    Storage::disk('public')->delete($attachment->thumbnail_path);
                }

                // データベースから削除
                $attachment->delete();
            }
        }
    }
}
