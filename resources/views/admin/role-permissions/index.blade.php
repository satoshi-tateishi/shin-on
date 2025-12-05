@extends('layouts.app')

@section('title', 'Role権限設定')

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-3 sm:py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-3 sm:px-6 py-2 sm:py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Role権限設定</h1>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    戻る
                </a>
            </div>
        </div>

        <div class="p-2 sm:p-6">
            <!-- 凡例 -->
            <div class="mb-4 sm:mb-6 p-2 sm:p-4 bg-gray-50 rounded-lg">
                <h3 class="text-xs sm:text-sm font-semibold text-gray-700 mb-2 sm:mb-3">凡例</h3>
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 sm:gap-4 text-xs sm:text-sm">
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 text-green-600 rounded">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span class="text-gray-600">許可</span>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 text-red-600 rounded">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <span class="text-gray-600">禁止</span>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 text-blue-600 rounded text-[10px] sm:text-xs font-bold">
                            👤
                        </span>
                        <span class="text-gray-600">担当者のみ</span>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-yellow-100 text-yellow-700 rounded text-[10px] sm:text-xs font-medium">
                            URL
                        </span>
                        <span class="text-gray-600">URL直接</span>
                    </div>
                </div>
            </div>

            <!-- Role説明 -->
            <div class="mb-4 sm:mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-4">
                <div class="p-2 sm:p-4 bg-gray-100 rounded-lg border-l-4 border-gray-400">
                    <h4 class="text-sm sm:text-base font-semibold text-gray-700">viewer（閲覧者）</h4>
                    <p class="text-xs sm:text-sm text-gray-600 mt-0.5 sm:mt-1">閲覧のみ</p>
                </div>
                <div class="p-2 sm:p-4 bg-green-50 rounded-lg border-l-4 border-green-400">
                    <h4 class="text-sm sm:text-base font-semibold text-green-700">general（一般）</h4>
                    <p class="text-xs sm:text-sm text-green-600 mt-0.5 sm:mt-1">基本操作可（担当公演編集+修理管理）</p>
                </div>
                <div class="p-2 sm:p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                    <h4 class="text-sm sm:text-base font-semibold text-blue-700">editor（編集者）</h4>
                    <p class="text-xs sm:text-sm text-blue-600 mt-0.5 sm:mt-1">編集・削除可（ユーザー管理除く）</p>
                </div>
                <div class="p-2 sm:p-4 bg-purple-50 rounded-lg border-l-4 border-purple-400">
                    <h4 class="text-sm sm:text-base font-semibold text-purple-700">admin（管理者）</h4>
                    <p class="text-xs sm:text-sm text-purple-600 mt-0.5 sm:mt-1">全機能へのフルアクセス</p>
                </div>
            </div>

            @foreach($permissions as $sectionKey => $section)
            <!-- {{ $section['title'] }} -->
            <div class="mb-6 sm:mb-8">
                <h2 class="text-sm sm:text-lg font-semibold text-gray-900 mb-1 sm:mb-2">{{ $section['title'] }}</h2>
                @if(isset($section['description']))
                    <p class="text-xs sm:text-sm text-gray-500 mb-2 sm:mb-3">{{ $section['description'] }}</p>
                @endif

                <div class="overflow-x-auto -mx-2 sm:mx-0">
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    機能
                                </th>
                                <th class="px-1 sm:px-4 py-2 sm:py-3 text-center text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wider w-10 sm:w-20">
                                    <span class="hidden sm:inline">viewer</span>
                                    <span class="sm:hidden">V</span>
                                </th>
                                <th class="px-1 sm:px-4 py-2 sm:py-3 text-center text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wider w-10 sm:w-20">
                                    <span class="hidden sm:inline">general</span>
                                    <span class="sm:hidden">G</span>
                                </th>
                                <th class="px-1 sm:px-4 py-2 sm:py-3 text-center text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wider w-10 sm:w-20">
                                    <span class="hidden sm:inline">editor</span>
                                    <span class="sm:hidden">E</span>
                                </th>
                                <th class="px-1 sm:px-4 py-2 sm:py-3 text-center text-[10px] sm:text-xs font-medium text-gray-500 uppercase tracking-wider w-10 sm:w-20">
                                    <span class="hidden sm:inline">admin</span>
                                    <span class="sm:hidden">A</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($section['items'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 sm:px-4 py-1.5 sm:py-3 text-xs sm:text-sm text-gray-900 {{ isset($item['indent']) && $item['indent'] ? 'pl-4 sm:pl-8' : '' }}">
                                    {{ $item['name'] }}
                                </td>
                                <td class="px-1 sm:px-4 py-1.5 sm:py-3 text-center">
                                    @if($item['viewer'] === true)
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 text-green-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @elseif($item['viewer'] === 'staff')
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 text-blue-600 rounded text-[10px] sm:text-xs font-bold">
                                            👤
                                        </span>
                                    @elseif($item['viewer'] === 'url')
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-yellow-100 text-yellow-700 rounded text-[8px] sm:text-xs font-medium">
                                            URL
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 text-red-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-1 sm:px-4 py-1.5 sm:py-3 text-center">
                                    @if($item['general'] === true)
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 text-green-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @elseif($item['general'] === 'staff')
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 text-blue-600 rounded text-[10px] sm:text-xs font-bold">
                                            👤
                                        </span>
                                    @elseif($item['general'] === 'url')
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-yellow-100 text-yellow-700 rounded text-[8px] sm:text-xs font-medium">
                                            URL
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 text-red-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-1 sm:px-4 py-1.5 sm:py-3 text-center">
                                    @if($item['editor'] === true)
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 text-green-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @elseif($item['editor'] === 'staff')
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 text-blue-600 rounded text-[10px] sm:text-xs font-bold">
                                            👤
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 text-red-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-1 sm:px-4 py-1.5 sm:py-3 text-center">
                                    @if($item['admin'] === true)
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 text-green-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 text-red-600 rounded">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            <!-- 注記 -->
            <div class="mt-4 sm:mt-8 p-2 sm:p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <h3 class="text-xs sm:text-sm font-semibold text-amber-800 mb-1 sm:mb-2">注記</h3>
                <ul class="text-[10px] sm:text-sm text-amber-700 space-y-0.5 sm:space-y-1">
                    <li>・ユーザーマスタはadminのみ編集可能</li>
                    <li>・👤 = 公演スタッフとして登録されているユーザー</li>
                    <li>・URL = ダッシュボードリンクなし、URL直接アクセスで閲覧可</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
