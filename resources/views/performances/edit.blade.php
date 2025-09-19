@extends('layouts.app')

@section('title', '公演編集')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800">{{ $performance->title }}</a>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">公演編集</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $performance->title }} の情報を編集します。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('performances.show', $performance) }}"
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
    <form method="POST" action="{{ route('performances.update', $performance) }}" class="px-4 py-5 sm:p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- 基本情報 -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 公演名 -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">
                            公演名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $performance->title) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-300 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- サブタイトル -->
                    <div>
                        <label for="subtitle" class="block text-sm font-medium text-gray-700">サブタイトル</label>
                        <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle', $performance->subtitle) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('subtitle') border-red-300 @enderror">
                        @error('subtitle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 公演種別 -->
                    <div>
                        <label for="performance_type" class="block text-sm font-medium text-gray-700">
                            公演種別 <span class="text-red-500">*</span>
                        </label>
                        <select name="performance_type" id="performance_type" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('performance_type') border-red-300 @enderror">
                            <option value="">選択してください</option>
                            <option value="演劇" {{ old('performance_type', $performance->performance_type) === '演劇' ? 'selected' : '' }}>演劇</option>
                            <option value="ミュージカル" {{ old('performance_type', $performance->performance_type) === 'ミュージカル' ? 'selected' : '' }}>ミュージカル</option>
                            <option value="コンサート" {{ old('performance_type', $performance->performance_type) === 'コンサート' ? 'selected' : '' }}>コンサート</option>
                            <option value="その他" {{ old('performance_type', $performance->performance_type) === 'その他' ? 'selected' : '' }}>その他</option>
                        </select>
                        @error('performance_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ステータス -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">
                            ステータス <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-300 @enderror">
                            <option value="">選択してください</option>
                            <option value="planning" {{ old('status', $performance->status) === 'planning' ? 'selected' : '' }}>企画中</option>
                            <option value="preparation" {{ old('status', $performance->status) === 'preparation' ? 'selected' : '' }}>準備中</option>
                            <option value="in_progress" {{ old('status', $performance->status) === 'in_progress' ? 'selected' : '' }}>進行中</option>
                            <option value="completed" {{ old('status', $performance->status) === 'completed' ? 'selected' : '' }}>完了</option>
                            <option value="cancelled" {{ old('status', $performance->status) === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 期間・会場情報 -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">期間・会場情報</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 開始日 -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">
                            開始日 <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $performance->start_date->format('Y-m-d')) }}" required
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
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $performance->end_date->format('Y-m-d')) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-300 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 会場 -->
                    <div class="md:col-span-2">
                        <label for="venue" class="block text-sm font-medium text-gray-700">会場</label>
                        <input type="text" name="venue" id="venue" value="{{ old('venue', $performance->venue) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('venue') border-red-300 @enderror">
                        @error('venue')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- スタッフ情報 -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">主要スタッフ</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 演出 -->
                    <div>
                        <label for="director" class="block text-sm font-medium text-gray-700">演出</label>
                        <input type="text" name="director" id="director" value="{{ old('director', $performance->director) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('director') border-red-300 @enderror">
                        @error('director')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- プロデューサー -->
                    <div>
                        <label for="producer" class="block text-sm font-medium text-gray-700">プロデューサー</label>
                        <input type="text" name="producer" id="producer" value="{{ old('producer', $performance->producer) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('producer') border-red-300 @enderror">
                        @error('producer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- その他情報 -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">その他情報</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 予算 -->
                    <div>
                        <label for="budget" class="block text-sm font-medium text-gray-700">予算</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" name="budget" id="budget" value="{{ old('budget', $performance->budget) }}" min="0" step="0.01"
                                   class="block w-full pr-12 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('budget') border-red-300 @enderror">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">円</span>
                            </div>
                        </div>
                        @error('budget')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 有効フラグ -->
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', $performance->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            有効
                        </label>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="mt-6">
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="4"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-300 @enderror">{{ old('note', $performance->note) }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 送信ボタン -->
        <div class="mt-8 flex justify-end space-x-3">
            <a href="{{ route('performances.show', $performance) }}"
               class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                更新
            </button>
        </div>
    </form>
</div>

<script>
// 開始日が変更されたら終了日の最小値を更新
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = this.value;
    const endDateInput = document.getElementById('end_date');

    if (startDate) {
        endDateInput.setAttribute('min', startDate);

        // 終了日が開始日より前の場合は、終了日を開始日に設定
        if (endDateInput.value && endDateInput.value < startDate) {
            endDateInput.value = startDate;
        }
    }
});

// ページ読み込み時に最小値を設定
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput.value) {
        endDateInput.setAttribute('min', startDateInput.value);
    }
});
</script>
@endsection