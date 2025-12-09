@extends('layouts.master')

@section('title', '機材作成')

@section('breadcrumb')
    > <a href="{{ route('master.equipments.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">機材マスタ 一覧</a>
    > <span class="text-gray-800 dark:text-gray-200">新規作成</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">新規機材作成</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.equipments.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
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
        <form id="create-form" method="POST" action="{{ route('master.equipments.store') }}" class="max-w-2xl" autocomplete="off"
              x-data="{ managementType: '{{ old('management_type', 'individual') }}' }">
            @csrf

            <div class="space-y-4 sm:space-y-6">
                <!-- 基本情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- サブカテゴリ -->
                            <div class="sm:col-span-2">
                                <label for="subcategory_id" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    サブカテゴリ <span class="text-red-500">*</span>
                                </label>
                                <select name="subcategory_id" id="subcategory_id" required
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('subcategory_id') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    @foreach($categories as $category)
                                        <optgroup label="{{ $category->name }}">
                                            @foreach($category->subcategories as $subcategory)
                                                <option value="{{ $subcategory->id }}" {{ old('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                                    {{ $subcategory->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('subcategory_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- メーカー名 -->
                            <div>
                                <label for="manufacturer" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    メーカー名
                                </label>
                                <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('manufacturer') border-red-300 @enderror">
                                @error('manufacturer')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 場所 -->
                            <div>
                                <label for="location_id" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    場所
                                </label>
                                <select name="location_id" id="location_id" required
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('location_id') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 機材名 -->
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    機材名 <span class="text-red-500">*</span>
                                </label>
                                <textarea name="name" id="name" rows="2" required
                                          class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                                 @error('name') border-red-300 @enderror">{{ old('name') }}</textarea>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 新音番号 -->
                            <div>
                                <label for="company_number" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    新音番号
                                </label>
                                <input type="text" name="company_number" id="company_number"
                                       :value="managementType === 'quantity' ? '' : '{{ old('company_number') }}'"
                                       :disabled="managementType === 'quantity'"
                                       :class="managementType === 'quantity' ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('company_number') border-red-300 @enderror">
                                @error('company_number')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 状態 -->
                            <div>
                                <label for="status" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    状態 <span class="text-red-500">*</span>
                                </label>
                                <select name="status" id="status" required
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('status') border-red-300 @enderror">
                                    <option value="available" {{ old('status', 'available') == 'available' ? 'selected' : '' }}>利用可能</option>
                                    <option value="in_use" {{ old('status') == 'in_use' ? 'selected' : '' }}>使用中</option>
                                    <option value="repair" {{ old('status') == 'repair' ? 'selected' : '' }}>修理中</option>
                                    <option value="retired" {{ old('status') == 'retired' ? 'selected' : '' }}>廃棄</option>
                                    <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>紛失</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 在庫管理 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">在庫管理</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                            <!-- 管理方式 -->
                            <div>
                                <label for="management_type" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    管理方式 <span class="text-red-500">*</span>
                                </label>
                                <select name="management_type" id="management_type" required x-model="managementType"
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('management_type') border-red-300 @enderror">
                                    <option value="individual">個体管理</option>
                                    <option value="quantity">数量管理</option>
                                </select>
                                @error('management_type')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 在庫数量 -->
                            <div>
                                <label for="quantity" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    在庫数量
                                </label>
                                <input type="number" name="quantity" id="quantity"
                                       :value="managementType === 'individual' ? 1 : '{{ old('quantity', 1) }}'"
                                       :disabled="managementType === 'individual'"
                                       :class="managementType === 'individual' ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       min="1" step="1"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('quantity') border-red-300 @enderror">
                                @error('quantity')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 単位 -->
                            <div>
                                <label for="unit" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    単位
                                </label>
                                <select name="unit" id="unit"
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('unit') border-red-300 @enderror">
                                    <option value="台" {{ old('unit', '台') == '台' ? 'selected' : '' }}>台</option>
                                    <option value="個" {{ old('unit') == '個' ? 'selected' : '' }}>個</option>
                                    <option value="本" {{ old('unit') == '本' ? 'selected' : '' }}>本</option>
                                    <option value="箱" {{ old('unit') == '箱' ? 'selected' : '' }}>箱</option>
                                    <option value="ケース" {{ old('unit') == 'ケース' ? 'selected' : '' }}>ケース</option>
                                    <option value="ラック" {{ old('unit') == 'ラック' ? 'selected' : '' }}>ラック</option>
                                    <option value="セット" {{ old('unit') == 'セット' ? 'selected' : '' }}>セット</option>
                                </select>
                                @error('unit')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 製品情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">製品情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 型番 -->
                            <div>
                                <label for="model_number" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    型番
                                </label>
                                <input type="text" name="model_number" id="model_number" value="{{ old('model_number') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('model_number') border-red-300 @enderror">
                                @error('model_number')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- シリアル番号 -->
                            <div>
                                <label for="serial_number" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    シリアル番号
                                </label>
                                <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('serial_number') border-red-300 @enderror">
                                @error('serial_number')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 購入情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">購入情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 購入先 -->
                            <div class="sm:col-span-2">
                                <label for="supplier" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    購入先
                                </label>
                                <input type="text" name="supplier" id="supplier" value="{{ old('supplier') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('supplier') border-red-300 @enderror">
                                @error('supplier')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 購入日 -->
                            <div>
                                <label for="purchase_date" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    購入日
                                </label>
                                <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('purchase_date') border-red-300 @enderror">
                                @error('purchase_date')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 保証期限 -->
                            <div>
                                <label for="warranty_expiry" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    保証期限
                                </label>
                                <input type="date" name="warranty_expiry" id="warranty_expiry" value="{{ old('warranty_expiry') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('warranty_expiry') border-red-300 @enderror">
                                @error('warranty_expiry')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 価格 -->
                            <div>
                                <label for="price" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    価格（円）
                                </label>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" min="0" step="0.01"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('price') border-red-300 @enderror">
                                @error('price')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <textarea name="notes" id="notes" rows="2"
                                  class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                         @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

