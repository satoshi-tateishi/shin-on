@extends('layouts.app')

@section('title', 'フェーズ編集')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800">{{ $performance->title }}</a>
    > <a href="{{ route('phases.show', $phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->name }}</a>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">フェーズ編集</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $phase->name }} の情報を編集してください。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('phases.show', $phase) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
    </div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <form method="POST" action="{{ route('phases.update', $phase) }}" class="px-4 py-5 sm:p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- フェーズ名 -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        フェーズ名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $phase->name) }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror"
                           placeholder="例: 稽古、リハーサル、本番">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 開始日 -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">
                        開始日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" id="start_date"
                           value="{{ old('start_date', $phase->start_date ? $phase->start_date->format('Y-m-d') : '') }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-300 @enderror">
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 終了日 -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">
                        終了日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" id="end_date"
                           value="{{ old('end_date', $phase->end_date ? $phase->end_date->format('Y-m-d') : '') }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-300 @enderror">
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 場所 -->
                <div>
                    <label for="location_id" class="block text-sm font-medium text-gray-700">場所</label>
                    <select name="location_id" id="location_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('location_id') border-red-300 @enderror">
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
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ソート順 -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700">
                        表示順 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="sort" id="sort" value="{{ old('sort', $phase->sort) }}" required min="1"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('sort') border-red-300 @enderror">
                    @error('sort')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">数字が小さいほど上に表示されます</p>
                </div>

                <!-- 備考 -->
                <div class="md:col-span-2">
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-300 @enderror"
                              placeholder="このフェーズについての詳細や注意事項">{{ old('note', $phase->note) }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- アクティブフラグ -->
                <div class="md:col-span-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', $phase->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            有効にする
                        </label>
                    </div>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- アクションボタン -->
        <div class="mt-8 flex justify-end space-x-3">
            <a href="{{ route('phases.show', $phase) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                更新
            </button>
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