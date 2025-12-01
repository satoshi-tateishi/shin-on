<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\PhaseEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PhaseEquipmentInheritanceController
 *
 * フェーズ間の機材継承機能を提供するコントローラー
 * checked_out（出庫中）の機材のみを継承対象とする
 */
class PhaseEquipmentInheritanceController extends Controller
{
    /**
     * 継承可能な継承先フェーズ取得
     */
    public function getInheritableTargetPhases(Request $request, $phaseId): JsonResponse
    {
        $includeOtherPerformances = $request->boolean('include_other_performances', false);

        // フェーズIDからPhaseモデルを取得
        $sourcePhase = Phase::with('performance')->findOrFail($phaseId);

        // 継承元の出庫中機材数をチェック
        $checkedOutCount = PhaseEquipment::where('phase_id', $sourcePhase->id)
            ->where('status', 'checked_out')
            ->count();

        if ($checkedOutCount === 0) {
            return response()->json([
                'error' => '継承可能な機材がありません（出庫中の機材がありません）',
            ], 400);
        }

        // 同一公演内の他フェーズ（完了フェーズを除外）
        $today = now()->toDateString();
        $samePerformancePhases = Phase::where('performance_id', $sourcePhase->performance_id)
            ->where('id', '!=', $sourcePhase->id)
            ->where('end_date', '>=', $today) // 完了フェーズを除外（フェーズの日付ベース）
            ->with('performance')
            ->orderBy('start_date')
            ->get();


        $result = [
            'source_phase' => [
                'id' => $sourcePhase->id,
                'name' => $sourcePhase->name,
                'performance_title' => $sourcePhase->performance->title,
                'checked_out_count' => $checkedOutCount,
            ],
            'same_performance' => $samePerformancePhases->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->name,
                    'start_date' => $phase->start_date->format('Y-m-d'),
                    'end_date' => $phase->end_date->format('Y-m-d'),
                    'phase_status' => $phase->phase_status,
                    'performance' => [
                        'id' => $phase->performance->id,
                        'title' => $phase->performance->title,
                    ],
                ];
            }),
        ];

        // 他公演フェーズ（完了フェーズを除外 - フェーズの日付ベース）
        if ($includeOtherPerformances) {
            $otherPerformancePhases = Phase::where('end_date', '>=', $today) // 完了フェーズを除外
                ->whereHas('performance', function ($query) use ($sourcePhase) {
                    $query->where('id', '!=', $sourcePhase->performance_id);
                })
                ->with('performance')
                ->orderBy('performance_id')
                ->orderBy('start_date', 'desc')
                ->get();

            $result['other_performances'] = $otherPerformancePhases->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'name' => $phase->name,
                    'start_date' => $phase->start_date->format('Y-m-d'),
                    'end_date' => $phase->end_date->format('Y-m-d'),
                    'phase_status' => $phase->phase_status,
                    'performance' => [
                        'id' => $phase->performance->id,
                        'title' => $phase->performance->title,
                    ],
                ];
            });
        }

        return response()->json($result);
    }

    /**
     * 継承プレビュー（継承可能な機材リスト取得）
     */
    public function getInheritancePreview(Request $request, $phaseId): JsonResponse
    {
        $validated = $request->validate([
            'target_phase_id' => 'required|exists:phases,id',
        ]);

        $sourcePhase = Phase::with('performance')->findOrFail($phaseId);
        $targetPhase = Phase::findOrFail($validated['target_phase_id']);

        // 継承先フェーズステータスチェック（完了フェーズへの継承は不可）
        if ($targetPhase->phase_status === 'completed') {
            return response()->json([
                'error' => '完了したフェーズへは継承できません',
            ], 400);
        }

        // 出庫中の機材のみ取得
        $sourceEquipments = PhaseEquipment::where('phase_id', $sourcePhase->id)
            ->where('status', 'checked_out')
            ->with('equipment.subcategory.category')
            ->get();

        if ($sourceEquipments->isEmpty()) {
            return response()->json([
                'error' => '継承可能な機材がありません（出庫中の機材がありません）',
            ], 400);
        }

        $equipments = $sourceEquipments->map(function ($phaseEquipment) use ($targetPhase, $sourcePhase) {
            $equipment = $phaseEquipment->equipment;

            // 期間重複チェック（継承元フェーズの機材は除外）
            $hasConflict = PhaseEquipment::where('equipment_id', $equipment->id)
                ->where('phase_id', '!=', $sourcePhase->id) // 継承元フェーズは除外
                ->whereHas('phase', function ($phaseQuery) use ($targetPhase) {
                    $phaseQuery->where('start_date', '<', $targetPhase->end_date)
                        ->where('end_date', '>', $targetPhase->start_date);
                })
                ->whereNotIn('status', ['checked_in'])
                ->exists();

            $canInherit = ! $hasConflict;
            $conflictReason = null;
            $availableQuantity = null;

            if ($hasConflict) {
                $conflictReason = 'period_overlap';
            } elseif ($equipment->management_type === 'quantity') {
                $availableQuantity = PhaseEquipment::getAvailableQuantity(
                    $equipment->id,
                    $targetPhase->start_date,
                    $targetPhase->end_date
                );
                if ($availableQuantity < $phaseEquipment->quantity) {
                    $canInherit = false;
                    $conflictReason = 'insufficient_quantity';
                }
            }

            return [
                'id' => $phaseEquipment->id,
                'equipment_id' => $equipment->id,
                'equipment_name' => $equipment->name,
                'management_type' => $equipment->management_type,
                'quantity' => $phaseEquipment->quantity,
                'note' => $phaseEquipment->note,
                'can_inherit' => $canInherit,
                'available_quantity' => $availableQuantity,
                'conflict_reason' => $conflictReason,
                'category' => $equipment->subcategory->category->name,
                'subcategory' => $equipment->subcategory->name,
                'manufacturer' => $equipment->manufacturer,
                'company_number' => $equipment->company_number,
            ];
        });

        return response()->json([
            'source_phase' => [
                'id' => $sourcePhase->id,
                'name' => $sourcePhase->name,
                'performance_title' => $sourcePhase->performance->title,
            ],
            'target_phase' => [
                'id' => $targetPhase->id,
                'name' => $targetPhase->name,
                'start_date' => $targetPhase->start_date,
                'end_date' => $targetPhase->end_date,
                'performance_title' => $targetPhase->performance->title,
            ],
            'equipments' => $equipments,
        ]);
    }

    /**
     * 機材継承実行
     */
    public function inheritEquipment(Request $request, $phaseId): JsonResponse
    {
        $validated = $request->validate([
            'target_phase_id' => 'required|exists:phases,id',
            'inherit_type' => 'required|in:all,selective',
            'equipment_selections' => 'required_if:inherit_type,selective|array',
            'equipment_selections.*.source_phase_equipment_id' => 'required|exists:phase_equipment,id',
            'equipment_selections.*.quantity' => 'required|integer|min:1',
            'equipment_selections.*.inherit_note' => 'boolean',
            'equipment_selections.*.new_note' => 'nullable|string|max:1000',
        ]);

        $sourcePhase = Phase::with('performance')->findOrFail($phaseId);
        $targetPhase = Phase::findOrFail($validated['target_phase_id']);

        // 継承先フェーズステータスチェック（完了フェーズへの継承は不可）
        if ($targetPhase->phase_status === 'completed') {
            return response()->json([
                'error' => '完了したフェーズへは継承できません',
            ], 400);
        }

        // 権限チェック（継承先への編集権限）
        $user = auth()->user();
        if (! $this->canEditPhase($user, $targetPhase)) {
            return response()->json([
                'error' => '継承先フェーズへの編集権限がありません',
            ], 403);
        }

        try {
            return DB::transaction(function () use ($validated, $sourcePhase, $targetPhase) {
                // 出庫中の機材のみ取得
                $sourceEquipments = PhaseEquipment::where('phase_id', $sourcePhase->id)
                    ->where('status', 'checked_out')
                    ->get();

                if ($sourceEquipments->isEmpty()) {
                    throw ValidationException::withMessages([
                        'general' => ['継承可能な機材がありません（出庫中の機材がありません）'],
                    ]);
                }

                $createdEquipmentIds = [];
                $warnings = [];

                if ($validated['inherit_type'] === 'all') {
                    // 全継承
                    foreach ($sourceEquipments as $sourceEquipment) {
                        $result = $this->createInheritedEquipment($sourceEquipment, $targetPhase, $sourceEquipment->quantity, $sourceEquipment->note);
                        if ($result['created']) {
                            $createdEquipmentIds[] = $result['id'];
                            // 継承元の機材を自動返却
                            $this->autoReturnSourceEquipment($sourceEquipment);
                        }
                        if ($result['warning']) {
                            $warnings[] = $result['warning'];
                        }
                    }
                } else {
                    // 選択継承
                    $selections = collect($validated['equipment_selections']);
                    foreach ($selections as $selection) {
                        $sourceEquipment = PhaseEquipment::find($selection['source_phase_equipment_id']);
                        if ($sourceEquipment && $sourceEquipment->status === 'checked_out') {
                            $note = $selection['inherit_note'] ? $sourceEquipment->note : ($selection['new_note'] ?? '');
                            $result = $this->createInheritedEquipment($sourceEquipment, $targetPhase, $selection['quantity'], $note);
                            if ($result['created']) {
                                $createdEquipmentIds[] = $result['id'];
                                // 継承元の機材を自動返却
                                $this->autoReturnSourceEquipment($sourceEquipment);
                            }
                            if ($result['warning']) {
                                $warnings[] = $result['warning'];
                            }
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'inherited_count' => count($createdEquipmentIds),
                    'total_source_count' => $sourceEquipments->count(),
                    'created_equipment_ids' => $createdEquipmentIds,
                    'warnings' => $warnings,
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '継承処理中にエラーが発生しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 継承機材を作成
     */
    private function createInheritedEquipment(PhaseEquipment $sourceEquipment, Phase $targetPhase, int $quantity, ?string $note): array
    {
        $equipment = $sourceEquipment->equipment;

        // 期間重複チェック（継承元フェーズの機材は除外）
        $hasConflict = PhaseEquipment::where('equipment_id', $equipment->id)
            ->where('phase_id', '!=', $sourceEquipment->phase_id) // 継承元フェーズは除外
            ->whereHas('phase', function ($phaseQuery) use ($targetPhase) {
                $phaseQuery->where('start_date', '<', $targetPhase->end_date)
                    ->where('end_date', '>', $targetPhase->start_date);
            })
            ->whereNotIn('status', ['checked_in'])
            ->exists();

        if ($hasConflict && $equipment->management_type === 'individual') {
            return [
                'created' => false,
                'warning' => [
                    'type' => 'period_conflict',
                    'message' => "{$equipment->name}は期間重複のため継承できませんでした",
                ],
            ];
        }

        // 数量管理機材の場合の利用可能数量チェック
        if ($equipment->management_type === 'quantity') {
            // 継承元フェーズを除外した利用可能数量を計算
            $usedQuantity = PhaseEquipment::where('equipment_id', $equipment->id)
                ->where('phase_id', '!=', $sourceEquipment->phase_id) // 継承元フェーズは除外
                ->whereHas('phase', function ($phaseQuery) use ($targetPhase) {
                    $phaseQuery->where('start_date', '<', $targetPhase->end_date)
                        ->where('end_date', '>', $targetPhase->start_date);
                })
                ->whereNotIn('status', ['checked_in'])
                ->sum('quantity');

            $availableQuantity = max(0, $equipment->quantity - $usedQuantity);

            if ($availableQuantity < $quantity) {
                $adjustedQuantity = min($quantity, $availableQuantity);
                if ($adjustedQuantity > 0) {
                    $quantity = $adjustedQuantity;
                    $warning = [
                        'type' => 'quantity_adjusted',
                        'message' => "{$equipment->name}の数量を{$sourceEquipment->quantity}個から{$quantity}個に調整しました",
                    ];
                } else {
                    return [
                        'created' => false,
                        'warning' => [
                            'type' => 'insufficient_quantity',
                            'message' => "{$equipment->name}は数量不足のため継承できませんでした",
                        ],
                    ];
                }
            }
        }

        // 継承機材作成
        $newPhaseEquipment = PhaseEquipment::create([
            'phase_id' => $targetPhase->id,
            'equipment_id' => $equipment->id,
            'quantity' => $quantity,
            'status' => 'reserved',
            'note' => $note,
            'source_phase_equipment_id' => $sourceEquipment->id,
        ]);

        $result = [
            'created' => true,
            'id' => $newPhaseEquipment->id,
            'warning' => null,
        ];

        if (isset($warning)) {
            $result['warning'] = $warning;
        }

        return $result;
    }

    /**
     * 継承元機材を自動返却
     */
    private function autoReturnSourceEquipment(PhaseEquipment $sourceEquipment): void
    {
        if ($sourceEquipment->status === 'checked_out') {
            $sourceEquipment->update([
                'status' => 'checked_in',
                'checkin_date' => now(),
                'checkin_user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * フェーズ編集権限チェック
     */
    private function canEditPhase($user, Phase $phase): bool
    {
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // 公演担当者かチェック
        return $phase->performance->staff->contains('user_id', $user->id);
    }
}
