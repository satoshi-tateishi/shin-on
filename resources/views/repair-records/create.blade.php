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

                <!-- 段階的機材選択（カテゴリ → サブカテゴリ → 機材） -->
                <!-- パフォーマンス最適化：必要なデータのみ動的取得 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- ステップ1: カテゴリ選択（初期ページロードで取得済み） -->
                    <div>
                        <label for="category_filter" class="block text-sm font-medium text-gray-700">カテゴリ</label>
                        <select name="category_filter" id="category_filter"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            <option value="">カテゴリを選択してください</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ステップ2: サブカテゴリ選択（API: /api/subcategories/by-category で動的取得） -->
                    <div>
                        <label for="subcategory_filter" class="block text-sm font-medium text-gray-700">サブカテゴリ</label>
                        <select name="subcategory_filter" id="subcategory_filter" disabled
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">カテゴリを先に選択してください</option>
                        </select>
                    </div>

                    <!-- ステップ3: 機材選択（API: /api/equipments/by-subcategory で動的取得） -->
                    <div>
                        <label for="equipment_id" class="block text-sm font-medium text-gray-700">機材 <span class="text-red-500">*</span></label>
                    <select name="equipment_id" id="equipment_id" required disabled
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm disabled:bg-gray-100 disabled:text-gray-500 @error('equipment_id') border-red-300 @enderror">
                        <option value="">サブカテゴリを先に選択してください</option>
                    </select>
                    @error('equipment_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    </div>
                </div>

                <!-- 将来予約警告エリア -->
                <div id="future-reservations-warning" class="hidden">
                    <div class="bg-amber-50 border border-amber-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-amber-800">
                                    この機材には将来の使用予約があります
                                </h3>
                                <div class="mt-2 text-sm text-amber-700">
                                    <p>修理報告後、管理者が代替機への変更や予約の調整を行う必要があります。</p>
                                    <div id="reservations-list" class="mt-2">
                                        <!-- 予約一覧がここに動的に表示される -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 担当者選択と故障発生日（サブカテゴリと位置揃え） -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- 担当者選択 -->
                    <div class="md:max-w-48">
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

                    <!-- 故障発生日（サブカテゴリの位置に配置） -->
                    <div class="md:max-w-48">
                        <label for="failure_occurred_at" class="block text-sm font-medium text-gray-700">故障発生日</label>
                        <input type="date" name="failure_occurred_at" id="failure_occurred_at" value="{{ old('failure_occurred_at') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm @error('failure_occurred_at') border-red-300 @enderror">
                        @error('failure_occurred_at')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 3列目は空スペース（位置揃えのため） -->
                    <div></div>
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
<script src="{{ asset('js/repair-record-form.js') }}"></script>
@endpush