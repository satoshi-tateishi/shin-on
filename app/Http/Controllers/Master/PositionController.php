<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Position::query();
        $query = $this->applyFilters($query, $request);

        $positions = $query->get();

        return view('master.positions.index', compact('positions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('master.positions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'ポジション名は必須です。',
            'name.unique' => 'このポジション名は既に存在します。',
        ]);

        $maxSort = Position::max('sort');
        $validated['sort'] = $maxSort !== null ? $maxSort + 1 : 1;
        $validated['is_active'] = $request->boolean('is_active', true);

        Position::create($validated);

        return redirect()->route('positions.index')
            ->with('success', 'ポジションを作成しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position): View
    {
        return view('master.positions.show', compact('position'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Position $position): View
    {
        return view('master.positions.edit', compact('position'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name,'.$position->id,
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'ポジション名は必須です。',
            'name.unique' => 'このポジション名は既に存在します。',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        $position->update($validated);

        return redirect()->route('positions.index')
            ->with('success', 'ポジションを更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): RedirectResponse
    {
        try {
            $position->delete();

            return redirect()->route('positions.index')
                ->with('success', 'ポジションを削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('positions.index')
                ->with('error', 'ポジションの削除に失敗しました: '.$e->getMessage());
        }
    }

    /**
     * CSV操作用の実装
     */
    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getCsvHeaders(): array
    {
        return ['sort', 'name', 'is_active', 'created_at', 'updated_at'];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->sort,
            $record->name,
            $record->is_active ? '1' : '0',
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
                    $record['sort'] = intval($value) ?: null;
                    break;
                case 'name':
                    $record['name'] = $value;
                    break;
                case 'is_active':
                    $record['is_active'] = in_array(strtolower($value), ['1', 'true', 'はい', 'yes']);
                    break;
                default:
                    // created_at, updated_at は自動設定されるため除外
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

        return "positions_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('ポジション名が入力されていません');
        }

        return true;
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
            'position_ids' => 'required|array',
            'position_ids.*' => 'exists:positions,id',
        ]);

        try {
            foreach ($request->position_ids as $index => $positionId) {
                Position::where('id', $positionId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: '.$e->getMessage()], 500);
        }
    }
}
