<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
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
                'equipment_name' => 'nullable|string|max:255',
                'performance_id' => 'nullable|exists:performances,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'バリデーションエラー: '.$e->getMessage(),
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

            // 機材名で検索
            if ($request->equipment_name) {
                $equipmentQuery->where('name', 'like', "%{$request->equipment_name}%");
            }

            // 公演でフィルタ（その公演のフェーズに紐づく機材のみ表示）
            if ($request->performance_id) {
                $equipmentQuery->whereHas('phaseEquipments.phase', function ($query) use ($request) {
                    $query->where('performance_id', $request->performance_id);
                });
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
                'error' => 'サーバーエラーが発生しました: '.$e->getMessage(),
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
    private function determineEquipmentStatusForDate(string $date, Collection $phases, Collection $repairs): array
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

        // 2. フェーズでの使用状態をチェック（checkout_date/checkin_dateベース）
        $applicablePhaseEquipments = [];
        foreach ($phases as $phaseEquipment) {
            // checkout_date と checkin_date で実際の使用期間を判定
            $isWithinUsagePeriod = $this->isDateWithinUsagePeriod($phaseEquipment, $dateCarbon);

            if ($isWithinUsagePeriod) {
                $applicablePhaseEquipments[] = $phaseEquipment;
            }
        }

        // 該当するphase_equipmentがある場合、優先順位で選択
        if (!empty($applicablePhaseEquipments)) {
            // ステータス優先順位: checked_out > reserved > checked_in
            // 同じ優先度の場合は最新のupdated_at
            $selectedEquipment = collect($applicablePhaseEquipments)
                ->sortBy([
                    function ($pe) {
                        // ステータス優先順位（数値が小さいほど優先）
                        return match($pe->status) {
                            'checked_out' => 1,
                            'reserved' => 2,
                            'checked_in' => 3,
                            default => 4
                        };
                    },
                    function ($pe) {
                        // 同じ優先度の場合は最新を優先（負の値で降順）
                        return -$pe->updated_at->timestamp;
                    }
                ])
                ->first();

            $status = $this->determinePhaseEquipmentStatus($selectedEquipment);

            return [
                'status' => $status,
                'detail' => $selectedEquipment->status,
                'phase_name' => $selectedEquipment->phase->name,
                'performance_title' => $selectedEquipment->phase->performance->display_name ?? null,
                'note' => $selectedEquipment->note,
                'checkout_date' => $selectedEquipment->checkout_date,
                'checkin_date' => $selectedEquipment->checkin_date,
            ];
        }

        // 3. デフォルト状態
        // repair_recordsとphase_equipmentで使用されていない場合は常に'available'
        // 修理中は1番目の処理で既に判定済み
        return [
            'status' => 'available',
            'detail' => 'available',
            'phase_name' => null,
            'performance_title' => null,
            'note' => null,
        ];
    }

    /**
     * 指定日がphase_equipmentの実際の使用期間内かどうかを判定
     */
    private function isDateWithinUsagePeriod(PhaseEquipment $phaseEquipment, Carbon $date): bool
    {
        switch ($phaseEquipment->status) {
            case 'reserved':
                // 予約済みの場合：フェーズ期間内で表示
                $phaseStart = Carbon::parse($phaseEquipment->phase->start_date);
                $phaseEnd = Carbon::parse($phaseEquipment->phase->end_date);
                return $phaseStart->lte($date) && $phaseEnd->gte($date);

            case 'checked_out':
                // 出庫中の場合：checkout_date以降で表示
                if (!$phaseEquipment->checkout_date) {
                    return false;
                }
                $checkoutDate = Carbon::parse($phaseEquipment->checkout_date);
                return $checkoutDate->lte($date);

            case 'checked_in':
                // 返却済みの場合：checkout_date から checkin_date までの期間で表示
                if (!$phaseEquipment->checkout_date || !$phaseEquipment->checkin_date) {
                    return false;
                }
                $checkoutDate = Carbon::parse($phaseEquipment->checkout_date);
                $checkinDate = Carbon::parse($phaseEquipment->checkin_date);
                return $checkoutDate->lte($date) && $checkinDate->gte($date);

            case 'cancelled':
                // キャンセル済みの場合：表示しない
                return false;

            default:
                return false;
        }
    }

    /**
     * フェーズ機材のステータスを判定
     */
    private function determinePhaseEquipmentStatus(PhaseEquipment $phaseEquipment): string
    {
        switch ($phaseEquipment->status) {
            case 'reserved':
                return 'reserved';
            case 'checked_out':
                return 'checked_out';
            case 'checked_in':
                // 返却済みでも実際の使用期間中は「使用中」として表示
                return 'checked_out';
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
            'categories' => $categories,
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
            'subcategories' => $subcategories,
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

    /**
     * 公演一覧を取得（予定・進行中のフェーズを含む公演のみ）
     */
    public function getPerformances()
    {
        $today = now()->toDateString();

        $performances = \App\Models\Performance::whereHas('phases', function ($query) use ($today) {
            // 予定(upcoming): start_date > today
            // 進行中(in_progress): start_date <= today AND end_date >= today
            $query->where(function ($q) use ($today) {
                $q->where('start_date', '>', $today) // 予定
                    ->orWhere(function ($sub) use ($today) {
                        $sub->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today); // 進行中
                    });
            });
        })
            ->orderBy('title')
            ->get(['id', 'title', 'short_name'])
            ->map(function ($performance) {
                return [
                    'id' => $performance->id,
                    'display_name' => $performance->display_name,
                ];
            });

        return response()->json([
            'success' => true,
            'performances' => $performances,
        ]);
    }

}
