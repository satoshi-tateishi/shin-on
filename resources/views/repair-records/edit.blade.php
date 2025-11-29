@extends('layouts.master')

@section('title', '修理記録編集')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800">修理管理</a>
    > <a href="{{ route('repair-records.show', $repairRecord) }}" class="text-blue-600 hover:text-blue-800">詳細</a>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">修理記録編集</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('repair-records.show', $repairRecord) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                詳細
            </a>
            <button type="submit" form="edit-form"
                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                更新
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <form id="edit-form" action="{{ route('repair-records.update', $repairRecord) }}" method="POST" enctype="multipart/form-data" class="max-w-2xl">
            @csrf
            @method('PUT')

            <!-- 削除対象写真のインデックスを記録する隠しフィールド -->
            <input type="hidden" name="removed_photos" id="removed_photos" value="">

            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded-md mb-4 sm:mb-6">
                    <ul class="list-disc list-inside text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4 sm:space-y-6">
                <!-- 機材選択 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">機材選択</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        @php
                            $categories = $equipments->pluck('subcategory.category')->unique('id');
                            $selectedEquipment = $equipments->where('id', old('equipment_id', $repairRecord->equipment_id))->first();
                            $selectedCategoryName = $selectedEquipment ? $selectedEquipment->subcategory->category->name : '';
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- カテゴリ選択 -->
                            <div>
                                <label for="category_filter" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                                <select name="category_filter" id="category_filter"
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">カテゴリを選択してください</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->name }}" {{ $selectedCategoryName === $category->name ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- サブカテゴリ選択 -->
                            <div>
                                <label for="subcategory_filter" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">サブカテゴリ</label>
                                <select name="subcategory_filter" id="subcategory_filter" {{ !$selectedCategoryName ? 'disabled' : '' }}
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500">
                                    <option value="">{{ $selectedCategoryName ? 'サブカテゴリを選択してください' : 'カテゴリを先に選択してください' }}</option>
                                    @if($selectedCategoryName)
                                        @php
                                            $subcategories = $equipments->where('subcategory.category.name', $selectedCategoryName)->pluck('subcategory')->unique('id');
                                            $selectedSubcategoryName = $selectedEquipment ? $selectedEquipment->subcategory->name : '';
                                        @endphp
                                        @foreach($subcategories as $subcategory)
                                            <option value="{{ $subcategory->name }}" {{ $selectedSubcategoryName === $subcategory->name ? 'selected' : '' }}>{{ $subcategory->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- 機材選択 -->
                        <div>
                            <label for="equipment_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                機材 <span class="text-red-500">*</span>
                            </label>
                            <select name="equipment_id" id="equipment_id" required {{ !$selectedEquipment ? 'disabled' : '' }}
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 @error('equipment_id') border-red-300 @enderror">
                                <option value="">{{ $selectedEquipment ? '機材を選択してください' : 'サブカテゴリを先に選択してください' }}</option>
                                @if($selectedEquipment)
                                    @php
                                        $equipmentOptions = $equipments->where('subcategory.name', $selectedEquipment->subcategory->name);
                                    @endphp
                                    @foreach($equipmentOptions as $equipment)
                                        <option value="{{ $equipment->id }}" {{ old('equipment_id', $repairRecord->equipment_id) == $equipment->id ? 'selected' : '' }}>
                                            {{ $equipment->name }}{{ $equipment->company_number ? ' [ ' . $equipment->company_number . ' ]' : '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('equipment_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 報告情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">報告情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 担当者選択 -->
                            <div>
                                <label for="staff_user_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    担当者 <span class="text-red-500">*</span>
                                </label>
                                <select name="staff_user_id" id="staff_user_id" required
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('staff_user_id') border-red-300 @enderror">
                                    <option value="">担当者を選択してください</option>
                                    @foreach($staffUsers as $staffUser)
                                        <option value="{{ $staffUser->id }}"
                                                {{ old('staff_user_id', $repairRecord->staff_user_id) == $staffUser->id ? 'selected' : '' }}>
                                            {{ $staffUser->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('staff_user_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 故障発生日 -->
                            <div>
                                <label for="failure_occurred_at" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">故障発生日</label>
                                <input type="date" name="failure_occurred_at" id="failure_occurred_at"
                                       value="{{ old('failure_occurred_at', $repairRecord->failure_occurred_at?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('failure_occurred_at') border-red-300 @enderror">
                                @error('failure_occurred_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 公演名 -->
                            <div>
                                <label for="performance_name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">公演名</label>
                                <input type="text" name="performance_name" id="performance_name"
                                       value="{{ old('performance_name', $repairRecord->performance_name) }}"
                                       placeholder="公演名を入力"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('performance_name') border-red-300 @enderror">
                                @error('performance_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 使用場所 -->
                            <div>
                                <label for="usage_location" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">使用場所</label>
                                <input type="text" name="usage_location" id="usage_location"
                                       value="{{ old('usage_location', $repairRecord->usage_location) }}"
                                       placeholder="使用場所を入力"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('usage_location') border-red-300 @enderror">
                                @error('usage_location')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- ステータス -->
                        <div>
                            <label for="status" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                ステータス <span class="text-red-500">*</span>
                            </label>
                            <select name="status" id="status" required
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('status') border-red-300 @enderror">
                                <option value="reported" {{ old('status', $repairRecord->status) === 'reported' ? 'selected' : '' }}>報告済み</option>
                                <option value="in_progress" {{ old('status', $repairRecord->status) === 'in_progress' ? 'selected' : '' }}>修理中</option>
                                <option value="completed" {{ old('status', $repairRecord->status) === 'completed' ? 'selected' : '' }}>完了</option>
                                <option value="cancelled" {{ old('status', $repairRecord->status) === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 問題内容 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">問題内容</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <!-- 故障箇所写真 -->
                        <div>
                            <label for="photos" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">故障箇所写真</label>

                            @php
                                $photos = [];
                                if ($repairRecord->photos && is_array($repairRecord->photos)) {
                                    foreach ($repairRecord->photos as $item) {
                                        if (is_string($item) && !empty(trim($item))) {
                                            $photos[] = $item;
                                        }
                                    }
                                }
                            @endphp

                            @if(count($photos) > 0)
                                <div class="mb-3 sm:mb-4">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">現在の写真</label>
                                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                        @foreach($photos as $index => $photoPath)
                                            @if(is_string($photoPath) && !empty(trim($photoPath)))
                                                <div class="relative" data-photo-index="{{ $index }}">
                                                    <div class="w-full h-32 sm:h-40 bg-gray-100 rounded-lg border overflow-hidden flex items-center justify-center">
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

                            @if(!$photos || count($photos) < 2)
                                <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('photos') border-red-300 @enderror">
                                @error('photos')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                @error('photos.*')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">
                                    @php
                                        $currentCount = $photos ? count($photos) : 0;
                                        $remainingSlots = 2 - $currentCount;
                                    @endphp
                                    あと{{ $remainingSlots }}枚まで追加できます（最大10MB/枚）
                                </p>

                                <!-- 警告メッセージエリア -->
                                <div id="file-limit-warning" class="mt-2 p-2 sm:p-3 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded-md hidden">
                                    <p class="text-xs sm:text-sm">⚠️ 最大2枚までしかアップロードできません。追加したい場合は、既存の画像を削除してから選択してください。</p>
                                </div>

                                <!-- 画像プレビューエリア -->
                                <div id="image-preview" class="mt-2 sm:mt-3 grid grid-cols-2 gap-3 sm:gap-4 hidden">
                                    <!-- プレビュー画像がここに動的に追加される -->
                                </div>
                            @endif
                        </div>

                        <!-- 問題内容 -->
                        <div>
                            <label for="problem_description" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                問題内容 <span class="text-red-500">*</span>
                            </label>
                            <textarea name="problem_description" id="problem_description" rows="8" required
                                      placeholder="発生した問題・不具合の詳細を記載してください"
                                      class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('problem_description') border-red-300 @enderror">{{ old('problem_description', $repairRecord->problem_description) }}</textarea>
                            @error('problem_description')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 修理詳細 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">修理詳細情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label for="repair_company" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理業者</label>
                                <input type="text" name="repair_company" id="repair_company" value="{{ old('repair_company', $repairRecord->repair_company) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('repair_company') border-red-300 @enderror">
                                @error('repair_company')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="repaired_by" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">社内修理担当者</label>
                                <input type="text" name="repaired_by" id="repaired_by" value="{{ old('repaired_by', $repairRecord->repaired_by) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('repaired_by') border-red-300 @enderror">
                                @error('repaired_by')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label for="started_at" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理開始日</label>
                                <input type="date" name="started_at" id="started_at"
                                       value="{{ old('started_at', $repairRecord->started_at?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('started_at') border-red-300 @enderror">
                                @error('started_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="completed_at" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理完了日</label>
                                <input type="date" name="completed_at" id="completed_at"
                                       value="{{ old('completed_at', $repairRecord->completed_at?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('completed_at') border-red-300 @enderror">
                                @error('completed_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="repair_description" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理内容</label>
                            <textarea name="repair_description" id="repair_description" rows="8"
                                      class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('repair_description') border-red-300 @enderror">{{ old('repair_description', $repairRecord->repair_description) }}</textarea>
                            @error('repair_description')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label for="repair_cost" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理費用(税別)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">¥</span>
                                    </div>
                                    <input type="text" name="repair_cost_display" id="repair_cost_display"
                                           value="{{ old('repair_cost', $repairRecord->repair_cost ? number_format((int)$repairRecord->repair_cost) : '') }}"
                                           class="block w-full pl-7 text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('repair_cost') border-red-300 @enderror"
                                           placeholder="0">
                                    <input type="hidden" name="repair_cost" id="repair_cost" value="{{ old('repair_cost', $repairRecord->repair_cost ? (int)$repairRecord->repair_cost : '') }}">
                                </div>
                                @error('repair_cost')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="warranty_until" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">修理保証期限</label>
                                <input type="date" name="warranty_until" id="warranty_until"
                                       value="{{ old('warranty_until', $repairRecord->warranty_until?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('warranty_until') border-red-300 @enderror">
                                @error('warranty_until')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <textarea name="note" id="note" rows="8"
                                  placeholder="その他の特記事項があれば記載してください"
                                  class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('note') border-red-300 @enderror">{{ old('note', $repairRecord->note) }}</textarea>
                        @error('note')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/repair-record-form.js') }}"></script>
<script>
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

    // 段階的機材選択機能（編集時の初期値設定対応）
    const categorySelect = document.getElementById('category_filter');
    const subcategorySelect = document.getElementById('subcategory_filter');
    const equipmentSelect = document.getElementById('equipment_id');

    if (categorySelect && subcategorySelect && equipmentSelect) {
        // 初期化時に既存値があれば段階的選択を有効化
        @if($selectedEquipment)
            // 既に選択済みの機材がある場合は、カテゴリとサブカテゴリも有効化
            subcategorySelect.disabled = false;
            subcategorySelect.classList.remove('disabled:bg-gray-100', 'disabled:text-gray-500');
            equipmentSelect.disabled = false;
            equipmentSelect.classList.remove('disabled:bg-gray-100', 'disabled:text-gray-500');
        @endif
    }
});
</script>
@endpush
