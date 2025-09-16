@extends('layouts.master')

@section('title', 'ユーザーマスタ編集')

@section('breadcrumb')
    > <a href="{{ route('master.users.index') }}" class="text-blue-600 hover:text-blue-800">ユーザーマスタ</a>
    > <span class="text-gray-800">{{ $user->name }}</span>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ユーザーマスタ編集</h1>
        <p class="mt-1 text-sm text-gray-600">ユーザー「{{ $user->name }}」の情報を編集します。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('master.users.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <div class="max-w-2xl">
            <!-- 注意事項 -->
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">編集時の注意事項</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>ユーザーのメールアドレスはLINE WORKSに登録されているメールアドレスと一致している必要があります</li>
                                <li>権限変更は慎重に行ってください</li>
                                <li>自分自身の権限は変更できません</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('master.users.update', $user) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="space-y-8">
                    <!-- 基本情報 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>

                        <!-- Icon Section -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-4">アイコン</label>
                            <div class="flex items-center space-x-6">
                                <!-- Current Icon Preview -->
                                <div class="flex-shrink-0">
                                    <div id="icon-preview-container" class="relative">
                                        @if($user->icon)
                                            <img id="icon-preview" src="{{ $user->icon }}" alt="Current Icon" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 transition-all duration-300">
                                            <div id="icon-placeholder" class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center hidden">
                                                <span class="text-gray-600 text-xl">{{ mb_substr($user->name, 0, 1) }}</span>
                                            </div>
                                        @else
                                            <div id="icon-placeholder" class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center transition-all duration-300">
                                                <span class="text-gray-600 text-xl">{{ mb_substr($user->name, 0, 1) }}</span>
                                            </div>
                                            <img id="icon-preview" src="" alt="Icon Preview" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 hidden transition-all duration-300">
                                        @endif
                                        <!-- Loading overlay -->
                                        <div id="loading-overlay" class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center hidden">
                                            <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload New Icon -->
                                <div class="flex-1">
                                    <input type="file" name="icon" id="icon-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors duration-200">
                                    <p class="mt-1 text-xs text-gray-500">JPEG, PNG, JPG, GIF, WebP形式対応。最大2MB（自動で200x200pxにリサイズされます）。</p>
                                    <p id="file-info" class="mt-1 text-xs text-blue-600 hidden"></p>
                                    @error('icon')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Remove Icon Button -->
                                @if($user->icon)
                                    <button type="button" onclick="if(confirm('アイコンを削除しますか？')) { document.getElementById('remove-icon-form').submit(); }" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200">
                                        削除
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- ソート順 -->
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                                    ソート順
                                </label>
                                <input type="number" name="sort" id="sort" value="{{ old('sort', $user->sort) }}" min="0"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('sort') border-red-300 @enderror">
                                @error('sort')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 所属 -->
                            <div>
                                <label for="affiliation" class="block text-sm font-medium text-gray-700 mb-2">
                                    所属 <span class="text-red-500">*</span>
                                </label>
                                <select name="affiliation" id="affiliation" required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('affiliation') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    <option value="employee" {{ old('affiliation', $user->affiliation) == 'employee' ? 'selected' : '' }}>社員</option>
                                    <option value="partner" {{ old('affiliation', $user->affiliation) == 'partner' ? 'selected' : '' }}>パートナー</option>
                                </select>
                                @error('affiliation')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 氏名 -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    氏名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- フリガナ -->
                            <div>
                                <label for="furigana" class="block text-sm font-medium text-gray-700 mb-2">
                                    フリガナ
                                </label>
                                <input type="text" name="furigana" id="furigana" value="{{ old('furigana', $user->furigana) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('furigana') border-red-300 @enderror">
                                @error('furigana')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- メールアドレス -->
                            <div class="md:col-span-2">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    メールアドレス <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- 職務情報 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">職務情報</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- 入社日 -->
                            <div>
                                <label for="hired_at" class="block text-sm font-medium text-gray-700 mb-2">
                                    入社日
                                </label>
                                <input type="date" name="hired_at" id="hired_at" value="{{ old('hired_at', $user->hired_at?->format('Y-m-d')) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('hired_at') border-red-300 @enderror">
                                @error('hired_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 退職日 -->
                            <div id="resigned_at_field">
                                <label for="resigned_at" class="block text-sm font-medium text-gray-700 mb-2">
                                    退職日
                                </label>
                                <input type="date" name="resigned_at" id="resigned_at" value="{{ old('resigned_at', $user->resigned_at?->format('Y-m-d')) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500
                                              @error('resigned_at') border-red-300 @enderror"
                                       {{ old('is_resigned', $user->is_resigned) ? '' : 'disabled' }}>
                                @error('resigned_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">退職済みチェック時のみ入力可能</p>
                            </div>

                            <!-- 生年月日 -->
                            <div>
                                <label for="birthday" class="block text-sm font-medium text-gray-700 mb-2">
                                    生年月日
                                </label>
                                <input type="date" name="birthday" id="birthday" value="{{ old('birthday', $user->birthday?->format('Y-m-d')) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('birthday') border-red-300 @enderror">
                                @error('birthday')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- 職種フラグ -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-3">職種・役割</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_staff" id="is_staff" value="1" {{ old('is_staff', $user->is_staff) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_staff" class="ml-2 block text-sm text-gray-900">
                                        スタッフ（公演担当者選択に表示）
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_designer" id="is_designer" value="1" {{ old('is_designer', $user->is_designer) ? 'checked' : '' }}
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <label for="is_designer" class="ml-2 block text-sm text-gray-900">
                                        サウンドデザイナー
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_driver" id="is_driver" value="1" {{ old('is_driver', $user->is_driver) ? 'checked' : '' }}
                                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <label for="is_driver" class="ml-2 block text-sm text-gray-900">
                                        ドライバー
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 状態フラグ -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-3">在職状態</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_on_leave" id="is_on_leave" value="1" {{ old('is_on_leave', $user->is_on_leave) ? 'checked' : '' }}
                                           class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                    <label for="is_on_leave" class="ml-2 block text-sm text-gray-900">
                                        休職中
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_resigned" id="is_resigned" value="1" {{ old('is_resigned', $user->is_resigned) ? 'checked' : '' }}
                                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                    <label for="is_resigned" class="ml-2 block text-sm text-gray-900">
                                        退職済み
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 連絡先情報 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">連絡先情報</h3>
                        <div class="space-y-4">
                            <!-- 携帯電話 -->
                            <div>
                                <label for="mobile_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    携帯電話
                                </label>
                                <input type="tel" name="mobile_phone" id="mobile_phone" value="{{ old('mobile_phone', $user->mobile_phone) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('mobile_phone') border-red-300 @enderror">
                                @error('mobile_phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 郵便番号・住所 -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        郵便番号
                                    </label>
                                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                                           placeholder="000-0000"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                  @error('postal_code') border-red-300 @enderror">
                                    @error('postal_code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                        住所
                                    </label>
                                    <input type="text" name="address" id="address" value="{{ old('address', $user->address) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                  @error('address') border-red-300 @enderror">
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- 緊急連絡先 -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        緊急連絡先氏名
                                    </label>
                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                  @error('emergency_contact_name') border-red-300 @enderror">
                                    @error('emergency_contact_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        緊急連絡先電話番号
                                    </label>
                                    <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                  @error('emergency_contact_phone') border-red-300 @enderror">
                                    @error('emergency_contact_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- システム権限 -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">システム権限</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 権限 -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                    権限 <span class="text-red-500">*</span>
                                </label>
                                <select name="role" id="role" required
                                        @if($user->id === auth()->user()->id) disabled @endif
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('role') border-red-300 @enderror">
                                    <option value="viewer" {{ old('role', $user->role) == 'viewer' ? 'selected' : '' }}>閲覧者</option>
                                    <option value="editor" {{ old('role', $user->role) == 'editor' ? 'selected' : '' }}>編集者</option>
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>管理者</option>
                                </select>
                                @if($user->id === auth()->user()->id)
                                    <input type="hidden" name="role" value="{{ $user->role }}">
                                    <p class="mt-1 text-sm text-gray-500">自分自身の権限は変更できません。</p>
                                @endif
                                @error('role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 有効状態 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">アカウント状態</label>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                            有効
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- メモ -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">メモ</h3>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                備考・メモ
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                             @error('notes') border-red-300 @enderror">{{ old('notes', $user->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>


                    <!-- 権限説明 -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">権限の説明</h4>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 mr-2">閲覧者</span>
                                データの閲覧のみ可能、編集・削除は不可
                            </div>
                            <div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 mr-2">編集者</span>
                                データの閲覧・作成・編集が可能、削除は不可
                            </div>
                            <div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 mr-2">管理者</span>
                                全ての操作が可能（削除・ユーザー管理含む）
                            </div>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-between pt-6 border-t border-gray-200">
                        <a href="{{ route('master.users.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            更新する
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden form for icon removal -->
    @if($user->icon)
        <form id="remove-icon-form" action="{{ route('master.users.remove-icon', $user) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <!-- JavaScript for Image Preview and Form State -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Icon preview functionality
            const iconInput = document.getElementById('icon-input');
            const iconPreview = document.getElementById('icon-preview');
            const iconPlaceholder = document.getElementById('icon-placeholder');
            const loadingOverlay = document.getElementById('loading-overlay');
            const fileInfo = document.getElementById('file-info');

            iconInput.addEventListener('change', function(event) {
                const file = event.target.files[0];

                if (file) {
                    // ファイル情報を表示
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    fileInfo.textContent = `選択されたファイル: ${file.name} (${fileSize}MB)`;
                    fileInfo.classList.remove('hidden');

                    // ファイルサイズチェック（2MB制限）
                    if (file.size > 2 * 1024 * 1024) {
                        fileInfo.textContent = 'エラー: ファイルサイズが2MBを超えています';
                        fileInfo.classList.remove('text-blue-600');
                        fileInfo.classList.add('text-red-600');
                        return;
                    }

                    // ローディング表示
                    loadingOverlay.classList.remove('hidden');

                    // FileReaderでプレビュー生成
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        setTimeout(() => { // ローディングアニメーションを見せるため少し遅延
                            iconPreview.src = e.target.result;
                            iconPreview.classList.remove('hidden');
                            iconPlaceholder.classList.add('hidden');
                            loadingOverlay.classList.add('hidden');

                            // ファイル情報の色を元に戻す
                            fileInfo.classList.remove('text-red-600');
                            fileInfo.classList.add('text-blue-600');
                        }, 500);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // ファイルが選択されていない場合は元に戻す
                    resetPreview();
                }
            });

            function resetPreview() {
                const hasCurrentIcon = iconPreview.src && iconPreview.src.indexOf('data:') === -1 && iconPreview.src !== window.location.href;

                if (hasCurrentIcon) {
                    // 既存のアイコンがある場合は元に戻す
                    iconPreview.classList.remove('hidden');
                    iconPlaceholder.classList.add('hidden');
                } else {
                    // 既存のアイコンがない場合はプレースホルダーを表示
                    iconPreview.classList.add('hidden');
                    iconPlaceholder.classList.remove('hidden');
                }

                fileInfo.classList.add('hidden');
                loadingOverlay.classList.add('hidden');
            }

            // Form state management
            const isResignedCheckbox = document.getElementById('is_resigned');
            const isOnLeaveCheckbox = document.getElementById('is_on_leave');
            const isStaffCheckbox = document.getElementById('is_staff');
            const isDesignerCheckbox = document.getElementById('is_designer');
            const isDriverCheckbox = document.getElementById('is_driver');
            const isActiveCheckbox = document.getElementById('is_active');
            const resignedAtField = document.getElementById('resigned_at');

            function updateResignedState() {
                if (isResignedCheckbox.checked) {
                    // 退職済みがONの場合、他のチェックボックスを全てOFFにして無効化
                    isOnLeaveCheckbox.checked = false;
                    isStaffCheckbox.checked = false;
                    isDesignerCheckbox.checked = false;
                    isDriverCheckbox.checked = false;
                    isActiveCheckbox.checked = false;

                    // 他のチェックボックスを無効化
                    isOnLeaveCheckbox.disabled = true;
                    isStaffCheckbox.disabled = true;
                    isDesignerCheckbox.disabled = true;
                    isDriverCheckbox.disabled = true;
                    isActiveCheckbox.disabled = true;

                    // チェックボックスの見た目を薄くする
                    isOnLeaveCheckbox.style.opacity = '0.5';
                    isStaffCheckbox.style.opacity = '0.5';
                    isDesignerCheckbox.style.opacity = '0.5';
                    isDriverCheckbox.style.opacity = '0.5';
                    isActiveCheckbox.style.opacity = '0.5';

                    // 退職日フィールドを有効にする
                    resignedAtField.disabled = false;
                    resignedAtField.style.opacity = '1';
                } else {
                    // 退職済みがOFFの場合、他のチェックボックスを有効化
                    isOnLeaveCheckbox.disabled = false;
                    isStaffCheckbox.disabled = false;
                    isDesignerCheckbox.disabled = false;
                    isDriverCheckbox.disabled = false;
                    isActiveCheckbox.disabled = false;

                    // チェックボックスの見た目を元に戻す
                    isOnLeaveCheckbox.style.opacity = '1';
                    isStaffCheckbox.style.opacity = '1';
                    isDesignerCheckbox.style.opacity = '1';
                    isDriverCheckbox.style.opacity = '1';
                    isActiveCheckbox.style.opacity = '1';

                    // 退職日フィールドを無効にする
                    resignedAtField.disabled = true;
                    resignedAtField.value = '';
                    resignedAtField.style.opacity = '0.5';
                }
            }

            // 初期状態を設定
            updateResignedState();

            // 退職済みチェックボックスの変更を監視
            isResignedCheckbox.addEventListener('change', updateResignedState);
        });
    </script>
@endsection