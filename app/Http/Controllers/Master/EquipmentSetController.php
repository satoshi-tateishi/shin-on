<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\EquipmentSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentSetController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request): View
    {
        $query = EquipmentSet::query();
        $query = $this->applyFilters($query, $request);

        $equipmentSets = $query->paginate(15);

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
        return ['sort', 'name', 'description', 'is_active', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
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

        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? '';

            switch ($header) {
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

        return $recordData;
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
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
}
