@extends('layouts.master')

@section('title', '公演詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <span class="text-gray-800">詳細</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $performance->title }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('performances.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>
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
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <!-- 基本情報 -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
            <x-status-badge :status="$performance->status" type="performance" />
        </div>
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <table class="w-full text-xs sm:text-sm">
                <tbody class="divide-y divide-gray-100">
                    <!-- 公演種別 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top">公演種別</th>
                        <td class="py-2 text-gray-900">{{ $performance->performance_type }}</td>
                    </tr>

                    <!-- 略称 -->
                    @if($performance->short_name)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top">略称</th>
                        <td class="py-2 text-gray-900">{{ $performance->short_name }}</td>
                    </tr>
                    @endif

                    <!-- 期間 -->
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top">期間</th>
                        <td class="py-2 text-gray-900">
                            @if($performance->start_date && $performance->end_date)
                                {{ $performance->start_date }} 〜 {{ $performance->end_date }}
                                @if($performance->duration_days)
                                    <span class="text-xs text-gray-500 ml-1">({{ $performance->duration_days }}日間)</span>
                                @endif
                            @elseif($performance->phases->count() > 0)
                                <span class="text-gray-500">フェーズで設定</span>
                            @else
                                <span class="text-gray-400">期間未設定</span>
                            @endif
                        </td>
                    </tr>

                    <!-- 演出 -->
                    @if($performance->director)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top">演出</th>
                        <td class="py-2 text-gray-900">{{ $performance->director }}</td>
                    </tr>
                    @endif

                    <!-- プロダクション -->
                    @if($performance->productions->count() > 0)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top whitespace-nowrap">プロダクション</th>
                        <td class="py-2 text-gray-900 whitespace-nowrap">{{ $performance->productions->pluck('name')->implode(', ') }}</td>
                    </tr>
                    @endif

                    <!-- 備考 -->
                    @if($performance->note)
                    <tr>
                        <th class="py-2 pr-4 text-left font-medium text-gray-500 w-24 sm:w-32 align-top">備考</th>
                        <td class="py-2 text-gray-900 whitespace-pre-wrap">{{ $performance->note }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- 担当者と公演チラシ画像 -->
            @if($performance->staff->count() > 0 || ($performance->attachments && $performance->attachments->count() > 0))
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-4 sm:space-y-0 sm:flex sm:gap-6">
                <!-- 担当者 -->
                @if($performance->staff->count() > 0)
                <div class="flex-shrink-0">
                    <div class="text-xs sm:text-sm font-medium text-gray-500 mb-2">担当者</div>
                    <div class="overflow-hidden border border-gray-200 rounded-lg inline-block">
                        <table class="table-auto divide-y divide-gray-200 text-xs sm:text-sm">
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($performance->staff->sortBy([['position.sort', 'asc'], ['user.sort', 'asc']]) as $staff)
                                    <tr>
                                        <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap text-gray-500 text-right border-r border-gray-200">{{ $staff->position->name }}</td>
                                        <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap text-gray-900">{{ $staff->user->name }}</td>
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
                    <div class="text-xs sm:text-sm font-medium text-gray-500 mb-2">公演チラシ画像</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($performance->attachments as $attachment)
                            @if($attachment->isImage())
                                <div class="w-24 h-24 sm:w-32 sm:h-32">
                                    <a href="{{ $attachment->file_url }}" target="_blank" class="block w-full h-full">
                                        <img src="{{ $attachment->thumbnail_url ?? $attachment->file_url }}"
                                             alt="{{ $attachment->original_name }}"
                                             class="w-full h-full object-contain hover:opacity-80 transition-opacity border border-gray-200 rounded">
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
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-medium text-gray-900">フェーズ一覧</h3>
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フェーズ名</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">期間</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">場所</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($performance->phases->sortBy('start_date') as $phase)
                        <tr class="hover:bg-gray-50 active:bg-gray-100 cursor-pointer" onclick="window.location='{{ route('phases.show', $phase) }}'">
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                <div class="text-xs sm:text-sm font-medium text-gray-900">{{ $phase->name }}</div>
                                @if($phase->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($phase->description, 30) }}</div>
                                @endif
                                <!-- モバイル用: 期間・場所表示 -->
                                <div class="sm:hidden mt-1 text-xs text-gray-500">
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
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 hidden sm:table-cell">
                                @if($phase->start_date && $phase->end_date)
                                    <div>{{ $phase->start_date->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500">〜 {{ $phase->end_date->format('Y-m-d') }}</div>
                                @else
                                    <span class="text-gray-400">日程未設定</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 hidden md:table-cell">
                                @if($phase->location)
                                    <div>{{ $phase->location->name }}</div>
                                    @if($phase->note)
                                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($phase->note, 30) }}</div>
                                    @endif
                                @else
                                    <div><span class="text-gray-400">場所未設定</span></div>
                                    @if($phase->note)
                                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($phase->note, 30) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                @php
                                    $statusColors = [
                                        'upcoming' => 'bg-gray-100 text-gray-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$phase->phase_status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $phase->phase_status_label }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-4 sm:px-6 py-6 sm:py-8">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900">フェーズが登録されていません</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500">まずは最初のフェーズを作成しましょう。</p>
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
