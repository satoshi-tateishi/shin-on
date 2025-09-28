<?php

namespace App\Services;

use App\Models\Performance;
use App\Models\PerformanceStaff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

            return $performance->load(['phases.location', 'staff.user', 'staff.position', 'productions']);
        });
    }

    public function updatePerformance(Performance $performance, array $data): Performance
    {
        return DB::transaction(function () use ($performance, $data) {
            $performance->update($this->extractPerformanceData($data));

            $this->syncProductions($performance, $data['production_ids'] ?? []);
            $this->syncStaff($performance, $data);

            return $performance->load(['phases.location', 'staff.user', 'staff.position', 'productions']);
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where('performances.title', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['performance_type'])) {
            $query->where('performances.performance_type', $filters['performance_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('performances.status', $filters['status']);
        }
    }

    private function extractPerformanceData(array $data): array
    {
        return [
            'title' => $data['title'],
            'short_name' => $data['short_name'] ?? null,
            'performance_type' => $data['performance_type'],
            'director' => $data['director'] ?? null,
            'status' => $data['status'],
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
        if (!empty($data['staff'])) {
            foreach ($data['staff'] as $staffData) {
                if (!empty($staffData['user_id']) && !empty($staffData['position_id'])) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $staffData['user_id'],
                        'position_id' => $staffData['position_id'],
                    ]);
                }
            }
        }

        // サウンドデザイナーを追加
        if (!empty($data['sound_designers'])) {
            foreach ($data['sound_designers'] as $designerId) {
                if (!empty($designerId)) {
                    PerformanceStaff::create([
                        'performance_id' => $performance->id,
                        'user_id' => $designerId,
                        'position_id' => 1, // サウンドデザインのポジションID
                    ]);
                }
            }
        }
    }
}