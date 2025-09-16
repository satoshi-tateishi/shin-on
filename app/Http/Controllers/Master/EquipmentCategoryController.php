<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\EquipmentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentCategoryController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = EquipmentCategory::withCount('subcategories');
        $query = $this->applyFilters($query, $request);

        $categories = $query->paginate(100);

        return view('master.equipment-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('master.equipment-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_categories,name',
        ], [
            'name.required' => 'カテゴリ名は必須です。',
            'name.unique' => 'このカテゴリ名は既に存在します。',
        ]);

        $validated['sort'] = $this->getNextSortOrder();

        EquipmentCategory::create($validated);

        return redirect()->route('equipment-categories.index')
            ->with('success', '機材カテゴリを作成しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(EquipmentCategory $equipmentCategory): View
    {
        $equipmentCategory->load('subcategories');

        return view('master.equipment-categories.show', compact('equipmentCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EquipmentCategory $equipmentCategory): View
    {
        return view('master.equipment-categories.edit', compact('equipmentCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EquipmentCategory $equipmentCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:equipment_categories,name,'.$equipmentCategory->id,
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'カテゴリ名は必須です。',
            'name.unique' => 'このカテゴリ名は既に存在します。',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $equipmentCategory->update($validated);

        return redirect()->route('equipment-categories.index')
            ->with('success', '機材カテゴリを更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EquipmentCategory $equipmentCategory): RedirectResponse
    {
        try {
            // 中分類が存在する場合は削除不可
            if ($equipmentCategory->subcategories()->count() > 0) {
                return redirect()->route('equipment-categories.index')
                    ->with('error', 'このカテゴリにはサブカテゴリが紐づいているため削除できません。');
            }

            $equipmentCategory->delete();

            return redirect()->route('equipment-categories.index')
                ->with('success', '機材カテゴリを削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('equipment-categories.index')
                ->with('error', '機材カテゴリの削除に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * Update sort order via drag and drop
     */
    public function updateSort(Request $request)
    {
        // 編集者・管理者のみがソート順更新可能
        if (!in_array(auth()->user()->role, ['editor', 'admin'])) {
            return response()->json(['success' => false, 'message' => '権限がありません。'], 403);
        }

        $request->validate([
            'equipment_category_ids' => 'required|array',
            'equipment_category_ids.*' => 'exists:equipment_categories,id',
        ]);

        try {
            foreach ($request->equipment_category_ids as $index => $categoryId) {
                EquipmentCategory::where('id', $categoryId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * CSV操作用の実装
     */
    protected function getModelClass(): string
    {
        return EquipmentCategory::class;
    }

    protected function getCsvHeaders(): array
    {
        return ['sort', 'name', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->sort,
            $record->name,
            $record->created_at?->format('Y-m-d H:i:s'),
            $record->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function mapCsvRowToRecord(array $headers, array $data): array
    {
        $record = [];

        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? '';

            switch ($header) {
                case 'sort':
                    $record['sort'] = intval($value ?: 0);
                    break;
                case 'name':
                    $record['name'] = $value;
                    break;
                // created_at, updated_at は自動設定されるため除外
                default:
                    break;
            }
        }

        return $record;
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
        return ['name' => $recordData['name']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "equipment_categories_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('カテゴリ名が入力されていません');
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'created_at', 'updated_at', 'subcategories_count'];
    }
}
