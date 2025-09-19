@extends('layouts.app')

@section('title', '修理記録作成')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800">修理管理</a>
    > <span class="text-gray-800">修理報告</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">修理報告</h1>
        <p class="mt-1 text-sm text-gray-600">機材の修理・メンテナンス報告を作成</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('repair-records.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form action="{{ route('repair-records.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-md mb-4">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 機材選択 -->
                <div>
                    <label for="equipment_id" class="block text-sm font-medium text-gray-700">機材 <span class="text-red-500">*</span></label>
                    <select name="equipment_id" id="equipment_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('equipment_id') border-red-300 @enderror">
                        <option value="">機材を選択してください</option>
                        @php
                            $groupedEquipments = $equipments->groupBy('subcategory.category.name')->map(function($categoryGroup) {
                                return $categoryGroup->groupBy('subcategory.name');
                            });
                        @endphp
                        @foreach($groupedEquipments as $categoryName => $subcategoryGroups)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($subcategoryGroups as $subcategoryName => $equipmentGroup)
                                    <optgroup label="　{{ $subcategoryName }}">
                                        @foreach($equipmentGroup as $equipment)
                                            <option value="{{ $equipment->id }}"
                                                    {{ old('equipment_id') == $equipment->id ? 'selected' : '' }}>
                                                　　{{ $equipment->name }}{{ $equipment->company_number ? ' [ ' . $equipment->company_number . ' ]' : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('equipment_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 担当者選択 -->
                <div>
                    <label for="staff_user_id" class="block text-sm font-medium text-gray-700">担当者 <span class="text-red-500">*</span></label>
                    <select name="staff_user_id" id="staff_user_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('staff_user_id') border-red-300 @enderror">
                        <option value="">担当者を選択してください</option>
                        @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}"
                                    {{ old('staff_user_id') == $staffUser->id ? 'selected' : '' }}>
                                {{ $staffUser->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_user_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 故障発生日 -->
                <div>
                    <label for="failure_occurred_at" class="block text-sm font-medium text-gray-700">故障発生日</label>
                    <input type="date" name="failure_occurred_at" id="failure_occurred_at" value="{{ old('failure_occurred_at') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('failure_occurred_at') border-red-300 @enderror">
                    @error('failure_occurred_at')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 公演名 -->
                <div>
                    <label for="performance_name" class="block text-sm font-medium text-gray-700">公演名</label>
                    <input type="text" name="performance_name" id="performance_name" value="{{ old('performance_name') }}"
                           placeholder="公演名を入力"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('performance_name') border-red-300 @enderror">
                    @error('performance_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 使用場所 -->
                <div>
                    <label for="usage_location" class="block text-sm font-medium text-gray-700">使用場所</label>
                    <input type="text" name="usage_location" id="usage_location" value="{{ old('usage_location') }}"
                           placeholder="使用場所を入力"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('usage_location') border-red-300 @enderror">
                    @error('usage_location')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 故障箇所写真 -->
                <div>
                    <label for="photos" class="block text-sm font-medium text-gray-700">故障箇所写真</label>
                    <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('photos') border-red-300 @enderror">
                    @error('photos')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">最大2枚までの写真をアップロードできます（最大10MB/枚）</p>

                    <!-- 警告メッセージエリア -->
                    <div id="file-limit-warning" class="mt-2 p-3 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded-md hidden">
                        <p class="text-sm">⚠️ 最大2枚までしかアップロードできません。追加したい場合は、既存の画像を削除してから選択してください。</p>
                    </div>

                    <!-- 画像プレビューエリア -->
                    <div id="image-preview" class="mt-3 grid grid-cols-2 md:grid-cols-2 gap-4 hidden">
                        <!-- プレビュー画像がここに動的に追加される -->
                    </div>
                </div>

                <!-- 問題内容 -->
                <div>
                    <label for="problem_description" class="block text-sm font-medium text-gray-700">問題内容 <span class="text-red-500">*</span></label>
                    <textarea name="problem_description" id="problem_description" rows="4" required
                              placeholder="発生した問題・不具合の詳細を記載してください"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('problem_description') border-red-300 @enderror">{{ old('problem_description') }}</textarea>
                    @error('problem_description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>


                <!-- 備考 -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="3"
                              placeholder="その他の特記事項があれば記載してください"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 送信ボタン -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route('repair-records.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                        </svg>
                        修理報告を作成
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const photosInput = document.getElementById('photos');
    const previewContainer = document.getElementById('image-preview');
    const warningContainer = document.getElementById('file-limit-warning');
    let allFiles = []; // 累積でファイルを保存する配列
    const MAX_FILES = 2; // 最大ファイル数

    photosInput.addEventListener('change', function(e) {
        console.log('ファイル選択イベント発生'); // デバッグ用

        const newFiles = Array.from(e.target.files);
        console.log('新しく選択されたファイル数:', newFiles.length); // デバッグ用

        if (newFiles.length === 0) {
            return;
        }

        // 警告を非表示にする
        warningContainer.classList.add('hidden');

        // 新しいファイルを既存のファイル配列に追加（制限内のみ）
        newFiles.forEach(file => {
            if (file.type.startsWith('image/')) {
                if (allFiles.length < MAX_FILES) {
                    allFiles.push(file);
                } else {
                    // 制限に達した場合は警告を表示
                    warningContainer.classList.remove('hidden');
                    console.log('ファイル数制限に達しました'); // デバッグ用
                }
            }
        });

        console.log('総ファイル数:', allFiles.length); // デバッグ用

        // 全てのプレビューを再生成
        updatePreviews();
        updateFileInput();
    });

    function updatePreviews() {
        // プレビューエリアをクリア
        previewContainer.innerHTML = '';

        if (allFiles.length === 0) {
            previewContainer.classList.add('hidden');
            return;
        }

        // プレビューエリアを表示
        previewContainer.classList.remove('hidden');

        allFiles.forEach((file, index) => {
            console.log('ファイル処理中:', file.name, 'タイプ:', file.type); // デバッグ用

            const reader = new FileReader();

            reader.onload = function(e) {
                console.log('ファイル読み込み完了:', file.name); // デバッグ用

                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group mb-2';

                previewDiv.innerHTML = `
                    <div class="w-full h-40 bg-gray-100 rounded-lg border border-gray-300 overflow-hidden flex items-center justify-center">
                        <img src="${e.target.result}"
                             alt="プレビュー ${index + 1}"
                             class="max-w-full max-h-full object-contain"
                             onload="console.log('画像表示成功: ${file.name}')"
                             onerror="console.error('画像表示エラー: ${file.name}')">
                    </div>
                    <div class="absolute top-2 right-2">
                        <button type="button"
                                class="remove-image bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs"
                                data-index="${index}"
                                title="画像を削除">
                            ×
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                `;

                previewContainer.appendChild(previewDiv);
            };

            reader.onerror = function() {
                console.error('ファイル読み込みエラー:', file.name); // デバッグ用
            };

            reader.readAsDataURL(file);
        });
    }

    function updateFileInput() {
        // DataTransferを使用してFileInputを更新
        const dt = new DataTransfer();
        allFiles.forEach(file => {
            dt.items.add(file);
        });
        photosInput.files = dt.files;
    }

    // 画像削除機能
    previewContainer.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-image');
        if (removeBtn) {
            const index = parseInt(removeBtn.dataset.index);
            console.log('画像削除:', index); // デバッグ用

            // 配列から該当ファイルを削除
            allFiles.splice(index, 1);

            // 警告を非表示にする（削除によって制限以下になった場合）
            if (allFiles.length < MAX_FILES) {
                warningContainer.classList.add('hidden');
            }

            // プレビューとFileInputを更新
            updatePreviews();
            updateFileInput();
        }
    });
});
</script>
@endpush