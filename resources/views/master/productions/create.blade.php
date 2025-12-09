@extends('layouts.master')

@section('title', 'プロダクションマスタ作成')

@section('breadcrumb')
    > <a href="{{ route('master.productions.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">プロダクションマスタ</a>
    > <span class="text-gray-800 dark:text-gray-200">新規作成</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">新規プロダクション</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.productions.index') }}"
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
        <form id="create-form" method="POST" action="{{ route('master.productions.store') }}" class="max-w-2xl">
            @csrf

            <div class="space-y-4 sm:space-y-6">
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
                            <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- プロダクション名 -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        プロダクション名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 郵便番号 -->
                <div>
                    <label for="postal_code" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        郵便番号
                    </label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                           placeholder="123-4567"
                           class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('postal_code') border-red-300 @enderror">
                    @error('postal_code')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 住所 -->
                <div>
                    <label for="address" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        住所
                    </label>
                    <input type="text" name="address" id="address" value="{{ old('address') }}"
                           class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('address') border-red-300 @enderror">
                    @error('address')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 備考 -->
                <div>
                    <label for="note" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        備考
                    </label>
                    <textarea name="note" id="note" rows="2"
                              class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                     @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 有効フラグ -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                        <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">
                            有効
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">無効にすると選択できなくなります</p>
                </div>
            </div>
        </form>
    </div>
@endsection
