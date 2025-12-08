@extends('layouts.master')

@section('title', 'アクティビティログ')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-4 sm:mb-6">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">アクティビティログ</h1>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors text-sm sm:text-base whitespace-nowrap">
                <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="hidden sm:inline">ダッシュボードに</span>戻る
            </a>
        </div>
    </div>

    <!-- User Filter -->
    <div class="mb-4 sm:mb-6" x-data>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">ユーザーで絞り込み:</label>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select
                    id="user-filter"
                    class="flex-1 sm:flex-none sm:w-64 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @change="
                        const url = new URL(window.location.href);
                        $event.target.value ? url.searchParams.set('user_id', $event.target.value) : url.searchParams.delete('user_id');
                        window.location.href = url.toString();
                    "
                >
                    <option value="">すべてのユーザー</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @if($userId)
                    <a href="{{ route('activity-logs.index', ['filter' => $filter]) }}"
                       class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg transition-colors text-sm whitespace-nowrap">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        クリア
                    </a>
                @endif
            </div>
        </div>
        @if($selectedUser)
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded-full">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ $selectedUser->name }} のアクティビティを表示中
                </span>
            </div>
        @endif
    </div>

    <!-- Clickable Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 sm:gap-4 mb-6">
        <!-- 全アクティビティ -->
        <a href="{{ route('activity-logs.index', array_filter(['filter' => 'all', 'user_id' => $userId])) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 p-3 sm:p-4 transition-all hover:shadow-md
                  {{ $filter === 'all' ? 'border-gray-500 ring-2 ring-gray-200 dark:ring-gray-700' : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">すべて</div>
                </div>
                @if($filter === 'all')
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </a>

        <!-- 機材操作 -->
        <a href="{{ route('activity-logs.index', array_filter(['filter' => 'equipment', 'user_id' => $userId])) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 p-3 sm:p-4 transition-all hover:shadow-md
                  {{ $filter === 'equipment' ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-900' : 'border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['equipment']) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">機材</div>
                </div>
                @if($filter === 'equipment')
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </a>

        <!-- 公演 -->
        <a href="{{ route('activity-logs.index', array_filter(['filter' => 'performance', 'user_id' => $userId])) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 p-3 sm:p-4 transition-all hover:shadow-md
                  {{ $filter === 'performance' ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-900' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($stats['performance']) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">公演</div>
                </div>
                @if($filter === 'performance')
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </a>

        <!-- フェーズ -->
        <a href="{{ route('activity-logs.index', array_filter(['filter' => 'phase', 'user_id' => $userId])) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 p-3 sm:p-4 transition-all hover:shadow-md
                  {{ $filter === 'phase' ? 'border-purple-500 ring-2 ring-purple-200 dark:ring-purple-900' : 'border-gray-100 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['phase']) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">フェーズ</div>
                </div>
                @if($filter === 'phase')
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </a>

        <!-- ログイン -->
        <a href="{{ route('activity-logs.index', array_filter(['filter' => 'login', 'user_id' => $userId])) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 p-3 sm:p-4 transition-all hover:shadow-md
                  {{ $filter === 'login' ? 'border-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-900' : 'border-gray-100 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-500' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['login']) }}</div>
                    <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ログイン</div>
                </div>
                @if($filter === 'login')
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </a>
    </div>

    <!-- Activity List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden">
        @if($activities->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($activities as $activity)
                    <div class="px-4 sm:px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-start gap-3">
                            <!-- Action Badge -->
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activity->action_color }} whitespace-nowrap flex-shrink-0">
                                {{ $activity->action_label }}
                            </span>
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->user?->name ?? '不明' }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $activity->description }}</span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity->created_at->format('Y/m/d H:i') }}
                                    <span class="text-gray-400 dark:text-gray-500">（{{ $activity->relative_time }}）</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($activities->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $activities->links() }}
                </div>
            @endif
        @else
            <div class="px-4 sm:px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="mt-2">該当するアクティビティがありません</p>
            </div>
        @endif
    </div>
</div>
@endsection
