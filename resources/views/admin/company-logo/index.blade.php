@extends('layouts.app')

@section('title', '会社ロゴ管理')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900">会社ロゴ管理</h1>
            <p class="text-gray-600 text-sm mt-1">ログイン画面とダッシュボードに表示される会社ロゴを管理します</p>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- 現在のロゴ表示 -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">現在のロゴ</h2>
                @if($logo)
                    <div class="bg-gray-50 rounded-lg p-6 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <img src="{{ $logo->url }}"
                                 alt="会社ロゴ"
                                 class="h-16 w-auto object-contain border border-gray-200 rounded bg-white p-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $logo->file_name }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ number_format($logo->file_size / 1024, 1) }} KB
                                    • {{ strtoupper(pathinfo($logo->file_name, PATHINFO_EXTENSION)) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    登録日時: {{ $logo->created_at->format('Y年n月j日 H:i') }}
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.company-logo.destroy') }}"
                              class="inline"
                              onsubmit="return confirm('現在のロゴを削除してもよろしいですか？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
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
                <form method="POST" action="{{ route('admin.company-logo.store') }}" enctype="multipart/form-data" id="logoForm">
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