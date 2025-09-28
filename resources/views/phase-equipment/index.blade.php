@extends('layouts.app')

@section('title', 'フェーズ機材管理')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <span class="text-gray-800">【{{ $phase->name }}】使用機材管理</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $phase->performance->title }}【{{ $phase->name }}】使用機材管理</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
            @if($phase->location)
                @ {{ $phase->location->name }}
            @endif
        </p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('phases.show', $phase) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            フェーズ詳細に戻る
        </a>
        @if(auth()->user()->role === 'editor' ||
            auth()->user()->role === 'admin' ||
            $phase->performance->staff->contains('user_id', auth()->id()))
            <a href="{{ route('phases.equipment.create', $phase) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                機材追加
            </a>
        @endif
    </div>
@endsection

@section('content')
<!-- 統計サマリー -->
<div class="mb-6">
    <!-- 一括ステータス変更ボタン -->
    @if(auth()->user()->role === 'editor' ||
        auth()->user()->role === 'admin' ||
        $phase->performance->staff->contains('user_id', auth()->id()))
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">一括ステータス変更</h3>
            <div class="flex flex-wrap gap-4">
                @if($equipmentStats['reserved'] > 0)
                    <form method="POST" action="{{ route('phases.equipment.bulk-checkout-reserved', $phase) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('予約済み{{ $equipmentStats['reserved'] }}件の機材を一括で出庫中に変更しますか？')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            予約済み → 出庫中 ({{ $equipmentStats['reserved'] }}件)
                        </button>
                    </form>
                @endif

                @if($equipmentStats['checked_out'] > 0)
                    <button type="button"
                            onclick="handleBulkReturn({{ $phase->id }}, {{ $equipmentStats['checked_out'] }})"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-green-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        出庫中 → 返却済み ({{ $equipmentStats['checked_out'] }}件)
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-equipment-stats-card
            title="予約済み"
            :count="$equipmentStats['reserved']"
            color="blue"
            :href="route('phases.equipment.index', [$phase, 'status' => 'reserved'])"
            :active="request('status') === 'reserved'"
        >
            <x-slot name="icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </x-slot>
        </x-equipment-stats-card>

        <x-equipment-stats-card
            title="出庫中"
            :count="$equipmentStats['checked_out']"
            color="orange"
            :href="route('phases.equipment.index', [$phase, 'status' => 'checked_out'])"
            :active="request('status') === 'checked_out'"
        >
            <x-slot name="icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </x-slot>
        </x-equipment-stats-card>

        <x-equipment-stats-card
            title="返却済み"
            :count="$equipmentStats['checked_in']"
            color="green"
            :href="route('phases.equipment.index', [$phase, 'status' => 'checked_in'])"
            :active="request('status') === 'checked_in'"
        >
            <x-slot name="icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </x-slot>
        </x-equipment-stats-card>
    </div>

    <!-- フィルター状態の表示 -->
    @if(request('status'))
        <div class="mt-4 p-3 bg-blue-50 rounded-lg flex justify-between items-center">
            <span class="text-sm text-blue-800">
                フィルター中:
                @switch(request('status'))
                    @case('reserved')
                        予約済み
                        @break
                    @case('checked_out')
                        出庫中
                        @break
                    @case('checked_in')
                        返却済み
                        @break
                    @default
                        {{ request('status') }}
                @endswitch
            </span>
            <a href="{{ route('phases.equipment.index', $phase) }}" class="text-sm text-blue-600 hover:text-blue-800 underline">フィルターを解除</a>
        </div>
    @endif
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
                                カテゴリ
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材名
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                数量
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ステータス
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                出庫・返却
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                アクション
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($phaseEquipments as $phaseEquipment)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            onclick="window.location='{{ route('phases.equipment.show', [$phase, $phaseEquipment]) }}'"
                            data-phase-equipment-id="{{ $phaseEquipment->id }}"
                            data-equipment-location="{{ $phaseEquipment->equipment->location_id }}"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $phaseEquipment->equipment->subcategory->category->name }}</div>
                                <div class="text-sm text-gray-500">{{ $phaseEquipment->equipment->subcategory->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        @if($phaseEquipment->equipment->manufacturer)
                                            <div class="text-xs text-gray-500 mb-1">
                                                {{ $phaseEquipment->equipment->manufacturer }}
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $phaseEquipment->equipment->name }}
                                            </div>
                                            @if($phaseEquipment->equipment->company_number)
                                                <div class="px-2 py-1 border border-gray-300 rounded text-xs text-gray-600">
                                                    {{ $phaseEquipment->equipment->company_number }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                <div class="text-sm text-gray-900">{{ $phaseEquipment->quantity }}</div>
                                @if(auth()->user()->role === 'editor' ||
                                    auth()->user()->role === 'admin' ||
                                    $phase->performance->staff->contains('user_id', auth()->id()))
                                    @if($phaseEquipment->equipment->management_type === 'quantity')
                                        <a href="{{ route('phases.equipment.edit', [$phase, $phaseEquipment]) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-900" onclick="event.stopPropagation()">数量変更</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-status-badge :status="$phaseEquipment->status" type="equipment" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($phaseEquipment->checkout_date)
                                    <div>出庫: {{ $phaseEquipment->checkout_date->format('Y/m/d') }}</div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" onclick="event.stopPropagation()">
                                <div class="flex space-x-2">
                                    @if(auth()->user()->role === 'editor' ||
                                        auth()->user()->role === 'admin' ||
                                        $phase->performance->staff->contains('user_id', auth()->id()))
                                        @if($phaseEquipment->canCheckout() || $phaseEquipment->status === 'checked_in')
                                            <button onclick="event.stopPropagation(); openCheckoutModal({{ $phaseEquipment->id }})"
                                                    class="text-orange-600 hover:text-orange-900">出庫</button>
                                        @endif

                                        @if($phaseEquipment->canCheckin())
                                            <button onclick="event.stopPropagation(); handleEquipmentReturn({{ $phaseEquipment->id }}, {{ $phase->id }})"
                                                    class="text-green-600 hover:text-green-900">返却</button>
                                        @endif

                                        @if(in_array($phaseEquipment->status, ['reserved', 'checked_out']))
                                            <form method="POST" action="{{ route('phases.equipment.destroy', [$phase, $phaseEquipment]) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('この機材の使用記録を削除しますか？')" onclick="event.stopPropagation()">
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
                <h3 class="mt-2 text-sm font-medium text-gray-900">該当機材なし</h3>
            </div>
        @endif
    </div>
</div>

<!-- 出庫モーダル -->
<div id="checkoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">機材出庫</h3>
            <form id="checkoutForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="checkout_date" class="block text-sm font-medium text-gray-700">出庫日</label>
                    <input type="date" name="checkout_date" id="checkout_date" required
                           value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCheckoutModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        戻る
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                        出庫実行
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

                <!-- 返却先選択（location_id=92-94の機材のみ表示） -->
                <div id="locationSelectDiv" class="mb-4 hidden">
                    <label for="to_location_id" class="block text-sm font-medium text-gray-700">返却先倉庫 <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex">
                        <input type="hidden" name="to_location_id" id="to_location_id">
                        <button type="button" id="selectLocationBtn" onclick="openLocationSelector()"
                                class="flex-1 bg-white border border-gray-300 rounded-md px-3 py-2 text-left shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <span id="selectedLocationText" class="text-gray-500">返却先倉庫を選択してください...</span>
                        </button>
                    </div>
                    <div id="locationError" class="text-red-500 text-sm mt-1 hidden">返却先倉庫を選択してください</div>
                </div>


                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCheckinModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        戻る
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

<!-- 場所選択モーダル -->
<x-location-selector-modal
    id="checkin-location-modal"
    title="返却先倉庫選択"
    placeholder="返却先倉庫を選択してください..."
    confirm-text="選択"
    :required="true"
/>

<script>
function openCheckoutModal(phaseEquipmentId) {
    const form = document.getElementById('checkoutForm');
    form.action = `{{ route('phases.equipment.checkout', [$phase, '__ID__']) }}`.replace('__ID__', phaseEquipmentId);
    document.getElementById('checkoutModal').classList.remove('hidden');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
}

async function openCheckinModal(phaseEquipmentId) {
    const form = document.getElementById('checkinForm');
    form.action = `{{ route('phases.equipment.checkin', [$phase, '__ID__']) }}`.replace('__ID__', phaseEquipmentId);

    // フォームをリセット
    form.reset();
    document.getElementById('checkin_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('to_location_id').value = '';
    document.getElementById('selectedLocationText').textContent = '返却先倉庫を選択してください...';
    document.getElementById('locationError').classList.add('hidden');

    // 機材情報を取得してlocation_id=92-94かチェック
    try {
        const equipmentData = await getEquipmentData(phaseEquipmentId);
        const requiresLocationSelection = equipmentData && (equipmentData.location_id >= 92 && equipmentData.location_id <= 94);

        const locationSelectDiv = document.getElementById('locationSelectDiv');
        if (requiresLocationSelection) {
            locationSelectDiv.classList.remove('hidden');
        } else {
            locationSelectDiv.classList.add('hidden');
        }
    } catch (error) {
        console.error('機材情報の取得に失敗:', error);
        // エラーの場合は安全側に倒して場所選択を非表示
        document.getElementById('locationSelectDiv').classList.add('hidden');
    }

    document.getElementById('checkinModal').classList.remove('hidden');
}

// 機材データを取得する関数（簡易実装）
async function getEquipmentData(phaseEquipmentId) {
    // 既存のテーブル行から機材情報を取得
    const equipmentRows = document.querySelectorAll('[data-equipment-location]');
    for (const row of equipmentRows) {
        const rowPhaseEquipmentId = row.getAttribute('data-phase-equipment-id');
        if (rowPhaseEquipmentId == phaseEquipmentId) {
            return {
                location_id: parseInt(row.getAttribute('data-equipment-location'))
            };
        }
    }
    return null;
}

// 統一された返却処理関数（show.blade.phpのcheckinEquipmentと同様）
async function handleEquipmentReturn(phaseEquipmentId, phaseId) {
    try {
        // 機材情報をAPIから取得してlocation_idをチェック
        const response = await fetch(`/phases/${phaseId}/equipment/${phaseEquipmentId}/equipment-info`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('機材情報の取得に失敗しました');
        }

        const data = await response.json();
        const equipment = data.equipment;

        // location_id が 92-94 の場合は返却先選択画面へ遷移
        if (equipment.location_id >= 92 && equipment.location_id <= 94) {
            // 機材情報をセッションストレージに保存
            sessionStorage.setItem('returnEquipmentData', JSON.stringify({
                phaseEquipmentId: phaseEquipmentId,
                phaseId: phaseId,
                equipmentId: equipment.id,
                equipmentName: equipment.name,
                companyNumber: equipment.company_number,
                locationId: equipment.location_id
            }));

            // 返却先選択画面へ遷移
            window.location.href = '/equipment-transfer/return-select';
            return;
        }

        // 通常の返却処理（location_id が 92-94 以外）
        // 既存のモーダルを開く
        openCheckinModal(phaseEquipmentId);

    } catch (error) {
        console.error('Equipment info fetch error:', error);
        alert('機材情報の取得中にエラーが発生しました: ' + error.message);
    }
}

// 一括返却処理（92-94の機材チェック含む）
async function handleBulkReturn(phaseId, equipmentCount) {
    if (!confirm(`出庫中の機材 ${equipmentCount}件を一括で返却済みに変更しますか？`)) {
        return;
    }

    try {
        // 出庫中のPhaseEquipmentと機材情報を取得
        const checkedOutEquipments = await getCheckedOutEquipments(phaseId);

        // location_id 92-94の機材と通常機材を分類
        const requiresLocationSelection = checkedOutEquipments.filter(item =>
            item.equipment.location_id >= 92 && item.equipment.location_id <= 94
        );
        const normalEquipments = checkedOutEquipments.filter(item =>
            item.equipment.location_id < 92 || item.equipment.location_id > 94
        );

        // まず通常機材（92-94以外）を自動返却
        if (normalEquipments.length > 0) {
            const normalEquipmentIds = normalEquipments.map(item => item.id);

            // 通常機材の自動返却API呼び出し
            const response = await fetch(`/phases/${phaseId}/equipment/bulk-checkin`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    equipment_ids: normalEquipmentIds
                })
            });

            if (!response.ok) {
                throw new Error('通常機材の返却処理に失敗しました');
            }
        }

        // 92-94の機材がある場合は返却先選択画面に遷移
        if (requiresLocationSelection.length > 0) {
            const bulkReturnData = requiresLocationSelection.map(item => ({
                phaseEquipmentId: item.id,
                phaseId: phaseId,
                equipmentId: item.equipment.id,
                equipmentName: item.equipment.name,
                companyNumber: item.equipment.company_number,
                locationId: item.equipment.location_id
            }));

            // 92-94機材のみを返却先選択画面に送る
            sessionStorage.setItem('bulkReturnData', JSON.stringify(bulkReturnData));

            // 返却先選択画面へ遷移
            window.location.href = '/equipment-transfer/return-select';
            return;
        }

        // 92-94機材がなく、通常機材のみの場合はページをリロード
        if (normalEquipments.length > 0 && requiresLocationSelection.length === 0) {
            window.location.reload();
            return;
        }

        // どちらもない場合（すべて返却済み）
        alert('返却対象の機材がありません。');

    } catch (error) {
        console.error('Bulk return error:', error);
        alert('一括返却処理中にエラーが発生しました: ' + error.message);
    }
}

// 出庫中のPhaseEquipmentと機材情報を取得
async function getCheckedOutEquipments(phaseId) {
    const url = `/phases/${phaseId}/equipment/checked-out-equipments`;
    console.log('Requesting URL:', url);

    const response = await fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    });

    console.log('Response status:', response.status);
    console.log('Response ok:', response.ok);

    if (!response.ok) {
        const responseText = await response.text();
        console.error('Response text:', responseText);
        throw new Error('出庫中機材の取得に失敗しました');
    }

    const data = await response.json();
    console.log('Response data:', data);
    return data.equipments || [];
}

function closeCheckinModal() {
    document.getElementById('checkinModal').classList.add('hidden');
}

// 場所選択モーダルを開く
function openLocationSelector() {
    const modalEvent = new CustomEvent('open-checkin-location-modal', {
        detail: {
            onConfirm: (location) => {
                selectReturnLocation(location);
            }
        }
    });
    window.dispatchEvent(modalEvent);
}

// 返却先倉庫を選択
function selectReturnLocation(location) {
    if (location) {
        document.getElementById('to_location_id').value = location.id;
        document.getElementById('selectedLocationText').textContent = location.name;
        document.getElementById('locationError').classList.add('hidden');
    }
}

// フォーム送信時のバリデーション
document.getElementById('checkinForm').addEventListener('submit', function(e) {
    const locationSelectDiv = document.getElementById('locationSelectDiv');
    const toLocationId = document.getElementById('to_location_id').value;

    // location_id=92-94の機材で返却先が選択されていない場合
    if (!locationSelectDiv.classList.contains('hidden') && !toLocationId) {
        e.preventDefault();
        document.getElementById('locationError').classList.remove('hidden');
        return false;
    }
});

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
<script src="{{ asset('js/equipment-management.js') }}"></script>
@endsection
