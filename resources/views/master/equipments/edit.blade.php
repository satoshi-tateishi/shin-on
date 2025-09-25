@extends('layouts.master')

@section('title', '機材編集')

@section('breadcrumb')
    > <a href="{{ route('master.equipments.index') }}" class="text-blue-600 hover:text-blue-800">機材マスタ 一覧</a>
    > <span class="text-gray-800">{{ $equipment->name }}@if($equipment->company_number) <span class="inline-block px-2 py-1 font-mono bg-gray-100 border border-gray-300 rounded">{{ $equipment->company_number }}</span>@endif</span>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $equipment->name }}@if($equipment->company_number) <span class="inline-block px-2 py-1 text-3XL font-mono bg-gray-100 border border-gray-300 rounded">{{ $equipment->company_number }}</span>@endif 編集</h1>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('master.equipments.index') }}"
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
        <form method="POST" action="{{ route('master.equipments.update', $equipment) }}" class="max-w-2xl" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- サブカテゴリ -->
                <div>
                    <label for="subcategory_id" class="block text-sm font-medium text-gray-700 mb-2">
                        サブカテゴリ <span class="text-red-500">*</span>
                    </label>
                    <select name="subcategory_id" id="subcategory_id" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                   @error('subcategory_id') border-red-300 @enderror">
                        <option value="">選択してください</option>
                        @foreach($categories as $category)
                            <optgroup label="{{ $category->name }}">
                                @foreach($category->subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}" {{ old('subcategory_id', $equipment->subcategory_id) == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('subcategory_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- メーカー名 -->
                <div>
                    <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">
                        メーカー名
                    </label>
                    <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer', $equipment->manufacturer) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('manufacturer') border-red-300 @enderror">
                    @error('manufacturer')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 機材名 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        機材名 <span class="text-red-500">*</span>
                    </label>
                    <textarea name="name" id="name" rows="2" required
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                     @error('name') border-red-300 @enderror">{{ old('name', $equipment->name) }}</textarea>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 新音番号 -->
                <div>
                    <label for="company_number" class="block text-sm font-medium text-gray-700 mb-2">
                        新音番号
                    </label>
                    <input type="text" name="company_number" id="company_number" value="{{ old('company_number', $equipment->company_number) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('company_number') border-red-300 @enderror">
                    @error('company_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 管理方式 -->
                <div>
                    <label for="management_type" class="block text-sm font-medium text-gray-700 mb-2">
                        管理方式 <span class="text-red-500">*</span>
                    </label>
                    <select name="management_type" id="management_type" required onchange="toggleQuantityField()"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                   @error('management_type') border-red-300 @enderror">
                        <option value="individual" {{ old('management_type', $equipment->management_type) == 'individual' ? 'selected' : '' }}>個体管理</option>
                        <option value="quantity" {{ old('management_type', $equipment->management_type) == 'quantity' ? 'selected' : '' }}>数量管理</option>
                    </select>
                    @error('management_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 在庫数量 -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        在庫数量
                    </label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $equipment->quantity) }}" min="1" step="1"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('quantity') border-red-300 @enderror">
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 単位 -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                        単位
                    </label>
                    <select name="unit" id="unit"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                   @error('unit') border-red-300 @enderror">
                        <option value="台" {{ old('unit', $equipment->unit) == '台' ? 'selected' : '' }}>台</option>
                        <option value="個" {{ old('unit', $equipment->unit) == '個' ? 'selected' : '' }}>個</option>
                        <option value="本" {{ old('unit', $equipment->unit) == '本' ? 'selected' : '' }}>本</option>
                        <option value="箱" {{ old('unit', $equipment->unit) == '箱' ? 'selected' : '' }}>箱</option>
                        <option value="ケース" {{ old('unit', $equipment->unit) == 'ケース' ? 'selected' : '' }}>ケース</option>
                        <option value="ラック" {{ old('unit', $equipment->unit) == 'ラック' ? 'selected' : '' }}>ラック</option>
                        <option value="セット" {{ old('unit', $equipment->unit) == 'セット' ? 'selected' : '' }}>セット</option>
                    </select>
                    @error('unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 型番 -->
                <div>
                    <label for="model_number" class="block text-sm font-medium text-gray-700 mb-2">
                        型番
                    </label>
                    <input type="text" name="model_number" id="model_number" value="{{ old('model_number', $equipment->model_number) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('model_number') border-red-300 @enderror">
                    @error('model_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- シリアル番号 -->
                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-2">
                        シリアル番号
                    </label>
                    <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $equipment->serial_number) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('serial_number') border-red-300 @enderror">
                    @error('serial_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 購入先 -->
                <div>
                    <label for="supplier" class="block text-sm font-medium text-gray-700 mb-2">
                        購入先
                    </label>
                    <input type="text" name="supplier" id="supplier" value="{{ old('supplier', $equipment->supplier) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('supplier') border-red-300 @enderror">
                    @error('supplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 購入日 -->
                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">
                        購入日
                    </label>
                    <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $equipment->purchase_date?->format('Y-m-d')) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('purchase_date') border-red-300 @enderror">
                    @error('purchase_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 保証期限 -->
                <div>
                    <label for="warranty_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                        保証期限
                    </label>
                    <input type="date" name="warranty_expiry" id="warranty_expiry" value="{{ old('warranty_expiry', $equipment->warranty_expiry?->format('Y-m-d')) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('warranty_expiry') border-red-300 @enderror">
                    @error('warranty_expiry')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 価格 -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        価格（円）
                    </label>
                    <input type="number" name="price" id="price" value="{{ old('price', $equipment->price) }}" min="0" step="0.01"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('price') border-red-300 @enderror">
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 状態 -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        状態 <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                   @error('status') border-red-300 @enderror">
                        <option value="available" {{ old('status', $equipment->status) == 'available' ? 'selected' : '' }}>利用可能</option>
                        <option value="in_use" {{ old('status', $equipment->status) == 'in_use' ? 'selected' : '' }}>使用中</option>
                        <option value="repair" {{ old('status', $equipment->status) == 'repair' ? 'selected' : '' }}>修理中</option>
                        <option value="maintenance" {{ old('status', $equipment->status) == 'maintenance' ? 'selected' : '' }}>メンテナンス中</option>
                        <option value="retired" {{ old('status', $equipment->status) == 'retired' ? 'selected' : '' }}>廃棄</option>
                        <option value="lost" {{ old('status', $equipment->status) == 'lost' ? 'selected' : '' }}>紛失</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 場所 -->
                <div>
                    <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                        場所
                    </label>
                    <select name="location_id" id="location_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                   @error('location_id') border-red-300 @enderror">
                        <option value="">選択してください</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ old('location_id', $equipment->location_id) == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 廃棄フラグ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        廃棄フラグ
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_discard" id="is_discard" value="1"
                               {{ old('is_discard', $equipment->is_discard) ? 'checked' : '' }}
                               onchange="toggleDiscardDate()"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_discard" class="ml-2 block text-sm text-gray-900">
                            廃棄
                        </label>
                    </div>
                    @error('is_discard')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 廃棄日 -->
                <div>
                    <label for="discard_at" class="block text-sm font-medium text-gray-700 mb-2">
                        廃棄日
                    </label>
                    <input type="date" name="discard_at" id="discard_at" value="{{ old('discard_at', $equipment->discard_at?->format('Y-m-d')) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('discard_at') border-red-300 @enderror">
                    @error('discard_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- スケジュール表示フラグ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        スケジュール表示
                    </label>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_schedule_visible" id="is_schedule_visible" value="1"
                               {{ old('is_schedule_visible', $equipment->is_schedule_visible) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_schedule_visible" class="ml-2 block text-sm text-gray-900">
                            スケジュール表に表示する
                        </label>
                    </div>
                    @error('is_schedule_visible')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 備考 -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        備考
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                     @error('notes') border-red-300 @enderror">{{ old('notes', $equipment->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ボタン -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('master.equipments.index') }}"
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
@endsection

@push('scripts')
<script>
    function toggleQuantityField() {
        const managementType = document.getElementById('management_type').value;
        const quantityField = document.getElementById('quantity');
        const companyNumberField = document.getElementById('company_number');

        if (managementType === 'individual') {
            // 個体管理：在庫数量1固定、新音番号入力可能
            quantityField.value = 1;
            quantityField.disabled = true;
            quantityField.classList.add('bg-gray-100', 'cursor-not-allowed');

            companyNumberField.disabled = false;
            companyNumberField.classList.remove('bg-gray-100', 'cursor-not-allowed');
        } else {
            // 数量管理：在庫数量入力可能、新音番号削除&無効化
            quantityField.disabled = false;
            quantityField.classList.remove('bg-gray-100', 'cursor-not-allowed');

            companyNumberField.value = '';
            companyNumberField.disabled = true;
            companyNumberField.classList.add('bg-gray-100', 'cursor-not-allowed');
        }
    }

    function toggleDiscardDate() {
        const discardFlag = document.getElementById('is_discard');
        const discardDate = document.getElementById('discard_at');

        if (discardFlag && discardDate) {
            if (discardFlag.checked) {
                discardDate.disabled = false;
                discardDate.classList.remove('bg-gray-100', 'cursor-not-allowed');
            } else {
                discardDate.disabled = true;
                discardDate.value = '';
                discardDate.classList.add('bg-gray-100', 'cursor-not-allowed');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleQuantityField();
        toggleDiscardDate();
    });
</script>
@endpush
