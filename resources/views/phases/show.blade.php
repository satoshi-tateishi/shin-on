@extends('layouts.app')

@section('title', 'フェーズ詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800">{{ $performance->title }}</a>
    > <span class="text-gray-800">【{{ $phase->name }}】フェーズ詳細</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $performance->title }}【{{ $phase->name }}】フェーズ詳細</h1>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('performances.show', $performance) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
        <a href="{{ route('phases.export-pdf', $phase) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
            </svg>
            PDF出力
        </a>
        @if(auth()->user()->role === 'editor' ||
            auth()->user()->role === 'admin' ||
            $performance->staff->contains('user_id', auth()->id()))
            <a href="{{ route('phases.edit', $phase) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                編集
            </a>
        @endif
        @if(auth()->user()->role === 'admin')
            <form method="POST" action="{{ route('phases.destroy', $phase) }}" id="deletePhaseForm" class="inline">
                @csrf
                @method('DELETE')
                <button type="button" onclick="showDeleteConfirmation()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    このフェーズを削除
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<!-- フェーズ基本情報 -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">基本情報</h3>

        <div class="space-y-4">
            <!-- ステータス -->
            <div>
                <dd class="mt-1">
                    @php
                        $statusColors = [
                            'upcoming' => 'bg-gray-100 text-gray-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'completed' => 'bg-green-100 text-green-800',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$phase->phase_status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $phase->phase_status_label }}
                    </span>
                </dd>
            </div>

            <!-- 状態 -->
            <div>
                <dd class="mt-1">
                    @if($phase->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            有効
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            無効
                        </span>
                    @endif
                </dd>
            </div>

            <!-- フェーズ名 -->
            <div>
                <dt class="text-sm font-medium text-gray-500">フェーズ名</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $phase->name }}</dd>
            </div>

            <!-- 期間 -->
            <div>
                <dt class="text-sm font-medium text-gray-500">期間</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($phase->start_date && $phase->end_date)
                        <div>{{ $phase->start_date->format('Y年m月d日') }} 〜 {{ $phase->end_date->format('Y年m月d日') }}</div>
                        <div class="text-xs text-gray-500">({{ $phase->duration_days }}日間)</div>
                    @elseif($phase->start_date)
                        <div>開始: {{ $phase->start_date->format('Y年m月d日') }}</div>
                        <div class="text-xs text-gray-500">終了日未設定</div>
                    @elseif($phase->end_date)
                        <div>終了: {{ $phase->end_date->format('Y年m月d日') }}</div>
                        <div class="text-xs text-gray-500">開始日未設定</div>
                    @else
                        <span class="text-gray-400">期間未設定</span>
                    @endif
                </dd>
            </div>

            <!-- 場所 -->
            <div>
                <dt class="text-sm font-medium text-gray-500">場所</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($phase->location)
                        {{ $phase->location->name }}
                    @else
                        <span class="text-gray-400">未設定</span>
                    @endif
                </dd>
            </div>

            <!-- 備考 -->
            @if($phase->note)
            <div>
                <dt class="text-sm font-medium text-gray-500">備考</dt>
                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $phase->note }}</dd>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 使用機材 -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">使用機材</h3>
            @if(auth()->user()->role === 'editor' ||
                auth()->user()->role === 'admin' ||
                $performance->staff->contains('user_id', auth()->id()))
                <a href="{{ route('phases.equipment.index', $phase) }}"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                    </svg>
                    使用機材 管理
                </a>
            @endif
        </div>

        @php
            $phaseEquipments = $phase->phaseEquipments()
                ->with(['equipment.subcategory.category'])
                ->whereIn('phase_equipment.status', ['reserved', 'checked_out']) // 返却済み(checked_in)を除外
                ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
                ->orderBy('equipments.sort')
                ->select('phase_equipment.*')
                ->get();

            // 同じ機材名でグループ化
            $groupedEquipments = $phaseEquipments->groupBy(function($item) {
                return $item->equipment->name;
            })->map(function($group) {
                $first = $group->first();
                return (object)[
                    'equipment' => $first->equipment,
                    'total_quantity' => $group->sum('quantity'),
                    'company_numbers' => $group->map(function($item) {
                        return $item->equipment->company_number;
                    })->filter()->unique()->implode(', ')
                ];
            })->take(10);

            $equipmentCount = $phase->phaseEquipments()
                ->whereIn('phase_equipment.status', ['reserved', 'checked_out'])
                ->count();
        @endphp

        @if($equipmentCount > 0)
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
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-r border-gray-200">
                                数量
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-r border-gray-200">
                                新音番号
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($groupedEquipments as $groupedEquipment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $groupedEquipment->equipment->subcategory->category->name }}</div>
                                <div class="text-sm text-gray-500">{{ $groupedEquipment->equipment->subcategory->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        @if($groupedEquipment->equipment->manufacturer)
                                            <div class="text-xs text-gray-500 mb-1">
                                                {{ $groupedEquipment->equipment->manufacturer }}
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $groupedEquipment->equipment->name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center border-l border-r border-gray-200">
                                <div class="text-sm text-gray-900">{{ $groupedEquipment->total_quantity }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left border-l border-r border-gray-200">
                                @if($groupedEquipment->company_numbers)
                                    <div class="text-sm text-gray-900">{{ $groupedEquipment->company_numbers }}</div>
                                @else
                                    <div class="text-sm text-gray-400">-</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($equipmentCount > 10)
                <div class="mt-4 text-center">
                    <a href="{{ route('phases.equipment.index', $phase) }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                        すべての機材を見る（{{ $equipmentCount }}件）
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">使用機材が登録されていません</h3>
                <p class="mt-1 text-sm text-gray-500">このフェーズで使用する機材を登録しましょう。</p>
            </div>
        @endif
    </div>
</div>

<!-- 削除確認モーダル -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 text-center mb-2">フェーズの削除</h3>
        <div class="bg-gray-50 rounded-md p-3 mb-4">
            <p class="text-sm text-gray-700 text-center">
                <span class="font-medium">公演:</span> {{ $performance->title }}<br>
                <span class="font-medium">フェーズ:</span> {{ $phase->name }}
            </p>
        </div>
        <p class="text-sm text-gray-500 text-center mb-4">
            このフェーズを削除すると、関連する機材使用記録もすべて削除されます。<br>
            この操作は取り消すことができません。
        </p>
        <p class="text-sm text-gray-700 text-center mb-4">
            続行するには、下のフィールドに <strong>delete</strong> と入力してください。
        </p>
        <input type="text" id="deleteConfirmInput" placeholder="delete と入力"
               class="w-full px-3 py-2 border border-gray-300 rounded-md text-center mb-4 focus:ring-red-500 focus:border-red-500">
        <div class="flex space-x-3">
            <button type="button" onclick="hideDeleteConfirmation()"
                    class="flex-1 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                キャンセル
            </button>
            <button type="button" id="confirmDeleteBtn" onclick="executeDelete()" disabled
                    class="flex-1 px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                削除
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showDeleteConfirmation() {
    if (confirm('本当にこのフェーズを削除しますか？関連する機材使用記録もすべて削除されます。')) {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
        document.getElementById('deleteConfirmInput').focus();
    }
}

function hideDeleteConfirmation() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
    document.getElementById('deleteConfirmInput').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

function executeDelete() {
    document.getElementById('deletePhaseForm').submit();
}

// 入力フィールドの監視
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('deleteConfirmInput');
    const button = document.getElementById('confirmDeleteBtn');

    input.addEventListener('input', function() {
        if (this.value === 'delete') {
            button.disabled = false;
        } else {
            button.disabled = true;
        }
    });

    // Enterキーで削除実行
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value === 'delete') {
            executeDelete();
        }
    });

    // Escapeキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideDeleteConfirmation();
        }
    });
});
</script>
@endpush

@endsection
