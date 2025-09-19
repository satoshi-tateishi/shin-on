@extends('layouts.app')

@section('title', '修理記録編集')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800">修理管理</a>
    > <a href="{{ route('repair-records.show', $repairRecord) }}" class="text-blue-600 hover:text-blue-800">修理記録詳細</a>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">修理記録編集</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $repairRecord->equipment->name }} の修理記録を編集</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('repair-records.show', $repairRecord) }}"
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
            <form action="{{ route('repair-records.update', $repairRecord) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- 削除対象写真のインデックスを記録する隠しフィールド -->
                <input type="hidden" name="removed_photos" id="removed_photos" value="">

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
                                                    {{ (old('equipment_id', $repairRecord->equipment_id) == $equipment->id) ? 'selected' : '' }}>
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

                <!-- 担当者 -->
                <div>
                    <label for="staff_user_id" class="block text-sm font-medium text-gray-700">担当者 <span class="text-red-500">*</span></label>
                    <select name="staff_user_id" id="staff_user_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('staff_user_id') border-red-300 @enderror">
                        <option value="">担当者を選択してください</option>
                        @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}"
                                    {{ old('staff_user_id', $repairRecord->staff_user_id) == $staffUser->id ? 'selected' : '' }}>
                                {{ $staffUser->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_user_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ステータス -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">ステータス <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('status') border-red-300 @enderror">
                        <option value="reported" {{ old('status', $repairRecord->status) === 'reported' ? 'selected' : '' }}>報告済み</option>
                        <option value="in_progress" {{ old('status', $repairRecord->status) === 'in_progress' ? 'selected' : '' }}>修理中</option>
                        <option value="completed" {{ old('status', $repairRecord->status) === 'completed' ? 'selected' : '' }}>完了</option>
                        <option value="cancelled" {{ old('status', $repairRecord->status) === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 故障発生日 -->
                <div>
                    <label for="failure_occurred_at" class="block text-sm font-medium text-gray-700">故障発生日</label>
                    <input type="date" name="failure_occurred_at" id="failure_occurred_at"
                           value="{{ old('failure_occurred_at', $repairRecord->failure_occurred_at?->format('Y-m-d')) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('failure_occurred_at') border-red-300 @enderror">
                    @error('failure_occurred_at')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 公演名 -->
                <div>
                    <label for="performance_name" class="block text-sm font-medium text-gray-700">公演名</label>
                    <input type="text" name="performance_name" id="performance_name"
                           value="{{ old('performance_name', $repairRecord->performance_name) }}"
                           placeholder="公演名を入力"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('performance_name') border-red-300 @enderror">
                    @error('performance_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 使用場所 -->
                <div>
                    <label for="usage_location" class="block text-sm font-medium text-gray-700">使用場所</label>
                    <input type="text" name="usage_location" id="usage_location"
                           value="{{ old('usage_location', $repairRecord->usage_location) }}"
                           placeholder="使用場所を入力"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('usage_location') border-red-300 @enderror">
                    @error('usage_location')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>


                <!-- 問題内容 -->
                <div>
                    <label for="problem_description" class="block text-sm font-medium text-gray-700">問題内容 <span class="text-red-500">*</span></label>
                    <textarea name="problem_description" id="problem_description" rows="4" required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('problem_description') border-red-300 @enderror">{{ old('problem_description', $repairRecord->problem_description) }}</textarea>
                    @error('problem_description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 故障箇所写真 -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">故障箇所写真</h3>


                    <!-- 既存の写真表示 -->
                    @php
                        $photos = [];
                        if ($repairRecord->photos && is_array($repairRecord->photos)) {
                            // 直接配列として処理（Eloquentのcastで配列になっている）
                            foreach ($repairRecord->photos as $item) {
                                if (is_string($item) && !empty(trim($item))) {
                                    $photos[] = $item;
                                }
                            }
                        }
                    @endphp

                    @if(count($photos) > 0)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">現在の写真</label>
                            <div class="grid grid-cols-2 gap-4">
                                @foreach($photos as $index => $photoPath)
                                    @if(is_string($photoPath) && !empty(trim($photoPath)))
                                        <div class="relative" data-photo-index="{{ $index }}">
                                            <div class="w-full h-40 bg-gray-100 rounded-lg border overflow-hidden flex items-center justify-center">
                                                <img src="{{ Storage::disk('public')->url($photoPath) }}"
                                                     alt="故障箇所写真 {{ $index + 1 }}"
                                                     class="max-w-full max-h-full object-contain">
                                            </div>
                                            <div class="absolute top-2 right-2">
                                                <button type="button"
                                                        class="bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs"
                                                        onclick="removeExistingPhoto({{ $index }})"
                                                        title="写真を削除">
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 新しい写真のアップロード -->
                    @if(!$photos || count($photos) < 2)
                        <div>
                            <label for="photos" class="block text-sm font-medium text-gray-700">写真を追加</label>
                            <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('photos') border-red-300 @enderror">
                            @error('photos')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                @php
                                    $currentCount = $photos ? count($photos) : 0;
                                    $remainingSlots = 2 - $currentCount;
                                @endphp
                                あと{{ $remainingSlots }}枚まで追加できます（最大10MB/枚）
                            </p>

                            <!-- 警告メッセージエリア -->
                            <div id="file-limit-warning" class="mt-2 p-3 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded-md hidden">
                                <p class="text-sm">⚠️ 最大2枚までしかアップロードできません。追加したい場合は、既存の画像を削除してから選択してください。</p>
                            </div>

                            <!-- 新しい写真のプレビューエリア -->
                            <div id="new-photo-preview" class="mt-3 grid grid-cols-2 gap-4 hidden">
                                <!-- プレビュー画像がここに動的に追加される -->
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 修理詳細 -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">修理詳細情報</h3>
                    <div class="space-y-6">

                    <div>
                        <label for="repair_company" class="block text-sm font-medium text-gray-700">修理業者</label>
                        <input type="text" name="repair_company" id="repair_company" value="{{ old('repair_company', $repairRecord->repair_company) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('repair_company') border-red-300 @enderror">
                        @error('repair_company')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="repaired_by" class="block text-sm font-medium text-gray-700">社内修理担当者</label>
                        <input type="text" name="repaired_by" id="repaired_by" value="{{ old('repaired_by', $repairRecord->repaired_by) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('repaired_by') border-red-300 @enderror">
                        @error('repaired_by')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="started_at" class="block text-sm font-medium text-gray-700">修理開始日</label>
                        <input type="date" name="started_at" id="started_at"
                               value="{{ old('started_at', $repairRecord->started_at?->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('started_at') border-red-300 @enderror">
                        @error('started_at')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="completed_at" class="block text-sm font-medium text-gray-700">修理完了日</label>
                        <input type="date" name="completed_at" id="completed_at"
                               value="{{ old('completed_at', $repairRecord->completed_at?->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('completed_at') border-red-300 @enderror">
                        @error('completed_at')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="repair_description" class="block text-sm font-medium text-gray-700">修理内容</label>
                        <textarea name="repair_description" id="repair_description" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('repair_description') border-red-300 @enderror">{{ old('repair_description', $repairRecord->repair_description) }}</textarea>
                        @error('repair_description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <div>
                        <label for="repair_cost" class="block text-sm font-medium text-gray-700">修理費用(税別)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">¥</span>
                            </div>
                            <input type="text" name="repair_cost_display" id="repair_cost_display"
                                   value="{{ old('repair_cost', $repairRecord->repair_cost ? number_format((int)$repairRecord->repair_cost) : '') }}"
                                   class="block w-full pl-7 border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('repair_cost') border-red-300 @enderror"
                                   placeholder="0">
                            <input type="hidden" name="repair_cost" id="repair_cost" value="{{ old('repair_cost', $repairRecord->repair_cost ? (int)$repairRecord->repair_cost : '') }}">
                        </div>
                        @error('repair_cost')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="warranty_until" class="block text-sm font-medium text-gray-700">修理保証期限</label>
                        <input type="date" name="warranty_until" id="warranty_until"
                               value="{{ old('warranty_until', $repairRecord->warranty_until?->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('warranty_until') border-red-300 @enderror">
                        @error('warranty_until')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('note') border-red-300 @enderror">{{ old('note', $repairRecord->note) }}</textarea>
                    @error('note')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 送信ボタン -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route('repair-records.show', $repairRecord) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        修理記録を更新
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
    const previewContainer = document.getElementById('new-photo-preview');
    const warningContainer = document.getElementById('file-limit-warning');
    let newFiles = []; // 新規追加ファイルを管理する配列

    // 既存の写真数を取得
    const existingPhotosCount = {{ $photos ? count($photos) : 0 }};
    const MAX_FILES = 2;

    if (photosInput && previewContainer) {
        photosInput.addEventListener('change', function(e) {
            const selectedFiles = Array.from(e.target.files);

            if (selectedFiles.length === 0) {
                return;
            }

            // 警告を非表示にする
            warningContainer.classList.add('hidden');

            // 新しいファイルを既存のファイル配列に追加（制限内のみ）
            selectedFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const totalCount = existingPhotosCount + newFiles.length;
                    if (totalCount < MAX_FILES) {
                        newFiles.push(file);
                    } else {
                        // 制限に達した場合は警告を表示
                        warningContainer.classList.remove('hidden');
                    }
                }
            });

            // プレビューを更新
            updateNewPhotoPreviews();
            updateFileInput();
        });
    }

    function updateNewPhotoPreviews() {
        // プレビューエリアをクリア
        previewContainer.innerHTML = '';

        if (newFiles.length === 0) {
            previewContainer.classList.add('hidden');
            return;
        }

        // プレビューエリアを表示
        previewContainer.classList.remove('hidden');

        newFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group mb-2';

                previewDiv.innerHTML = `
                    <div class="w-full h-40 bg-gray-100 rounded-lg border border-gray-300 overflow-hidden flex items-center justify-center">
                        <img src="${e.target.result}"
                             alt="プレビュー ${index + 1}"
                             class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="absolute top-2 right-2">
                        <button type="button"
                                class="remove-new-image bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs"
                                data-index="${index}"
                                title="画像を削除">
                            ×
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                `;

                previewContainer.appendChild(previewDiv);
            };

            reader.readAsDataURL(file);
        });
    }

    function updateFileInput() {
        // DataTransferを使用してFileInputを更新
        const dt = new DataTransfer();
        newFiles.forEach(file => {
            dt.items.add(file);
        });
        if (photosInput) {
            photosInput.files = dt.files;
        }
    }

    // 新規画像削除機能
    if (previewContainer) {
        previewContainer.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-new-image');
            if (removeBtn) {
                const index = parseInt(removeBtn.dataset.index);

                // 配列から該当ファイルを削除
                newFiles.splice(index, 1);

                // 警告を非表示にする（削除によって制限以下になった場合）
                const totalCount = existingPhotosCount + newFiles.length;
                if (totalCount < MAX_FILES) {
                    warningContainer.classList.add('hidden');
                }

                // プレビューとFileInputを更新
                updateNewPhotoPreviews();
                updateFileInput();
            }
        });
    }
});

// 既存写真の削除機能
function removeExistingPhoto(index) {
    if (!confirm('この写真を削除しますか？')) {
        return;
    }

    // 既存の隠しフィールドを取得
    let removedPhotosInput = document.getElementById('removed_photos');
    if (!removedPhotosInput) {
        return;
    }

    // 削除する写真のインデックスを追加
    let removedIndexes = removedPhotosInput.value ? removedPhotosInput.value.split(',') : [];
    if (!removedIndexes.includes(index.toString())) {
        removedIndexes.push(index.toString());
        removedPhotosInput.value = removedIndexes.join(',');
    }

    // 該当の写真要素を非表示にする
    const photoElement = document.querySelector(`[data-photo-index="${index}"]`);
    if (photoElement) {
        photoElement.style.display = 'none';
    }
}

// 修理費用のカンマ区切り処理
document.addEventListener('DOMContentLoaded', function() {
    const repairCostDisplay = document.getElementById('repair_cost_display');
    const repairCostHidden = document.getElementById('repair_cost');

    if (repairCostDisplay && repairCostHidden) {
        // 入力時のカンマ区切り処理
        repairCostDisplay.addEventListener('input', function(e) {
            // 数字以外を除去
            let value = e.target.value.replace(/[^\d]/g, '');

            // 空の場合の処理
            if (value === '') {
                repairCostHidden.value = '';
                e.target.value = '';
                return;
            }

            // 数値に変換
            let numValue = parseInt(value);

            // カンマ区切りでフォーマット
            e.target.value = numValue.toLocaleString();

            // hiddenフィールドに数値のみ設定
            repairCostHidden.value = numValue;
        });

        // フォーカス時の処理（カンマを一時的に除去）
        repairCostDisplay.addEventListener('focus', function(e) {
            if (e.target.value) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value) {
                    e.target.value = value;
                }
            }
        });

        // フォーカスアウト時の処理（カンマを復活）
        repairCostDisplay.addEventListener('blur', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                let numValue = parseInt(value);
                e.target.value = numValue.toLocaleString();
                repairCostHidden.value = numValue;
            }
        });
    }
});
</script>
@endpush
