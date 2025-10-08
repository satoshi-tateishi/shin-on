<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Location::query();
        $query = $this->applyFilters($query, $request);

        // タイプでのフィルター
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $locations = $query->get();
        $types = ['劇場', '稽古場', '倉庫'];

        return view('master.locations.index', compact('locations', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $types = ['劇場', '稽古場', '倉庫'];

        return view('master.locations.create', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:劇場,稽古場,倉庫',
            'name' => 'required|string|max:255',
            'furigana' => 'nullable|string|max:255',
            'tel1_name' => 'nullable|string|max:255',
            'tel1' => 'nullable|string|max:255',
            'tel2_name' => 'nullable|string|max:255',
            'tel2' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
            'email1_name' => 'nullable|string|max:255',
            'email1' => 'nullable|email|max:255',
            'email2_name' => 'nullable|string|max:255',
            'email2' => 'nullable|email|max:255',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_inventory_visible' => 'sometimes|boolean',
            'is_transfer_visible' => 'sometimes|boolean',
        ], [
            'type.required' => '場所タイプは必須です。',
            'name.required' => '場所名は必須です。',
            'email1.email' => '有効なメールアドレスを入力してください。',
            'email2.email' => '有効なメールアドレスを入力してください。',
        ]);

        $validated['sort'] = $this->getNextSortOrder();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_inventory_visible'] = $request->boolean('is_inventory_visible');
        $validated['is_transfer_visible'] = $request->boolean('is_transfer_visible');

        Location::create($validated);

        return redirect()->route('locations.index')
            ->with('success', '場所・倉庫を作成しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): View
    {
        $location->load('equipments');

        return view('master.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Location $location): View
    {
        $types = ['劇場', '稽古場', '倉庫'];

        return view('master.locations.edit', compact('location', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:劇場,稽古場,倉庫',
            'name' => 'required|string|max:255',
            'furigana' => 'nullable|string|max:255',
            'tel1_name' => 'nullable|string|max:255',
            'tel1' => 'nullable|string|max:255',
            'tel2_name' => 'nullable|string|max:255',
            'tel2' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
            'email1_name' => 'nullable|string|max:255',
            'email1' => 'nullable|email|max:255',
            'email2_name' => 'nullable|string|max:255',
            'email2' => 'nullable|email|max:255',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_inventory_visible' => 'sometimes|boolean',
            'is_transfer_visible' => 'sometimes|boolean',
        ], [
            'type.required' => '場所タイプは必須です。',
            'name.required' => '場所名は必須です。',
            'email1.email' => '有効なメールアドレスを入力してください。',
            'email2.email' => '有効なメールアドレスを入力してください。',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['is_inventory_visible'] = $request->boolean('is_inventory_visible', false);
        $validated['is_transfer_visible'] = $request->boolean('is_transfer_visible', false);

        $location->update($validated);

        return redirect()->route('locations.index')
            ->with('success', '場所・倉庫を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location): RedirectResponse
    {
        try {
            // 機材が存在する場合は削除不可
            if ($location->equipments()->count() > 0) {
                return redirect()->route('locations.index')
                    ->with('error', 'この場所には機材が紐づいているため削除できません。');
            }

            $location->delete();

            return redirect()->route('locations.index')
                ->with('success', '場所・倉庫を削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('locations.index')
                ->with('error', '場所・倉庫の削除に失敗しました: '.$e->getMessage());
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
            'location_ids' => 'required|array',
            'location_ids.*' => 'exists:locations,id',
        ]);

        try {
            foreach ($request->location_ids as $index => $locationId) {
                Location::where('id', $locationId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: '.$e->getMessage()], 500);
        }
    }

    /**
     * CSV操作用の実装
     */
    protected function getModelClass(): string
    {
        return Location::class;
    }

    protected function getCsvHeaders(): array
    {
        return [
            'id', 'sort', 'type', 'name', 'furigana', 'tel1_name', 'tel1', 'tel2_name', 'tel2',
            'fax', 'email1_name', 'email1', 'email2_name', 'email2', 'postal_code', 'address',
            'note', 'is_active', 'is_inventory_visible', 'is_transfer_visible', 'created_at', 'updated_at',
        ];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->id,
            $record->sort,
            $record->type,
            $record->name,
            $record->furigana,
            $record->tel1_name,
            $record->tel1,
            $record->tel2_name,
            $record->tel2,
            $record->fax,
            $record->email1_name,
            $record->email1,
            $record->email2_name,
            $record->email2,
            $record->postal_code,
            $record->address,
            $record->note,
            $record->is_active ? '1' : '0',
            $record->is_inventory_visible ? '1' : '0',
            $record->is_transfer_visible ? '1' : '0',
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
                case 'type':
                    $recordData['type'] = $value ?: '倉庫';
                    break;
                case 'name':
                    $recordData['name'] = $value;
                    break;
                case 'furigana':
                    $recordData['furigana'] = $value ?: null;
                    break;
                case 'tel1_name':
                    $recordData['tel1_name'] = $value ?: null;
                    break;
                case 'tel1':
                    $recordData['tel1'] = $value ?: null;
                    break;
                case 'tel2_name':
                    $recordData['tel2_name'] = $value ?: null;
                    break;
                case 'tel2':
                    $recordData['tel2'] = $value ?: null;
                    break;
                case 'fax':
                    $recordData['fax'] = $value ?: null;
                    break;
                case 'email1_name':
                    $recordData['email1_name'] = $value ?: null;
                    break;
                case 'email1':
                    $recordData['email1'] = $value ?: null;
                    break;
                case 'email2_name':
                    $recordData['email2_name'] = $value ?: null;
                    break;
                case 'email2':
                    $recordData['email2'] = $value ?: null;
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
                case 'is_inventory_visible':
                    $recordData['is_inventory_visible'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_transfer_visible':
                    $recordData['is_transfer_visible'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
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
        // IDがある場合はIDで特定、なければnameとtypeで特定
        if (!empty($recordData['id'])) {
            return ['id' => $recordData['id']];
        }
        return ['name' => $recordData['name'], 'type' => $recordData['type']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "locations_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('場所名が入力されていません');
        }
        if (! in_array($recordData['type'], ['劇場', '稽古場', '倉庫'])) {
            throw new \Exception('無効な場所タイプです');
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'sort', 'type', 'created_at', 'updated_at', 'equipments_count'];
    }
}
