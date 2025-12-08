@extends('layouts.master')

@section('title', '会社設定')

@section('content')
<div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">会社設定</h1>
        </div>

        <div class="p-4 sm:p-6">
            <!-- 会社基本情報フォーム -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">会社基本情報</h2>
                <form method="POST" action="{{ route('admin.company-info.store-company-info') }}" class="space-y-4 max-w-md">
                    @csrf
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">会社名 <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" id="company_name"
                               value="{{ old('company_name', $companyInfo->company_name ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <div class="max-w-[200px]">
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">郵便番号</label>
                        <input type="text" name="postal_code" id="postal_code"
                               value="{{ old('postal_code', $companyInfo->postal_code ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="000-0000">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">住所</label>
                        <input type="text" name="address" id="address"
                               value="{{ old('address', $companyInfo->address ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="max-w-[200px]">
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">電話番号</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $companyInfo->phone ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="00-0000-0000">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            {{ $companyInfo ? '更新' : '登録' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- 修理担当者フォーム -->
            <div class="mb-8 pt-8 border-t border-b border-gray-200 dark:border-gray-700 pb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">修理担当者</h2>
                <form method="POST" action="{{ route('admin.company-info.store-company-info') }}" class="space-y-4 max-w-md">
                    @csrf
                    <!-- 会社基本情報を隠しフィールドとして保持 -->
                    <input type="hidden" name="company_name" value="{{ $companyInfo->company_name ?? '' }}">
                    <input type="hidden" name="postal_code" value="{{ $companyInfo->postal_code ?? '' }}">
                    <input type="hidden" name="address" value="{{ $companyInfo->address ?? '' }}">
                    <input type="hidden" name="phone" value="{{ $companyInfo->phone ?? '' }}">

                    <div class="max-w-[200px]">
                        <label for="repair_contact_person" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">修理担当者名</label>
                        <input type="text" name="repair_contact_person" id="repair_contact_person"
                               value="{{ old('repair_contact_person', $companyInfo->repair_contact_person ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="repair_contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">修理担当者メールアドレス</label>
                        <input type="email" name="repair_contact_email" id="repair_contact_email"
                               value="{{ old('repair_contact_email', $companyInfo->repair_contact_email ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            {{ $companyInfo ? '更新' : '登録' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- 現在のロゴ表示 -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">会社ロゴ</h2>
                @if($logo)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 sm:p-6 mb-4">
                        <div class="flex items-center space-x-4">
                            <img src="{{ $logo->url }}"
                                 alt="会社ロゴ"
                                 class="h-14 sm:h-16 w-auto object-contain border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-800 p-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 dark:text-white text-sm sm:text-base truncate">{{ $logo->file_name }}</p>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($logo->file_size / 1024, 1) }} KB
                                    • {{ strtoupper(pathinfo($logo->file_name, PATHINFO_EXTENSION)) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    登録日時: {{ $logo->created_at->format('Y年n月j日 H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <form method="POST" action="{{ route('admin.company-info.destroy') }}"
                              onsubmit="return confirm('現在のロゴを削除してもよろしいですか？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                                削除
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400">ロゴが設定されていません</p>
                    </div>
                @endif
            </div>

            <!-- ロゴアップロードフォーム -->
            <div x-data="logoUploader()">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $logo ? '新しいロゴをアップロード' : 'ロゴをアップロード' }}
                </h2>
                <form method="POST" action="{{ route('admin.company-info.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div
                        @dragenter.prevent="isDragging = true"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                        @click="$refs.fileInput.click()"
                        :class="isDragging ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
                        class="border-2 border-dashed rounded-lg p-6 transition-colors duration-200 cursor-pointer"
                    >
                        <div class="text-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                <label class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                                    <span>ファイルを選択</span>
                                    <input
                                        x-ref="fileInput"
                                        name="logo"
                                        type="file"
                                        class="sr-only"
                                        accept="image/*"
                                        required
                                        @change="handleFileSelect($event)"
                                        @click.stop
                                    >
                                </label>
                                <p class="pl-1">またはドラッグ&ドロップ</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PNG, JPG, GIF, SVG（最大2MB）
                            </p>
                            <div x-show="fileName" x-cloak class="mt-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">選択されたファイル: <span x-text="fileName"></span></p>
                                <div class="mt-2">
                                    <img x-show="previewUrl" :src="previewUrl" class="h-24 w-auto mx-auto object-contain border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-800 p-2">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            {{ $logo ? '更新' : 'アップロード' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- 注意事項 -->
            <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <h3 class="font-medium text-blue-900 dark:text-blue-300 mb-2">📝 注意事項</h3>
                <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                    <li>• 推奨サイズ: 横幅200px以上、縦横比は自由</li>
                    <li>• 背景透明のPNGファイルがおすすめです</li>
                    <li>• 新しいロゴをアップロードすると、古いロゴは自動的に無効になります</li>
                    <li>• ログイン画面とダッシュボードに即座に反映されます</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function logoUploader() {
    return {
        isDragging: false,
        fileName: '',
        previewUrl: '',

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.processFile(files[0]);
            }
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.processFile(file);
            }
        },

        processFile(file) {
            // ファイルタイプチェック
            if (!file.type.startsWith('image/')) {
                alert('画像ファイルを選択してください。');
                return;
            }

            // ファイルサイズチェック (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('ファイルサイズは2MB以下にしてください。');
                return;
            }

            this.fileName = file.name;

            // プレビュー表示
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);

            // FileInputにファイルを設定（ドラッグ&ドロップ時）
            if (this.$refs.fileInput.files.length === 0) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.fileInput.files = dataTransfer.files;
            }
        }
    }
}
</script>
@endsection