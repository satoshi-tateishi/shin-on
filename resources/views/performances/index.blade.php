@extends('layouts.master')

@section('title', '公演管理')

@section('breadcrumb')
    > <span class="text-gray-800">公演一覧</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">公演一覧</h1>
        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <div class="flex justify-end">
                <a href="{{ route('performances.create') }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">新規公演</span>作成
                </a>
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
        <!-- 検索・フィルター -->
        <div class="bg-white border border-gray-200 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">検索条件</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <form method="GET" action="{{ route('performances.index') }}" class="space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="search" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">公演名</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="公演名で検索">
                        </div>

                        <div>
                            <label for="performance_type" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">種別</label>
                            <select name="performance_type" id="performance_type"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全て</option>
                                @foreach($performanceTypes as $type)
                                    <option value="{{ $type['value'] }}" {{ request('performance_type') === $type['value'] ? 'selected' : '' }}>
                                        {{ $type['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="phase_status" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">フェーズ状態</label>
                            <select name="phase_status" id="phase_status"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全て</option>
                                @foreach(\App\Enums\PhaseStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ request('phase_status') === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('performances.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 min-w-[70px]">
                            クリア
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 min-w-[70px]">
                            検索
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 公演一覧 -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">公演情報</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">期間</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">会場</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フェーズ状態</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($performances as $performance)
                            <tr class="hover:bg-gray-50 active:bg-gray-100 cursor-pointer" onclick="window.location='{{ route('performances.show', $performance) }}'">
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    <div>
                                        <div class="text-xs sm:text-sm font-medium text-gray-900">{{ $performance->title }}</div>
                                        @if($performance->director)
                                            <div class="text-xs text-gray-400">演出: {{ $performance->director }}</div>
                                        @endif
                                        <!-- モバイル用: 期間表示 -->
                                        <div class="md:hidden text-xs text-gray-500 mt-1">
                                            @if($performance->start_date && $performance->end_date)
                                                {{ $performance->start_date }} 〜 {{ $performance->end_date }}
                                            @elseif($performance->phases_count > 0)
                                                フェーズで設定
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 hidden md:table-cell">
                                    @if($performance->start_date && $performance->end_date)
                                        <div>{{ $performance->start_date }}</div>
                                        <div class="text-xs text-gray-500">〜 {{ $performance->end_date }}</div>
                                        @if($performance->duration_days)
                                            <div class="text-xs text-gray-400">({{ $performance->duration_days }}日間)</div>
                                        @endif
                                    @elseif($performance->phases_count > 0)
                                        <div class="text-gray-500 text-xs">フェーズで設定</div>
                                    @else
                                        <div class="text-gray-400 text-xs">期間未設定</div>
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 hidden lg:table-cell">
                                    @if($performance->phases->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($performance->phases->sortBy('start_date')->take(2) as $phase)
                                                <div class="text-xs">
                                                    <span class="font-medium text-gray-700">{{ $phase->name }}:</span>
                                                    <span>{{ $phase->location ? $phase->location->name : '未設定' }}</span>
                                                </div>
                                            @endforeach
                                            @if($performance->phases->count() > 2)
                                                <div class="text-xs text-gray-400">他{{ $performance->phases->count() - 2 }}件</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">フェーズ未設定</span>
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    <div class="flex flex-col items-start gap-1">
                                        @if($performance->phases->count() > 0)
                                            <div class="space-y-1">
                                                @foreach($performance->phases->sortBy('start_date') as $phase)
                                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                        <x-status-badge :status="$phase->phase_status" type="phase" class="flex-shrink-0 w-14 justify-center" />
                                                        <span class="text-xs text-gray-500">{{ $phase->name }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">フェーズなし</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 sm:px-6 py-8 sm:py-12 text-center">
                                    <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900">公演がありません</h3>
                                    <p class="mt-1 text-xs sm:text-sm text-gray-500">公演が登録されていません。</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ページネーション -->
        @if($performances->hasPages())
            <div class="mt-4 sm:mt-6">
                {{ $performances->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
