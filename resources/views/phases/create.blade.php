@extends('layouts.master')

@section('title', 'フェーズ作成')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800">{{ $performance->title }}</a>
    > <span class="text-gray-800">フェーズ作成</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600">{{ $performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">フェーズ作成</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('performances.show', $performance) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
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
    <form id="create-form" method="POST" action="{{ route('performances.phases.store', $performance) }}" class="space-y-4 sm:space-y-6">
        @csrf

        <!-- 基本情報 -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                <!-- フェーズ名 -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                        フェーズ名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                           placeholder="例: 稽古、リハーサル、本番">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- 開始日 -->
                    <div>
                        <label for="start_date" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                            開始日 <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-300 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 終了日 -->
                    <div>
                        <label for="end_date" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                            終了日 <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                               class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-300 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 場所 -->
                <div>
                    <label for="location_id" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">場所</label>
                    <select name="location_id" id="location_id"
                            class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('location_id') border-red-300 @enderror">
                        <option value="">場所を選択</option>
                        @php
                            $groupedLocations = $locations->groupBy('type');
                        @endphp
                        @foreach($groupedLocations as $type => $locationGroup)
                            <optgroup label="{{ $type }}">
                                @foreach($locationGroup as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('location_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
                          class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-300 @enderror"
                          placeholder="スタジオ番号や補足情報など">{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- アクティブフラグ -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900">有効にする</label>
                </div>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 開始日が変更されたら、終了日の最小値を設定
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });

    // 初期設定
    if (startDateInput.value) {
        endDateInput.min = startDateInput.value;
    }
});
</script>
@endpush

@endsection
