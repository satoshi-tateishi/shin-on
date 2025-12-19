@extends('layouts.master')

@section('title', '使用場所マスタ編集')

@section('breadcrumb')
    > <a href="{{ route('master.locations.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">使用場所マスタ</a>
    > <span class="text-gray-800 dark:text-gray-200">{{ $location->name }}</span>
    > <span class="text-gray-800 dark:text-gray-200">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $location->name }} - 編集</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.locations.show', $location) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>
            <button type="submit" form="edit-form"
                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                保存
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <form id="edit-form" method="POST" action="{{ route('master.locations.update', $location) }}" class="max-w-2xl">
            @csrf
            @method('PUT')

            <div class="space-y-4 sm:space-y-6">
                <!-- 基本情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- タイプ -->
                            <div>
                                <label for="type" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    タイプ <span class="text-red-500">*</span>
                                </label>
                                <select name="type" id="type" required
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('type') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" {{ old('type', $location->type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 有効フラグ -->
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">状態</label>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                           {{ old('is_active', $location->is_active) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                    <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">
                                        有効
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">無効にすると選択できなくなります</p>
                            </div>
                        </div>

                        <!-- フィルタ表示設定 -->
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-3 sm:p-4 rounded-lg">
                            <h4 class="text-xs sm:text-sm font-medium text-blue-900 dark:text-blue-300 mb-2 sm:mb-3">フィルタ表示設定</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <!-- 在庫管理フィルタ表示 -->
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_inventory_visible" id="is_inventory_visible" value="1"
                                               {{ old('is_inventory_visible', $location->is_inventory_visible) ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                        <label for="is_inventory_visible" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">
                                            在庫管理フィルタに表示
                                        </label>
                                    </div>
                                </div>

                                <!-- 倉庫間移動フィルタ表示 -->
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_transfer_visible" id="is_transfer_visible" value="1"
                                               {{ old('is_transfer_visible', $location->is_transfer_visible) ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                        <label for="is_transfer_visible" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">
                                            倉庫間移動フィルタに表示
                                        </label>
                                    </div>
                                </div>

                                <!-- 主要倉庫フラグ -->
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="is_main_warehouse" id="is_main_warehouse" value="1"
                                               {{ old('is_main_warehouse', $location->is_main_warehouse) ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                        <label for="is_main_warehouse" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">
                                            主要倉庫（大型機材返却対象）
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 場所名 -->
                            <div>
                                <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    場所名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $location->name) }}" required
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ふりがな -->
                            <div>
                                <label for="furigana" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    ふりがな
                                </label>
                                <input type="text" name="furigana" id="furigana" value="{{ old('furigana', $location->furigana) }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('furigana') border-red-300 @enderror">
                                @error('furigana')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 連絡先情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">連絡先情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <!-- 電話番号1 -->
                        <div class="grid grid-cols-3 gap-2 sm:gap-4">
                            <div>
                                <label for="tel1_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    電話1名称
                                </label>
                                <input type="text" name="tel1_name" id="tel1_name" value="{{ old('tel1_name', $location->tel1_name) }}"
                                       placeholder="代表"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel1_name') border-red-300 @enderror">
                            </div>
                            <div class="col-span-2">
                                <label for="tel1" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    電話番号1
                                </label>
                                <input type="text" name="tel1" id="tel1" value="{{ old('tel1', $location->tel1) }}"
                                       placeholder="03-1234-5678"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel1') border-red-300 @enderror">
                            </div>
                        </div>

                        <!-- 電話番号2 -->
                        <div class="grid grid-cols-3 gap-2 sm:gap-4">
                            <div>
                                <label for="tel2_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    電話2名称
                                </label>
                                <input type="text" name="tel2_name" id="tel2_name" value="{{ old('tel2_name', $location->tel2_name) }}"
                                       placeholder="担当者"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel2_name') border-red-300 @enderror">
                            </div>
                            <div class="col-span-2">
                                <label for="tel2" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    電話番号2
                                </label>
                                <input type="text" name="tel2" id="tel2" value="{{ old('tel2', $location->tel2) }}"
                                       placeholder="03-1234-5679"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel2') border-red-300 @enderror">
                            </div>
                        </div>

                        <!-- FAX -->
                        <div>
                            <label for="fax" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                FAX
                            </label>
                            <input type="text" name="fax" id="fax" value="{{ old('fax', $location->fax) }}"
                                   placeholder="03-1234-5680"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                          @error('fax') border-red-300 @enderror">
                        </div>

                        <!-- メールアドレス1 -->
                        <div class="grid grid-cols-3 gap-2 sm:gap-4">
                            <div>
                                <label for="email1_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    メール1名称
                                </label>
                                <input type="text" name="email1_name" id="email1_name" value="{{ old('email1_name', $location->email1_name) }}"
                                       placeholder="代表"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email1_name') border-red-300 @enderror">
                            </div>
                            <div class="col-span-2">
                                <label for="email1" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    メール1
                                </label>
                                <input type="email" name="email1" id="email1" value="{{ old('email1', $location->email1) }}"
                                       placeholder="info@example.com"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email1') border-red-300 @enderror">
                            </div>
                        </div>

                        <!-- メールアドレス2 -->
                        <div class="grid grid-cols-3 gap-2 sm:gap-4">
                            <div>
                                <label for="email2_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    メール2名称
                                </label>
                                <input type="text" name="email2_name" id="email2_name" value="{{ old('email2_name', $location->email2_name) }}"
                                       placeholder="担当者"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email2_name') border-red-300 @enderror">
                            </div>
                            <div class="col-span-2">
                                <label for="email2" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    メール2
                                </label>
                                <input type="email" name="email2" id="email2" value="{{ old('email2', $location->email2) }}"
                                       placeholder="manager@example.com"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email2') border-red-300 @enderror">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 住所情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">住所情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <!-- 郵便番号 -->
                        <div>
                            <label for="postal_code" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                郵便番号
                            </label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $location->postal_code) }}"
                                   placeholder="123-4567"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                          @error('postal_code') border-red-300 @enderror">
                        </div>

                        <!-- 住所 -->
                        <div>
                            <label for="address" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                住所
                            </label>
                            <textarea name="address" id="address" rows="2"
                                      class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                             @error('address') border-red-300 @enderror">{{ old('address', $location->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <textarea name="note" id="note" rows="2"
                                  class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                         @error('note') border-red-300 @enderror">{{ old('note', $location->note) }}</textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection