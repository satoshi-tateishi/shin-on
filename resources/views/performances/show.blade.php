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
        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('performances.edit', $performance) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                編集
            </a>
        @endif

        @if($performance->phases->count() > 0)
            <a href="{{ route('performances.phases.index', $performance) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                フェーズ管理
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

            <div class="space-y-4">
                <!-- ステータス -->
                <div>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $performance->status === 'planning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $performance->status === 'preparation' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $performance->status === 'in_progress' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $performance->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $performance->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $performance->status_label }}
                        </span>
                    </dd>
                </div>

                <!-- 公演種別 -->
                <div>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $performance->performance_type === '演劇' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $performance->performance_type === 'ミュージカル' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $performance->performance_type === 'リーディング' ? 'bg-amber-100 text-amber-800' : '' }}
                            {{ $performance->performance_type === 'ダンス' ? 'bg-pink-100 text-pink-800' : '' }}
                            {{ $performance->performance_type === 'イベント' ? 'bg-indigo-100 text-indigo-800' : '' }}
                            {{ $performance->performance_type === 'コンサート' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $performance->performance_type === 'その他' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ $performance->performance_type }}
                        </span>
                    </dd>
                </div>

                <!-- 公演名 -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">公演名</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $performance->title }}
                    </dd>
                </div>

                <!-- 期間 -->
                <div>
                    <dt class="text-sm font-medium text-gray-500">期間</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($performance->start_date && $performance->end_date)
                            <div>{{ $performance->start_date }} 〜 {{ $performance->end_date }}</div>
                            @if($performance->duration_days)
                                <div class="text-xs text-gray-500">({{ $performance->duration_days }}日間)</div>
                            @endif
                        @elseif($performance->phases->count() > 0)
                            <span class="text-gray-500">フェーズで設定</span>
                        @else
                            <span class="text-gray-400">期間未設定</span>
                        @endif
                    </dd>
                </div>

                <!-- 演出 -->
                @if($performance->director)
                <div>
                    <dt class="text-sm font-medium text-gray-500">演出</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $performance->director }}</dd>
                </div>
                @endif

                <!-- プロダクション -->
                @if($performance->productions->count() > 0)
                <div>
                    <dt class="text-sm font-medium text-gray-500">プロダクション</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $performance->productions->pluck('name')->implode(', ') }}
                    </dd>
                </div>
                @endif

                <!-- 担当者 -->
                @if($performance->staff->count() > 0)
                <div>
                    <dt class="text-sm font-medium text-gray-500">担当者</dt>
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
                                    @foreach($performance->staff as $staff)
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

                <!-- 備考 -->
                @if($performance->note)
                <div>
                    <dt class="text-sm font-medium text-gray-500">備考</dt>
                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $performance->note }}</dd>
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
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材使用</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($performance->phases->sortBy('sort') as $phase)
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
                                        {{ $phase->location->name }}
                                    @else
                                        <span class="text-gray-400">場所未設定</span>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @php
                                        $equipmentCount = $phase->phaseEquipment ? $phase->phaseEquipment->count() : 0;
                                    @endphp
                                    @if($equipmentCount > 0)
                                        <a href="{{ route('performances.phases.equipment.index', [$performance, $phase]) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            {{ $equipmentCount }}件
                                        </a>
                                    @else
                                        <span class="text-gray-400">未設定</span>
                                    @endif
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
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
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
