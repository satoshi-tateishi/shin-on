<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentSet;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhaseEquipmentApiController extends Controller
{
    /**
     * Get available equipment for phase (AJAX)
     *
     * Retrieves a list of available equipment for a specific phase based on filtering criteria.
     * This method handles equipment availability checking, conflict detection, and quantity calculations.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @param Phase $phase The phase for which to retrieve available equipment
     *
     * Query parameters:
     * - category_id: Filter by equipment category ID
     * - subcategory_id: Filter by equipment subcategory ID
     * - search: Search term for equipment name, company_number, or model_number
     *
     * @return JsonResponse Returns JSON array of available equipment with the following structure:
     * [
     *   {
     *     "id": int,
     *     "name": string,
     *     "company_number": string,
     *     "model_number": string,
     *     "management_type": string,
     *     "quantity": int,
     *     "available_quantity": int,
     *     "has_conflict": bool,
     *     "category": string,
     *     "subcategory": string
     *   }
     * ]
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
     *
     * Validates the availability of all equipment items within an equipment set for a specific phase.
     * This method checks for conflicts and availability for each item in the set, providing
     * comprehensive availability information.
     *
     * @param Request $request The HTTP request containing the set_id parameter
     * @param Phase $phase The phase for which to check equipment set availability
     *
     * Query parameters:
     * - set_id: The ID of the equipment set to check availability for
     *
     * @return JsonResponse Returns JSON object with availability details:
     * {
     *   "set_id": int,
     *   "set_name": string,
     *   "all_available": bool,
     *   "items": [
     *     {
     *       "equipment_id": int,
     *       "equipment_name": string,
     *       "required_quantity": int,
     *       "available_quantity": int,
     *       "has_conflict": bool,
     *       "is_available": bool
     *     }
     *   ]
     * }
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If equipment set not found
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
     * Get equipment information API (for checking location_id during return process)
     *
     * Retrieves detailed information about a specific equipment item associated with a phase.
     * This method is primarily used during the return/checkin process to verify location
     * requirements and management type.
     *
     * @param Phase $phase The phase associated with the equipment
     * @param PhaseEquipment $phaseEquipment The phase equipment record to retrieve info for
     *
     * @return JsonResponse Returns JSON object with equipment details:
     * {
     *   "success": bool,
     *   "equipment": {
     *     "id": int,
     *     "name": string,
     *     "company_number": string,
     *     "location_id": int,
     *     "management_type": string,
     *     "location_name": string|null
     *   }
     * }
     *
     * On error returns:
     * {
     *   "success": false,
     *   "error": string
     * }
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
     * Get checked out equipment list API (for location_id checking during bulk return)
     *
     * Retrieves a list of all equipment currently checked out for a specific phase.
     * This method is used for bulk return operations and location requirement validation.
     * Each equipment item includes location information to determine if location selection
     * is required during the return process.
     *
     * @param Phase $phase The phase for which to retrieve checked out equipment
     *
     * @return JsonResponse Returns JSON object with checked out equipment list:
     * {
     *   "success": bool,
     *   "equipments": [
     *     {
     *       "id": int,
     *       "phase_id": int,
     *       "quantity": int,
     *       "equipment": {
     *         "id": int,
     *         "name": string,
     *         "company_number": string,
     *         "location_id": int,
     *         "management_type": string,
     *         "location_name": string|null
     *       }
     *     }
     *   ]
     * }
     *
     * On error returns:
     * {
     *   "success": false,
     *   "error": string
     * }
     *
     * Logs detailed information for debugging purposes including phase ID and equipment count.
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