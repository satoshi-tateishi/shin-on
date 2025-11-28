@extends('layouts.app')

@section('title', '会社設定')

@section('content')
<div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">会社設定</h1>
        </div>

        <div class="p-4 sm:p-6">
            <!-- 会社基本情報フォーム -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">会社基本情報</h2>
                <form method="POST" action="{{ route('admin.company-info.store-company-info') }}" class="space-y-4 max-w-md">
                    @csrf
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">会社名 <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" id="company_name"
                               value="{{ old('company_name', $companyInfo->company_name ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <div class="max-w-[200px]">
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">郵便番号</label>
                        <input type="text" name="postal_code" id="postal_code"
                               value="{{ old('postal_code', $companyInfo->postal_code ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="000-0000">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">住所</label>
                        <input type="text" name="address" id="address"
                               value="{{ old('address', $companyInfo->address ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="max-w-[200px]">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $companyInfo->phone ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
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
            <div class="mb-8 pt-8 border-t border-b border-gray-200 pb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">修理担当者</h2>
                <form method="POST" action="{{ route('admin.company-info.store-company-info') }}" class="space-y-4 max-w-md">
                    @csrf
                    <!-- 会社基本情報を隠しフィールドとして保持 -->
                    <input type="hidden" name="company_name" value="{{ $companyInfo->company_name ?? '' }}">
                    <input type="hidden" name="postal_code" value="{{ $companyInfo->postal_code ?? '' }}">
                    <input type="hidden" name="address" value="{{ $companyInfo->address ?? '' }}">
                    <input type="hidden" name="phone" value="{{ $companyInfo->phone ?? '' }}">

                    <div class="max-w-[200px]">
                        <label for="repair_contact_person" class="block text-sm font-medium text-gray-700 mb-1">修理担当者名</label>
                        <input type="text" name="repair_contact_person" id="repair_contact_person"
                               value="{{ old('repair_contact_person', $companyInfo->repair_contact_person ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="repair_contact_email" class="block text-sm font-medium text-gray-700 mb-1">修理担当者メールアドレス</label>
                        <input type="email" name="repair_contact_email" id="repair_contact_email"
                               value="{{ old('repair_contact_email', $companyInfo->repair_contact_email ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
                <h2 class="text-lg font-semibold text-gray-900 mb-4">会社ロゴ</h2>
                @if($logo)
                    <div class="bg-gray-50 rounded-lg p-4 sm:p-6 mb-4">
                        <div class="flex items-center space-x-4">
                            <img src="{{ $logo->url }}"
                                 alt="会社ロゴ"
                                 class="h-14 sm:h-16 w-auto object-contain border border-gray-200 rounded bg-white p-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 text-sm sm:text-base truncate">{{ $logo->file_name }}</p>
                                <p class="text-xs sm:text-sm text-gray-600">
                                    {{ number_format($logo->file_size / 1024, 1) }} KB
                                    • {{ strtoupper(pathinfo($logo->file_name, PATHINFO_EXTENSION)) }}
                                </p>
                                <p class="text-xs text-gray-500">
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
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-600">ロゴが設定されていません</p>
                    </div>
                @endif
            </div>

            <!-- ロゴアップロードフォーム -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $logo ? '新しいロゴをアップロード' : 'ロゴをアップロード' }}
                </h2>
                <form method="POST" action="{{ route('admin.company-info.store') }}" enctype="multipart/form-data" id="logoForm">
                    @csrf
                    <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 transition-colors duration-200 hover:border-gray-400">
                        <div class="text-center">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="logo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>ファイルを選択</span>
                                    <input id="logo" name="logo" type="file" class="sr-only" accept="image/*" required>
                                </label>
                                <p class="pl-1">またはドラッグ&ドロップ</p>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                PNG, JPG, GIF, SVG（最大2MB）
                            </p>
                            <div id="fileInfo" class="mt-4 hidden">
                                <p class="text-sm text-gray-700">選択されたファイル: <span id="fileName"></span></p>
                                <div id="previewContainer" class="mt-2">
                                    <img id="preview" class="h-24 w-auto mx-auto object-contain border border-gray-200 rounded bg-white p-2" style="display: none;">
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
            <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-medium text-blue-900 mb-2">📝 注意事項</h3>
                <ul class="text-sm text-blue-800 space-y-1">
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
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('logo');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const preview = document.getElementById('preview');

    // ドラッグ&ドロップのイベントハンドラー
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // ドラッグオーバー時のスタイル変更
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    }

    // ファイルドロップ時の処理
    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            handleFile(files[0]);
        }
    }

    // ファイル選択時の処理
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    });

    function handleFile(file) {
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

        // ファイル情報を表示
        fileName.textContent = file.name;
        fileInfo.classList.remove('hidden');

        // プレビュー表示
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);

        // FileInputにファイルを設定
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }

    // ドロップゾーンクリック時にファイル選択ダイアログを開く
    dropZone.addEventListener('click', function(e) {
        if (e.target !== fileInput && !e.target.closest('label')) {
            fileInput.click();
        }
    });
});
</script>
@endsection