<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentMovement;
use App\Models\Location;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase Equipment Checkout Controller
 *
 * Handles checkout and checkin operations for phase equipment.
 * This controller contains methods extracted from PhaseEquipmentController
 * to provide focused checkout/checkin functionality.
 */
class PhaseEquipmentCheckoutController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Checkout equipment (change status to checked_out)
     *
     * This method handles the checkout process for individual equipment items.
     * It validates the request data, updates the phase equipment status,
     * and creates an equipment movement record for tracking.
     *
     * @param  Request  $request  The HTTP request containing checkout data
     * @param  Phase  $phase  The phase the equipment belongs to
     * @param  PhaseEquipment  $phaseEquipment  The equipment to checkout
     * @return RedirectResponse Redirect back with success or error message
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

            // アクティビティログ記録
            $this->activityLogService->logEquipmentCheckout($phaseEquipment, $phase, $phaseEquipment->quantity);

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
     *
     * This method handles the checkin process for individual equipment items.
     * It includes special handling for equipment requiring location selection
     * (location_id 92-94) and updates the equipment's physical location.
     *
     * @param  Request  $request  The HTTP request containing checkin data
     * @param  Phase  $phase  The phase the equipment belongs to
     * @param  PhaseEquipment  $phaseEquipment  The equipment to checkin
     * @return RedirectResponse Redirect back with success or error message
     */
    public function checkin(Request $request, Phase $phase, PhaseEquipment $phaseEquipment): RedirectResponse
    {
        if (! $phaseEquipment->canCheckin()) {
            return back()->withErrors([
                'error' => 'この機材は返却できません。',
            ]);
        }

        $equipment = $phaseEquipment->equipment;

        // location_id=92-94の機材は返却先選択が必要
        $requiresLocationSelection = in_array($equipment->location_id, [92, 93, 94]);

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

        // location_id=92-94で返却先が選択されていない場合のエラー
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

            // location_id=92-94の機材の場合、機材の場所を更新
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

            // アクティビティログ記録
            $this->activityLogService->logEquipmentCheckin($phaseEquipment, $phase, $phaseEquipment->quantity);

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
     * Bulk checkout reserved and checked-in equipment in the phase
     *
     * This method performs a bulk checkout operation on equipment that is
     * either in reserved or checked_in status. It processes all eligible
     * equipment within the phase simultaneously.
     *
     * @param  Phase  $phase  The phase containing equipment to checkout
     * @return RedirectResponse Redirect back with success or error message
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
     *
     * This method performs a bulk checkout operation specifically on equipment
     * that is in reserved status only. It excludes checked_in equipment.
     *
     * @param  Phase  $phase  The phase containing reserved equipment to checkout
     * @return RedirectResponse Redirect back with success or error message
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
     *
     * This method performs a bulk checkout operation specifically on equipment
     * that is in checked_in status only. It excludes reserved equipment.
     *
     * @param  Phase  $phase  The phase containing checked-in equipment to checkout
     * @return RedirectResponse Redirect back with success or error message
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
     *
     * This method performs a bulk checkin operation on equipment that is
     * currently checked out. It supports both full bulk checkin and selective
     * checkin based on equipment IDs provided via JSON request.
     *
     * @param  Phase  $phase  The phase containing checked-out equipment to checkin
     * @param  Request  $request  The HTTP request, may contain equipment_ids for selective checkin
     * @return RedirectResponse|JsonResponse Redirect back or JSON response based on request type
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
}
