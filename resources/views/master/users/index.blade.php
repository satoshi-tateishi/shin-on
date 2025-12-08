@extends('layouts.master')

@section('title', 'ユーザーマスタ')

@section('breadcrumb')
    > <span class="text-gray-400 dark:text-gray-500">マスタ管理</span> > <span class="text-gray-800 dark:text-gray-200">ユーザー</span>
@endsection

@section('header')
    <div>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">ユーザーマスタ</h1>
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="flex gap-2 sm:gap-3 ml-auto">
            <a href="{{ route('master.users.create') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規登録
            </a>

            <!-- CSV Menu (Dropdown) -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-600 z-50">
                    <div class="py-1">
                        <a href="{{ route('master.users.export-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVエクスポート
                        </a>
                        <a href="{{ route('master.users.template-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVテンプレート
                        </a>
                        <button @click="open = false; window.dispatchEvent(new CustomEvent('open-csv-import-modal', { detail: { actionUrl: '{{ route('master.users.import-csv') }}' }}))"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            CSVインポート
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <!-- Status Filter Buttons -->
        <div class="mb-4 sm:mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('master.users.index', ['status' => 'all']) }}"
                   class="px-3 py-1.5 text-xs sm:text-sm rounded-lg font-medium transition-colors {{ request('status') == 'all' ? 'bg-gray-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    全て
                </a>
                <a href="{{ route('master.users.index', ['status' => 'active']) }}"
                   class="px-3 py-1.5 text-xs sm:text-sm rounded-lg font-medium transition-colors {{ request('status', 'active') == 'active' ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    在職
                </a>
                <a href="{{ route('master.users.index', ['status' => 'on_leave']) }}"
                   class="px-3 py-1.5 text-xs sm:text-sm rounded-lg font-medium transition-colors {{ request('status') == 'on_leave' ? 'bg-yellow-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    休職
                </a>
                <a href="{{ route('master.users.index', ['status' => 'resigned']) }}"
                   class="px-3 py-1.5 text-xs sm:text-sm rounded-lg font-medium transition-colors {{ request('status') == 'resigned' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    退職
                </a>
            </div>
        </div>

        <!-- Results Table -->
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                氏名
                            </th>
                            <th class="px-2 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                年齢
                            </th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">
                                勤続
                            </th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                所属
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer @if($user->is_resigned) bg-red-50 dark:bg-red-900/30 opacity-75 @elseif($user->is_on_leave) bg-yellow-50 dark:bg-yellow-900/30 @endif"
                                onclick="window.location.href='{{ route('master.users.show', $user) }}'">
                                <td class="px-3 sm:px-6 py-2 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white @if($user->is_resigned) line-through text-gray-500 dark:text-gray-400 @endif">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="px-2 sm:px-6 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    @if($user->birthday)
                                        {{ \Carbon\Carbon::parse($user->birthday)->age }}歳
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                    @if($user->hired_at)
                                        @if($user->resigned_at)
                                            {{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::parse($user->resigned_at))->format('%y年%mヶ月') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::now())->format('%y年%mヶ月') }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 sm:px-6 py-2 whitespace-nowrap">
                                    @switch($user->affiliation)
                                        @case('employee')
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                社員
                                            </span>
                                            @break
                                        @case('partner')
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                                パートナー
                                            </span>
                                            @break
                                        @default
                                            <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                            @break
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="text-center py-8 sm:py-12">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">ユーザーが見つかりません</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">検索条件を変更してください。</p>
            </div>
        @endif
    </div>
@endsection
