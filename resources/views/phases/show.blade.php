@extends('layouts.app')

@section('title', 'フェーズ詳細')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $performance) }}" class="text-blue-600 hover:text-blue-800">{{ $performance->title }}</a>
    > <span class="text-gray-800">{{ $phase->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $phase->name }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $performance->title }} のフェーズ詳細</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('performances.show', $performance) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('phases.edit', $phase) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                編集
            </a>
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

<!-- 機材使用情報 -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">機材使用</h3>
            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                <a href="{{ route('phases.equipment.index', $phase) }}"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                    </svg>
                    機材管理
                </a>
            @endif
        </div>

        @php
            $equipmentCount = $phase->phaseEquipments ? $phase->phaseEquipments->count() : 0;
        @endphp

        @if($equipmentCount > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            機材使用登録
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>このフェーズで{{ $equipmentCount }}件の機材使用が登録されています。</p>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <a href="{{ route('phases.equipment.index', $phase) }}"
                                   class="bg-blue-50 px-2 py-1.5 rounded-md text-sm font-medium text-blue-800 hover:bg-blue-100">
                                    詳細を見る
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">機材使用が登録されていません</h3>
                <p class="mt-1 text-sm text-gray-500">このフェーズで使用する機材を登録しましょう。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('phases.equipment.index', $phase) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            機材使用を登録
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@if(auth()->user()->role === 'admin')
<!-- 削除セクション -->
<div class="bg-white shadow rounded-lg border border-red-200">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-red-900 mb-4">危険ゾーン</h3>

        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        フェーズの削除
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>このフェーズを削除すると、関連する機材使用記録もすべて削除されます。この操作は取り消すことができません。</p>
                    </div>
                    <div class="mt-4">
                        <form method="POST" action="{{ route('phases.destroy', $phase) }}" onsubmit="return confirm('本当にこのフェーズを削除しますか？関連する機材使用記録もすべて削除されます。')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-50 px-2 py-1.5 rounded-md text-sm font-medium text-red-800 hover:bg-red-100">
                                フェーズを削除
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection