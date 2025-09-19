<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use App\Models\RepairRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class RepairRecordController extends Controller
{
    /**
     * Display a listing of repair records.
     */
    public function index(Request $request): View
    {
        $query = RepairRecord::with([
            'equipment.subcategory.category',
            'reportedBy',
        ]);

        // フィルタリング
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }


        if ($request->filled('equipment_category')) {
            $query->whereHas('equipment.subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->equipment_category);
            });
        }

        if ($request->filled('equipment_subcategory')) {
            $query->whereHas('equipment', function ($q) use ($request) {
                $q->where('subcategory_id', $request->equipment_subcategory);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('problem_description', 'like', "%{$search}%")
                    ->orWhere('repair_description', 'like', "%{$search}%")
                    ->orWhere('repair_company', 'like', "%{$search}%")
                    ->orWhereHas('equipment', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('model_number', 'like', "%{$search}%");
                    });
            });
        }


        $repairRecords = $query->orderBy('reported_at', 'desc')->paginate(20);

        // 統計データ
        $stats = [
            'total' => RepairRecord::count(),
            'reported' => RepairRecord::reported()->count(),
            'in_progress' => RepairRecord::inProgress()->count(),
            'completed' => RepairRecord::completed()->count(),
        ];

        // フィルタ用データ
        $equipmentCategories = EquipmentCategory::active()->ordered()->get();
        $equipmentSubcategories = EquipmentSubcategory::with('category')->ordered()->get();

        return view('repair-records.index', compact(
            'repairRecords',
            'stats',
            'equipmentCategories',
            'equipmentSubcategories'
        ));
    }

    /**
     * Show the form for creating a new repair record.
     */
    public function create(Request $request): View
    {
        $equipment = null;
        if ($request->filled('equipment_id')) {
            $equipment = Equipment::with('subcategory.category')->find($request->equipment_id);
        }

        $equipments = Equipment::with('subcategory.category')
            ->active()
            ->join('equipment_subcategories', 'equipments.subcategory_id', '=', 'equipment_subcategories.id')
            ->join('equipment_categories', 'equipment_subcategories.category_id', '=', 'equipment_categories.id')
            ->orderBy('equipment_categories.sort')
            ->orderBy('equipment_subcategories.sort')
            ->orderBy('equipments.sort')
            ->select('equipments.*')
            ->get();

        $staffUsers = User::where('is_staff', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return view('repair-records.create', compact('equipment', 'equipments', 'staffUsers'));
    }

    /**
     * Store a newly created repair record.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate(RepairRecord::getValidationRules());
            $validated['reported_by'] = auth()->id();
            $validated['reported_at'] = now();

            // デフォルト値を設定
            $validated['status'] = 'reported';

            // 写真のアップロード・リサイズ処理
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                $photos = $request->file('photos');

                // ファイル数チェック（最大2枚）
                if (count($photos) > 2) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['photos' => 'アップロードできる写真は最大2枚までです。']);
                }

                foreach ($photos as $photo) {
                    try {
                        // ファイル名を生成
                        $filename = uniqid() . '_' . time() . '.jpg';
                        $path = 'repair-photos/' . $filename;

                        // 画像をリサイズ（幅800px、アスペクト比維持、品質75%）
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($photo);
                        $resizedImage = $image->scale(width: 800)->toJpeg(75);

                        // publicディスクに保存
                        Storage::disk('public')->put($path, $resizedImage);
                        $photoPaths[] = $path;
                    } catch (\Exception $e) {
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['photos' => '画像処理でエラーが発生しました: ' . $e->getMessage()]);
                    }
                }
            }
            $validated['photos'] = $photoPaths;

            DB::transaction(function () use ($validated) {
                $repairRecord = RepairRecord::create($validated);

                // 機材ステータスを修理中に更新（必要に応じて）
                if ($validated['status'] === 'in_progress') {
                    Equipment::find($validated['equipment_id'])->update(['status' => 'repair']);
                }
            });

            return redirect()
                ->route('repair-records.index')
                ->with('success', '修理記録を作成しました。');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified repair record.
     */
    public function show(RepairRecord $repairRecord): View
    {
        $repairRecord->load([
            'equipment.subcategory.category',
            'reportedBy',
            'staffUser',
        ]);

        // 同じ機材の修理履歴
        $relatedRepairs = RepairRecord::where('equipment_id', $repairRecord->equipment_id)
            ->where('id', '!=', $repairRecord->id)
            ->with('reportedBy')
            ->orderBy('reported_at', 'desc')
            ->limit(5)
            ->get();

        return view('repair-records.show', compact('repairRecord', 'relatedRepairs'));
    }

    /**
     * Show the form for editing the repair record.
     */
    public function edit(RepairRecord $repairRecord): View
    {
        $repairRecord->load('equipment.subcategory.category');

        $equipments = Equipment::with('subcategory.category')
            ->active()
            ->join('equipment_subcategories', 'equipments.subcategory_id', '=', 'equipment_subcategories.id')
            ->join('equipment_categories', 'equipment_subcategories.category_id', '=', 'equipment_categories.id')
            ->orderBy('equipment_categories.sort')
            ->orderBy('equipment_subcategories.sort')
            ->orderBy('equipments.sort')
            ->select('equipments.*')
            ->get();

        $staffUsers = User::where('is_staff', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return view('repair-records.edit', compact('repairRecord', 'equipments', 'staffUsers'));
    }

    /**
     * Update the specified repair record.
     */
    public function update(Request $request, RepairRecord $repairRecord): RedirectResponse
    {
        try {
            $validated = $request->validate(RepairRecord::getValidationRules(true));

            // 写真の処理
            $existingPhotos = $repairRecord->photos;

            // 既存の写真パスを安全に抽出
            $photoPaths = [];

            if ($existingPhotos) {
                if (is_string($existingPhotos)) {
                    $decoded = json_decode($existingPhotos, true);
                    if ($decoded) {
                        $existingPhotos = $decoded;
                    }
                }

                // 再帰的に文字列のパスを抽出
                $this->extractValidPhotoPaths($existingPhotos, $photoPaths);
            }

            // 削除対象の写真を除外
            if ($request->filled('removed_photos')) {
                $removedIndexes = explode(',', $request->removed_photos);
                $removedIndexes = array_map('intval', $removedIndexes);

                // 削除対象のファイルを物理削除
                foreach ($removedIndexes as $removeIndex) {
                    if (isset($photoPaths[$removeIndex])) {
                        $filePath = $photoPaths[$removeIndex];
                        if (Storage::disk('public')->exists($filePath)) {
                            Storage::disk('public')->delete($filePath);
                        }
                    }
                }

                // 配列から削除対象を除外
                $photoPaths = array_filter($photoPaths, function($path, $index) use ($removedIndexes) {
                    return !in_array($index, $removedIndexes);
                }, ARRAY_FILTER_USE_BOTH);

                // インデックスを再整理
                $photoPaths = array_values($photoPaths);
            }

            if ($request->hasFile('photos')) {
                $photos = $request->file('photos');

                // 既存写真数と新規写真数の合計チェック（最大2枚）
                $totalCount = count($photoPaths) + count($photos);
                if ($totalCount > 2) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['photos' => 'アップロードできる写真は最大2枚までです。']);
                }

                foreach ($photos as $photo) {
                    try {
                        // ファイル名を生成
                        $filename = uniqid() . '_' . time() . '.jpg';
                        $path = 'repair-photos/' . $filename;

                        // 画像をリサイズ（幅800px、アスペクト比維持、品質75%）
                        $manager = new ImageManager(new Driver());
                        $image = $manager->read($photo);
                        $resizedImage = $image->scale(width: 800)->toJpeg(75);

                        // publicディスクに保存
                        Storage::disk('public')->put($path, $resizedImage);
                        $photoPaths[] = $path;
                    } catch (\Exception $e) {
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['photos' => '画像処理でエラーが発生しました: ' . $e->getMessage()]);
                    }
                }
            }
            $validated['photos'] = $photoPaths;

            // removed_photosはデータベースに保存しないので削除
            unset($validated['removed_photos']);

            DB::transaction(function () use ($repairRecord, $validated) {
                $oldStatus = $repairRecord->status;
                $repairRecord->update($validated);

                // ステータス変更時の機材ステータス更新
                if ($oldStatus !== $validated['status']) {
                    $this->updateEquipmentStatus($repairRecord->equipment, $validated['status']);
                }
            });

            return redirect()
                ->route('repair-records.show', $repairRecord)
                ->with('success', '修理記録を更新しました。');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the repair record.
     */
    public function destroy(RepairRecord $repairRecord): RedirectResponse
    {
        $repairRecord->delete();

        return redirect()
            ->route('repair-records.index')
            ->with('success', '修理記録を削除しました。');
    }

    /**
     * Start repair work.
     */
    public function start(Request $request, RepairRecord $repairRecord): RedirectResponse
    {
        if ($repairRecord->status !== 'reported') {
            return redirect()->back()->with('error', '報告済み状態の修理のみ開始できます。');
        }

        $validated = $request->validate([
            'started_at' => 'required|date',
            'repair_company' => 'nullable|string|max:255',
            'repaired_by' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($repairRecord, $validated) {
            $repairRecord->update([
                'status' => 'in_progress',
                'started_at' => $validated['started_at'],
                'repair_company' => $validated['repair_company'],
                'repaired_by' => $validated['repaired_by'],
            ]);

            // 機材ステータスを修理中に更新
            $repairRecord->equipment->update(['status' => 'repair']);
        });

        return redirect()
            ->route('repair-records.show', $repairRecord)
            ->with('success', '修理を開始しました。');
    }

    /**
     * Complete repair work.
     */
    public function complete(Request $request, RepairRecord $repairRecord): RedirectResponse
    {
        if ($repairRecord->status !== 'in_progress') {
            return redirect()->back()->with('error', '修理中状態の修理のみ完了できます。');
        }

        $validated = $request->validate([
            'completed_at' => 'required|date|after_or_equal:started_at',
            'repair_description' => 'required|string|max:10000',
            'repair_cost' => 'nullable|numeric|min:0',
            'warranty_until' => 'nullable|date|after_or_equal:today',
            'note' => 'nullable|string|max:10000',
        ]);

        DB::transaction(function () use ($repairRecord, $validated) {
            $repairRecord->update([
                'status' => 'completed',
                'completed_at' => $validated['completed_at'],
                'repair_description' => $validated['repair_description'],
                'repair_cost' => $validated['repair_cost'],
                'warranty_until' => $validated['warranty_until'],
                'note' => $validated['note'],
            ]);

            // 機材ステータスを利用可能に戻す
            $repairRecord->equipment->update(['status' => 'available']);
        });

        return redirect()
            ->route('repair-records.show', $repairRecord)
            ->with('success', '修理を完了しました。');
    }

    /**
     * Cancel repair work.
     */
    public function cancel(Request $request, RepairRecord $repairRecord): RedirectResponse
    {
        if ($repairRecord->status === 'completed') {
            return redirect()->back()->with('error', '完了済みの修理はキャンセルできません。');
        }

        $validated = $request->validate([
            'note' => 'required|string|max:10000',
        ]);

        DB::transaction(function () use ($repairRecord, $validated) {
            $repairRecord->update([
                'status' => 'cancelled',
                'note' => $validated['note'],
            ]);

            // 機材ステータスを利用可能に戻す
            $repairRecord->equipment->update(['status' => 'available']);
        });

        return redirect()
            ->route('repair-records.show', $repairRecord)
            ->with('success', '修理をキャンセルしました。');
    }

    /**
     * Get repair statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_repairs' => RepairRecord::count(),
            'urgent_repairs' => RepairRecord::urgent()->count(),
            'in_progress' => RepairRecord::inProgress()->count(),
            'this_month_cost' => RepairRecord::completedBetween(
                now()->startOfMonth(),
                now()->endOfMonth()
            )->sum('repair_cost'),
            'avg_repair_days' => RepairRecord::completed()
                ->whereNotNull(['started_at', 'completed_at'])
                ->get()
                ->avg('repair_duration'),
        ];

        return response()->json($stats);
    }

    /**
     * 配列から有効な写真パスを再帰的に抽出
     */
    private function extractValidPhotoPaths($data, array &$result): void
    {
        if (is_string($data) && !empty($data)) {
            $result[] = $data;
        } elseif (is_array($data)) {
            foreach ($data as $item) {
                $this->extractValidPhotoPaths($item, $result);
            }
        }
    }

    /**
     * Update equipment status based on repair status.
     */
    private function updateEquipmentStatus(Equipment $equipment, string $repairStatus): void
    {
        $equipmentStatus = match ($repairStatus) {
            'in_progress' => 'repair',
            'completed', 'cancelled' => 'available',
            default => $equipment->status,
        };

        if ($equipment->status !== $equipmentStatus) {
            $equipment->update(['status' => $equipmentStatus]);
        }
    }
}
