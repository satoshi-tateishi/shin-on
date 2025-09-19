@extends('layouts.app')

@section('title', 'フェーズ機材管理')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <span class="text-gray-800">{{ $phase->name }} - 機材管理</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $phase->name }} - 機材管理</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $phase->performance->title }} |
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
            @if($phase->location)
                | {{ $phase->location->name }}
            @endif
        </p>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <a href="{{ route('phases.equipment.create', $phase) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                機材追加
            </a>
        </div>
    @endif
@endsection

@section('content')
<!-- 統計サマリー -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">総機材数</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $equipmentStats['total'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">予約済み</dt>
                        <dd class="text-lg font-medium text-blue-900">{{ $equipmentStats['reserved'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">貸出中</dt>
                        <dd class="text-lg font-medium text-orange-900">{{ $equipmentStats['checked_out'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">返却済み</dt>
                        <dd class="text-lg font-medium text-green-900">{{ $equipmentStats['checked_in'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">キャンセル</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $equipmentStats['cancelled'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 機材一覧 -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        @if($phaseEquipments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材情報
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                カテゴリ
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                数量
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ステータス
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                貸出・返却
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                アクション
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($phaseEquipments as $phaseEquipment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $phaseEquipment->equipment->name }}
                                        </div>
                                        @if($phaseEquipment->equipment->company_number)
                                            <div class="text-sm text-gray-500">
                                                新音番号: {{ $phaseEquipment->equipment->company_number }}
                                            </div>
                                        @endif
                                        @if($phaseEquipment->equipment->model_number)
                                            <div class="text-sm text-gray-500">
                                                型番: {{ $phaseEquipment->equipment->model_number }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $phaseEquipment->equipment->subcategory->category->name }}</div>
                                <div class="text-sm text-gray-500">{{ $phaseEquipment->equipment->subcategory->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $phaseEquipment->quantity }}</div>
                                <div class="text-sm text-gray-500">{{ $phaseEquipment->equipment->management_type_label }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $phaseEquipment->status_color }}">
                                    {{ $phaseEquipment->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($phaseEquipment->checkout_date)
                                    <div>貸出: {{ $phaseEquipment->checkout_date->format('Y/m/d') }}</div>
                                    @if($phaseEquipment->checkoutUser)
                                        <div class="text-xs">{{ $phaseEquipment->checkoutUser->name }}</div>
                                    @endif
                                @endif
                                @if($phaseEquipment->checkin_date)
                                    <div>返却: {{ $phaseEquipment->checkin_date->format('Y/m/d') }}</div>
                                    @if($phaseEquipment->checkinUser)
                                        <div class="text-xs">{{ $phaseEquipment->checkinUser->name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('phases.equipment.show', [$phase, $phaseEquipment]) }}"
                                       class="text-blue-600 hover:text-blue-900">詳細</a>

                                    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                        @if($phaseEquipment->canCheckout())
                                            <button onclick="openCheckoutModal({{ $phaseEquipment->id }})"
                                                    class="text-orange-600 hover:text-orange-900">貸出</button>
                                        @endif

                                        @if($phaseEquipment->canCheckin())
                                            <button onclick="openCheckinModal({{ $phaseEquipment->id }})"
                                                    class="text-green-600 hover:text-green-900">返却</button>
                                        @endif

                                        @if($phaseEquipment->canCancel())
                                            <form method="POST" action="{{ route('phases.equipment.cancel', [$phase, $phaseEquipment]) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('本当にキャンセルしますか？')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900">キャンセル</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('phases.equipment.edit', [$phase, $phaseEquipment]) }}"
                                           class="text-indigo-600 hover:text-indigo-900">編集</a>

                                        @if($phaseEquipment->status !== 'checked_out')
                                            <form method="POST" action="{{ route('phases.equipment.destroy', [$phase, $phaseEquipment]) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('本当に削除しますか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <div class="mt-6">
                {{ $phaseEquipments->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">機材が登録されていません</h3>
                <p class="mt-1 text-sm text-gray-500">このフェーズで使用する機材を追加してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('phases.equipment.create', $phase) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            機材追加
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- 貸出モーダル -->
<div id="checkoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">機材貸出</h3>
            <form id="checkoutForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="checkout_date" class="block text-sm font-medium text-gray-700">貸出日</label>
                    <input type="date" name="checkout_date" id="checkout_date" required
                           value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="checkout_note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="checkout_note" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCheckoutModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        キャンセル
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                        貸出実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 返却モーダル -->
<div id="checkinModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">機材返却</h3>
            <form id="checkinForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="checkin_date" class="block text-sm font-medium text-gray-700">返却日</label>
                    <input type="date" name="checkin_date" id="checkin_date" required
                           value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="checkin_note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="checkin_note" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCheckinModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        キャンセル
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        返却実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCheckoutModal(phaseEquipmentId) {
    const form = document.getElementById('checkoutForm');
    form.action = `{{ route('phases.equipment.checkout', [$phase, '__ID__']) }}`.replace('__ID__', phaseEquipmentId);
    document.getElementById('checkoutModal').classList.remove('hidden');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
}

function openCheckinModal(phaseEquipmentId) {
    const form = document.getElementById('checkinForm');
    form.action = `{{ route('phases.equipment.checkin', [$phase, '__ID__']) }}`.replace('__ID__', phaseEquipmentId);
    document.getElementById('checkinModal').classList.remove('hidden');
}

function closeCheckinModal() {
    document.getElementById('checkinModal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.addEventListener('click', function(event) {
    const checkoutModal = document.getElementById('checkoutModal');
    const checkinModal = document.getElementById('checkinModal');

    if (event.target === checkoutModal) {
        closeCheckoutModal();
    }
    if (event.target === checkinModal) {
        closeCheckinModal();
    }
});
</script>
@endsection