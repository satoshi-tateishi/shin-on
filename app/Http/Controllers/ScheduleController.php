<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use App\Models\PhaseEquipment;
use App\Models\RepairRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ScheduleController extends Controller
{
    /**
     * スケジュール表メイン画面を表示
     */
    public function index()
    {
        return view('schedule.index');
    }

    /**
     * 機材スケジュールデータをAPI形式で取得
     */
    public function getEquipmentSchedule(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'category_id' => 'nullable|exists:equipment_categories,id',
                'subcategory_id' => 'nullable|exists:equipment_subcategories,id',
                'equipment_ids' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'バリデーションエラー: ' . $e->getMessage()
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $categoryId = $request->category_id;
            $subcategoryId = $request->subcategory_id;
            $equipmentIds = $request->equipment_ids ?
                array_map('intval', explode(',', $request->equipment_ids)) : null;

            $page = $request->get('page', 1);
            $perPage = min($request->get('per_page', 100), 100); // 最大100件に制限

            // 機材の基本データを取得（ページネーション対応）
            $equipmentQuery = Equipment::with(['category', 'subcategory'])
                ->where('is_discard', false)
                ->where('is_schedule_visible', true);

            if ($subcategoryId) {
                $equipmentQuery->where('subcategory_id', $subcategoryId);
            } elseif ($categoryId) {
                $equipmentQuery->whereHas('subcategory', function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                });
            }

            if ($equipmentIds) {
                $equipmentQuery->whereIn('id', $equipmentIds);
            }

            $equipmentQuery->orderBy('sort')
                ->orderBy('name');

            $equipments = $equipmentQuery->paginate($perPage, ['*'], 'page', $page);

            $equipmentIds = $equipments->pluck('id')->toArray();

            // 指定期間内のフェーズ機材使用データを取得（クエリ最適化）
            $phaseEquipments = $this->getPhaseEquipmentsInDateRange(
                $equipmentIds,
                $startDate,
                $endDate
            );

            // 修理・メンテナンス情報を取得（クエリ最適化）
            $repairRecords = $this->getRepairRecordsInDateRange(
                $equipmentIds,
                $startDate,
                $endDate
            );

            // 日付範囲を生成
            $dateRange = $this->generateDateRange($startDate, $endDate);

            // スケジュールデータを構築
            $scheduleData = $this->buildScheduleData(
                $equipments->items(),
                $dateRange,
                $phaseEquipments,
                $repairRecords
            );

            return response()->json([
                'success' => true,
                'equipment_schedules' => $scheduleData,
                'pagination' => [
                    'current_page' => $equipments->currentPage(),
                    'last_page' => $equipments->lastPage(),
                    'per_page' => $equipments->perPage(),
                    'total' => $equipments->total(),
                    'from' => $equipments->firstItem(),
                    'to' => $equipments->lastItem(),
                ],
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'サーバーエラーが発生しました: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * 指定期間内のフェーズ機材使用データを取得
     */
    private function getPhaseEquipmentsInDateRange(array $equipmentIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return PhaseEquipment::with([
            'phase.performance',
            'equipment',
            'checkoutUser',
            'checkinUser',
        ])
            ->whereIn('equipment_id', $equipmentIds)
            ->whereHas('phase', function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // フェーズ期間と指定期間が重複する場合
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                        ->where('end_date', '>=', $startDate->format('Y-m-d'));
                });
            })
            ->get()
            ->groupBy('equipment_id');
    }

    /**
     * 指定期間内の修理・メンテナンス記録を取得
     */
    private function getRepairRecordsInDateRange(array $equipmentIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return RepairRecord::with('equipment')
            ->whereIn('equipment_id', $equipmentIds)
            ->where(function ($query) use ($startDate, $endDate) {
                // 修理期間と指定期間が重複する場合（修理開始日基準）
                $query->whereNotNull('started_at')
                    ->where('started_at', '<=', $endDate->format('Y-m-d'))
                    ->where(function ($q) use ($startDate) {
                        // 修理完了日がnullまたは指定開始日以降
                        $q->whereNull('completed_at')
                            ->orWhere('completed_at', '>=', $startDate->format('Y-m-d'));
                    });
            })
            ->get()
            ->groupBy('equipment_id');
    }

    /**
     * 日付範囲を生成
     */
    private function generateDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * スケジュールデータを構築
     */
    private function buildScheduleData(array $equipments, array $dateRange, Collection $phaseEquipments, Collection $repairRecords): array
    {
        $scheduleData = [];

        foreach ($equipments as $equipment) {
            $equipmentPhases = $phaseEquipments->get($equipment->id, collect());
            $equipmentRepairs = $repairRecords->get($equipment->id, collect());

            $dailyStatus = [];

            foreach ($dateRange as $date) {
                $status = $this->determineEquipmentStatusForDate(
                    $equipment,
                    $date,
                    $equipmentPhases,
                    $equipmentRepairs
                );

                $dailyStatus[$date] = $status;
            }

            $scheduleData[] = [
                'equipment_id' => $equipment->id,
                'equipment_name' => $equipment->name,
                'equipment_code' => $equipment->company_number,
                'category' => $equipment->category->name ?? null,
                'subcategory' => $equipment->subcategory->name ?? null,
                'management_type' => $equipment->management_type,
                'total_quantity' => $equipment->quantity,
                'daily_status' => $dailyStatus,
            ];
        }

        return $scheduleData;
    }

    /**
     * 指定日における機材のステータスを決定
     */
    private function determineEquipmentStatusForDate(Equipment $equipment, string $date, Collection $phases, Collection $repairs): array
    {
        $dateCarbon = Carbon::parse($date);

        // 1. 修理・メンテナンス状態をチェック（最優先）
        foreach ($repairs as $repair) {
            // キャンセルされた修理は無視
            if ($repair->status === 'cancelled') {
                continue;
            }

            $startedDate = $repair->started_at ? Carbon::parse($repair->started_at) : null;
            $completedDate = $repair->completed_at ? Carbon::parse($repair->completed_at) : null;

            // 修理開始日以降、完了日前まで修理中として扱う（修理開始前は使用可能）
            if ($startedDate && $startedDate->lte($dateCarbon) && (! $completedDate || $dateCarbon->lt($completedDate))) {
                return [
                    'status' => 'repair',
                    'detail' => $repair->repair_type ?? 'repair',
                    'phase_name' => null,
                    'performance_title' => null,
                    'note' => $repair->problem_description ?? $repair->symptoms,
                ];
            }
        }

        // 2. フェーズでの使用状態をチェック
        foreach ($phases as $phaseEquipment) {
            $phase = $phaseEquipment->phase;
            $phaseStart = Carbon::parse($phase->start_date);
            $phaseEnd = Carbon::parse($phase->end_date);

            if ($phaseStart->lte($dateCarbon) && $phaseEnd->gte($dateCarbon)) {
                // フェーズ期間内でのステータス判定
                $status = $this->determinePhaseEquipmentStatus($phaseEquipment, $dateCarbon);

                return [
                    'status' => $status,
                    'detail' => $phaseEquipment->status,
                    'phase_name' => $phase->name,
                    'performance_title' => $phase->performance->display_name ?? null,
                    'note' => $phaseEquipment->note,
                ];
            }
        }

        // 3. 機材自体のステータス（デフォルト）
        // ただし、修理記録がある場合は修理期間外は'available'とする
        $hasRepairRecords = $repairs->isNotEmpty();
        $defaultStatus = $hasRepairRecords ? 'available' : $equipment->status;

        return [
            'status' => $defaultStatus,
            'detail' => $defaultStatus,
            'phase_name' => null,
            'performance_title' => null,
            'note' => null,
        ];
    }

    /**
     * フェーズ機材のステータスを判定
     */
    private function determinePhaseEquipmentStatus(PhaseEquipment $phaseEquipment, Carbon $date): string
    {
        switch ($phaseEquipment->status) {
            case 'reserved':
                return 'reserved';
            case 'checked_out':
                return 'checked_out';
            case 'checked_in':
                return 'checked_in';
            case 'cancelled':
                return 'available';
            default:
                return 'available';
        }
    }

    /**
     * カテゴリ一覧を取得（フィルタリング用）
     */
    public function getCategories()
    {
        $categories = \App\Models\EquipmentCategory::where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * サブカテゴリ一覧を取得（カテゴリIDで絞り込み）
     */
    public function getSubcategories(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:equipment_categories,id',
        ]);

        $query = EquipmentSubcategory::where('is_active', true);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $subcategories = $query->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        return response()->json([
            'success' => true,
            'subcategories' => $subcategories
        ]);
    }

    /**
     * 機材一覧を取得（フィルタリング用）
     */
    public function getEquipments(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:equipment_categories,id',
            'subcategory_id' => 'nullable|exists:equipment_subcategories,id',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Equipment::where('is_discard', false)
            ->where('is_schedule_visible', true);

        if ($request->subcategory_id) {
            $query->where('subcategory_id', $request->subcategory_id);
        } elseif ($request->category_id) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_number', 'like', "%{$search}%");
            });
        }

        $equipments = $query->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'company_number']);

        return response()->json(['equipments' => $equipments]);
    }
}
