<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleCellMemo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleCellMemoController extends Controller
{
    /**
     * 指定期間のセルメモを取得
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'equipment_ids' => 'sometimes|array',
            'equipment_ids.*' => 'integer',
        ]);

        $query = ScheduleCellMemo::query()
            ->whereBetween('schedule_date', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['equipment_ids'])) {
            $query->whereIn('equipment_id', $validated['equipment_ids']);
        }

        $memos = $query->get()->map(function ($memo) {
            return [
                'equipment_id' => $memo->equipment_id,
                'schedule_date' => $memo->schedule_date->format('Y-m-d'),
                'memo' => $memo->memo,
                'color' => $memo->color,
            ];
        });

        return response()->json([
            'success' => true,
            'memos' => $memos,
        ]);
    }

    /**
     * セルメモを保存（新規作成 or 更新）
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|integer|exists:equipments,id',
            'schedule_date' => 'required|date',
            'memo' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
        ]);

        try {
            $memo = ScheduleCellMemo::updateOrCreate(
                [
                    'equipment_id' => $validated['equipment_id'],
                    'schedule_date' => $validated['schedule_date'],
                ],
                [
                    'memo' => $validated['memo'],
                    'color' => $validated['color'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );

            return response()->json([
                'success' => true,
                'memo' => [
                    'equipment_id' => $memo->equipment_id,
                    'schedule_date' => $memo->schedule_date->format('Y-m-d'),
                    'memo' => $memo->memo,
                    'color' => $memo->color,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'メモの保存に失敗しました',
            ], 500);
        }
    }

    /**
     * セルメモを削除
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|integer',
            'schedule_date' => 'required|date',
        ]);

        try {
            $deleted = ScheduleCellMemo::where('equipment_id', $validated['equipment_id'])
                ->where('schedule_date', $validated['schedule_date'])
                ->delete();

            return response()->json([
                'success' => true,
                'deleted' => $deleted > 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'メモの削除に失敗しました',
            ], 500);
        }
    }
}
