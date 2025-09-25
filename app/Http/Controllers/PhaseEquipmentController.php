<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\EquipmentSet;
use App\Models\Location;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PhaseEquipmentController extends Controller
{
    /**
     * Display a listing of equipment for the phase.
     */
    public function index(Phase $phase): View
    {
        // フェーズの機材使用記録を取得
        $phaseEquipments = $phase->phaseEquipments()
            ->with(['equipment.subcategory.category', 'checkoutUser', 'checkinUser'])
            ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
            ->orderBy('equipments.sort')
            ->orderByDesc('phase_equipment.created_at')
            ->select('phase_equipment.*')
            ->paginate(20);

        $equipmentStats = [
            'total' => PhaseEquipment::forPhase($phase->id)->count(),
            'reserved' => PhaseEquipment::forPhase($phase->id)->reserved()->count(),
            'checked_out' => PhaseEquipment::forPhase($phase->id)->checkedOut()->count(),
            'checked_in' => PhaseEquipment::forPhase($phase->id)->checkedIn()->count(),
        ];

        return view('phase-equipment.index', compact('phase', 'phaseEquipments', 'equipmentStats'));
    }

    /**
     * Show the form for adding equipment to phase.
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
     */
    public function destroy(Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        $phaseEquipment->delete();

        return redirect()
            ->route('phases.equipment.index', $phase)
            ->with('success', '機材使用記録を削除しました。');
    }

    /**
     * Checkout equipment (change status to checked_out)
     */
    public function checkout(Request $request, Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        if (! $phaseEquipment->canCheckout()) {
            return back()->withErrors([
                'error' => 'この機材は出庫できません。',
            ]);
        }

        $validated = $request->validate([
            'checkout_date' => 'required|date',
            'from_location_id' => 'nullable|exists:locations,id',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // PhaseEquipment レコードを更新
            $phaseEquipment->update([
                'status' => 'checked_out',
                'checkout_date' => $validated['checkout_date'],
                'checkout_user_id' => auth()->id(),
                'note' => $validated['note'] ?? $phaseEquipment->note,
            ]);

            // EquipmentMovement レコードを作成
            EquipmentMovement::createCheckout(
                $phaseEquipment->equipment_id,
                $phase->id,
                $phaseEquipment->quantity,
                auth()->id(),
                $validated['from_location_id'] ?? null,
                $validated['note'] ?? null
            );

            DB::commit();

            return back()->with('success', '機材を出庫しました。');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors([
                'error' => '出庫処理に失敗しました。',
            ]);
        }
    }

    /**
     * Checkin equipment (change status to checked_in)
     */
    public function checkin(Request $request, Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        if (! $phaseEquipment->canCheckin()) {
            return back()->withErrors([
                'error' => 'この機材は返却できません。',
            ]);
        }

        $equipment = $phaseEquipment->equipment;

        // location_id=90-92の機材は返却先選択が必要
        $requiresLocationSelection = in_array($equipment->location_id, [90, 91, 92]);

        $validationRules = [
            'checkin_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ];

        if ($requiresLocationSelection) {
            $validationRules['to_location_id'] = 'required|exists:locations,id';
        } else {
            $validationRules['to_location_id'] = 'nullable|exists:locations,id';
        }

        $validated = $request->validate($validationRules);

        // location_id=90-92で返却先が選択されていない場合のエラー
        if ($requiresLocationSelection && empty($validated['to_location_id'])) {
            return back()->withErrors([
                'to_location_id' => 'この機材は返却先倉庫の選択が必要です。',
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // PhaseEquipment レコードを更新
            $phaseEquipment->update([
                'status' => 'checked_in',
                'checkin_date' => $validated['checkin_date'],
                'checkin_user_id' => auth()->id(),
                'note' => $validated['note'] ?? $phaseEquipment->note,
            ]);

            // location_id=90-92の機材の場合、機材の場所を更新
            $toLocationId = $validated['to_location_id'] ?? null;
            if ($requiresLocationSelection && $toLocationId) {
                $equipment->update(['location_id' => $toLocationId]);
            }

            // EquipmentMovement レコードを作成
            EquipmentMovement::createCheckin(
                $phaseEquipment->equipment_id,
                $phase->id,
                $phaseEquipment->quantity,
                auth()->id(),
                $toLocationId,
                $validated['note'] ?? null
            );

            DB::commit();

            $message = '機材を返却しました。';
            if ($requiresLocationSelection && $toLocationId) {
                $locationName = Location::find($toLocationId)?->name;
                $message .= "（返却先: {$locationName}）";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors([
                'error' => '返却処理に失敗しました。',
            ]);
        }
    }

    /**
     * Get available equipment for phase (AJAX)
     */
    public function getAvailableEquipment(Request $request, Phase $phase): JsonResponse
    {
        $categoryId = $request->get('category_id');
        $subcategoryId = $request->get('subcategory_id');
        $search = $request->get('search');

        $query = Equipment::with('subcategory.category')
            ->where('status', 'available');

        if ($categoryId) {
            $query->whereHas('subcategory', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_number', 'like', "%{$search}%")
                    ->orWhere('model_number', 'like', "%{$search}%");
            });
        }

        $equipments = $query->orderBy('sort')->get()->map(function ($equipment) use ($phase) {
            $hasConflict = PhaseEquipment::hasEquipmentConflict(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            $availableQuantity = 0;
            if ($equipment->management_type === 'quantity') {
                $availableQuantity = PhaseEquipment::getAvailableQuantity(
                    $equipment->id,
                    $phase->start_date,
                    $phase->end_date
                );
            }

            return [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'company_number' => $equipment->company_number,
                'model_number' => $equipment->model_number,
                'management_type' => $equipment->management_type,
                'quantity' => $equipment->quantity,
                'available_quantity' => $availableQuantity,
                'has_conflict' => $hasConflict,
                'category' => $equipment->subcategory->category->name,
                'subcategory' => $equipment->subcategory->name,
            ];
        });

        return response()->json($equipments);
    }

    /**
     * Check equipment set availability (AJAX)
     */
    public function checkSetAvailability(Request $request, Phase $phase): JsonResponse
    {
        $setId = $request->get('set_id');
        $equipmentSet = EquipmentSet::with('equipmentItems.equipment')->findOrFail($setId);

        $availability = [];
        $allAvailable = true;

        foreach ($equipmentSet->equipmentItems as $item) {
            $equipment = $item->equipment;
            $hasConflict = PhaseEquipment::hasEquipmentConflict(
                $equipment->id,
                $phase->start_date,
                $phase->end_date
            );

            $availableQuantity = 0;
            if ($equipment->management_type === 'quantity' && ! $hasConflict) {
                $availableQuantity = PhaseEquipment::getAvailableQuantity(
                    $equipment->id,
                    $phase->start_date,
                    $phase->end_date
                );
            }

            $isAvailable = ! $hasConflict && ($equipment->management_type === 'individual' || $availableQuantity >= $item->quantity);

            if (! $isAvailable) {
                $allAvailable = false;
            }

            $availability[] = [
                'equipment_id' => $equipment->id,
                'equipment_name' => $equipment->name,
                'required_quantity' => $item->quantity,
                'available_quantity' => $availableQuantity,
                'has_conflict' => $hasConflict,
                'is_available' => $isAvailable,
            ];
        }

        return response()->json([
            'set_id' => $setId,
            'set_name' => $equipmentSet->name,
            'all_available' => $allAvailable,
            'items' => $availability,
        ]);
    }

    /**
     * Store multiple equipment at once (bulk operation)
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

    /**
     * Bulk checkout reserved equipment in the phase
     */
    public function bulkCheckout(Phase $phase): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $targetEquipments = $phase->phaseEquipments()
                ->whereIn('status', ['reserved', 'checked_in'])
                ->get();

            if ($targetEquipments->isEmpty()) {
                return back()->withErrors(['error' => '予約済みまたは返却済みの機材がありません。']);
            }

            $updatedCount = 0;
            $today = now()->format('Y-m-d');

            foreach ($targetEquipments as $phaseEquipment) {
                $phaseEquipment->update([
                    'status' => 'checked_out',
                    'checkout_date' => $today,
                    'checkout_user_id' => auth()->id(),
                ]);

                EquipmentMovement::createCheckout(
                    $phaseEquipment->equipment_id,
                    $phase->id,
                    $phaseEquipment->quantity,
                    auth()->id(),
                    null,
                    '一括出庫'
                );

                $updatedCount++;
            }

            DB::commit();

            return back()->with('success', "予約済み・返却済み機材 {$updatedCount}件を一括で出庫中に変更しました。");

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => '一括出庫処理に失敗しました。']);
        }
    }

    /**
     * Bulk checkout reserved equipment only
     */
    public function bulkCheckoutReserved(Phase $phase): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $targetEquipments = $phase->phaseEquipments()
                ->where('status', 'reserved')
                ->get();

            if ($targetEquipments->isEmpty()) {
                return back()->withErrors(['error' => '予約済みの機材がありません。']);
            }

            $updatedCount = 0;
            $today = now()->format('Y-m-d');

            foreach ($targetEquipments as $phaseEquipment) {
                $phaseEquipment->update([
                    'status' => 'checked_out',
                    'checkout_date' => $today,
                    'checkout_user_id' => auth()->id(),
                ]);

                EquipmentMovement::createCheckout(
                    $phaseEquipment->equipment_id,
                    $phase->id,
                    $phaseEquipment->quantity,
                    auth()->id(),
                    null,
                    '一括出庫（予約済み）'
                );

                $updatedCount++;
            }

            DB::commit();

            return back()->with('success', "予約済み機材 {$updatedCount}件を一括で出庫中に変更しました。");

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => '一括出庫処理に失敗しました。']);
        }
    }

    /**
     * Bulk checkout checked-in equipment only
     */
    public function bulkCheckoutCheckedIn(Phase $phase): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $targetEquipments = $phase->phaseEquipments()
                ->where('status', 'checked_in')
                ->get();

            if ($targetEquipments->isEmpty()) {
                return back()->withErrors(['error' => '返却済みの機材がありません。']);
            }

            $updatedCount = 0;
            $today = now()->format('Y-m-d');

            foreach ($targetEquipments as $phaseEquipment) {
                $phaseEquipment->update([
                    'status' => 'checked_out',
                    'checkout_date' => $today,
                    'checkout_user_id' => auth()->id(),
                ]);

                EquipmentMovement::createCheckout(
                    $phaseEquipment->equipment_id,
                    $phase->id,
                    $phaseEquipment->quantity,
                    auth()->id(),
                    null,
                    '一括出庫（返却済み）'
                );

                $updatedCount++;
            }

            DB::commit();

            return back()->with('success', "返却済み機材 {$updatedCount}件を一括で出庫中に変更しました。");

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => '一括出庫処理に失敗しました。']);
        }
    }

    /**
     * Bulk checkin checked out equipment in the phase
     */
    public function bulkCheckin(Phase $phase, Request $request)
    {
        try {
            DB::beginTransaction();

            // JSONリクエストの場合は特定の機材IDのみ処理
            if ($request->isJson() && $request->has('equipment_ids')) {
                $equipmentIds = $request->input('equipment_ids');
                $checkedOutEquipments = $phase->phaseEquipments()
                    ->where('status', 'checked_out')
                    ->whereIn('id', $equipmentIds)
                    ->get();
            } else {
                // 従来の処理（全出庫中機材を処理）
                $checkedOutEquipments = $phase->phaseEquipments()
                    ->where('status', 'checked_out')
                    ->get();
            }

            if ($checkedOutEquipments->isEmpty()) {
                if ($request->isJson()) {
                    return response()->json(['success' => false, 'message' => '対象の機材がありません。'], 404);
                }

                return back()->withErrors(['error' => '出庫中の機材がありません。']);
            }

            $updatedCount = 0;
            $today = now()->format('Y-m-d');

            foreach ($checkedOutEquipments as $phaseEquipment) {
                $phaseEquipment->update([
                    'status' => 'checked_in',
                    'checkin_date' => $today,
                    'checkin_user_id' => auth()->id(),
                ]);

                EquipmentMovement::createCheckin(
                    $phaseEquipment->equipment_id,
                    $phase->id,
                    $phaseEquipment->quantity,
                    auth()->id(),
                    null,
                    '一括返却'
                );

                $updatedCount++;
            }

            DB::commit();

            if ($request->isJson()) {
                return response()->json(['success' => true, 'updated_count' => $updatedCount]);
            }

            return back()->with('success', "出庫中機材 {$updatedCount}件を一括で返却済みに変更しました。");

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->isJson()) {
                return response()->json(['success' => false, 'message' => '一括返却処理に失敗しました。'], 500);
            }

            return back()->withErrors(['error' => '一括返却処理に失敗しました。']);
        }
    }

    /**
     * 機材情報取得API（返却時のlocation_idチェック用）
     */
    public function getEquipmentInfo(Phase $phase, PhaseEquipment $phaseEquipment): JsonResponse
    {
        try {
            $equipment = $phaseEquipment->equipment;

            return response()->json([
                'success' => true,
                'equipment' => [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'company_number' => $equipment->company_number,
                    'location_id' => $equipment->location_id,
                    'management_type' => $equipment->management_type,
                    'location_name' => $equipment->location->name ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => '機材情報の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 出庫中機材一覧取得API（一括返却用のlocation_idチェック用）
     */
    public function getCheckedOutEquipments(Phase $phase): JsonResponse
    {
        try {
            \Log::info('getCheckedOutEquipments called for phase: '.$phase->id);

            $checkedOutEquipments = $phase->phaseEquipments()
                ->where('status', 'checked_out')
                ->with(['equipment.location'])
                ->get();

            \Log::info('Found checked out equipments: '.$checkedOutEquipments->count());

            return response()->json([
                'success' => true,
                'equipments' => $checkedOutEquipments->map(function ($phaseEquipment) {
                    return [
                        'id' => $phaseEquipment->id,
                        'phase_id' => $phaseEquipment->phase_id,
                        'quantity' => $phaseEquipment->quantity,
                        'equipment' => [
                            'id' => $phaseEquipment->equipment->id,
                            'name' => $phaseEquipment->equipment->name,
                            'company_number' => $phaseEquipment->equipment->company_number,
                            'location_id' => $phaseEquipment->equipment->location_id,
                            'management_type' => $phaseEquipment->equipment->management_type,
                            'location_name' => $phaseEquipment->equipment->location->name ?? null,
                        ],
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getCheckedOutEquipments: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => '出庫中機材の取得に失敗しました: '.$e->getMessage(),
            ], 500);
        }
    }
}
