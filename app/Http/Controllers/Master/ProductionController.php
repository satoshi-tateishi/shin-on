<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\Production;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request): View
    {
        $query = Production::query();
        $query = $this->applyFilters($query, $request);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $productions = $query->get();
        $types = ['株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', 'その他'];

        return view('master.productions.index', compact('productions', 'types'));
    }

    public function create(): View
    {
        $types = ['株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', 'その他'];

        return view('master.productions.create', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:株式会社,有限会社,合同会社,財団法人,公益財団法人,その他',
            'name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['sort'] = $this->getNextSortOrder();
        $validated['is_active'] = $request->boolean('is_active', true);

        Production::create($validated);

        return redirect()->route('productions.index')
            ->with('success', 'プロダクションを作成しました。');
    }

    public function show(Production $production): View
    {
        return view('master.productions.show', compact('production'));
    }

    public function edit(Production $production): View
    {
        $types = ['株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', 'その他'];

        return view('master.productions.edit', compact('production', 'types'));
    }

    public function update(Request $request, Production $production): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:株式会社,有限会社,合同会社,財団法人,公益財団法人,その他',
            'name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $production->update($validated);

        return redirect()->route('productions.index')
            ->with('success', 'プロダクションを更新しました。');
    }

    public function destroy(Production $production): RedirectResponse
    {
        try {
            $production->delete();

            return redirect()->route('productions.index')
                ->with('success', 'プロダクションを削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('productions.index')
                ->with('error', 'プロダクションの削除に失敗しました: '.$e->getMessage());
        }
    }

    protected function getModelClass(): string
    {
        return Production::class;
    }

    protected function getCsvHeaders(): array
    {
        return ['sort', 'type', 'name', 'postal_code', 'address', 'note', 'is_active', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->sort,
            $record->type,
            $record->name,
            $record->postal_code,
            $record->address,
            $record->note,
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
                case 'type':
                    $recordData['type'] = $value ?: '株式会社';
                    break;
                case 'name':
                    $recordData['name'] = $value;
                    break;
                case 'postal_code':
                    $recordData['postal_code'] = $value ?: null;
                    break;
                case 'address':
                    $recordData['address'] = $value ?: null;
                    break;
                case 'note':
                    $recordData['note'] = $value ?: null;
                    break;
                case 'is_active':
                    $recordData['is_active'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
            }
        }

        return $recordData;
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
            'production_ids' => 'required|array',
            'production_ids.*' => 'exists:productions,id',
        ]);

        try {
            foreach ($request->production_ids as $index => $productionId) {
                Production::where('id', $productionId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: '.$e->getMessage()], 500);
        }
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
        return ['name' => $recordData['name'], 'type' => $recordData['type']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "productions_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('プロダクション名が入力されていません');
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'type', 'created_at', 'updated_at'];
    }
}
