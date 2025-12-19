<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentSubcategoryController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request): View
    {
        $query = EquipmentSubcategory::with('category')->withCount('equipments');
        $query = $this->applyFilters($query, $request);

        // カテゴリでのフィルター
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subcategories = $query->get();
        $categories = EquipmentCategory::ordered()->get();

        return view('master.equipment-subcategories.index', compact('subcategories', 'categories'));
    }

    public function create(): View
    {
        $categories = EquipmentCategory::ordered()->get();

        return view('master.equipment-subcategories.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:equipment_categories,id',
            'name' => 'required|string|max:255',
        ], [
            'category_id.required' => 'カテゴリは必須です。',
            'category_id.exists' => '選択されたカテゴリが存在しません。',
            'name.required' => 'サブカテゴリ名は必須です。',
        ]);

        // 同一大分類内での名前重複チェック
        $exists = EquipmentSubcategory::where('category_id', $validated['category_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'この大分類には既に同じ名前の中分類が存在します。'])
                ->withInput();
        }

        $validated['sort'] = $this->getNextSortOrder();
        EquipmentSubcategory::create($validated);

        return redirect()->route('equipment-subcategories.index')
            ->with('success', '機材中分類を作成しました。');
    }

    public function show(EquipmentSubcategory $equipmentSubcategory): View
    {
        $equipmentSubcategory->load('category');

        $totalEquipments = $equipmentSubcategory->equipments()->count();
        $equipments = $equipmentSubcategory->equipments()->simplePaginate(20);

        return view('master.equipment-subcategories.show', compact('equipmentSubcategory', 'equipments', 'totalEquipments'));
    }

    public function edit(EquipmentSubcategory $equipmentSubcategory): View
    {
        $categories = EquipmentCategory::ordered()->get();

        return view('master.equipment-subcategories.edit', compact('equipmentSubcategory', 'categories'));
    }

    public function update(Request $request, EquipmentSubcategory $equipmentSubcategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:equipment_categories,id',
            'name' => 'required|string|max:255',
        ], [
            'category_id.required' => 'カテゴリは必須です。',
            'category_id.exists' => '選択されたカテゴリが存在しません。',
            'name.required' => 'サブカテゴリ名は必須です。',
        ]);

        // 同一大分類内での名前重複チェック（自分以外）
        $exists = EquipmentSubcategory::where('category_id', $validated['category_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $equipmentSubcategory->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'この大分類には既に同じ名前の中分類が存在します。'])
                ->withInput();
        }

        // is_active フィールドを適切に処理
        $validated['is_active'] = $request->input('is_active') == '1' ? 1 : 0;
        $equipmentSubcategory->update($validated);

        return redirect()->route('equipment-subcategories.index')
            ->with('success', '機材中分類を更新しました。');
    }

    public function destroy(EquipmentSubcategory $equipmentSubcategory): RedirectResponse
    {
        try {
            // 機材が存在する場合は削除不可
            if ($equipmentSubcategory->equipments()->count() > 0) {
                return redirect()->route('equipment-subcategories.index')
                    ->with('error', 'この中分類には機材が紐づいているため削除できません。');
            }

            $equipmentSubcategory->delete();

            return redirect()->route('equipment-subcategories.index')
                ->with('success', '機材中分類を削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('equipment-subcategories.index')
                ->with('error', '機材中分類の削除に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * Update sort order via drag and drop
     */
    public function updateSort(Request $request)
    {
        // 編集者・管理者のみがソート順更新可能
        if (! in_array(auth()->user()->role, ['editor', 'admin'])) {
            return response()->json(['success' => false, 'message' => '権限がありません。'], 403);
        }

        $request->validate([
            'equipment_subcategory_ids' => 'required|array',
            'equipment_subcategory_ids.*' => 'exists:equipment_subcategories,id',
        ]);

        try {
            foreach ($request->equipment_subcategory_ids as $index => $subcategoryId) {
                EquipmentSubcategory::where('id', $subcategoryId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: '.$e->getMessage()], 500);
        }
    }

    protected function getModelClass(): string
    {
        return EquipmentSubcategory::class;
    }

    protected function getCsvHeaders(): array
    {
        return ['id', 'category_id', 'sort', 'name', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->id,
            $record->category_id,
            $record->sort,
            $record->name,
            $record->created_at?->format('Y-m-d H:i:s'),
            $record->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function mapCsvRowToRecord(array $headers, array $data): array
    {
        $record = [];
        $id = null;

        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? '';

            switch ($header) {
                case 'id':
                    $id = $value ? (int) $value : null;
                    break;
                case 'category_id':
                    $record['category_id'] = intval($value ?: 0);
                    break;
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

        // IDがあれば含める（更新時の識別用）
        if ($id) {
            $record['id'] = $id;
        }

        return $record;
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
        // IDがある場合はIDで特定、なければcategory_idとnameで特定
        if (! empty($recordData['id'])) {
            return ['id' => $recordData['id']];
        }

        return ['category_id' => $recordData['category_id'], 'name' => $recordData['name']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "equipment_subcategories_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('中分類名が入力されていません');
        }
        if (empty($recordData['category_id']) || $recordData['category_id'] <= 0) {
            throw new \Exception('有効な大分類IDが入力されていません');
        }

        // 大分類の存在確認
        if (! EquipmentCategory::where('id', $recordData['category_id'])->exists()) {
            throw new \Exception("大分類ID {$recordData['category_id']} が存在しません");
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'created_at', 'updated_at', 'equipments_count'];
    }

    /**
     * カテゴリ別サブカテゴリ取得（API用）
     *
     * 修理記録作成フォームでの段階的サブカテゴリ選択に使用。
     * 通信量削減のため、カテゴリ選択時に該当サブカテゴリのみ動的取得。
     *
     * @param  Request  $request
     *                            - category: カテゴリ名（必須）
     * @return JsonResponse
     *                      - success: boolean
     *                      - subcategories: array サブカテゴリ一覧（sort順）
     *                      - id: サブカテゴリID
     *                      - name: サブカテゴリ名
     *                      - sort: ソート順
     */
    public function getSubcategoriesByCategory(Request $request)
    {
        try {
            $category = $request->input('category');

            // パラメータバリデーション
            if (empty($category)) {
                return response()->json([
                    'success' => false,
                    'error' => 'カテゴリを指定してください',
                ], 400);
            }

            // 指定されたカテゴリに属するアクティブなサブカテゴリを取得
            $subcategories = EquipmentSubcategory::with('category')
                ->whereHas('category', function ($query) use ($category) {
                    $query->where('name', $category);
                })
                ->active()  // アクティブなもののみ
                ->orderBy('sort')  // ソート値は重複しないため、これのみでOK
                ->get()
                ->map(function ($subcategory) {
                    return [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'sort' => $subcategory->sort ?? 999999,
                    ];
                });

            return response()->json([
                'success' => true,
                'subcategories' => $subcategories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
