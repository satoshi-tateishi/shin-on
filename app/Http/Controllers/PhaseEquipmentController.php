<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhaseEquipmentRequest;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\EquipmentSet;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Services\PhaseEquipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PhaseEquipmentController - CRUD Operations Only
 *
 * This controller handles the basic CRUD operations for phase equipment management.
 * It provides functionality to create, read, update, and delete equipment assignments
 * to phases, including validation for equipment conflicts and quantity management.
 */
class PhaseEquipmentController extends Controller
{
    public function __construct(
        private PhaseEquipmentService $phaseEquipmentService
    ) {}

    /**
     * Display a listing of equipment for the phase.
     */
    public function index(Request $request, Phase $phase): View
    {
        $statusFilter = $request->get('status');
        $result = $this->phaseEquipmentService->getPhaseEquipmentWithStats($phase, $statusFilter);

        // ページネーションリンクにURLパラメータを維持
        $result['equipments']->appends($request->query());

        return view('phase-equipment.index', [
            'phase' => $phase,
            'phaseEquipments' => $result['equipments'],
            'equipmentStats' => $result['stats'],
        ]);
    }

    /**
     * Show the form for adding equipment to phase.
     *
     * Displays the form for creating new phase equipment assignments. Loads all necessary
     * data including equipment categories, subcategories, and equipment sets for selection.
     *
     * @param Phase $phase The phase to add equipment to
     * @return View The create form view with category and equipment set data
     */
    public function create(Phase $phase): View
    {
        $categories = EquipmentCategory::active()->ordered()->get();

        $subcategories = \App\Models\EquipmentSubcategory::with('category')
            ->ordered()
            ->get();

        $equipmentSets = EquipmentSet::with('equipmentItems.equipment')
            ->active()
            ->orderBy('sort')
            ->get();

        return view('phase-equipment.create', compact('phase', 'categories', 'subcategories', 'equipmentSets'));
    }

    /**
     * Store a newly created phase equipment usage.
     *
     * Handles both single equipment assignment and bulk equipment assignment operations.
     * Validates equipment availability, handles conflict checking for individual equipment,
     * and manages quantity constraints for quantity-managed equipment.
     *
     * @param Request $request The HTTP request containing equipment data
     * @param Phase $phase The phase to assign equipment to
     * @return RedirectResponse Redirect response with success or error messages
     */
    public function store(Request $request, Phase $phase): RedirectResponse
    {
        // 複数機材の一括追加に対応
        if ($request->has('equipment_data')) {
            return $this->storeBulkEquipment($request, $phase);
        }

        // 従来の単一機材追加処理
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        // 個体管理機材の場合のみ期間重複チェック
        if ($equipment->management_type === 'individual') {
            $hasConflict = PhaseEquipment::hasEquipmentConflict(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            if ($hasConflict) {
                return back()->withErrors([
                    'equipment_id' => '指定された機材は、この期間中に他のフェーズで使用予定です。',
                ])->withInput();
            }
        }

        // 数量管理機材の場合、既存レコードの重複チェック
        if ($equipment->management_type === 'quantity') {
            $existingRecord = PhaseEquipment::where('phase_id', $phase->id)
                ->where('equipment_id', $equipment->id)
                ->first();

            if ($existingRecord) {
                return back()->withErrors([
                    'equipment_id' => 'この機材は既にこのフェーズに登録されています。数量を変更する場合は編集画面をご利用ください。',
                ])->withInput();
            }

            $availableQuantity = PhaseEquipment::getAvailableQuantity(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            if ($validated['quantity'] > $availableQuantity) {
                return back()->withErrors([
                    'quantity' => "使用可能数量は最大 {$availableQuantity} 個です。",
                ])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // フェーズ機材使用記録を作成
            $phaseEquipment = PhaseEquipment::create([
                'phase_id' => $phase->id,
                'equipment_id' => $equipment->id,
                'quantity' => $validated['quantity'],
                'status' => 'reserved',
                'note' => $validated['note'] ?? null,
            ]);

            DB::commit();

            return redirect()
                ->route('phases.equipment.index', $phase)
                ->with('success', '機材使用予約を登録しました。');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors([
                'error' => '機材使用予約の登録に失敗しました。',
            ])->withInput();
        }
    }

    /**
     * Display the specified phase equipment.
     *
     * Shows detailed information about a specific phase equipment assignment including
     * equipment details, movement history, and other usage records for the same equipment.
     *
     * @param Phase $phase The phase containing the equipment
     * @param PhaseEquipment $phaseEquipment The specific phase equipment record
     * @return View The detail view with equipment information and related data
     */
    public function show(Phase $phase, PhaseEquipment $phaseEquipment): View
    {
        $phaseEquipment->load([
            'equipment.subcategory.category',
            'checkoutUser',
            'checkinUser',
        ]);

        // phaseにperformanceリレーションを読み込む
        $phase->load('performance');

        $movements = EquipmentMovement::forPhase($phase->id)
            ->forEquipment($phaseEquipment->equipment_id)
            ->with(['fromLocation', 'toLocation', 'movedBy'])
            ->ordered()
            ->get();

        // 他の使用予定を取得（予約済み・出庫中のみ、返却済みは除外）
        $otherUsages = PhaseEquipment::where('equipment_id', $phaseEquipment->equipment_id)
            ->where('id', '!=', $phaseEquipment->id)
            ->whereIn('status', ['reserved', 'checked_out'])
            ->whereNotIn('status', ['checked_in'])
            ->with(['phase.performance'])
            ->get();

        return view('phase-equipment.show', compact('phase', 'phaseEquipment', 'movements', 'otherUsages'));
    }

    /**
     * Show the form for editing the specified phase equipment.
     *
     * Displays the edit form for a phase equipment assignment. Calculates available
     * quantities for quantity-managed equipment and identifies potential conflicts.
     *
     * @param Phase $phase The phase containing the equipment
     * @param PhaseEquipment $phaseEquipment The phase equipment record to edit
     * @return View The edit form view with current data and availability information
     */
    public function edit(Phase $phase, PhaseEquipment $phaseEquipment): View
    {
        // 必要なリレーションを確実に読み込む
        $phaseEquipment->load([
            'equipment.subcategory.category',
            'checkoutUser',
            'checkinUser',
        ]);

        // phaseにperformanceリレーションを読み込む
        $phase->load('performance');

        // 数量管理機材の場合、最大利用可能数量を計算
        $maxQuantity = 1; // デフォルト（個体管理機材）
        $availableQuantity = 0; // 利用可能数量（表示用）

        if ($phaseEquipment->equipment && $phaseEquipment->equipment->management_type === 'quantity') {
            // 同じ機材で他の使用中数量を計算（現在のレコードは除外）
            $otherUsedQuantity = PhaseEquipment::where('equipment_id', $phaseEquipment->equipment->id)
                ->where('id', '!=', $phaseEquipment->id)
                ->sum('quantity');

            // 現在未使用数量 = 機材総数 - 現在の使用数量 - 他で使用中の数量
            $availableQuantity = max(0, $phaseEquipment->equipment->quantity - $phaseEquipment->quantity - $otherUsedQuantity);

            // 編集時の最大入力可能数 = 利用可能数量 + 現在の使用数量
            $maxQuantity = $availableQuantity + $phaseEquipment->quantity;
        }

        // 他の使用予定を取得（参考情報として）
        $otherUsages = PhaseEquipment::where('equipment_id', $phaseEquipment->equipment_id)
            ->where('id', '!=', $phaseEquipment->id)
            ->whereNotIn('status', ['cancelled'])
            ->with(['phase.performance'])
            ->get();

        // 期間重複があるかチェック
        $hasConflicts = $phaseEquipment->equipment &&
            $phaseEquipment->equipment->management_type === 'individual' &&
            PhaseEquipment::hasEquipmentConflict(
                $phaseEquipment->equipment_id,
                $phase->start_date,
                $phase->end_date,
                $phaseEquipment->id
            );

        return view('phase-equipment.edit', compact(
            'phase',
            'phaseEquipment',
            'maxQuantity',
            'availableQuantity',
            'otherUsages',
            'hasConflicts'
        ));
    }

    /**
     * Update the specified phase equipment.
     *
     * Updates a phase equipment record with new quantity and note values.
     * Validates quantity constraints for quantity-managed equipment.
     *
     * @param Request $request The HTTP request containing update data
     * @param Phase $phase The phase containing the equipment
     * @param PhaseEquipment $phaseEquipment The phase equipment record to update
     * @return RedirectResponse Redirect response with success or error messages
     */
    public function update(Request $request, Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        $equipment = $phaseEquipment->equipment;

        // 数量管理機材の場合、使用可能数量チェック
        if ($equipment->management_type === 'quantity') {
            $availableQuantity = PhaseEquipment::getAvailableQuantity(
                $equipment->id,
                $phase->start_date,
                $phase->end_date,
                $phaseEquipment->id // 現在の記録は除外
            );

            if ($validated['quantity'] > $availableQuantity) {
                return back()->withErrors([
                    'quantity' => "使用可能数量は最大 {$availableQuantity} 個です。",
                ])->withInput();
            }
        }

        $phaseEquipment->update($validated);

        return redirect()
            ->route('phases.equipment.show', [$phase, $phaseEquipment])
            ->with('success', '機材使用情報を更新しました。');
    }

    /**
     * Remove the specified phase equipment.
     *
     * Deletes a phase equipment record, effectively removing the equipment
     * assignment from the phase.
     *
     * @param Phase $phase The phase containing the equipment
     * @param PhaseEquipment $phaseEquipment The phase equipment record to delete
     * @return RedirectResponse Redirect response with success message
     */
    public function destroy(Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        $phaseEquipment->delete();

        return redirect()
            ->route('phases.equipment.index', $phase)
            ->with('success', '機材使用記録を削除しました。');
    }

    /**
     * Store multiple equipment at once (bulk operation).
     *
     * Private helper method that handles bulk equipment assignment to a phase.
     * Validates each equipment item individually and creates records in a transaction.
     * Handles both individual and quantity-managed equipment with appropriate validation.
     *
     * @param Request $request The HTTP request containing bulk equipment data
     * @param Phase $phase The phase to assign equipment to
     * @return RedirectResponse Redirect response with results summary
     */
    private function storeBulkEquipment(Request $request, Phase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_data' => 'required|json',
        ]);

        $equipmentData = json_decode($validated['equipment_data'], true);

        if (empty($equipmentData)) {
            return back()->withErrors(['equipment_data' => '機材が選択されていません。']);
        }

        $errors = [];
        $successCount = 0;
        $equipmentNames = [];

        try {
            DB::beginTransaction();

            foreach ($equipmentData as $index => $item) {
                $equipment = Equipment::find($item['equipment_id']);
                if (! $equipment) {
                    $errors[] = "機材ID {$item['equipment_id']} が見つかりません。";

                    continue;
                }

                // 個体管理機材の場合のみ期間重複チェック
                if ($equipment->management_type === 'individual') {
                    $hasConflict = PhaseEquipment::hasEquipmentConflict(
                        $equipment->id,
                        $phase->start_date,
                        $phase->end_date
                    );

                    if ($hasConflict) {
                        $errors[] = "{$equipment->name} は、この期間中に他のフェーズで使用予定です。";

                        continue;
                    }
                }

                // 数量管理機材の場合、重複チェックと使用可能数量チェック
                if ($equipment->management_type === 'quantity') {
                    $existingRecord = PhaseEquipment::where('phase_id', $phase->id)
                        ->where('equipment_id', $equipment->id)
                        ->first();

                    if ($existingRecord) {
                        $errors[] = "{$equipment->name} は既にこのフェーズに登録されています。数量を変更する場合は編集画面をご利用ください。";

                        continue;
                    }

                    $availableQuantity = PhaseEquipment::getAvailableQuantity(
                        $equipment->id,
                        $phase->start_date,
                        $phase->end_date
                    );

                    if ($item['quantity'] > $availableQuantity) {
                        $errors[] = "{$equipment->name} の使用可能数量は最大 {$availableQuantity} 個です。";

                        continue;
                    }
                }

                // フェーズ機材使用記録を作成
                $phaseEquipment = PhaseEquipment::create([
                    'phase_id' => $phase->id,
                    'equipment_id' => $equipment->id,
                    'quantity' => $item['quantity'],
                    'status' => 'reserved',
                    'note' => null,
                ]);

                // 機材移動ログを記録
                EquipmentMovement::create([
                    'equipment_id' => $equipment->id,
                    'phase_equipment_id' => $phaseEquipment->id,
                    'action' => 'reserved',
                    'quantity' => $item['quantity'],
                    'performed_by' => auth()->id(),
                    'performed_at' => now(),
                ]);

                $successCount++;
                $equipmentNames[] = $equipment->name;
            }

            DB::commit();

            if ($successCount > 0) {
                $message = "{$successCount}件の機材を追加しました";
                if (! empty($errors)) {
                    $message .= '（'.count($errors).'件のエラーがありました）';
                }

                return redirect()
                    ->route('phases.equipment.index', $phase)
                    ->with('success', $message);
            } else {
                return back()->withErrors($errors);
            }

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => '機材追加中にエラーが発生しました: '.$e->getMessage()]);
        }
    }
}