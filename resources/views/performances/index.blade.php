@extends('layouts.app')

@section('title', '公演管理')

@section('breadcrumb')
    > <span class="text-gray-800">公演一覧</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">公演一覧</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <a href="{{ route('performances.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規公演作成
            </a>
        </div>
    @endif
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <!-- 検索・フィルター -->
        <div class="mb-6">
            <form method="GET" action="{{ route('performances.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">公演名で検索</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="公演名">
                </div>

                <div>
                    <label for="performance_type" class="block text-sm font-medium text-gray-700">公演種別</label>
                    <select name="performance_type" id="performance_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">全て</option>
                        @foreach($performanceTypes as $type)
                            <option value="{{ $type['value'] }}" {{ request('performance_type') === $type['value'] ? 'selected' : '' }}>
                                {{ $type['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">ステータス</label>
                    <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">全て</option>
                        @foreach($performanceStatuses as $status)
                            <option value="{{ $status['value'] }}" {{ request('status') === $status['value'] ? 'selected' : '' }}>
                                {{ $status['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gray-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-gray-700">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- 公演一覧テーブル -->
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">公演情報</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種別</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">期間</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">会場</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">フェーズ数</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($performances as $performance)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('performances.show', $performance) }}'">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $performance->title }}</div>
                                    @if($performance->director)
                                        <div class="text-xs text-gray-400">演出: {{ $performance->director }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-performance-type-badge :type="$performance->performance_type" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($performance->start_date && $performance->end_date)
                                    <div>{{ $performance->start_date }}</div>
                                    <div class="text-xs text-gray-500">〜</div>
                                    <div>{{ $performance->end_date }}</div>
                                    @if($performance->duration_days)
                                        <div class="text-xs text-gray-500 mt-1">({{ $performance->duration_days }}日間)</div>
                                    @endif
                                @elseif($performance->phases_count > 0)
                                    <div class="text-gray-500 text-xs">フェーズで設定</div>
                                @else
                                    <div class="text-gray-400 text-xs">期間未設定</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($performance->phases->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($performance->phases->sortBy('start_date') as $phase)
                                            <div class="text-xs">
                                                <span class="font-medium text-gray-700">{{ $phase->name }}:</span>
                                                <span>{{ $phase->location ? $phase->location->name : '未設定' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-500">フェーズ未設定</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-status-badge :status="$performance->status" type="performance" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <span>{{ $performance->phases_count ?? 0 }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                公演が登録されていません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        @if($performances->hasPages())
            <div class="mt-6">
                {{ $performances->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
