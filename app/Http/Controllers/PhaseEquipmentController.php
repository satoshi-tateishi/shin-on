<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMovement;
use App\Models\EquipmentSet;
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
        $phaseEquipments = PhaseEquipment::with([
            'equipment.subcategory.category',
            'checkoutUser',
            'checkinUser',
        ])
            ->forPhase($phase->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $equipmentStats = [
            'total' => $phaseEquipments->total(),
            'reserved' => PhaseEquipment::forPhase($phase->id)->reserved()->count(),
            'checked_out' => PhaseEquipment::forPhase($phase->id)->checkedOut()->count(),
            'checked_in' => PhaseEquipment::forPhase($phase->id)->checkedIn()->count(),
            'cancelled' => PhaseEquipment::forPhase($phase->id)->cancelled()->count(),
        ];

        return view('phase-equipment.index', compact('phase', 'phaseEquipments', 'equipmentStats'));
    }

    /**
     * Show the form for adding equipment to phase.
     */
    public function create(Phase $phase): View
    {
        $categories = EquipmentCategory::with('subcategories.equipments')
            ->active()
            ->orderBy('sort')
            ->get();

        $equipmentSets = EquipmentSet::with('items.equipment')
            ->active()
            ->orderBy('sort')
            ->get();

        return view('phase-equipment.create', compact('phase', 'categories', 'equipmentSets'));
    }

    /**
     * Store a newly created phase equipment usage.
     */
    public function store(Request $request, Phase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        // 期間重複チェック
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

        // 数量管理機材の場合、使用可能数量チェック
        if ($equipment->management_type === 'quantity') {
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

        $movements = EquipmentMovement::forPhase($phase->id)
            ->forEquipment($phaseEquipment->equipment_id)
            ->with(['fromLocation', 'toLocation', 'movedBy'])
            ->ordered()
            ->get();

        return view('phase-equipment.show', compact('phase', 'phaseEquipment', 'movements'));
    }

    /**
     * Show the form for editing the specified phase equipment.
     */
    public function edit(Phase $phase, PhaseEquipment $phaseEquipment): View
    {
        $phaseEquipment->load('equipment.subcategory.category');

        return view('phase-equipment.edit', compact('phase', 'phaseEquipment'));
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
        if ($phaseEquipment->status === 'checked_out') {
            return back()->withErrors([
                'error' => '貸出中の機材は削除できません。先に返却処理を行ってください。',
            ]);
        }

        $phaseEquipment->delete();

        return redirect()
            ->route('phases.equipment.index', $phase)
            ->with('success', '機材使用予約を削除しました。');
    }

    /**
     * Checkout equipment (change status to checked_out)
     */
    public function checkout(Request $request, Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        if (! $phaseEquipment->canCheckout()) {
            return back()->withErrors([
                'error' => 'この機材は貸出できません。',
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

            return back()->with('success', '機材を貸出しました。');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors([
                'error' => '貸出処理に失敗しました。',
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

        $validated = $request->validate([
            'checkin_date' => 'required|date',
            'to_location_id' => 'nullable|exists:locations,id',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // PhaseEquipment レコードを更新
            $phaseEquipment->update([
                'status' => 'checked_in',
                'checkin_date' => $validated['checkin_date'],
                'checkin_user_id' => auth()->id(),
                'note' => $validated['note'] ?? $phaseEquipment->note,
            ]);

            // EquipmentMovement レコードを作成
            EquipmentMovement::createCheckin(
                $phaseEquipment->equipment_id,
                $phase->id,
                $phaseEquipment->quantity,
                auth()->id(),
                $validated['to_location_id'] ?? null,
                $validated['note'] ?? null
            );

            DB::commit();

            return back()->with('success', '機材を返却しました。');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors([
                'error' => '返却処理に失敗しました。',
            ]);
        }
    }

    /**
     * Cancel equipment reservation
     */
    public function cancel(Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        if (! $phaseEquipment->canCancel()) {
            return back()->withErrors([
                'error' => 'この機材はキャンセルできません。',
            ]);
        }

        $phaseEquipment->update(['status' => 'cancelled']);

        return back()->with('success', '機材使用予約をキャンセルしました。');
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

        $equipments = $query->orderBy('name')->get()->map(function ($equipment) use ($phase) {
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
        $equipmentSet = EquipmentSet::with('items.equipment')->findOrFail($setId);

        $availability = [];
        $allAvailable = true;

        foreach ($equipmentSet->items as $item) {
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
}
