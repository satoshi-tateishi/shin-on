<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\CompanyLogo;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentSubcategory;
use App\Models\Location;
use App\Services\LineWorksBotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request)
    {
        $query = Equipment::forIndex();

        // Equipment固有のフィルターを適用（検索、ソート、カテゴリ等すべて含む）
        $this->applyEquipmentFilters($query, $request);

        // JSON形式のレスポンスが要求された場合（機材検索API用）
        if ($request->input('format') === 'json') {
            // 機材セット用検索は個体管理の機材のみ対象
            $query->where('management_type', 'individual');

            // 他の機材セットに既に登録済みの機材を除外
            if ($request->has('exclude_assigned')) {
                $currentSetId = $request->input('current_set_id');

                $assignedEquipmentIds = \App\Models\EquipmentSetItem::query()
                    ->when($currentSetId, function ($q) use ($currentSetId) {
                        // 現在編集中のセットは除外（新規追加時はnull）
                        $q->where('equipment_set_id', '!=', $currentSetId);
                    })
                    ->pluck('equipment_id')
                    ->toArray();

                if (! empty($assignedEquipmentIds)) {
                    $query->whereNotIn('id', $assignedEquipmentIds);
                }
            }

            $equipments = $query->with(['category', 'subcategory', 'location'])->limit(20)->get();

            return response()->json(['equipments' => $equipments]);
        }

        // ソートモード判定
        if ($request->get('sort_mode') === 'all') {
            $equipments = $query->get();
        } else {
            $equipments = $query->paginate(200);
        }

        // パフォーマンス向上のためマスターデータをキャッシュ（30分）
        $categories = Cache::remember('equipment_categories', 1800, function () {
            return EquipmentCategory::ordered()->get();
        });

        $subcategories = Cache::remember('equipment_subcategories_with_category', 1800, function () {
            return EquipmentSubcategory::with('category')->ordered()->get();
        });

        $locations = Cache::remember('warehouse_locations', 1800, function () {
            return Location::warehouses()->ordered()->get();
        });

        return view('master.equipments.index', compact('equipments', 'categories', 'subcategories', 'locations'));
    }

    public function create(): View
    {
        $categories = EquipmentCategory::ordered()->get();
        $locations = Location::warehouses()->ordered()->get();

        return view('master.equipments.create', compact('categories', 'locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subcategory_id' => 'required|exists:equipment_subcategories,id',
            'manufacturer' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'management_type' => 'required|in:individual,quantity',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|in:台,個,本,箱,ケース,ラック,セット',
            'model_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,in_use,repair,retired,lost',
            'location_id' => 'nullable|exists:locations,id',
            'now_location_id' => 'nullable|exists:locations,id',
            'is_discard' => 'nullable|boolean',
            'is_schedule_visible' => 'nullable|boolean',
            'discard_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'subcategory_id.required' => 'サブカテゴリは必須です。',
            'subcategory_id.exists' => '選択されたサブカテゴリが存在しません。',
            'name.required' => '機材名は必須です。',
            'management_type.required' => '管理方式は必須です。',
            'management_type.in' => '正しい管理方式を選択してください。',
            'status.required' => '状態は必須です。',
            'status.in' => '正しい状態を選択してください。',
            'location_id.exists' => '選択された場所が存在しません。',
        ]);

        $validated['sort'] = $this->getNextSortOrder();
        Equipment::create($validated);

        return redirect()->route('master.equipments.index')
            ->with('success', '機材を作成しました。');
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load(['category', 'subcategory', 'location']);

        return view('master.equipments.show', compact('equipment'));
    }

    public function edit(Equipment $equipment): View
    {
        $categories = EquipmentCategory::ordered()->get();
        $subcategories = EquipmentSubcategory::where('category_id', $equipment->subcategory->category_id ?? null)->ordered()->get();
        $locations = Location::warehouses()->ordered()->get();

        return view('master.equipments.edit', compact('equipment', 'categories', 'subcategories', 'locations'));
    }

    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'subcategory_id' => 'required|exists:equipment_subcategories,id',
            'manufacturer' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'management_type' => 'required|in:individual,quantity',
            'quantity' => 'nullable|integer|min:1',
            'unit' => 'nullable|in:台,個,本,箱,ケース,ラック,セット',
            'model_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,in_use,repair,retired,lost',
            'location_id' => 'nullable|exists:locations,id',
            'now_location_id' => 'nullable|exists:locations,id',
            'is_discard' => 'nullable|boolean',
            'is_schedule_visible' => 'nullable|boolean',
            'discard_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'subcategory_id.required' => 'サブカテゴリは必須です。',
            'subcategory_id.exists' => '選択されたサブカテゴリが存在しません。',
            'name.required' => '機材名は必須です。',
            'management_type.required' => '管理方式は必須です。',
            'management_type.in' => '正しい管理方式を選択してください。',
            'status.required' => '状態は必須です。',
            'status.in' => '正しい状態を選択してください。',
            'location_id.exists' => '選択された場所が存在しません。',
        ]);

        $equipment->update($validated);

        return redirect()->route('master.equipments.index')
            ->with('success', '機材を更新しました。');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        try {
            $equipment->delete();

            return redirect()->route('equipments.index')
                ->with('success', '機材を削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('equipments.index')
                ->with('error', '機材の削除に失敗しました: '.$e->getMessage());
        }
    }

    public function getSubcategories(Request $request)
    {
        $subcategories = EquipmentSubcategory::where('category_id', $request->category_id)
            ->ordered()
            ->get(['id', 'name']);

        return response()->json($subcategories);
    }

    protected function getModelClass(): string
    {
        return Equipment::class;
    }

    protected function getCsvHeaders(): array
    {
        return [
            'id', 'subcategory_id', 'sort', 'manufacturer', 'name', 'company_number',
            'management_type', 'quantity', 'unit', 'model_number', 'serial_number',
            'supplier', 'purchase_date', 'warranty_expiry', 'price', 'status',
            'location_id', 'now_location_id', 'is_schedule_visible', 'is_discard', 'discard_at', 'notes', 'created_at', 'updated_at',
        ];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->id,
            $record->subcategory_id,
            $record->sort,
            $record->manufacturer,
            $record->name,
            $record->company_number,
            $record->management_type,
            $record->quantity,
            $record->unit,
            $record->model_number,
            $record->serial_number,
            $record->supplier,
            $record->purchase_date?->format('Y-m-d'),
            $record->warranty_expiry?->format('Y-m-d'),
            $record->price,
            $record->status,
            $record->location_id,
            $record->now_location_id,
            $record->is_schedule_visible ? '1' : '0',
            $record->is_discard ? '1' : '0',
            $record->discard_at?->format('Y-m-d H:i:s'),
            $record->notes,
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
                case 'subcategory_id':
                    $recordData['subcategory_id'] = ! empty($value) ? intval($value) : null;
                    break;
                case 'sort':
                    $recordData['sort'] = intval($value ?: 0);
                    break;
                case 'manufacturer':
                    $recordData['manufacturer'] = $value ?: null;
                    break;
                case 'name':
                    $recordData['name'] = $value;
                    break;
                case 'company_number':
                    $recordData['company_number'] = $value ?: null;
                    break;
                case 'management_type':
                    $recordData['management_type'] = $value ?: null;
                    break;
                case 'quantity':
                    $recordData['quantity'] = ! empty($value) ? intval($value) : null;
                    break;
                case 'unit':
                    $recordData['unit'] = $value ?: null;
                    break;
                case 'model_number':
                    $recordData['model_number'] = $value ?: null;
                    break;
                case 'serial_number':
                    $recordData['serial_number'] = $value ?: null;
                    break;
                case 'supplier':
                    $recordData['supplier'] = $value ?: null;
                    break;
                case 'purchase_date':
                    $recordData['purchase_date'] = ! empty($value) ? $value : null;
                    break;
                case 'warranty_expiry':
                    $recordData['warranty_expiry'] = ! empty($value) ? $value : null;
                    break;
                case 'price':
                    $recordData['price'] = ! empty($value) ? floatval($value) : null;
                    break;
                case 'status':
                    $recordData['status'] = $value ?: 'available';
                    break;
                case 'location_id':
                    $recordData['location_id'] = ! empty($value) ? intval($value) : null;
                    break;
                case 'now_location_id':
                    $recordData['now_location_id'] = ! empty($value) ? intval($value) : null;
                    break;
                case 'is_schedule_visible':
                    $recordData['is_schedule_visible'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_discard':
                    $recordData['is_discard'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'discard_at':
                    $recordData['discard_at'] = ! empty($value) ? $value : null;
                    break;
                case 'notes':
                    $recordData['notes'] = $value ?: null;
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
        // IDがある場合はIDで特定、なければnameとcompany_numberで特定
        if (!empty($recordData['id'])) {
            return ['id' => $recordData['id']];
        }
        return ['name' => $recordData['name'], 'company_number' => $recordData['company_number']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "equipments_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        if (empty($recordData['name'])) {
            throw new \Exception('機材名が入力されていません');
        }

        // subcategory_id の確認（NULLでない場合のみ存在チェック）
        if (!is_null($recordData['subcategory_id'])) {
            if (! EquipmentSubcategory::where('id', $recordData['subcategory_id'])->exists()) {
                throw new \Exception("サブカテゴリID {$recordData['subcategory_id']} が存在しません");
            }
        }

        // location_id の確認（NULLでない場合のみ存在チェック）
        if (!is_null($recordData['location_id'])) {
            if (! Location::where('id', $recordData['location_id'])->exists()) {
                throw new \Exception("場所ID {$recordData['location_id']} が存在しません");
            }
        }

        // 現在地の存在確認（指定されている場合）
        if (! empty($recordData['now_location_id']) && ! Location::where('id', $recordData['now_location_id'])->exists()) {
            throw new \Exception("現在地ID {$recordData['now_location_id']} が存在しません");
        }

        // 状態の妥当性チェック
        $validStatuses = ['available', 'in_use', 'repair', 'broken', 'retired', 'lost'];
        if (! in_array($recordData['status'], $validStatuses)) {
            throw new \Exception("無効な状態です: {$recordData['status']}");
        }

        return true;
    }

    /**
     * Equipment統合フィルターメソッド
     */
    protected function applyEquipmentFilters($query, Request $request): void
    {
        // 検索機能
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $search = $request->search;
                $q->where('name', 'LIKE', '%'.$search.'%')
                    ->orWhere('model_number', 'LIKE', '%'.$search.'%')
                    ->orWhere('serial_number', 'LIKE', '%'.$search.'%')
                    ->orWhere('company_number', 'LIKE', '%'.$search.'%');
            });
        }

        // カテゴリでのフィルター（サブカテゴリ経由）
        if ($request->filled('category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // サブカテゴリでのフィルター
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        // 場所でのフィルター
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // 状態でのフィルター
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ソート処理
        $sortBy = $request->get('sort_by', 'sort');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, $this->getSortableColumns())) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('sort')->orderBy('name');
        }
    }

    protected function getSortableColumns(): array
    {
        return ['name', 'model_number', 'serial_number', 'purchase_date', 'status', 'sort', 'created_at', 'updated_at'];
    }

    /**
     * CSV インポート（HasCsvOperationsトレイトをオーバーライド）
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'csv_file.mimes' => 'CSVファイルをアップロードしてください。',
            'csv_file.max' => 'ファイルサイズは2MB以内にしてください。',
        ]);

        try {
            $csvContent = file_get_contents($request->file('csv_file')->getPathname());
            $result = $this->processCsvImport($csvContent);

            if ($result['success']) {
                return redirect()->back()->with('success',
                    "CSVインポートが完了しました。{$result['imported']}件のデータを処理しました。");
            } else {
                $errorMessage = 'CSVインポートでエラーが発生しました。';
                if (! empty($result['errors'])) {
                    $errorMessage .= "\n\n詳細:\n".implode("\n", $result['errors']);
                }

                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->with('import_errors', $result['errors']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'CSVファイルの処理中にエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * 機材の将来予約を取得（API用）
     */
    public function getFutureReservations(Equipment $equipment)
    {
        try {
            $futureReservations = $equipment->getFutureReservations();

            $reservations = $futureReservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'phase_name' => $reservation->phase->name,
                    'performance_title' => $reservation->phase->performance->display_name ?? null,
                    'start_date' => $reservation->phase->start_date->format('Y/m/d'),
                    'end_date' => $reservation->phase->end_date->format('Y/m/d'),
                    'status' => $reservation->status,
                ];
            });

            return response()->json([
                'success' => true,
                'reservations' => $reservations,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * カテゴリ・サブカテゴリによる機材フィルタリング（API用）
     *
     * 修理記録作成フォームでの段階的機材選択に使用。
     * パフォーマンス最適化のため、必要最小限のデータのみ返却。
     *
     * @param Request $request
     *   - category: カテゴリ名（必須）
     *   - subcategory: サブカテゴリ名（必須）
     * @return JsonResponse
     *   - success: boolean
     *   - equipments: array 機材一覧（sort順）
     *     - id: 機材ID
     *     - name: 機材名
     *     - company_number: 会社管理番号
     *     - sort: ソート順
     */
    public function getEquipmentsBySubcategory(Request $request)
    {
        try {
            $category = $request->input('category');
            $subcategory = $request->input('subcategory');

            // パラメータバリデーション
            if (empty($category) || empty($subcategory)) {
                return response()->json([
                    'success' => false,
                    'error' => 'カテゴリとサブカテゴリの両方を指定してください',
                ], 400);
            }

            // 指定されたカテゴリ・サブカテゴリに属する機材を取得
            $equipments = Equipment::with(['subcategory.category'])
                ->whereHas('subcategory.category', function ($query) use ($category) {
                    $query->where('name', $category);
                })
                ->whereHas('subcategory', function ($query) use ($subcategory) {
                    $query->where('name', $subcategory);
                })
                ->orderBy('sort')  // ソート値は重複しないため、これのみでOK
                ->get()
                ->map(function ($equipment) {
                    return [
                        'id' => $equipment->id,
                        'name' => $equipment->name,
                        'company_number' => $equipment->company_number,
                        'sort' => $equipment->sort ?? 999999,
                    ];
                });

            return response()->json([
                'success' => true,
                'equipments' => $equipments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 機材マスタ一覧をPDF出力
     */
    public function exportPdf(Request $request)
    {
        // PDF生成は時間がかかるため、実行時間を延長
        set_time_limit(120);

        try {
            // PDF出力用：同じ機材をグループ化（manufacturer + name + subcategory）
            $query = Equipment::select([
                'subcategory_id',
                'manufacturer', 'name',
            ])
            ->selectRaw('GROUP_CONCAT(company_number ORDER BY sort SEPARATOR ", ") as company_numbers')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->with([
                'subcategory:id,category_id,name',
                'subcategory.category:id,name',
            ])
            ->groupBy('manufacturer', 'name', 'subcategory_id');

            // フィルター適用（グループ化前のカラムのみ）
            if ($request->filled('category_id')) {
                $query->whereHas('subcategory', fn ($q) => $q->where('category_id', $request->category_id));
            }
            if ($request->filled('subcategory_id')) {
                $query->where('subcategory_id', $request->subcategory_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('manufacturer', 'like', "%{$search}%")
                      ->orWhere('company_number', 'like', "%{$search}%");
                });
            }

            $equipments = $query->orderBy('subcategory_id')->orderBy('name')->get();

            // カテゴリ別にグループ化
            $groupedEquipments = $equipments->groupBy(function ($equipment) {
                return $equipment->subcategory->category->name ?? '未分類';
            });

            // アクティブなロゴを取得
            $companyLogo = CompanyLogo::getActiveLogo();
            $logoPath = $companyLogo ? public_path('storage/'.$companyLogo->file_path) : null;

            // フィルター情報を取得
            $filterInfo = [];
            if ($request->filled('category_id')) {
                $category = EquipmentCategory::find($request->category_id);
                $filterInfo['カテゴリ'] = $category?->name ?? '不明';
            }
            if ($request->filled('subcategory_id')) {
                $subcategory = EquipmentSubcategory::find($request->subcategory_id);
                $filterInfo['サブカテゴリ'] = $subcategory?->name ?? '不明';
            }
            if ($request->filled('location_id')) {
                $location = Location::find($request->location_id);
                $filterInfo['基本倉庫'] = $location?->name ?? '不明';
            }
            if ($request->filled('status')) {
                $statusLabels = [
                    'available' => '利用可能',
                    'in_use' => '使用中',
                    'broken' => '故障',
                    'retired' => '廃止',
                ];
                $filterInfo['状態'] = $statusLabels[$request->status] ?? $request->status;
            }
            if ($request->filled('search')) {
                $filterInfo['検索'] = $request->search;
            }

            $pdf = Pdf::loadView('master.equipments.pdf', [
                'groupedEquipments' => $groupedEquipments,
                'totalCount' => $equipments->count(),
                'filterInfo' => $filterInfo,
                'exportDate' => now()->format('Y年m月d日 H:i'),
                'logoPath' => $logoPath,
            ]);

            $pdf->setPaper('A4', 'portrait');

            $filename = '機材マスタ一覧_'.now()->format('Ymd_His').'.pdf';

            // TODO: 本番では download に戻す
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF出力エラー: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'PDF出力中にエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * 機材マスタ一覧PDFをLINE WORKSに送信
     */
    public function sendPdfToLineWorks(Request $request): RedirectResponse
    {
        $tempFilePath = null;

        try {
            $user = auth()->user();

            if (! $user->lineworks_id) {
                return redirect()->route('master.equipments.index')
                    ->with('error', 'LINE WORKS IDが設定されていません。');
            }

            // PDF出力用：同じ機材をグループ化（manufacturer + name + subcategory）
            $query = Equipment::select([
                'subcategory_id',
                'manufacturer', 'name',
            ])
            ->selectRaw('GROUP_CONCAT(company_number ORDER BY sort SEPARATOR ", ") as company_numbers')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->with([
                'subcategory:id,category_id,name',
                'subcategory.category:id,name',
            ])
            ->groupBy('manufacturer', 'name', 'subcategory_id');

            // フィルター適用
            if ($request->filled('category_id')) {
                $query->whereHas('subcategory', fn ($q) => $q->where('category_id', $request->category_id));
            }
            if ($request->filled('subcategory_id')) {
                $query->where('subcategory_id', $request->subcategory_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('manufacturer', 'like', "%{$search}%")
                      ->orWhere('company_number', 'like', "%{$search}%");
                });
            }

            $equipments = $query->orderBy('subcategory_id')->orderBy('name')->get();

            // カテゴリ別にグループ化
            $groupedEquipments = $equipments->groupBy(function ($equipment) {
                return $equipment->subcategory->category->name ?? '未分類';
            });

            // アクティブなロゴを取得
            $companyLogo = CompanyLogo::getActiveLogo();
            $logoPath = $companyLogo ? public_path('storage/'.$companyLogo->file_path) : null;

            // フィルター情報
            $filterInfo = [];
            if ($request->filled('category_id')) {
                $category = EquipmentCategory::find($request->category_id);
                $filterInfo['カテゴリ'] = $category?->name ?? '不明';
            }
            if ($request->filled('subcategory_id')) {
                $subcategory = EquipmentSubcategory::find($request->subcategory_id);
                $filterInfo['サブカテゴリ'] = $subcategory?->name ?? '不明';
            }
            if ($request->filled('search')) {
                $filterInfo['検索'] = $request->search;
            }

            $pdf = Pdf::loadView('master.equipments.pdf', [
                'groupedEquipments' => $groupedEquipments,
                'totalCount' => $equipments->count(),
                'filterInfo' => $filterInfo,
                'exportDate' => now()->format('Y年m月d日 H:i'),
                'logoPath' => $logoPath,
            ]);

            $pdf->setPaper('A4', 'portrait');

            $filename = '機材マスタ一覧_'.now()->format('Ymd_His').'.pdf';

            // 一時ディレクトリに保存
            $tempDir = 'temp';
            if (! Storage::exists($tempDir)) {
                Storage::makeDirectory($tempDir);
            }

            $tempFileName = uniqid('equipment_pdf_').'.pdf';
            $tempFilePath = storage_path("app/{$tempDir}/{$tempFileName}");

            file_put_contents($tempFilePath, $pdf->output());

            // LINE WORKSに送信
            $botService = app(LineWorksBotService::class);
            $botService->sendPdfToUser($user->lineworks_id, $tempFilePath, $filename);

            Log::info('Equipment PDF sent to LINE WORKS', [
                'user_id' => $user->id,
                'lineworks_id' => $user->lineworks_id,
                'filename' => $filename,
            ]);

            return redirect()->route('master.equipments.index')
                ->with('success', 'PDFファイルをLINE WORKSに送信しました。');
        } catch (\Exception $e) {
            Log::error('Failed to send Equipment PDF to LINE WORKS', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('master.equipments.index')
                ->with('error', 'PDFの送信に失敗しました: '.$e->getMessage());
        } finally {
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }
}
