@extends('layouts.master')

@section('title', 'フェーズ機材管理')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <a href="{{ route('phases.show', $phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->name }}</a>
    > <span class="text-gray-800">使用機材管理</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600">{{ $phase->performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1">{{ $phase->name }} 使用機材管理</h1>
        <p class="text-xs sm:text-sm text-gray-500 mb-2">
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
            @if($phase->location)
                @ {{ $phase->location->name }}
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('phases.show', $phase) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>

            <div class="flex-1"></div>

            @if(auth()->user()->role === 'editor' ||
                auth()->user()->role === 'admin' ||
                $phase->performance->staff->contains('user_id', auth()->id()))
                <a href="{{ route('phases.equipment.create', $phase) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    機材追加
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <!-- 一括ステータス変更ボタン -->
    @if(auth()->user()->role === 'editor' ||
        auth()->user()->role === 'admin' ||
        $phase->performance->staff->contains('user_id', auth()->id()))
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">一括ステータス変更</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-wrap gap-2 sm:gap-4">
                    @if($equipmentStats['reserved'] > 0)
                        <form method="POST" action="{{ route('phases.equipment.bulk-checkout-reserved', $phase) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    onclick="return confirm('予約済み{{ $equipmentStats['reserved'] }}件の機材を一括で出庫中に変更しますか？')"
                                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden sm:inline">予約済み → 出庫中</span>
                                <span class="sm:hidden">出庫</span>
                                ({{ $equipmentStats['reserved'] }})
                            </button>
                        </form>
                    @endif

                    @if($equipmentStats['checked_out'] > 0)
                        <button type="button"
                                onclick="handleBulkReturn({{ $phase->id }}, {{ $equipmentStats['checked_out'] }})"
                                class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-green-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-green-700">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="hidden sm:inline">出庫中 → 返却済み</span>
                            <span class="sm:hidden">返却</span>
                            ({{ $equipmentStats['checked_out'] }})
                        </button>

                        <button type="button"
                                onclick="openInheritanceModal()"
                                class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-purple-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-purple-700">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span class="hidden sm:inline">他フェーズへ</span>継承 ({{ $equipmentStats['checked_out'] }})
                        </button>
                    @else
                        <button type="button" disabled
                                class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-gray-300 border border-transparent text-xs sm:text-sm font-medium rounded-md text-gray-500 cursor-not-allowed">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            継承可能な機材なし
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- 統計サマリー -->
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
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
        <div class="p-2 sm:p-3 bg-blue-50 rounded-lg flex justify-between items-center">
            <span class="text-xs sm:text-sm text-blue-800">
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
            <a href="{{ route('phases.equipment.index', $phase) }}" class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 underline">解除</a>
        </div>
    @endif

    <!-- 機材一覧 -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        @if($phaseEquipments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材
                            </th>
                            <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12 sm:w-20">
                                数量
                            </th>
                            <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                ステータス
                            </th>
                            <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                出庫・返却
                            </th>
                            <th scope="col" class="px-2 sm:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                操作
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
                            <td class="px-3 sm:px-6 py-3 sm:py-4 max-w-[200px] sm:max-w-none">
                                <div class="text-xs text-gray-500 truncate" title="{{ $phaseEquipment->equipment->subcategory->category->name }} / {{ $phaseEquipment->equipment->subcategory->name }}">
                                    {{ $phaseEquipment->equipment->subcategory->category->name }} / {{ $phaseEquipment->equipment->subcategory->name }}
                                </div>
                                @if($phaseEquipment->equipment->manufacturer)
                                    <div class="text-xs text-gray-400 truncate">{{ $phaseEquipment->equipment->manufacturer }}</div>
                                @endif
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-xs sm:text-sm font-medium text-gray-900">{{ $phaseEquipment->equipment->name }}</span>
                                    @if($phaseEquipment->equipment->company_number)
                                        <span class="px-1.5 py-0.5 border border-gray-300 rounded text-xs text-gray-600">{{ $phaseEquipment->equipment->company_number }}</span>
                                    @endif
                                </div>
                                <!-- モバイル用ステータス表示 -->
                                <div class="sm:hidden mt-1">
                                    <x-status-badge :status="$phaseEquipment->status" type="equipment" />
                                </div>
                            </td>
                            <td class="px-2 sm:px-6 py-3 sm:py-4 text-center" onclick="event.stopPropagation()">
                                <div class="text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->quantity }}</div>
                                @if(auth()->user()->role === 'editor' ||
                                    auth()->user()->role === 'admin' ||
                                    $phase->performance->staff->contains('user_id', auth()->id()))
                                    @if($phaseEquipment->equipment->management_type === 'quantity')
                                        <a href="{{ route('phases.equipment.edit', [$phase, $phaseEquipment]) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-900" onclick="event.stopPropagation()">変更</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">
                                <x-status-badge :status="$phaseEquipment->status" type="equipment" />
                            </td>
                            <td class="px-2 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-500 hidden md:table-cell">
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
                            <td class="px-2 sm:px-6 py-3 sm:py-4 text-right" onclick="event.stopPropagation()">
                                <div class="flex flex-col items-end gap-0.5 sm:flex-row sm:justify-end sm:gap-2">
                                    @if(auth()->user()->role === 'editor' ||
                                        auth()->user()->role === 'admin' ||
                                        $phase->performance->staff->contains('user_id', auth()->id()))
                                        @if($phaseEquipment->canCheckout())
                                            <button type="button" onclick="event.stopPropagation(); var form = document.getElementById('checkoutForm'); if(form) { form.action = '/phases/{{ $phase->id }}/equipment/{{ $phaseEquipment->id }}/checkout'; document.getElementById('checkoutModal').classList.remove('hidden'); }"
                                                    class="text-xs sm:text-sm text-orange-600 hover:text-orange-900">出庫</button>
                                        @endif

                                        @if($phaseEquipment->canCheckin())
                                            <button type="button" onclick="event.stopPropagation(); handleEquipmentReturn({{ $phaseEquipment->id }}, {{ $phase->id }})"
                                                    class="text-xs sm:text-sm text-green-600 hover:text-green-900">返却</button>
                                        @endif

                                        @if(in_array($phaseEquipment->status, ['reserved', 'checked_out']))
                                            <form method="POST" action="{{ route('phases.equipment.destroy', [$phase, $phaseEquipment]) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('この機材の使用記録を削除しますか？')" onclick="event.stopPropagation()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs sm:text-sm text-red-600 hover:text-red-900">削除</button>
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
            <div class="px-3 sm:px-6 py-3 sm:py-4 border-t border-gray-200">
                {{ $phaseEquipments->links() }}
            </div>
        @else
            <div class="text-center py-8 sm:py-12">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900">該当機材なし</h3>
            </div>
        @endif
    </div>
</div>

<!-- 出庫モーダル -->
<div id="checkoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 sm:p-5 border w-80 sm:w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4">機材出庫</h3>
            <form id="checkoutForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="checkout_date" class="block text-xs sm:text-sm font-medium text-gray-700">出庫日</label>
                    <input type="date" name="checkout_date" id="checkout_date" required
                           value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex justify-end space-x-2 sm:space-x-3">
                    <button type="button" onclick="closeCheckoutModal()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        戻る
                    </button>
                    <button type="submit"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                        出庫実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 返却モーダル -->
<div id="checkinModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 sm:p-5 border w-80 sm:w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4">機材返却</h3>
            <form id="checkinForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="checkin_date" class="block text-xs sm:text-sm font-medium text-gray-700">返却日</label>
                    <input type="date" name="checkin_date" id="checkin_date" required
                           value="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- 返却先選択（location_id=92-94の機材のみ表示） -->
                <div id="locationSelectDiv" class="mb-4 hidden">
                    <label for="to_location_id" class="block text-xs sm:text-sm font-medium text-gray-700">返却先倉庫 <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex">
                        <input type="hidden" name="to_location_id" id="to_location_id">
                        <button type="button" id="selectLocationBtn" onclick="openLocationSelector()"
                                class="flex-1 bg-white border border-gray-300 rounded-md px-3 py-2 text-left text-sm shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <span id="selectedLocationText" class="text-gray-500">返却先倉庫を選択...</span>
                        </button>
                    </div>
                    <div id="locationError" class="text-red-500 text-xs mt-1 hidden">返却先倉庫を選択してください</div>
                </div>

                <div class="flex justify-end space-x-2 sm:space-x-3">
                    <button type="button" onclick="closeCheckinModal()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        戻る
                    </button>
                    <button type="submit"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
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

<!-- 機材継承モーダル -->
<div id="inheritanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 sm:top-20 mx-auto p-4 sm:p-6 border w-full max-w-4xl shadow-lg rounded-md bg-white mx-2 sm:mx-auto">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h3 id="inheritanceModalTitle" class="text-base sm:text-lg font-medium text-gray-900">継承先フェーズを選択</h3>
                <button type="button" onclick="closeInheritanceModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- ステップ1: 継承先選択 -->
            <div id="step1" class="step-content">
                <div class="space-y-4">
                    <!-- 同一公演内フェーズ -->
                    <div>
                        <h5 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">同一公演内のフェーズ</h5>
                        <div id="samePerformancePhases" class="space-y-2">
                            <!-- 動的に追加される -->
                        </div>
                    </div>

                    <!-- 他公演フェーズ -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="text-xs sm:text-sm font-medium text-gray-700">他公演のフェーズ</h5>
                            <button type="button" id="toggleOtherPerformances" onclick="toggleOtherPerformances()"
                                    class="text-xs sm:text-sm text-blue-600 hover:text-blue-800">表示</button>
                        </div>
                        <div id="otherPerformancePhases" class="space-y-2 hidden">
                            <!-- 動的に追加される -->
                        </div>
                    </div>
                </div>

                <div class="mt-4 sm:mt-6 flex justify-end space-x-2 sm:space-x-3">
                    <button type="button" onclick="closeInheritanceModal()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        キャンセル
                    </button>
                    <button type="button" id="nextToStep2" onclick="goToStep2()" disabled
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        次へ
                    </button>
                </div>
            </div>

            <!-- ステップ2: 継承確認 -->
            <div id="step2" class="step-content hidden">
                <div id="inheritancePreview" class="mb-4 sm:mb-6">
                    <!-- 動的に追加される -->
                </div>

                <div class="mt-4 sm:mt-6 flex justify-end space-x-2 sm:space-x-3">
                    <button type="button" onclick="goToStep1()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        戻る
                    </button>
                    <button type="button" id="executeInheritance" onclick="executeInheritance()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700">
                        継承実行
                    </button>
                </div>
            </div>

            <!-- 実行中表示 -->
            <div id="executingStep" class="step-content hidden">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <p class="mt-2 text-xs sm:text-sm text-gray-600">機材を継承中...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
}

async function openCheckinModal(phaseEquipmentId) {
    const form = document.getElementById('checkinForm');
    form.action = `/phases/{{ $phase->id }}/equipment/${phaseEquipmentId}/checkin`;

    // フォームをリセット
    form.reset();
    document.getElementById('checkin_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('to_location_id').value = '';
    document.getElementById('selectedLocationText').textContent = '返却先倉庫を選択...';
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
        document.getElementById('locationSelectDiv').classList.add('hidden');
    }

    document.getElementById('checkinModal').classList.remove('hidden');
}

async function getEquipmentData(phaseEquipmentId) {
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

async function handleEquipmentReturn(phaseEquipmentId, phaseId) {
    try {
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

        if (equipment.location_id >= 92 && equipment.location_id <= 94) {
            sessionStorage.setItem('returnEquipmentData', JSON.stringify({
                phaseEquipmentId: phaseEquipmentId,
                phaseId: phaseId,
                equipmentId: equipment.id,
                equipmentName: equipment.name,
                companyNumber: equipment.company_number,
                locationId: equipment.location_id
            }));
            window.location.href = '/equipment-transfer/return-select';
            return;
        }

        openCheckinModal(phaseEquipmentId);

    } catch (error) {
        console.error('Equipment info fetch error:', error);
        alert('機材情報の取得中にエラーが発生しました: ' + error.message);
    }
}

async function handleBulkReturn(phaseId, equipmentCount) {
    if (!confirm(`出庫中の機材 ${equipmentCount}件を一括で返却済みに変更しますか？`)) {
        return;
    }

    try {
        const checkedOutEquipments = await getCheckedOutEquipments(phaseId);

        const requiresLocationSelection = checkedOutEquipments.filter(item =>
            item.equipment.location_id >= 92 && item.equipment.location_id <= 94
        );
        const normalEquipments = checkedOutEquipments.filter(item =>
            item.equipment.location_id < 92 || item.equipment.location_id > 94
        );

        if (normalEquipments.length > 0) {
            const normalEquipmentIds = normalEquipments.map(item => item.id);

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

        if (requiresLocationSelection.length > 0) {
            const bulkReturnData = requiresLocationSelection.map(item => ({
                phaseEquipmentId: item.id,
                phaseId: phaseId,
                equipmentId: item.equipment.id,
                equipmentName: item.equipment.name,
                companyNumber: item.equipment.company_number,
                locationId: item.equipment.location_id
            }));

            sessionStorage.setItem('bulkReturnData', JSON.stringify(bulkReturnData));
            window.location.href = '/equipment-transfer/return-select';
            return;
        }

        if (normalEquipments.length > 0 && requiresLocationSelection.length === 0) {
            window.location.reload();
            return;
        }

        alert('返却対象の機材がありません。');

    } catch (error) {
        console.error('Bulk return error:', error);
        alert('一括返却処理中にエラーが発生しました: ' + error.message);
    }
}

async function getCheckedOutEquipments(phaseId) {
    const url = `/phases/${phaseId}/equipment/checked-out-equipments`;

    const response = await fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error('出庫中機材の取得に失敗しました');
    }

    const data = await response.json();
    return data.equipments || [];
}

function closeCheckinModal() {
    document.getElementById('checkinModal').classList.add('hidden');
}

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

function selectReturnLocation(location) {
    if (location) {
        document.getElementById('to_location_id').value = location.id;
        document.getElementById('selectedLocationText').textContent = location.name;
        document.getElementById('locationError').classList.add('hidden');
    }
}

document.getElementById('checkinForm').addEventListener('submit', function(e) {
    const locationSelectDiv = document.getElementById('locationSelectDiv');
    const toLocationId = document.getElementById('to_location_id').value;

    if (!locationSelectDiv.classList.contains('hidden') && !toLocationId) {
        e.preventDefault();
        document.getElementById('locationError').classList.remove('hidden');
        return false;
    }
});

document.addEventListener('click', function(event) {
    const checkoutModal = document.getElementById('checkoutModal');
    const checkinModal = document.getElementById('checkinModal');
    const inheritanceModal = document.getElementById('inheritanceModal');

    if (event.target === checkoutModal) {
        closeCheckoutModal();
    }
    if (event.target === checkinModal) {
        closeCheckinModal();
    }
    if (event.target === inheritanceModal) {
        closeInheritanceModal();
    }
});

let inheritanceData = {
    selectedTargetPhaseId: null,
    targetPhases: null,
    previewData: null
};

async function openInheritanceModal() {
    try {
        const response = await fetch(`/phases/{{ $phase->id }}/inheritable-target-phases`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            alert(errorData.error || '継承先フェーズの取得に失敗しました');
            return;
        }

        inheritanceData.targetPhases = await response.json();
        document.getElementById('inheritanceModal').classList.remove('hidden');
        showStep(1);
        renderPhaseList();

    } catch (error) {
        console.error('Inheritance modal error:', error);
        alert('継承モーダルの表示中にエラーが発生しました');
    }
}

function closeInheritanceModal() {
    document.getElementById('inheritanceModal').classList.add('hidden');
    inheritanceData.selectedTargetPhaseId = null;
    inheritanceData.previewData = null;
    showStep(1);
}

function showStep(stepIdentifier) {
    document.querySelectorAll('.step-content').forEach(step => {
        step.classList.add('hidden');
    });

    let elementId;
    if (stepIdentifier === 'executingStep') {
        elementId = 'executingStep';
    } else {
        elementId = `step${stepIdentifier}`;
    }

    document.getElementById(elementId).classList.remove('hidden');

    // モーダルタイトルを更新
    const titleElement = document.getElementById('inheritanceModalTitle');
    if (titleElement) {
        if (stepIdentifier === 1) {
            titleElement.textContent = '継承先フェーズを選択';
        } else if (stepIdentifier === 2) {
            titleElement.textContent = '継承内容の確認';
        }
    }
}

function renderPhaseList() {
    const samePerformanceContainer = document.getElementById('samePerformancePhases');
    const otherPerformanceContainer = document.getElementById('otherPerformancePhases');

    samePerformanceContainer.innerHTML = '';
    if (inheritanceData.targetPhases.same_performance.length > 0) {
        inheritanceData.targetPhases.same_performance.forEach(phase => {
            const phaseElement = createPhaseElement(phase);
            samePerformanceContainer.appendChild(phaseElement);
        });
    } else {
        samePerformanceContainer.innerHTML = '<p class="text-xs sm:text-sm text-gray-500">継承可能なフェーズがありません</p>';
    }

    otherPerformanceContainer.innerHTML = '';
}

function createPhaseElement(phase) {
    const div = document.createElement('div');
    div.className = 'border rounded-lg p-2 sm:p-3 cursor-pointer hover:bg-gray-50 transition-colors';
    div.setAttribute('data-phase-id', phase.id);

    const startDate = formatDateForDisplay(phase.start_date);
    const endDate = formatDateForDisplay(phase.end_date);
    const statusColor = getPhaseStatusColor(phase.phase_status);

    div.innerHTML = `
        <div class="flex items-center">
            <input type="radio" name="target_phase" value="${phase.id}" class="mr-2 sm:mr-3 flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="text-xs sm:text-sm text-gray-600 truncate">${phase.performance.title}</div>
                <div class="text-sm sm:text-base font-medium text-gray-900">${phase.name}</div>
                <div class="flex flex-wrap items-center gap-1 text-xs text-gray-400">
                    <span>${startDate} 〜 ${endDate}</span>
                    <span class="px-1.5 py-0.5 bg-${statusColor}-100 text-${statusColor}-800 rounded">
                        ${getPhaseStatusLabel(phase.phase_status)}
                    </span>
                </div>
            </div>
        </div>
    `;

    div.addEventListener('click', () => selectTargetPhase(phase.id));

    return div;
}

function getPhaseStatusColor(status) {
    const colors = {
        'upcoming': 'blue',
        'in_progress': 'green',
        'completed': 'gray'
    };
    return colors[status] || 'gray';
}

function getPhaseStatusLabel(status) {
    const labels = {
        'upcoming': '予定',
        'in_progress': '進行中',
        'completed': '完了'
    };
    return labels[status] || status;
}

function selectTargetPhase(phaseId) {
    inheritanceData.selectedTargetPhaseId = phaseId;

    document.querySelectorAll('input[name="target_phase"]').forEach(radio => {
        radio.checked = radio.value == phaseId;
    });

    document.getElementById('nextToStep2').disabled = false;
}

async function toggleOtherPerformances() {
    const container = document.getElementById('otherPerformancePhases');
    const button = document.getElementById('toggleOtherPerformances');

    if (container.classList.contains('hidden')) {
        if (!inheritanceData.targetPhases.other_performances) {
            try {
                const response = await fetch(`/phases/{{ $phase->id }}/inheritable-target-phases?include_other_performances=true`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    inheritanceData.targetPhases.other_performances = data.other_performances;
                }
            } catch (error) {
                console.error('Error fetching other performances:', error);
            }
        }

        container.innerHTML = '';
        if (inheritanceData.targetPhases.other_performances && inheritanceData.targetPhases.other_performances.length > 0) {
            inheritanceData.targetPhases.other_performances.forEach(phase => {
                const phaseElement = createPhaseElement(phase);
                container.appendChild(phaseElement);
            });
        } else {
            container.innerHTML = '<p class="text-xs sm:text-sm text-gray-500">継承可能な他公演フェーズがありません</p>';
        }

        container.classList.remove('hidden');
        button.textContent = '非表示';
    } else {
        container.classList.add('hidden');
        button.textContent = '表示';
    }
}

async function goToStep2() {
    if (!inheritanceData.selectedTargetPhaseId) {
        alert('継承先フェーズを選択してください');
        return;
    }

    try {
        const response = await fetch(`/phases/{{ $phase->id }}/inheritance-preview?target_phase_id=${inheritanceData.selectedTargetPhaseId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            const errorData = await response.json();
            alert(errorData.error || '継承プレビューの取得に失敗しました');
            return;
        }

        inheritanceData.previewData = await response.json();
        renderInheritancePreview();
        showStep(2);

    } catch (error) {
        console.error('Step 2 error:', error);
        alert('継承プレビューの取得中にエラーが発生しました');
    }
}

function goToStep1() {
    showStep(1);
}

function formatDateForDisplay(dateString) {
    if (!dateString) return '';
    // ISO8601形式 (2022-03-20T15:00:00.000000Z) またはY-m-d形式を処理
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function renderInheritancePreview() {
    const container = document.getElementById('inheritancePreview');
    const data = inheritanceData.previewData;

    const targetStartDate = formatDateForDisplay(data.target_phase.start_date);
    const targetEndDate = formatDateForDisplay(data.target_phase.end_date);

    container.innerHTML = `
        <div class="bg-blue-50 rounded-lg p-3 sm:p-4 mb-4">
            <h5 class="text-sm font-medium text-blue-900">継承情報</h5>
            <div class="text-xs sm:text-sm text-blue-700 space-y-1">
                <div><strong>継承元:</strong> ${data.source_phase.performance_title} - ${data.source_phase.name}</div>
                <div><strong>継承先:</strong> ${data.target_phase.performance_title} - ${data.target_phase.name}</div>
                <div><strong>期間:</strong> ${targetStartDate} 〜 ${targetEndDate}</div>
            </div>
        </div>

        <div class="overflow-auto -mx-4 sm:mx-0 max-h-[50vh] border border-gray-200 rounded-md">
            <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">機材</th>
                        <th class="px-2 sm:px-6 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12 sm:w-20 bg-gray-50">数量</th>
                        <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell bg-gray-50">継承可否</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${data.equipments.map(equipment => `
                        <tr class="${equipment.can_inherit ? '' : 'bg-red-50'}">
                            <td class="px-2 sm:px-6 py-2 sm:py-4 max-w-[180px] sm:max-w-none">
                                <div class="text-xs text-gray-500 truncate">${equipment.category} / ${equipment.subcategory}</div>
                                ${equipment.manufacturer ? `<div class="text-xs text-gray-400 truncate">${equipment.manufacturer}</div>` : ''}
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-xs sm:text-sm font-medium text-gray-900">${equipment.equipment_name}</span>
                                    ${equipment.company_number ? `<span class="px-1.5 py-0.5 border border-gray-300 rounded text-xs text-gray-600">${equipment.company_number}</span>` : ''}
                                </div>
                                <!-- モバイル用: 継承可否表示 -->
                                <div class="sm:hidden mt-1">
                                    ${equipment.can_inherit ?
                                        '<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">継承可能</span>' :
                                        `<span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">継承不可</span>
                                         <span class="text-xs text-red-600 ml-1">${getConflictReasonText(equipment.conflict_reason)}</span>`
                                    }
                                </div>
                            </td>
                            <td class="px-2 sm:px-6 py-2 sm:py-4 text-center">
                                <div class="text-xs sm:text-sm text-gray-900">${equipment.quantity}</div>
                                ${equipment.management_type === 'quantity' && equipment.available_quantity !== null ?
                                    `<div class="text-xs text-gray-500">可: ${equipment.available_quantity}</div>` : ''}
                            </td>
                            <td class="px-2 sm:px-6 py-2 sm:py-4 hidden sm:table-cell">
                                ${equipment.can_inherit ?
                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">継承可能</span>' :
                                    `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">継承不可</span>
                                     <div class="text-xs text-red-600 mt-1">${getConflictReasonText(equipment.conflict_reason)}</div>`
                                }
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function getConflictReasonText(reason) {
    const reasons = {
        'period_overlap': '期間重複',
        'insufficient_quantity': '数量不足'
    };
    return reasons[reason] || reason;
}

async function executeInheritance() {
    if (!inheritanceData.selectedTargetPhaseId || !inheritanceData.previewData) {
        alert('継承データが不正です');
        return;
    }

    showStep('executingStep');

    try {
        const response = await fetch(`/phases/{{ $phase->id }}/inherit-to`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                target_phase_id: inheritanceData.selectedTargetPhaseId,
                inherit_type: 'all'
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            alert(`継承が完了しました。${result.inherited_count}件の機材を継承しました。`);
            if (result.warnings && result.warnings.length > 0) {
                const warningMessages = result.warnings.map(w => w.message).join('\n');
                alert(`警告:\n${warningMessages}`);
            }

            closeInheritanceModal();
            window.location.reload();
        } else {
            throw new Error(result.error || '継承処理に失敗しました');
        }

    } catch (error) {
        console.error('Inheritance execution error:', error);
        alert('継承実行中にエラーが発生しました: ' + error.message);
        showStep(2);
    }
}
</script>
<script src="{{ asset('js/equipment-management.js') }}"></script>
@endpush

@endsection
