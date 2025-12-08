@extends('layouts.master')

@section('title', '公演詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">公演一覧</a>
    > <span class="text-gray-800 dark:text-gray-200">詳細</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $performance->title }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('performances.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>
            <div class="flex items-center gap-2">
                @if(auth()->user()->role === 'editor' ||
                    auth()->user()->role === 'admin' ||
                    $performance->staff->contains('user_id', auth()->id()))
                    <a href="{{ route('performances.edit', $performance) }}"
                       class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        編集
                    </a>
                @endif
                @if(auth()->user()->role === 'admin')
                    <div x-data="{ showDeleteModal: false }">
                        <button type="button" @click="showDeleteModal = true"
                                class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            削除
                        </button>

                        <!-- 削除確認モーダル -->
                        <div x-show="showDeleteModal"
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-70" @click="showDeleteModal = false"></div>
                            <div class="flex items-center justify-center min-h-screen p-4">
                                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-auto border border-gray-200 dark:border-gray-700"
                                     @click.stop>
                                    <div class="p-4 sm:p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <h3 class="ml-3 text-base sm:text-lg font-medium text-gray-900 dark:text-white">公演の削除</h3>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            以下の公演を削除しますか？
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-4 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded">
                                            {{ $performance->title }}
                                        </p>
                                        <p class="text-xs text-red-600 dark:text-red-400 mb-6">
                                            ※ 関連するフェーズや機材使用記録もすべて削除されます。この操作は取り消せません。
                                        </p>
                                        <div class="flex justify-end gap-2 sm:gap-3">
                                            <button type="button"
                                                    @click="showDeleteModal = false"
                                                    class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                                                キャンセル
                                            </button>
                                            <form action="{{ route('performances.destroy', $performance) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                                    削除する
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <!-- 基本情報 -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
        </div>
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <table class="w-full text-xs sm:text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <!-- 公演種別 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top">公演種別</th>
                        <td class="py-2 text-gray-900 dark:text-white">{{ $performance->performance_type }}</td>
                    </tr>

                    <!-- 略称 -->
                    @if($performance->short_name)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top">略称</th>
                        <td class="py-2 text-gray-900 dark:text-white">{{ $performance->short_name }}</td>
                    </tr>
                    @endif

                    <!-- 期間 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top">期間</th>
                        <td class="py-2 text-gray-900 dark:text-white">
                            @if($performance->start_date && $performance->end_date)
                                {{ $performance->start_date }} 〜 {{ $performance->end_date }}
                                @if($performance->duration_days)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ $performance->duration_days }}日間)</span>
                                @endif
                            @elseif($performance->phases->count() > 0)
                                <span class="text-gray-500 dark:text-gray-400">フェーズで設定</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">期間未設定</span>
                            @endif
                        </td>
                    </tr>

                    <!-- 演出 -->
                    @if($performance->director)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top">演出</th>
                        <td class="py-2 text-gray-900 dark:text-white">{{ $performance->director }}</td>
                    </tr>
                    @endif

                    <!-- プロダクション -->
                    @if($performance->productions->count() > 0)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top whitespace-nowrap">プロダクション</th>
                        <td class="py-2 text-gray-900 dark:text-white whitespace-nowrap">{{ $performance->productions->pluck('name')->implode(', ') }}</td>
                    </tr>
                    @endif

                    <!-- 備考 -->
                    @if($performance->note)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 w-24 sm:w-32 align-top">備考</th>
                        <td class="py-2 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $performance->note }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- 担当者と公演チラシ画像 -->
            @if($performance->staff->count() > 0 || ($performance->attachments && $performance->attachments->count() > 0))
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-4 sm:space-y-0 sm:flex sm:gap-6">
                <!-- 担当者 -->
                @if($performance->staff->count() > 0)
                <div class="flex-shrink-0">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">担当者</div>
                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg inline-block">
                        <table class="table-auto divide-y divide-gray-200 dark:divide-gray-700 text-xs sm:text-sm">
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($performance->staff->sortBy([['position.sort', 'asc'], ['user.sort', 'asc']]) as $staff)
                                    <tr>
                                        <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap text-gray-500 dark:text-gray-400 text-right border-r border-gray-200 dark:border-gray-700">{{ $staff->position->name }}</td>
                                        <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap text-gray-900 dark:text-white">{{ $staff->user->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- 公演チラシ画像 -->
                @if($performance->attachments && $performance->attachments->count() > 0)
                <div class="flex-grow">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">公演チラシ画像</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($performance->attachments as $attachment)
                            @if($attachment->isImage())
                                <div class="w-24 h-24 sm:w-32 sm:h-32">
                                    <a href="{{ $attachment->file_url }}" target="_blank" class="block w-full h-full">
                                        <img src="{{ $attachment->thumbnail_url ?? $attachment->file_url }}"
                                             alt="{{ $attachment->original_name }}"
                                             class="w-full h-full object-contain hover:opacity-80 transition-opacity border border-gray-200 dark:border-gray-700 rounded">
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- フェーズ一覧 -->
    @if($performance->phases->count() > 0)
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">フェーズ一覧</h3>
            @if(auth()->user()->role === 'editor' ||
                auth()->user()->role === 'admin' ||
                $performance->staff->contains('user_id', auth()->id()))
                <a href="{{ route('performances.phases.create', $performance) }}"
                   class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">フェーズ</span>追加
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">フェーズ名</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">期間</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">場所</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">状態</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($performance->phases->sortBy('start_date') as $phase)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 active:bg-gray-100 dark:active:bg-gray-600 cursor-pointer" onclick="window.location='{{ route('phases.show', $phase) }}'">
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{{ $phase->name }}</div>
                                @if($phase->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($phase->description, 30) }}</div>
                                @endif
                                <!-- モバイル用: 期間・場所表示 -->
                                <div class="sm:hidden mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if($phase->start_date && $phase->end_date)
                                        {{ $phase->start_date->format('m/d') }} 〜 {{ $phase->end_date->format('m/d') }}
                                    @else
                                        日程未設定
                                    @endif
                                    @if($phase->location)
                                        / {{ $phase->location->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 dark:text-white hidden sm:table-cell">
                                @if($phase->start_date && $phase->end_date)
                                    <div>{{ $phase->start_date->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">〜 {{ $phase->end_date->format('Y-m-d') }}</div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">日程未設定</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 dark:text-white hidden md:table-cell">
                                @if($phase->location)
                                    <div>{{ $phase->location->name }}</div>
                                    @if($phase->note)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($phase->note, 30) }}</div>
                                    @endif
                                @else
                                    <div><span class="text-gray-400 dark:text-gray-500">場所未設定</span></div>
                                    @if($phase->note)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($phase->note, 30) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                @php
                                    $statusColors = [
                                        'upcoming' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                                        'in_progress' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
                                        'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                        'pending_return' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-800 dark:text-orange-300',
                                    ];

                                    $displayStatus = $phase->phase_status;
                                    $displayLabel = $phase->phase_status_label;

                                    // 完了かつ未返却機材がある場合
                                    if ($phase->phase_status === 'completed' && $phase->hasUnreturnedEquipment()) {
                                        $displayStatus = 'pending_return';
                                        $displayLabel = '返却待ち';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$displayStatus] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                                    {{ $displayLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="px-4 sm:px-6 py-6 sm:py-8">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">フェーズが登録されていません</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">まずは最初のフェーズを作成しましょう。</p>
                @if(auth()->user()->role === 'editor' ||
                    auth()->user()->role === 'admin' ||
                    $performance->staff->contains('user_id', auth()->id()))
                    <div class="mt-4 sm:mt-6">
                        <a href="{{ route('performances.phases.create', $performance) }}"
                           class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-transparent shadow-sm text-xs sm:text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            フェーズを作成
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
