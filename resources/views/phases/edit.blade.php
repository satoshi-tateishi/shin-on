@extends('layouts.master')

@section('title', 'フェーズ編集')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">公演一覧</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ $performance->title }}</a>
    > <a href="{{ route('phases.show', $phase) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ $phase->name }}</a>
    > <span class="text-gray-800 dark:text-gray-200">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600 dark:text-gray-400">{{ $performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $phase->name }} 編集</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('phases.show', $phase) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
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
    <form id="edit-form" method="POST" action="{{ route('phases.update', $phase) }}" class="space-y-4 sm:space-y-6"
          x-data="{ startDate: '{{ old('start_date', $phase->start_date?->format('Y-m-d')) }}' }">
        @csrf
        @method('PUT')

        <!-- 基本情報 -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                <!-- フェーズ名 -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        フェーズ名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $phase->name) }}" required
                           class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                           placeholder="例: 稽古、リハーサル、本番">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- 開始日 -->
                    <div>
                        <label for="start_date" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            開始日 <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" x-model="startDate" required
                               class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-300 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 終了日 -->
                    <div>
                        <label for="end_date" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            終了日 <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date"
                               value="{{ old('end_date', $phase->end_date ? $phase->end_date->format('Y-m-d') : '') }}" :min="startDate" required
                               class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-300 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 場所 -->
                <div>
                    <label for="location_id" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">場所</label>
                    <select name="location_id" id="location_id"
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('location_id') border-red-300 @enderror">
                        <option value="">場所を選択</option>
                        @if(isset($locations) && $locations->count() > 0)
                            @php
                                $groupedLocations = $locations->groupBy('type');
                            @endphp
                            @foreach($groupedLocations as $type => $locationGroup)
                                <optgroup label="{{ $type }}">
                                    @foreach($locationGroup as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id', $phase->location_id) == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @endif
                    </select>
                    @error('location_id')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
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
                          class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-300 @enderror"
                          placeholder="スタジオ番号や補足情報など">{{ old('note', $phase->note) }}</textarea>
                @error('note')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- アクティブフラグ -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $phase->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                    <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">有効にする</label>
                </div>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </form>
</div>

@endsection
