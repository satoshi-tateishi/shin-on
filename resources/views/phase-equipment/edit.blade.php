@extends('layouts.master')

@section('title', '機材使用数量の編集')

@section('breadcrumb')
    > <a href="{{ route('phases.equipment.show', [$phase, $phaseEquipment]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">機材詳細</a>
    > <span class="text-gray-800 dark:text-gray-200">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600 dark:text-gray-400">{{ $phase->performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $phase->name }} 機材使用編集</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('phases.equipment.show', [$phase, $phaseEquipment]) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="px-4 py-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-4 sm:mb-6">使用数量の編集</h3>

            @if($errors->any())
                <div class="mb-4 sm:mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-md p-3 sm:p-4">
                    <div class="flex">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-2 sm:ml-3">
                            <h3 class="text-xs sm:text-sm font-medium text-red-800 dark:text-red-300">入力エラーがあります</h3>
                            <div class="mt-1 sm:mt-2 text-xs sm:text-sm text-red-700 dark:text-red-400">
                                <ul class="list-disc pl-4 sm:pl-5 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('phases.equipment.update', [$phase, $phaseEquipment]) }}">
                @csrf
                @method('PUT')

                <!-- 現在の機材情報（編集不可） -->
                <div class="mb-4 sm:mb-6 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 sm:p-4">
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">機材名</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-gray-100">{{ $phaseEquipment->equipment->name }}</dd>
                        </div>
                        @if($phaseEquipment->equipment->company_number)
                            <div>
                                <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">新音番号</dt>
                                <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-gray-100">{{ $phaseEquipment->equipment->company_number }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 編集可能項目 -->
                <div class="space-y-4 sm:space-y-6">
                    <!-- 数量 -->
                    <div>
                        <label for="quantity" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">使用数量</label>
                        <div class="mt-1">
                            @if($phaseEquipment->equipment->management_type === 'individual')
                                <input type="hidden" name="quantity" value="1">
                                <div class="mt-1 block w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs sm:text-sm text-gray-900 dark:text-gray-100">
                                    1個（個体管理機材）
                                </div>
                            @else
                                <input type="number" name="quantity" id="quantity"
                                       value="{{ old('quantity', $phaseEquipment->quantity) }}"
                                       min="1" max="{{ $maxQuantity }}" required
                                       class="mt-1 block w-20 sm:w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <div class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    現在未使用: {{ $availableQuantity }}個<br>
                                    最大 {{ $maxQuantity }}個まで変更可能
                                </div>
                            @endif
                        </div>
                        @error('quantity')
                            <p class="mt-1 text-xs sm:text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 重複チェック警告 -->
                    @if($hasConflicts)
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3 sm:p-4">
                            <div class="flex">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-2 sm:ml-3">
                                    <h3 class="text-xs sm:text-sm font-medium text-yellow-800 dark:text-yellow-300">期間重複の警告</h3>
                                    <div class="mt-1 sm:mt-2 text-xs sm:text-sm text-yellow-700 dark:text-yellow-400">
                                        <p>この機材は他のフェーズでも使用予定があります。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 送信ボタン -->
                <div class="mt-6 sm:mt-8 flex justify-end gap-2 sm:space-x-3">
                    <a href="{{ route('phases.equipment.show', [$phase, $phaseEquipment]) }}"
                       class="px-3 sm:px-4 py-2 bg-gray-300 dark:bg-gray-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 hover:bg-gray-400 dark:hover:bg-gray-500">
                        戻る
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        更新
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 他の使用予定表示 -->
    @if($otherUsages->count() > 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 py-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-3 sm:mb-4">同一機材の他の使用予定</h3>

                <!-- モバイル用カード表示 -->
                <div class="sm:hidden space-y-3">
                    @foreach($otherUsages as $usage)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">{{ $usage->phase->performance->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $usage->phase->name }}</p>
                                </div>
                                <span class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $usage->status === 'reserved' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                    {{ $usage->status === 'checked_out' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                    {{ $usage->status === 'checked_in' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : '' }}">
                                    {{ $usage->status_label }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $usage->phase->start_date->format('Y/m/d') }} ～ {{ $usage->phase->end_date->format('Y/m/d') }}</span>
                                <span class="ml-2">{{ $usage->quantity }}個</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- デスクトップ用テーブル表示 -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">公演・フェーズ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">期間</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">数量</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ステータス</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($otherUsages as $usage)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $usage->phase->performance->title }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $usage->phase->name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        <div>{{ $usage->phase->start_date->format('Y/m/d') }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">～ {{ $usage->phase->end_date->format('Y/m/d') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $usage->quantity }}個
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $usage->status === 'reserved' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                            {{ $usage->status === 'checked_out' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                            {{ $usage->status === 'checked_in' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' : '' }}">
                                            {{ $usage->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
// 数量入力時のリアルタイムバリデーション
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.max);

            if (value > max) {
                this.value = max;
                showWarning('利用可能数量を超えています。最大数量: ' + max + '個');
            } else if (value < 1) {
                this.value = 1;
                showWarning('数量は1個以上で入力してください。');
            }
        });
    }
});

function showWarning(message) {
    // 既存の警告を削除
    const existingWarning = document.getElementById('quantity-warning');
    if (existingWarning) {
        existingWarning.remove();
    }

    // 新しい警告を表示
    const quantityInput = document.getElementById('quantity');
    const warning = document.createElement('div');
    warning.id = 'quantity-warning';
    warning.className = 'mt-1 text-sm text-red-600';
    warning.textContent = message;
    quantityInput.parentNode.appendChild(warning);

    // 3秒後に警告を削除
    setTimeout(function() {
        const warningElement = document.getElementById('quantity-warning');
        if (warningElement) {
            warningElement.remove();
        }
    }, 3000);
}
</script>
@endsection