<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\PhaseEquipment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleControllerSimple extends Controller
{
    /**
     * 機材スケジュールデータをAPI形式で取得（簡単版）
     */
    public function getEquipmentSchedule(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:20',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $perPage = $request->get('per_page', 5);

            // 機材の基本データを取得（制限付き）
            $equipments = Equipment::with(['category', 'subcategory'])
                ->where('is_discard', false)
                ->limit($perPage)
                ->get();

            $equipmentIds = $equipments->pluck('id')->toArray();

            // 日付範囲を生成
            $dateRange = [];
            $current = $startDate->copy();
            $maxDays = 31; // 最大31日
            $dayCount = 0;

            while ($current->lte($endDate) && $dayCount < $maxDays) {
                $dateRange[] = $current->format('Y-m-d');
                $current->addDay();
                $dayCount++;
            }

            // 指定期間内のフェーズ機材使用データを取得
            $phaseEquipments = PhaseEquipment::whereIn('equipment_id', $equipmentIds)
                ->get()
                ->groupBy('equipment_id');

            // スケジュールデータを構築
            $scheduleData = [];

            foreach ($equipments as $equipment) {
                $equipmentPhases = $phaseEquipments->get($equipment->id, collect());

                $dailyStatus = [];

                foreach ($dateRange as $date) {
                    // 基本的なステータス判定
                    $status = [
                        'status' => 'available',
                        'detail' => 'available',
                        'phase_name' => null,
                        'performance_title' => null,
                        'note' => null,
                    ];

                    // フェーズでの使用状態をチェック
                    foreach ($equipmentPhases as $phaseEquipment) {
                        if ($phaseEquipment->phase) {
                            $phaseStart = Carbon::parse($phaseEquipment->phase->start_date);
                            $phaseEnd = Carbon::parse($phaseEquipment->phase->end_date);
                            $currentDate = Carbon::parse($date);

                            if ($phaseStart->lte($currentDate) && $phaseEnd->gte($currentDate)) {
                                $status = [
                                    'status' => $phaseEquipment->status ?? 'reserved',
                                    'detail' => $phaseEquipment->status ?? 'reserved',
                                    'phase_name' => $phaseEquipment->phase->name ?? null,
                                    'performance_title' => $phaseEquipment->phase->performance->title ?? null,
                                    'note' => $phaseEquipment->note,
                                ];
                                break;
                            }
                        }
                    }

                    $dailyStatus[$date] = $status;
                }

                $scheduleData[] = [
                    'equipment_id' => $equipment->id,
                    'equipment_name' => $equipment->name,
                    'equipment_code' => $equipment->code,
                    'category' => $equipment->category->name ?? null,
                    'subcategory' => $equipment->subcategory->name ?? null,
                    'management_type' => $equipment->management_type,
                    'total_quantity' => $equipment->quantity,
                    'daily_status' => $dailyStatus,
                ];
            }

            return response()->json([
                'success' => true,
                'equipment_schedules' => $scheduleData,
                'date_range' => $dateRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'equipment_count' => count($scheduleData),
                'date_count' => count($dateRange),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ], 500);
        }
    }

    /**
     * カテゴリ一覧を取得（フィルタリング用）
     */
    public function getCategories()
    {
        try {
            $categories = \App\Models\EquipmentCategory::orderBy('sort')
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
