@extends('layouts.master')

@section('title', '修理報告')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">修理管理</a>
    > <span class="text-gray-800 dark:text-gray-200">新規作成</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">修理報告</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('repair-records.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
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
        <form id="create-form" action="{{ route('repair-records.store') }}" method="POST" enctype="multipart/form-data" class="max-w-2xl">
            @csrf

            @if ($errors->any())
                <div class="bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 px-3 sm:px-4 py-2 sm:py-3 rounded-md mb-4 sm:mb-6">
                    <ul class="list-disc list-inside text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4 sm:space-y-6">
                <!-- 機材選択 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">機材選択</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- カテゴリ選択 -->
                            <div>
                                <label for="category_filter" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
                                <select name="category_filter" id="category_filter"
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">カテゴリを選択してください</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- サブカテゴリ選択 -->
                            <div>
                                <label for="subcategory_filter" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">サブカテゴリ</label>
                                <select name="subcategory_filter" id="subcategory_filter" disabled
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-600 disabled:text-gray-500 dark:disabled:text-gray-400">
                                    <option value="">カテゴリを先に選択してください</option>
                                </select>
                            </div>
                        </div>

                        <!-- 機材選択 -->
                        <div>
                            <label for="equipment_id" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                機材 <span class="text-red-500">*</span>
                            </label>
                            <select name="equipment_id" id="equipment_id" required disabled
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-600 disabled:text-gray-500 dark:disabled:text-gray-400 @error('equipment_id') border-red-300 @enderror">
                                <option value="">サブカテゴリを先に選択してください</option>
                            </select>
                            @error('equipment_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 将来予約警告エリア -->
                        <div id="future-reservations-warning" class="hidden">
                            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-md p-3 sm:p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-4 w-4 sm:h-5 sm:w-5 text-amber-400 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-2 sm:ml-3">
                                        <h3 class="text-xs sm:text-sm font-medium text-amber-800 dark:text-amber-300">
                                            この機材には将来の使用予約があります
                                        </h3>
                                        <div class="mt-1 sm:mt-2 text-xs sm:text-sm text-amber-700 dark:text-amber-400">
                                            <p>修理報告後、管理者が代替機への変更や予約の調整を行う必要があります。</p>
                                            <div id="reservations-list" class="mt-2">
                                                <!-- 予約一覧がここに動的に表示される -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 報告情報 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">報告情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 担当者選択 -->
                            <div>
                                <label for="staff_user_id" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    担当者 <span class="text-red-500">*</span>
                                </label>
                                <select name="staff_user_id" id="staff_user_id" required
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('staff_user_id') border-red-300 @enderror">
                                    <option value="">担当者を選択してください</option>
                                    @foreach($staffUsers as $staffUser)
                                        <option value="{{ $staffUser->id }}"
                                                {{ old('staff_user_id') == $staffUser->id ? 'selected' : '' }}>
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
                                <label for="failure_occurred_at" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">故障発生日</label>
                                <input type="date" name="failure_occurred_at" id="failure_occurred_at" value="{{ old('failure_occurred_at') }}"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('failure_occurred_at') border-red-300 @enderror">
                                @error('failure_occurred_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <!-- 公演名 -->
                            <div>
                                <label for="performance_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">公演名</label>
                                <input type="text" name="performance_name" id="performance_name" value="{{ old('performance_name') }}"
                                       placeholder="公演名を入力"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('performance_name') border-red-300 @enderror">
                                @error('performance_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 使用場所 -->
                            <div>
                                <label for="usage_location" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">使用場所</label>
                                <input type="text" name="usage_location" id="usage_location" value="{{ old('usage_location') }}"
                                       placeholder="使用場所を入力"
                                       class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('usage_location') border-red-300 @enderror">
                                @error('usage_location')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 問題内容 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">問題内容</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                        <!-- 故障箇所写真 -->
                        <div>
                            <label for="photos" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">故障箇所写真</label>
                            <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('photos') border-red-300 @enderror">
                            @error('photos')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">最大2枚までの写真をアップロードできます（最大10MB/枚）</p>

                            <!-- 警告メッセージエリア -->
                            <div id="file-limit-warning" class="mt-2 p-2 sm:p-3 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 text-yellow-700 dark:text-yellow-300 rounded-md hidden">
                                <p class="text-xs sm:text-sm">⚠️ 最大2枚までしかアップロードできません。</p>
                            </div>

                            <!-- 画像プレビューエリア -->
                            <div id="image-preview" class="mt-2 sm:mt-3 grid grid-cols-2 gap-3 sm:gap-4 hidden">
                                <!-- プレビュー画像がここに動的に追加される -->
                            </div>
                        </div>

                        <!-- 問題内容 -->
                        <div>
                            <label for="problem_description" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                問題内容 <span class="text-red-500">*</span>
                            </label>
                            <textarea name="problem_description" id="problem_description" rows="8" required
                                      placeholder="発生した問題・不具合の詳細を記載してください"
                                      class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('problem_description') border-red-300 @enderror">{{ old('problem_description') }}</textarea>
                            @error('problem_description')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <textarea name="note" id="note" rows="8"
                                  placeholder="その他の特記事項があれば記載してください"
                                  class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
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
@endpush
