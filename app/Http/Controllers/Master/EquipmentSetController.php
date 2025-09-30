<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentSet;
use App\Models\EquipmentSetItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EquipmentSetController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request): View
    {
        $query = EquipmentSet::query();
        $query = $this->applyFilters($query, $request);

        $equipmentSets = $query->get();

        return view('master.equipment-sets.index', compact('equipmentSets'));
    }

    public function create(): View
    {
        return view('master.equipment-sets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_sets,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'セット名は必須です。',
            'name.unique' => 'このセット名は既に使用されています。',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort'] = $this->getNextSortOrder();

        EquipmentSet::create($validated);

        return redirect()->route('equipment-sets.index')
            ->with('success', '機材セットを作成しました。');
    }

    public function show(EquipmentSet $equipmentSet): View
    {
        // セット構成機材をソート順で取得
        $equipmentSet->load(['equipmentItemsOrdered.equipment.category', 'equipmentItemsOrdered.equipment.subcategory', 'equipmentItemsOrdered.equipment.location']);

        return view('master.equipment-sets.show', compact('equipmentSet'));
    }

    public function edit(EquipmentSet $equipmentSet): View
    {
        return view('master.equipment-sets.edit', compact('equipmentSet'));
    }

    public function update(Request $request, EquipmentSet $equipmentSet): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_sets,name,'.$equipmentSet->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'セット名は必須です。',
            'name.unique' => 'このセット名は既に使用されています。',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $equipmentSet->update($validated);

        return redirect()->route('equipment-sets.index')
            ->with('success', '機材セットを更新しました。');
    }

    public function destroy(EquipmentSet $equipmentSet): RedirectResponse
    {
        try {
            $equipmentSet->delete();

            return redirect()->route('equipment-sets.index')
                ->with('success', '機材セットを削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('equipment-sets.index')
                ->with('error', '機材セットの削除に失敗しました: '.$e->getMessage());
        }
    }

    protected function getModelClass(): string
    {
        return EquipmentSet::class;
    }

    protected function getCsvHeaders(): array
    {
        return ['id', 'sort', 'name', 'description', 'is_active', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->id,
            $record->sort,
            $record->name,
            $record->description,
            $record->is_active ? '1' : '0',
            $record->created_at?->format('Y-m-d H:i:s'),
            $record->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function mapCsvRowToRecord(array $headers, array $data): array
    {
        $recordData = [];
        $id = null;

        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? '';

            switch ($header) {
                case 'id':
                    $id = $value ? (int) $value : null;
                    break;
                case 'sort':
                    $recordData['sort'] = intval($value ?: 0);
                    break;
                case 'name':
                    $recordData['name'] = $value;
                    break;
                case 'description':
                    $recordData['description'] = $value ?: null;
                    break;
                case 'is_active':
                    $recordData['is_active'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
            }
        }

        // IDがあれば含める（更新時の識別用）
        if ($id) {
            $recordData['id'] = $id;
        }

        return $recordData;
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
        // IDがある場合はIDで特定、なければnameで特定
        if (!empty($recordData['id'])) {
            return ['id' => $recordData['id']];
        }
        return ['name' => $recordData['name']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "equipment_sets_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('セット名が入力されていません');
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'created_at', 'updated_at'];
    }

    // === 機材セット内容管理API ===

    /**
     * セットに機材を追加
     */
    public function addEquipment(Request $request, EquipmentSet $equipmentSet): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'quantity' => 'required|integer|min:1',
            'is_required' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // 重複チェック
            $existingItem = EquipmentSetItem::where([
                'equipment_set_id' => $equipmentSet->id,
                'equipment_id' => $validated['equipment_id'],
            ])->first();

            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機材は既にセットに含まれています。',
                ], 422);
            }

            // ソート順の最大値を取得
            $maxSort = EquipmentSetItem::where('equipment_set_id', $equipmentSet->id)
                ->max('sort_order') ?? 0;

            // 機材をセットに追加
            EquipmentSetItem::create([
                'equipment_set_id' => $equipmentSet->id,
                'equipment_id' => $validated['equipment_id'],
                'quantity' => $validated['quantity'],
                'sort_order' => $maxSort + 1,
                'is_required' => $validated['is_required'] ?? true,
                'notes' => $validated['notes'],
            ]);

            DB::commit();

            $equipment = Equipment::find($validated['equipment_id']);

            return response()->json([
                'success' => true,
                'message' => "機材「{$equipment->name}」をセットに追加しました。",
                'equipment' => $equipment,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('機材セット追加エラー: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '機材の追加に失敗しました。',
            ], 500);
        }
    }

    /**
     * セットから機材を削除
     */
    public function removeEquipment(EquipmentSet $equipmentSet, Equipment $equipment): JsonResponse
    {
        try {
            DB::beginTransaction();

            $deleted = EquipmentSetItem::where([
                'equipment_set_id' => $equipmentSet->id,
                'equipment_id' => $equipment->id,
            ])->delete();

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機材はセットに含まれていません。',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "機材「{$equipment->name}」をセットから削除しました。",
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('機材セット削除エラー: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '機材の削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * セット構成機材の順序を更新
     */
    public function updateItemSort(Request $request, EquipmentSet $equipmentSet): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.equipment_id' => 'required|integer|exists:equipments,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['items'] as $item) {
                EquipmentSetItem::where([
                    'equipment_set_id' => $equipmentSet->id,
                    'equipment_id' => $item['equipment_id'],
                ])->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'セット内機材の順序を更新しました。',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('機材セット順序更新エラー: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '順序の更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * セット構成機材の設定を更新
     */
    public function updateEquipmentItem(Request $request, EquipmentSet $equipmentSet, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'is_required' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $updated = EquipmentSetItem::where([
                'equipment_set_id' => $equipmentSet->id,
                'equipment_id' => $equipment->id,
            ])->update([
                'quantity' => $validated['quantity'],
                'is_required' => $validated['is_required'] ?? true,
                'notes' => $validated['notes'],
            ]);

            if (! $updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機材はセットに含まれていません。',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "機材「{$equipment->name}」の設定を更新しました。",
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('機材セット設定更新エラー: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '設定の更新に失敗しました。',
            ], 500);
        }
    }

}
