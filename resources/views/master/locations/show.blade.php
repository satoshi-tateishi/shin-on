@extends('layouts.master')

@section('title', '使用場所マスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.locations.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">使用場所マスタ</a>
    > <span class="text-gray-800 dark:text-gray-200">{{ $location->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $location->name }}</h1>
        <div class="flex items-center justify-between">
            <x-button variant="secondary" :href="route('master.locations.index')">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </x-button>

            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                <x-button variant="primary" :href="route('master.locations.edit', $location)">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    編集
                </x-button>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <div class="grid grid-cols-2 gap-3 sm:gap-6">
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">タイプ</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    @switch($location->type)
                                        @case('劇場') bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 @break
                                        @case('稽古場') bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 @break
                                        @case('倉庫') bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 @break
                                        @default bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 @break
                                    @endswitch">
                                    {{ $location->type }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $location->is_active ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' }}">
                                    {{ $location->status_label }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">ふりがな</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->furigana ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">機材数</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    {{ $location->equipments_count }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">郵便番号</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->postal_code ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">住所</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->address ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">作成日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->created_at->format('Y-m-d') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">更新日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->updated_at->format('Y-m-d') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 連絡先情報 -->
            @if($location->tel1 || $location->tel2 || $location->fax || $location->email1 || $location->email2)
                <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">連絡先情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-6">
                            @if($location->tel1)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                                        電話1
                                        @if($location->tel1_name)
                                            ({{ $location->tel1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">
                                        <a href="tel:{{ $location->tel1 }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $location->tel1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->tel2)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                                        電話2
                                        @if($location->tel2_name)
                                            ({{ $location->tel2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">
                                        <a href="tel:{{ $location->tel2 }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $location->tel2 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->fax)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">FAX</dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->fax }}</dd>
                                </div>
                            @endif

                            @if($location->email1)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                                        メール1
                                        @if($location->email1_name)
                                            ({{ $location->email1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">
                                        <a href="mailto:{{ $location->email1 }}" class="text-blue-600 hover:text-blue-800 break-all">
                                            {{ $location->email1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->email2)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                                        メール2
                                        @if($location->email2_name)
                                            ({{ $location->email2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">
                                        <a href="mailto:{{ $location->email2 }}" class="text-blue-600 hover:text-blue-800 break-all">
                                            {{ $location->email2 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- 備考 -->
            @if($location->note)
                <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="whitespace-pre-wrap text-xs sm:text-sm text-gray-900 dark:text-white">{{ $location->note }}</div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
