@extends('layouts.app')

@section('title', '機材使用編集')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $phaseEquipment->phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phaseEquipment->phase->performance->title }}</a>
    > <a href="{{ route('phases.equipment.index', $phaseEquipment->phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phaseEquipment->phase->name }} - 機材管理</a>
    > <a href="{{ route('phases.equipment.show', [$phaseEquipment->phase, $phaseEquipment]) }}" class="text-blue-600 hover:text-blue-800">機材使用詳細</a>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材使用編集</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $phaseEquipment->phase->performance->title }} - {{ $phaseEquipment->phase->name }} |
            {{ $phaseEquipment->equipment->name }}
        </p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('phases.equipment.show', [$phaseEquipment->phase, $phaseEquipment]) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent text-sm font-medium rounded-md text-gray-700 hover:bg-gray-400">
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
            <h3 class="text-lg font-medium text-gray-900 mb-6">機材使用情報の編集</h3>

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">入力エラーがあります</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('phases.equipment.update', [$phaseEquipment->phase, $phaseEquipment]) }}">
                @csrf
                @method('PUT')

                <!-- 現在の機材情報（編集不可） -->
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">機材情報</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">機材名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $phaseEquipment->equipment->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">カテゴリ</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $phaseEquipment->equipment->category->name ?? '-' }} > {{ $phaseEquipment->equipment->subcategory->name ?? '-' }}</dd>
                        </div>
                        @if($phaseEquipment->equipment->company_number)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">新音番号</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $phaseEquipment->equipment->company_number }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">管理方式</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $phaseEquipment->equipment->management_type === 'individual' ? '個体管理' : '数量管理' }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- 編集可能項目 -->
                <div class="space-y-6">
                    <!-- 数量 -->
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">使用数量</label>
                        <div class="mt-1">
                            @if($phaseEquipment->equipment->management_type === 'individual')
                                <input type="hidden" name="quantity" value="1">
                                <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900">
                                    1個（個体管理機材）
                                </div>
                            @else
                                <input type="number" name="quantity" id="quantity"
                                       value="{{ old('quantity', $phaseEquipment->quantity) }}"
                                       min="1" max="{{ $maxQuantity }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <div class="mt-1 text-sm text-gray-500">
                                    利用可能数量: {{ $maxQuantity }}個（現在の使用数を含む）
                                </div>
                            @endif
                        </div>
                        @error('quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 備考 -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                        <textarea name="note" id="note" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="使用に関する特記事項や要望などを記入してください">{{ old('note', $phaseEquipment->note) }}</textarea>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ステータス情報表示 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-md font-medium text-blue-900 mb-3">現在のステータス</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-blue-700">状態</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $phaseEquipment->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $phaseEquipment->status === 'checked_out' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $phaseEquipment->status === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $phaseEquipment->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ $phaseEquipment->status_label }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-blue-700">予約者</dt>
                                <dd class="mt-1 text-sm text-blue-900">{{ $phaseEquipment->reservedBy->name }}</dd>
                            </div>
                            @if($phaseEquipment->checked_out_at)
                                <div>
                                    <dt class="text-sm font-medium text-blue-700">貸出日時</dt>
                                    <dd class="mt-1 text-sm text-blue-900">{{ $phaseEquipment->checked_out_at->format('Y/m/d H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-blue-700">貸出担当者</dt>
                                    <dd class="mt-1 text-sm text-blue-900">{{ $phaseEquipment->checkedOutBy->name ?? '-' }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- 重複チェック警告 -->
                    @if($hasConflicts)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">期間重複の警告</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>この機材は他のフェーズでも使用予定があります。数量を変更する際は他の使用予定を確認してください。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 送信ボタン -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('phases.equipment.show', [$phaseEquipment->phase, $phaseEquipment]) }}"
                       class="px-4 py-2 bg-gray-300 border border-transparent text-sm font-medium rounded-md text-gray-700 hover:bg-gray-400">
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
    </div>

    <!-- 他の使用予定表示 -->
    @if($otherUsages->count() > 0)
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">同一機材の他の使用予定</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">公演・フェーズ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">期間</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">数量</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">予約者</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($otherUsages as $usage)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $usage->phase->performance->title }}</div>
                                        <div class="text-sm text-gray-500">{{ $usage->phase->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div>{{ $usage->phase->start_date->format('Y/m/d H:i') }}</div>
                                        <div>{{ $usage->phase->end_date->format('Y/m/d H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $usage->quantity }}個
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $usage->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $usage->status === 'checked_out' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $usage->status === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $usage->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ $usage->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $usage->reservedBy->name }}
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