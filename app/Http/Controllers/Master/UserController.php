<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasCsvOperations;
use App\Http\Controllers\Concerns\HasMasterOperations;
use App\Http\Controllers\Concerns\HasSortableRecords;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    use HasCsvOperations, HasMasterOperations, HasSortableRecords;

    public function index(Request $request): View
    {
        $query = User::query();
        $query = $this->applyUserFilters($query, $request);

        $users = $query->get();

        return view('master.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('master.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:general,viewer,editor,admin',
            'sort' => 'nullable|integer|min:0',
            'affiliation' => 'required|in:employee,partner',
            'furigana' => 'nullable|string|max:255',
            'hired_at' => 'nullable|date',
            'resigned_at' => 'nullable|date',
            'birthday' => 'nullable|date',
            'mobile_phone' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_staff' => 'nullable|boolean',
            'is_designer' => 'nullable|boolean',
            'is_driver' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => '氏名は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'role.required' => '権限は必須です。',
            'role.in' => '正しい権限を選択してください。',
            'affiliation.required' => '所属は必須です。',
            'affiliation.in' => '正しい所属を選択してください。',
            'sort.integer' => 'ソート順は数値で入力してください。',
            'sort.min' => 'ソート順は0以上で入力してください。',
            'hired_at.date' => '入社日は正しい日付形式で入力してください。',
            'resigned_at.date' => '退職日は正しい日付形式で入力してください。',
            'birthday.date' => '生年月日は正しい日付形式で入力してください。',
            'icon.image' => 'アイコンは画像ファイルである必要があります。',
            'icon.mimes' => 'アイコンはJPEG、PNG、JPG、GIF、WebP形式のファイルをアップロードしてください。',
            'icon.max' => 'アイコンのファイルサイズは2MB以下である必要があります。',
        ]);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $iconPath = $this->handleIconUpload($request->file('icon'));
            $validated['icon'] = $iconPath;
        }

        // システム登録ユーザーはログイン不可なのでパスワードは設定しない
        $validated['password'] = null;
        $validated['lineworks_id'] = null;

        // チェックボックスの値を適切に処理（チェックされていない場合はfalse）
        $validated['is_staff'] = $request->has('is_staff');
        $validated['is_designer'] = $request->has('is_designer');
        $validated['is_driver'] = $request->has('is_driver');
        $validated['is_active'] = $request->has('is_active');

        // 新規作成時は在職状態フラグをデフォルトでfalse
        $validated['is_on_leave'] = false;
        $validated['is_resigned'] = false;

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'ユーザーを作成しました。');
    }

    public function show(User $user): View
    {
        return view('master.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('master.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:general,viewer,editor,admin',
            'sort' => 'nullable|integer|min:0',
            'affiliation' => 'required|in:employee,partner',
            'furigana' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'hired_at' => 'nullable|date',
            'resigned_at' => 'nullable|date',
            'birthday' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'is_staff' => 'nullable|boolean',
            'is_designer' => 'nullable|boolean',
            'is_driver' => 'nullable|boolean',
            'is_on_leave' => 'nullable|boolean',
            'is_resigned' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'name.required' => '氏名は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'role.required' => '権限は必須です。',
            'role.in' => '正しい権限を選択してください。',
            'affiliation.required' => '所属は必須です。',
            'affiliation.in' => '正しい所属を選択してください。',
            'sort.integer' => 'ソート順は数値で入力してください。',
            'sort.min' => 'ソート順は0以上で入力してください。',
            'hired_at.date' => '入社日は正しい日付形式で入力してください。',
            'resigned_at.date' => '退職日は正しい日付形式で入力してください。',
            'birthday.date' => '生年月日は正しい日付形式で入力してください。',
            'icon.image' => 'アイコンは画像ファイルである必要があります。',
            'icon.mimes' => 'アイコンはJPEG、PNG、JPG、GIF、WebP形式のファイルをアップロードしてください。',
            'icon.max' => 'アイコンのファイルサイズは2MB以下である必要があります。',
        ]);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            // Delete old icon if it exists
            if ($user->icon && file_exists(public_path($user->icon))) {
                unlink(public_path($user->icon));
            }
            $iconPath = $this->handleIconUpload($request->file('icon'));
            $validated['icon'] = $iconPath;
        }

        // チェックボックスの値を適切に処理（チェックされていない場合はfalse）
        $validated['is_staff'] = $request->has('is_staff');
        $validated['is_designer'] = $request->has('is_designer');
        $validated['is_driver'] = $request->has('is_driver');
        $validated['is_on_leave'] = $request->has('is_on_leave');
        $validated['is_resigned'] = $request->has('is_resigned');
        $validated['is_active'] = $request->has('is_active');

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(User $user): RedirectResponse
    {
        // 自分自身は削除不可
        if ($user->id === auth()->user()->id) {
            return redirect()->route('users.index')
                ->with('error', '自分自身を削除することはできません。');
        }

        try {
            $user->delete();

            return redirect()->route('users.index')
                ->with('success', 'ユーザーを削除しました。');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('error', 'ユーザーの削除に失敗しました: '.$e->getMessage());
        }
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getCsvHeaders(): array
    {
        return [
            'id', 'sort', 'name', 'furigana', 'email', 'mobile_phone', 'birthday', 'hired_at', 'resigned_at',
            'postal_code', 'address', 'emergency_contact_name', 'emergency_contact_phone', 'notes',
            'affiliation', 'role', 'is_designer', 'is_staff', 'is_driver', 'is_on_leave',
            'is_resigned', 'is_active', 'created_at', 'updated_at',
        ];
    }

    protected function mapRecordToCsvRow($record): array
    {
        return [
            $record->id,
            $record->sort,
            $record->name,
            $record->furigana,
            $record->email,
            $record->mobile_phone,
            $record->birthday?->format('Y-m-d'),
            $record->hired_at?->format('Y-m-d'),
            $record->resigned_at?->format('Y-m-d'),
            $record->postal_code,
            $record->address,
            $record->emergency_contact_name,
            $record->emergency_contact_phone,
            $record->notes,
            $record->affiliation,
            $record->role,
            $record->is_designer ? '1' : '0',
            $record->is_staff ? '1' : '0',
            $record->is_driver ? '1' : '0',
            $record->is_on_leave ? '1' : '0',
            $record->is_resigned ? '1' : '0',
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
                    $recordData['sort'] = (int) $value ?: null;
                    break;
                case 'name':
                    $recordData['name'] = $value;
                    break;
                case 'furigana':
                    $recordData['furigana'] = $value ?: null;
                    break;
                case 'email':
                    $recordData['email'] = $value;
                    break;
                case 'role':
                    $recordData['role'] = $value;
                    break;
                case 'affiliation':
                    $recordData['affiliation'] = $value;
                    break;
                case 'hired_at':
                    $recordData['hired_at'] = $value ? date('Y-m-d', strtotime($value)) : null;
                    break;
                case 'birthday':
                    $recordData['birthday'] = $value ? date('Y-m-d', strtotime($value)) : null;
                    break;
                case 'mobile_phone':
                    $recordData['mobile_phone'] = $value ?: null;
                    break;
                case 'postal_code':
                    $recordData['postal_code'] = $value ?: null;
                    break;
                case 'address':
                    $recordData['address'] = $value ?: null;
                    break;
                case 'emergency_contact_name':
                    $recordData['emergency_contact_name'] = $value ?: null;
                    break;
                case 'emergency_contact_phone':
                    $recordData['emergency_contact_phone'] = $value ?: null;
                    break;
                case 'notes':
                    $recordData['notes'] = $value ?: null;
                    break;
                case 'is_staff':
                    $recordData['is_staff'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_designer':
                    $recordData['is_designer'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_driver':
                    $recordData['is_driver'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_on_leave':
                    $recordData['is_on_leave'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_resigned':
                    $recordData['is_resigned'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
                case 'is_active':
                    $recordData['is_active'] = in_array($value, ['1', 'true', 'TRUE', 'はい', 'Yes']);
                    break;
            }
        }

        // デフォルト値の設定
        $recordData['lineworks_id'] = null;
        $recordData['lineworks_token'] = null;
        $recordData['lineworks_refresh_token'] = null;

        // IDがあれば含める（更新時の識別用）
        if ($id) {
            $recordData['id'] = $id;
        }

        return $recordData;
    }

    protected function getUniqueIdentifier(array $recordData): array
    {
        // IDがある場合はIDで特定、なければemailで特定
        if (! empty($recordData['id'])) {
            return ['id' => $recordData['id']];
        }

        return ['email' => $recordData['email']];
    }

    protected function getCsvFilename(string $type): string
    {
        $timestamp = now()->format('Ymd_His');

        return "users_{$type}_{$timestamp}.csv";
    }

    protected function validateCsvRecord(array $recordData, int $lineNumber): bool
    {
        // 必須項目のバリデーション
        if (empty($recordData['name'])) {
            throw new \Exception('name は必須です');
        }

        if (empty($recordData['email'])) {
            throw new \Exception('email は必須です');
        }

        if (! filter_var($recordData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('email の形式が正しくありません');
        }

        if (! in_array($recordData['role'] ?? '', ['admin', 'editor', 'general', 'viewer'])) {
            throw new \Exception('role は「admin」「editor」「general」「viewer」のいずれかを指定してください');
        }

        if (! in_array($recordData['affiliation'] ?? '', ['employee', 'partner'])) {
            throw new \Exception('affiliation は「employee」「partner」のいずれかを指定してください');
        }

        return true;
    }

    protected function getSortableColumns(): array
    {
        return ['sort', 'name', 'email', 'role', 'hired_at', 'created_at', 'updated_at'];
    }

    /**
     * Handle icon upload
     */
    private function handleIconUpload($iconFile): string
    {
        // Create storage directory if it doesn't exist
        $uploadPath = public_path('storage/icons/users');
        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generate unique filename
        $extension = $iconFile->getClientOriginalExtension();
        $filename = uniqid('user_icon_').'.'.$extension;

        // Move the uploaded file
        $iconFile->move($uploadPath, $filename);

        return '/storage/icons/users/'.$filename;
    }

    /**
     * Remove user icon
     */
    public function removeIcon(User $user): RedirectResponse
    {
        if ($user->icon && file_exists(public_path($user->icon))) {
            unlink(public_path($user->icon));
        }

        $user->update(['icon' => null]);

        return redirect()->back()->with('success', 'アイコンを削除しました。');
    }

    /**
     * ユーザー専用のフィルター機能
     */
    protected function applyUserFilters($query, Request $request)
    {
        // ステータスフィルター
        $status = $request->get('status', 'active');
        switch ($status) {
            case 'active':
                $query->where('is_resigned', false)->where('is_on_leave', false);
                break;
            case 'on_leave':
                $query->where('is_on_leave', true);
                break;
            case 'resigned':
                $query->where('is_resigned', true);
                break;
            case 'all':
                // フィルターなし（すべて表示）
                break;
        }

        // 名前での検索
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%'.$request->search.'%');
        }

        // ソート順
        if ($status === 'all') {
            // 「すべて」選択時のみソート機能を有効にする
            $sortBy = $request->get('sort_by', 'sort');
            $sortOrder = $request->get('sort_order', 'asc');

            if (in_array($sortBy, $this->getSortableColumns())) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('sort');
            }
        } else {
            // その他のフィルターではsortカラム順でソート
            $query->orderBy('sort');
        }

        return $query;
    }

    /**
     * Update sort order via drag and drop
     */
    public function updateSort(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            foreach ($request->user_ids as $index => $userId) {
                User::where('id', $userId)->update(['sort' => $index + 1]);
            }

            return response()->json(['success' => true, 'message' => 'ソート順を更新しました。']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ソート順の更新に失敗しました: '.$e->getMessage()], 500);
        }
    }
}
