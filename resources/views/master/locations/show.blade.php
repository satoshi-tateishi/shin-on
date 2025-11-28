@extends('layouts.master')

@section('title', '使用場所マスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.locations.index') }}" class="text-blue-600 hover:text-blue-800">使用場所マスタ</a>
    > <span class="text-gray-800">{{ $location->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $location->name }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.locations.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>

            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                <a href="{{ route('master.locations.edit', $location) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    編集
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <div class="grid grid-cols-2 gap-3 sm:gap-6">
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">タイプ</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    @switch($location->type)
                                        @case('劇場') bg-purple-100 text-purple-800 @break
                                        @case('稽古場') bg-green-100 text-green-800 @break
                                        @case('倉庫') bg-blue-100 text-blue-800 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch">
                                    {{ $location->type }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $location->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $location->status_label }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">ふりがな</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->furigana ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">機材数</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $location->equipments_count }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">郵便番号</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->postal_code ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">住所</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->address ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->created_at->format('Y-m-d') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->updated_at->format('Y-m-d') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 連絡先情報 -->
            @if($location->tel1 || $location->tel2 || $location->fax || $location->email1 || $location->email2)
                <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">連絡先情報</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-6">
                            @if($location->tel1)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500">
                                        電話1
                                        @if($location->tel1_name)
                                            ({{ $location->tel1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900">
                                        <a href="tel:{{ $location->tel1 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->tel1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->tel2)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500">
                                        電話2
                                        @if($location->tel2_name)
                                            ({{ $location->tel2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900">
                                        <a href="tel:{{ $location->tel2 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->tel2 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->fax)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500">FAX</dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900">{{ $location->fax }}</dd>
                                </div>
                            @endif

                            @if($location->email1)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500">
                                        メール1
                                        @if($location->email1_name)
                                            ({{ $location->email1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900">
                                        <a href="mailto:{{ $location->email1 }}" class="text-blue-600 hover:text-blue-800 break-all">
                                            {{ $location->email1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->email2)
                                <div>
                                    <dt class="text-xs sm:text-sm font-medium text-gray-500">
                                        メール2
                                        @if($location->email2_name)
                                            ({{ $location->email2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-xs sm:text-sm text-gray-900">
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
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="whitespace-pre-wrap text-xs sm:text-sm text-gray-900">{{ $location->note }}</div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
