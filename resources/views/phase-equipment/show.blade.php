@extends('layouts.master')

@section('title', '機材使用詳細')

@section('breadcrumb')
    > <a href="{{ route('phases.equipment.index', $phaseEquipment->phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phaseEquipment->phase->name }} - 機材管理</a>
    > <span class="text-gray-800">詳細</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600">{{ $phaseEquipment->phase->performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1">{{ $phaseEquipment->phase->name }} 機材使用詳細</h1>
        <p class="text-xs sm:text-sm text-gray-500 mb-2">
            {{ $phaseEquipment->phase->start_date->format('Y/m/d') }} ～ {{ $phaseEquipment->phase->end_date->format('Y/m/d') }}
            @if($phaseEquipment->phase->location)
                @ {{ $phaseEquipment->phase->location->name }}
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('phases.equipment.index', $phaseEquipment->phase) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>

            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                @if($phaseEquipment->status === 'reserved' || $phaseEquipment->status === 'checked_out')
                    <a href="{{ route('phases.equipment.edit', [$phaseEquipment->phase, $phaseEquipment]) }}"
                       class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        編集
                    </a>
                @endif

                @if($phaseEquipment->status === 'reserved')
                    <button onclick="window.checkoutEquipment({{ $phaseEquipment->id }}, {{ $phaseEquipment->phase->id }})"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-green-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-green-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="hidden sm:inline">出庫実行</span>
                        <span class="sm:hidden">出庫</span>
                    </button>
                @elseif($phaseEquipment->status === 'checked_out')
                    <button onclick="window.checkinEquipment({{ $phaseEquipment->id }}, {{ $phaseEquipment->phase->id }})"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-purple-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-purple-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <span class="hidden sm:inline">返却実行</span>
                        <span class="sm:hidden">返却</span>
                    </button>
                @endif

                @if(in_array($phaseEquipment->status, ['reserved', 'checked_out']))
                    <button onclick="window.deleteEquipment({{ $phaseEquipment->id }}, {{ $phaseEquipment->phase->id }})"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        削除
                    </button>
                @endif
            @endif
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- 基本情報 -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- ステータス -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">使用状況</h3>
                <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium mt-2
                    {{ $phaseEquipment->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $phaseEquipment->status === 'checked_out' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $phaseEquipment->status === 'checked_in' ? 'bg-blue-100 text-blue-800' : '' }}">
                    {{ $phaseEquipment->status_label }}
                </span>

                <div class="mt-3 sm:mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">登録者</dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs sm:text-sm font-medium text-gray-500">予約日時</dt>
                        <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->created_at->format('Y/m/d H:i') }}</dd>
                    </div>
                    @if($phaseEquipment->checked_out_at)
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">出庫担当者</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->checkedOutBy->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">出庫日時</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->checked_out_at->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                    @if($phaseEquipment->checked_in_at)
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">返却担当者</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->checkedInBy->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">返却日時</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->checked_in_at->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                </div>
                </div>
            </div>

            <!-- 機材情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">機材情報</h3>
                    <div class="text-xs sm:text-sm text-gray-500">
                        <div>{{ $phaseEquipment->equipment->subcategory->category->name ?? '-' }}</div>
                        <div>{{ $phaseEquipment->equipment->subcategory->name ?? '-' }}</div>
                    </div>

                    <div class="mt-2 sm:mt-3 flex flex-wrap items-center gap-1 sm:gap-2">
                        <span class="text-xs sm:text-sm text-gray-900 font-medium">{{ $phaseEquipment->equipment->name }}</span>
                        @if($phaseEquipment->equipment->company_number)
                            <span class="px-1.5 sm:px-2 py-0.5 sm:py-1 border border-gray-300 rounded text-xs text-gray-600">
                                {{ $phaseEquipment->equipment->company_number }}
                            </span>
                        @endif
                        <span class="text-xs sm:text-sm text-gray-600">使用数量 : {{ $phaseEquipment->quantity }}個</span>
                    </div>

                    @if($phaseEquipment->equipment->description)
                        <div class="mt-3 sm:mt-4">
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">機材説明</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->equipment->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- フェーズ情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">フェーズ情報</h3>
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">フェーズ名</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->phase->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">使用場所</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->phase->location->name ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">開始日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->phase->start_date->format('Y/m/d') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">終了日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $phaseEquipment->phase->end_date->format('Y/m/d') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            @if($phaseEquipment->note)
                <!-- 備考 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 py-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">備考</h3>
                        <p class="text-xs sm:text-sm text-gray-900 whitespace-pre-line">{{ $phaseEquipment->note }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- サイドバー -->
        <div class="space-y-4 sm:space-y-6">
            <!-- 移動履歴 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">移動履歴</h3>
                    <div class="space-y-2 sm:space-y-3">
                        @forelse($movements as $movement)
                            <div class="flex items-start space-x-2 sm:space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-6 w-6 sm:h-8 sm:w-8 rounded-full text-xs font-medium
                                        {{ $movement->movement_type === 'checkout' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $movement->movement_type === 'checkin' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ in_array($movement->movement_type, ['transfer', 'maintenance']) ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $movement->movement_type === 'repair_start' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $movement->movement_type === 'repair_complete' ? 'bg-purple-100 text-purple-800' : '' }}">
                                        @switch($movement->movement_type)
                                            @case('checkout')
                                                出
                                                @break
                                            @case('checkin')
                                                返
                                                @break
                                            @case('transfer')
                                                移
                                                @break
                                            @case('maintenance')
                                                保
                                                @break
                                            @case('repair_start')
                                                修
                                                @break
                                            @case('repair_complete')
                                                完
                                                @break
                                            @default
                                                他
                                        @endswitch
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-gray-900">
                                        {{ $movement->movement_type_label }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $movement->moved_at->format('Y/m/d H:i') }}
                                        @if($movement->moved_by)
                                            - {{ $movement->movedBy->name }}
                                        @endif
                                    </p>
                                    @if($movement->from_location)
                                        <p class="text-xs text-gray-500">
                                            {{ $movement->fromLocation->name }} → {{ $movement->toLocation->name ?? '不明' }}
                                        </p>
                                    @endif
                                    @if($movement->quantity && $movement->quantity != 1)
                                        <p class="text-xs text-gray-500">数量: {{ $movement->quantity }}個</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs sm:text-sm text-gray-500 text-center py-3 sm:py-4">移動履歴がありません</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 同一機材の他の使用状況 -->
            @if($otherUsages->count() > 0)
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 py-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">他の使用予定</h3>
                        <div class="space-y-2 sm:space-y-3">
                            @foreach($otherUsages as $usage)
                                @if($usage->status !== 'checked_in')
                                <div class="border border-gray-200 rounded-lg p-2 sm:p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                                                {{ $usage->phase->performance->title }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $usage->phase->name }} - {{ $usage->quantity }}個
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $usage->phase->start_date->format('m/d') }} ～ {{ $usage->phase->end_date->format('m/d') }}
                                            </p>
                                        </div>
                                        <span class="flex-shrink-0 inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium
                                            {{ $usage->status === 'reserved' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $usage->status === 'checked_out' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ $usage->status_label }}
                                        </span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- モーダル -->
<div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 sm:top-20 mx-2 sm:mx-auto p-4 sm:p-5 border w-auto sm:w-96 max-w-sm shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4" id="modalTitle"></h3>
            <form id="actionForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="DELETE" id="methodField">
                <div class="flex justify-end space-x-2 sm:space-x-3">
                    <button type="button" onclick="window.closeModal()"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        戻る
                    </button>
                    <button type="submit" id="confirmButton"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm rounded-md text-white">
                        実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// モーダル外クリックで閉じる機能のみ残す（JavaScriptクラスで処理済みの機能と重複しないため）
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('actionModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeModal();
            }
        });
    }
});
</script>
@endsection