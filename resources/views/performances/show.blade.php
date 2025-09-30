@extends('layouts.app')

@section('title', '公演詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <span class="text-gray-800">{{ $performance->title }} 詳細</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $performance->title }} 詳細</h1>
    </div>

    <div class="flex space-x-3">
        @if(auth()->user()->role === 'editor' ||
            auth()->user()->role === 'admin' ||
            $performance->staff->contains('user_id', auth()->id()))
            <a href="{{ route('performances.edit', $performance) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                編集
            </a>
        @endif



        <a href="{{ route('performances.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
            </svg>
            一覧に戻る
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- 基本情報 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">基本情報</h3>

            <!-- 基本情報テーブル -->
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg mb-4">
                <table class="w-full divide-y divide-gray-300">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- ステータス -->
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">ステータス</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">
                                <x-status-badge :status="$performance->status" type="performance" />
                            </td>
                        </tr>

                        <!-- 公演種別 -->
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">公演種別</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">
                                <x-performance-type-badge :type="$performance->performance_type" />
                            </td>
                        </tr>

                        <!-- 公演名 -->
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">公演名</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">{{ $performance->title }}</td>
                        </tr>

                        <!-- 略称 -->
                        @if($performance->short_name)
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">略称</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $performance->short_name }}
                                </span>
                            </td>
                        </tr>
                        @endif

                        <!-- 期間 -->
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">期間</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">
                                @if($performance->start_date && $performance->end_date)
                                    {{ $performance->start_date }} 〜 {{ $performance->end_date }}
                                    @if($performance->duration_days)
                                        <span class="text-xs text-gray-500 ml-2">({{ $performance->duration_days }}日間)</span>
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
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">演出</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">{{ $performance->director }}</td>
                        </tr>
                        @endif

                        <!-- プロダクション -->
                        @if($performance->productions->count() > 0)
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 text-right w-32">プロダクション</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900">
                                {{ $performance->productions->pluck('name')->implode(', ') }}
                            </td>
                        </tr>
                        @endif

                        <!-- 備考 -->
                        @if($performance->note)
                        <tr>
                            <td class="pl-4 pr-3 py-3 whitespace-nowrap text-sm font-medium text-gray-500 bg-gray-50 align-top text-right w-32">備考</td>
                            <td class="pl-3 px-6 py-3 text-sm text-gray-900 whitespace-pre-wrap">{{ $performance->note }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

                <!-- 担当者と公演チラシ画像 -->
                @if($performance->staff->count() > 0 || ($performance->attachments && $performance->attachments->count() > 0))
                <div class="flex gap-6">
                    <!-- 担当者 -->
                    @if($performance->staff->count() > 0)
                    <div class="flex-shrink-0">
                        <dt class="text-sm font-medium text-gray-500 mb-2">担当者</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg inline-block">
                                <table class="table-auto divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="pl-3 pr-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">ポジション</th>
                                            <th class="pl-2 pr-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">担当者</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($performance->staff->sortBy([['position.sort', 'asc'], ['user.sort', 'asc']]) as $staff)
                                            <tr>
                                                <td class="pl-3 pr-2 py-2 whitespace-nowrap text-sm text-gray-900 text-right border-r border-gray-300">{{ $staff->position->name }}</td>
                                                <td class="pl-2 pr-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $staff->user->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </dd>
                    </div>
                    @endif

                    <!-- 公演チラシ画像 -->
                    @if($performance->attachments && $performance->attachments->count() > 0)
                    <div class="flex-grow">
                        <dt class="text-sm font-medium text-gray-500 mb-2">公演チラシ画像</dt>
                        <dd class="mt-1">
                            <div class="flex flex-wrap gap-2">
                                @foreach($performance->attachments as $attachment)
                                    @if($attachment->isImage())
                                        <div class="w-32 h-32">
                                            <a href="{{ $attachment->file_url }}" target="_blank" class="block w-full h-full">
                                                <img src="{{ $attachment->thumbnail_url ?? $attachment->file_url }}"
                                                     alt="{{ $attachment->original_name }}"
                                                     class="w-full h-full object-contain hover:opacity-80 transition-opacity border border-gray-200 rounded">
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </dd>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- フェーズ一覧 -->
    @if($performance->phases->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">フェーズ一覧</h3>
                @if(auth()->user()->role === 'editor' ||
                    auth()->user()->role === 'admin' ||
                    $performance->staff->contains('user_id', auth()->id()))
                    <a href="{{ route('performances.phases.create', $performance) }}"
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        フェーズ追加
                    </a>
                @endif
            </div>

            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フェーズ名</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">期間</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">場所</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($performance->phases->sortBy('start_date') as $phase)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('phases.show', $phase) }}'">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $phase->name }}</div>
                                    @if($phase->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($phase->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($phase->start_date && $phase->end_date)
                                        <div>{{ $phase->start_date->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">〜 {{ $phase->end_date->format('Y-m-d') }}</div>
                                    @else
                                        <span class="text-gray-400">日程未設定</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($phase->location)
                                        <div>{{ $phase->location->name }}</div>
                                        @if($phase->note)
                                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($phase->note, 50) }}</div>
                                        @endif
                                    @else
                                        <div><span class="text-gray-400">場所未設定</span></div>
                                        @if($phase->note)
                                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($phase->note, 50) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">フェーズが登録されていません</h3>
                <p class="mt-1 text-sm text-gray-500">まずは最初のフェーズを作成しましょう。</p>
                @if(auth()->user()->role === 'editor' ||
                    auth()->user()->role === 'admin' ||
                    $performance->staff->contains('user_id', auth()->id()))
                    <div class="mt-6">
                        <a href="{{ route('performances.phases.create', $performance) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
