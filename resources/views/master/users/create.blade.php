@extends('layouts.master')

@section('title', 'ユーザーマスタ作成')

@section('breadcrumb')
    > <a href="{{ route('master.users.index') }}" class="text-blue-600 hover:text-blue-800">ユーザーマスタ</a>
    > <span class="text-gray-800">新規作成</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">新規ユーザー</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.users.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>
            <button type="submit" form="create-form"
                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                作成
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <div class="max-w-2xl">
            <form id="create-form" method="POST" action="{{ route('master.users.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4 sm:space-y-6">
                    <!-- 基本情報 -->
                    <div class="bg-gray-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">基本情報</h3>

                        <!-- Icon Section -->
                        <div class="mb-4 sm:mb-6">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-4">アイコン</label>
                            <div class="flex items-center space-x-3 sm:space-x-6">
                                <!-- Icon Preview -->
                                <div class="flex-shrink-0">
                                    <div id="icon-preview-container" class="relative">
                                        <div id="icon-placeholder" class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-gray-600 text-base sm:text-xl">?</span>
                                        </div>
                                        <img id="icon-preview" src="" alt="Icon Preview" class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-gray-200 hidden">
                                        <!-- Loading overlay -->
                                        <div id="loading-overlay" class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center hidden">
                                            <div class="animate-spin rounded-full h-4 w-4 sm:h-6 sm:w-6 border-2 border-white border-t-transparent"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Icon -->
                                <div class="flex-1">
                                    <input type="file" name="icon" id="icon-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                           class="mt-1 block w-full text-xs sm:text-sm text-gray-500 file:mr-2 sm:file:mr-4 file:py-1.5 file:px-3 sm:file:py-2 sm:file:px-4 file:rounded-md file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700">
                                    <p class="mt-1 text-xs text-gray-500 hidden sm:block">JPEG, PNG, GIF, WebP形式対応。最大2MB。</p>
                                    <p id="file-info" class="mt-1 text-xs text-blue-600 hidden"></p>
                                    @error('icon')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <!-- 所属 -->
                            <div>
                                <label for="affiliation" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    所属 <span class="text-red-500">*</span>
                                </label>
                                <select name="affiliation" id="affiliation" required
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('affiliation') border-red-300 @enderror">
                                    <option value="">選択</option>
                                    <option value="employee" {{ old('affiliation') == 'employee' ? 'selected' : '' }}>社員</option>
                                    <option value="partner" {{ old('affiliation') == 'partner' ? 'selected' : '' }}>パートナー</option>
                                </select>
                                @error('affiliation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ソート順 -->
                            <div>
                                <label for="sort" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    ソート順
                                </label>
                                <input type="number" name="sort" id="sort" value="{{ old('sort', 0) }}" min="0"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('sort') border-red-300 @enderror">
                                @error('sort')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 氏名 -->
                            <div>
                                <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    氏名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- フリガナ -->
                            <div>
                                <label for="furigana" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    フリガナ
                                </label>
                                <input type="text" name="furigana" id="furigana" value="{{ old('furigana') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('furigana') border-red-300 @enderror">
                                @error('furigana')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- メールアドレス -->
                            <div class="col-span-2">
                                <label for="email" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    メールアドレス <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- 職務情報 -->
                    <div class="bg-gray-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">職務情報</h3>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <!-- 入社日 -->
                            <div>
                                <label for="hired_at" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    入社日
                                </label>
                                <input type="date" name="hired_at" id="hired_at" value="{{ old('hired_at') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('hired_at') border-red-300 @enderror">
                                @error('hired_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 生年月日 -->
                            <div>
                                <label for="birthday" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    生年月日
                                </label>
                                <input type="date" name="birthday" id="birthday" value="{{ old('birthday') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('birthday') border-red-300 @enderror">
                                @error('birthday')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- 職種フラグ -->
                        <div class="mt-3 sm:mt-4">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">職種・役割</label>
                            <div class="flex flex-wrap gap-3 sm:gap-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_staff" id="is_staff" value="1" {{ old('is_staff') ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_staff" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        スタッフ
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_designer" id="is_designer" value="1" {{ old('is_designer') ? 'checked' : '' }}
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <label for="is_designer" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        デザイナー
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_driver" id="is_driver" value="1" {{ old('is_driver') ? 'checked' : '' }}
                                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <label for="is_driver" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        ドライバー
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 連絡先情報 -->
                    <div class="bg-gray-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">連絡先情報</h3>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <!-- 携帯電話 -->
                            <div class="col-span-2">
                                <label for="mobile_phone" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    携帯電話
                                </label>
                                <input type="tel" name="mobile_phone" id="mobile_phone" value="{{ old('mobile_phone') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('mobile_phone') border-red-300 @enderror">
                                @error('mobile_phone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 郵便番号 -->
                            <div>
                                <label for="postal_code" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    郵便番号
                                </label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                                       placeholder="000-0000"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('postal_code') border-red-300 @enderror">
                                @error('postal_code')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 住所 -->
                            <div>
                                <label for="address" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    住所
                                </label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('address') border-red-300 @enderror">
                                @error('address')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 緊急連絡先氏名 -->
                            <div>
                                <label for="emergency_contact_name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    緊急連絡先
                                </label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('emergency_contact_name') border-red-300 @enderror">
                                @error('emergency_contact_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 緊急連絡先電話番号 -->
                            <div>
                                <label for="emergency_contact_phone" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    緊急連絡先TEL
                                </label>
                                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('emergency_contact_phone') border-red-300 @enderror">
                                @error('emergency_contact_phone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- システム権限 -->
                    <div class="bg-gray-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">システム権限</h3>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <!-- 権限 -->
                            <div>
                                <label for="role" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    権限 <span class="text-red-500">*</span>
                                </label>
                                <select name="role" id="role" required
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('role') border-red-300 @enderror">
                                    <option value="">選択</option>
                                    <option value="general" {{ old('role') == 'general' ? 'selected' : '' }}>一般</option>
                                    <option value="viewer" {{ old('role') == 'viewer' ? 'selected' : '' }}>閲覧者</option>
                                    <option value="editor" {{ old('role') == 'editor' ? 'selected' : '' }}>編集者</option>
                                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>管理者</option>
                                </select>
                                @error('role')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 有効状態 -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">状態</label>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        有効
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- メモ -->
                    <div class="bg-gray-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">メモ</h3>
                        <div>
                            <textarea name="notes" id="notes" rows="2"
                                      class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                             @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Image Preview -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                iconPreview.classList.add('hidden');
                iconPlaceholder.classList.remove('hidden');
                fileInfo.classList.add('hidden');
                loadingOverlay.classList.add('hidden');
            }
        });
    </script>
@endsection