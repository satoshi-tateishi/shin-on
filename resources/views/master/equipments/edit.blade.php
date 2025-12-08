@extends('layouts.master')

@section('title', '機材編集')

@section('breadcrumb')
    > <a href="{{ route('master.equipments.index') }}" class="text-blue-600 hover:text-blue-800">機材マスタ 一覧</a>
    > <span class="text-gray-800">{{ str_replace(["\r\n", "\r", "\n"], ' ', $equipment->name) }}@if($equipment->company_number) <span class="inline-block px-2 py-1 font-mono bg-gray-100 border border-gray-300 rounded">{{ $equipment->company_number }}</span>@endif</span>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 whitespace-pre-line mb-2">{{ $equipment->name }}@if($equipment->company_number) <span class="inline-block px-1 sm:px-2 py-0.5 sm:py-1 text-base sm:text-xl font-mono bg-gray-100 border border-gray-300 rounded">{{ $equipment->company_number }}</span>@endif</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.equipments.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
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
        <form id="edit-form" method="POST" action="{{ route('master.equipments.update', $equipment) }}" class="max-w-2xl" autocomplete="off"
              x-data="{
                  managementType: '{{ old('management_type', $equipment->management_type) }}',
                  isDiscard: {{ old('is_discard', $equipment->is_discard) ? 'true' : 'false' }}
              }">
            @csrf
            @method('PUT')

            <div class="space-y-4 sm:space-y-6">
                <!-- 基本情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- サブカテゴリ -->
                            <div class="sm:col-span-2">
                                <label for="subcategory_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    サブカテゴリ <span class="text-red-500">*</span>
                                </label>
                                <select name="subcategory_id" id="subcategory_id" required
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
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
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- メーカー名 -->
                            <div>
                                <label for="manufacturer" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    メーカー名
                                </label>
                                <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer', $equipment->manufacturer) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('manufacturer') border-red-300 @enderror">
                                @error('manufacturer')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 場所 -->
                            <div>
                                <label for="location_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    場所
                                </label>
                                <select name="location_id" id="location_id"
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('location_id') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id', $equipment->location_id) == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 機材名 -->
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    機材名 <span class="text-red-500">*</span>
                                </label>
                                <textarea name="name" id="name" rows="2" required
                                          class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                 @error('name') border-red-300 @enderror">{{ old('name', $equipment->name) }}</textarea>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 新音番号 -->
                            <div>
                                <label for="company_number" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    新音番号
                                </label>
                                <input type="text" name="company_number" id="company_number"
                                       :value="managementType === 'quantity' ? '' : '{{ old('company_number', $equipment->company_number) }}'"
                                       :disabled="managementType === 'quantity'"
                                       :class="managementType === 'quantity' ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('company_number') border-red-300 @enderror">
                                @error('company_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 状態 -->
                            <div>
                                <label for="status" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    状態 <span class="text-red-500">*</span>
                                </label>
                                <select name="status" id="status" required
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('status') border-red-300 @enderror">
                                    <option value="available" {{ old('status', $equipment->status) == 'available' ? 'selected' : '' }}>利用可能</option>
                                    <option value="in_use" {{ old('status', $equipment->status) == 'in_use' ? 'selected' : '' }}>使用中</option>
                                    <option value="repair" {{ old('status', $equipment->status) == 'repair' ? 'selected' : '' }}>修理中</option>
                                    <option value="retired" {{ old('status', $equipment->status) == 'retired' ? 'selected' : '' }}>廃棄</option>
                                    <option value="lost" {{ old('status', $equipment->status) == 'lost' ? 'selected' : '' }}>紛失</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 在庫管理 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">在庫管理</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                            <!-- 管理方式 -->
                            <div>
                                <label for="management_type" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    管理方式 <span class="text-red-500">*</span>
                                </label>
                                <select name="management_type" id="management_type" required x-model="managementType"
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('management_type') border-red-300 @enderror">
                                    <option value="individual">個体管理</option>
                                    <option value="quantity">数量管理</option>
                                </select>
                                @error('management_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 在庫数量 -->
                            <div>
                                <label for="quantity" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    在庫数量
                                </label>
                                <input type="number" name="quantity" id="quantity"
                                       :value="managementType === 'individual' ? 1 : '{{ old('quantity', $equipment->quantity) }}'"
                                       min="1" step="1"
                                       :disabled="managementType === 'individual'"
                                       :class="managementType === 'individual' ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('quantity') border-red-300 @enderror">
                                @error('quantity')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 単位 -->
                            <div>
                                <label for="unit" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    単位
                                </label>
                                <select name="unit" id="unit"
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
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
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 製品情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">製品情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 型番 -->
                            <div>
                                <label for="model_number" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    型番
                                </label>
                                <input type="text" name="model_number" id="model_number" value="{{ old('model_number', $equipment->model_number) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('model_number') border-red-300 @enderror">
                                @error('model_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- シリアル番号 -->
                            <div>
                                <label for="serial_number" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    シリアル番号
                                </label>
                                <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $equipment->serial_number) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('serial_number') border-red-300 @enderror">
                                @error('serial_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 購入情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">購入情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 購入先 -->
                            <div class="sm:col-span-2">
                                <label for="supplier" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    購入先
                                </label>
                                <input type="text" name="supplier" id="supplier" value="{{ old('supplier', $equipment->supplier) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('supplier') border-red-300 @enderror">
                                @error('supplier')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 購入日 -->
                            <div>
                                <label for="purchase_date" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    購入日
                                </label>
                                <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $equipment->purchase_date?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('purchase_date') border-red-300 @enderror">
                                @error('purchase_date')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 保証期限 -->
                            <div>
                                <label for="warranty_expiry" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    保証期限
                                </label>
                                <input type="date" name="warranty_expiry" id="warranty_expiry" value="{{ old('warranty_expiry', $equipment->warranty_expiry?->format('Y-m-d')) }}"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('warranty_expiry') border-red-300 @enderror">
                                @error('warranty_expiry')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 価格 -->
                            <div>
                                <label for="price" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    価格（円）
                                </label>
                                <input type="number" name="price" id="price" value="{{ old('price', $equipment->price) }}" min="0" step="0.01"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('price') border-red-300 @enderror">
                                @error('price')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 管理設定 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">管理設定</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- スケジュール表示フラグ -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    スケジュール表示
                                </label>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_schedule_visible" id="is_schedule_visible" value="1"
                                           {{ old('is_schedule_visible', $equipment->is_schedule_visible) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_schedule_visible" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        スケジュール表に表示する
                                    </label>
                                </div>
                                @error('is_schedule_visible')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 廃棄フラグ -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    廃棄フラグ
                                </label>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_discard" id="is_discard" value="1"
                                           x-model="isDiscard"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_discard" class="ml-2 block text-xs sm:text-sm text-gray-900">
                                        廃棄
                                    </label>
                                </div>
                                @error('is_discard')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 廃棄日 -->
                            <div>
                                <label for="discard_at" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                    廃棄日
                                </label>
                                <input type="date" name="discard_at" id="discard_at"
                                       :value="isDiscard ? '{{ old('discard_at', $equipment->discard_at?->format('Y-m-d')) }}' : ''"
                                       :disabled="!isDiscard"
                                       :class="!isDiscard ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('discard_at') border-red-300 @enderror">
                                @error('discard_at')
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
                        <textarea name="notes" id="notes" rows="2"
                                  class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                         @error('notes') border-red-300 @enderror">{{ old('notes', $equipment->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

