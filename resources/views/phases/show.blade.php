@extends('layouts.master')

@section('title', 'フェーズ詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">公演一覧</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ $performance->title }}</a>
    > <span class="text-gray-800 dark:text-gray-200">{{ $phase->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600 dark:text-gray-400">{{ $performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $phase->name }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('performances.show', $performance) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>

            <div class="flex-1"></div>

            <!-- PDF出力ドロップダウン -->
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button"
                        class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <span class="hidden sm:inline">PDF</span>出力
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="origin-top-right absolute right-0 mt-2 w-48 sm:w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-10">
                    <div class="py-1" role="menu">
                        <a href="{{ route('phases.export-pdf', $phase) }}"
                           class="flex items-center px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                           role="menuitem">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            ダウンロード
                        </a>
                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-lineworks-modal'))"
                                class="flex items-center w-full px-3 sm:px-4 py-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-left"
                                role="menuitem">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            LINE WORKSに送信
                        </button>
                    </div>
                </div>
            </div>

            @if(auth()->user()->role === 'editor' ||
                auth()->user()->role === 'admin' ||
                $performance->staff->contains('user_id', auth()->id()))
                <a href="{{ route('phases.edit', $phase) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    編集
                </a>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'editor']))
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-delete-modal'))"
                        class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span class="hidden sm:inline">削除</span>
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6"
     x-data="phaseShowPage()"
     @keydown.escape.window="showLineWorksModal = false; showDeleteModal = false"
     @open-lineworks-modal.window="showLineWorksModal = true"
     @open-delete-modal.window="showDeleteModal = true; deleteConfirmation = ''">
    <!-- 成功/エラーメッセージ -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-3 sm:px-4 py-2 sm:py-3 rounded-lg flex items-start text-xs sm:text-sm">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-3 sm:px-4 py-2 sm:py-3 rounded-lg flex items-start text-xs sm:text-sm">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- フェーズ基本情報 -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
            <div class="flex items-center gap-2">
                @php
                    $statusColors = [
                        'upcoming' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                        'in_progress' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
                        'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                    ];
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$phase->phase_status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                    {{ $phase->phase_status_label }}
                </span>
                @if(!$phase->is_active)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                        無効
                    </span>
                @endif
            </div>
        </div>
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <table class="w-full text-xs sm:text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <!-- 期間 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-20 sm:w-24 align-top">期間</th>
                        <td class="py-2 text-gray-900 dark:text-white">
                            @if($phase->start_date && $phase->end_date)
                                <div>{{ $phase->start_date->format('Y年m月d日') }} 〜 {{ $phase->end_date->format('Y年m月d日') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">({{ $phase->duration_days }}日間)</div>
                            @elseif($phase->start_date)
                                開始: {{ $phase->start_date->format('Y年m月d日') }}
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">終了日未設定</span>
                            @elseif($phase->end_date)
                                終了: {{ $phase->end_date->format('Y年m月d日') }}
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">開始日未設定</span>
                            @else
                                <span class="text-gray-400">期間未設定</span>
                            @endif
                        </td>
                    </tr>

                    <!-- 場所 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-20 sm:w-24 align-top">場所</th>
                        <td class="py-2 text-gray-900 dark:text-white">
                            @if($phase->location)
                                {{ $phase->location->name }}
                            @else
                                <span class="text-gray-400">未設定</span>
                            @endif
                        </td>
                    </tr>

                    <!-- 備考 -->
                    @if($phase->note)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-20 sm:w-24 align-top">備考</th>
                        <td class="py-2 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $phase->note }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 使用機材 -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">使用機材</h3>
            @if(auth()->user()->role === 'editor' ||
                auth()->user()->role === 'admin' ||
                $performance->staff->contains('user_id', auth()->id()))
                <a href="{{ route('phases.equipment.index', $phase) }}"
                   class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                    </svg>
                    <span class="hidden sm:inline">使用機材</span>管理
                </a>
            @endif
        </div>

        @php
            $phaseEquipments = $phase->phaseEquipments()
                ->with(['equipment.subcategory.category'])
                ->whereIn('phase_equipment.status', ['reserved', 'checked_out'])
                ->join('equipments', 'phase_equipment.equipment_id', '=', 'equipments.id')
                ->orderBy('equipments.sort')
                ->select('phase_equipment.*')
                ->get();

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
            });

            $equipmentCount = $phase->phaseEquipments()
                ->whereIn('phase_equipment.status', ['reserved', 'checked_out'])
                ->count();
        @endphp

        @if($equipmentCount > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                機材
                            </th>
                            <th scope="col" class="px-3 sm:px-6 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16 sm:w-20">
                                数量
                            </th>
                            <th scope="col" class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">
                                新音番号
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($groupedEquipments as $groupedEquipment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $groupedEquipment->equipment->subcategory->category->name }} / {{ $groupedEquipment->equipment->subcategory->name }}
                                </div>
                                @if($groupedEquipment->equipment->manufacturer)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $groupedEquipment->equipment->manufacturer }}</div>
                                @endif
                                <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{{ $groupedEquipment->equipment->name }}</div>
                                <!-- モバイル用: 新音番号 -->
                                @if($groupedEquipment->company_numbers)
                                    <div class="sm:hidden text-xs text-gray-500 dark:text-gray-400 mt-1 pt-1 border-t border-dashed border-gray-300 dark:border-gray-600">{{ $groupedEquipment->company_numbers }}</div>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-center">
                                <div class="text-xs sm:text-sm text-gray-900 dark:text-white">{{ $groupedEquipment->total_quantity }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">
                                @if($groupedEquipment->company_numbers)
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $groupedEquipment->company_numbers }}</div>
                                @else
                                    <div class="text-sm text-gray-400">-</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="px-4 sm:px-6 py-6 sm:py-8 text-center">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">使用機材が登録されていません</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">このフェーズで使用する機材を登録しましょう。</p>
            </div>
        @endif
    </div>

    <!-- LINE WORKS送信確認モーダル -->
    <div x-show="showLineWorksModal"
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center z-50"
         @click.self="showLineWorksModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 mx-auto bg-blue-100 dark:bg-blue-900/50 rounded-full mb-3 sm:mb-4">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white text-center mb-2">LINE WORKSに送信</h3>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3 sm:p-4 mb-3 sm:mb-4">
                <div class="space-y-1 sm:space-y-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <span class="font-medium">送信先:</span>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">公演:</span>
                        <span class="text-right">{{ $performance->title }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">フェーズ:</span>
                        <span>{{ $phase->name }}</span>
                    </div>
                </div>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 text-center mb-3 sm:mb-4">
                フェーズ詳細のPDFファイルをあなたのLINE WORKSアカウントに送信します。
            </p>
            <form method="POST" action="{{ route('phases.send-lineworks', $phase) }}">
                @csrf
                <div class="flex space-x-2 sm:space-x-3">
                    <button type="button" @click="showLineWorksModal = false"
                            class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        キャンセル
                    </button>
                    <button type="submit"
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        送信
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- 削除確認モーダル -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center z-50"
         @click.self="showDeleteModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-3 sm:mb-4">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white text-center mb-2">フェーズの削除</h3>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3 mb-3 sm:mb-4">
                <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 text-center">
                    <span class="font-medium">公演:</span> {{ $performance->title }}<br>
                    <span class="font-medium">フェーズ:</span> {{ $phase->name }}
                </p>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 text-center mb-3 sm:mb-4">
                このフェーズを削除すると、関連する機材使用記録もすべて削除されます。<br>
                この操作は取り消すことができません。
            </p>
            <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 text-center mb-3 sm:mb-4">
                続行するには、下のフィールドに <strong>delete</strong> と入力してください。
            </p>
            <input type="text"
                   x-model="deleteConfirmation"
                   @keydown.enter="canDelete && $refs.deleteForm.submit()"
                   placeholder="delete と入力"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-center text-sm mb-3 sm:mb-4 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
            <div class="flex space-x-2 sm:space-x-3">
                <button type="button" @click="showDeleteModal = false"
                        class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    キャンセル
                </button>
                <form method="POST" action="{{ route('phases.destroy', $phase) }}" x-ref="deleteForm" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            :disabled="!canDelete"
                            class="w-full px-3 sm:px-4 py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed">
                        削除
                    </button>
                </form>
            </div>
        </div>
    </div>
</div><!-- Alpine.js x-data scope end -->

@push('scripts')
<script>
function phaseShowPage() {
    return {
        showLineWorksModal: false,
        showDeleteModal: false,
        deleteConfirmation: '',
        get canDelete() {
            return this.deleteConfirmation.toLowerCase() === 'delete';
        }
    }
}
</script>
@endpush

@endsection
