@extends('layouts.master')

@section('title', '使用場所マスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.locations.index') }}" class="text-blue-600 hover:text-blue-800">使用場所マスタ</a>
    > <span class="text-gray-800">{{ $location->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">使用場所マスタ詳細</h1>
        <p class="mt-1 text-sm text-gray-600">使用場所「{{ $location->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('locations.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.locations.edit', $location) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                編集
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="p-6">
        <div class="max-w-6xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $location->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $location->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">タイプ</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
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
                            <dt class="text-sm font-medium text-gray-500">場所名</dt>
                            <dd class="mt-1 text-sm font-bold text-gray-900">{{ $location->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ふりがな</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $location->furigana ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $location->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $location->status_label }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">機材数</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $location->equipments_count }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">在庫フィルタ表示</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $location->is_inventory_visible ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $location->is_inventory_visible ? '表示' : '非表示' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">移動フィルタ表示</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $location->is_transfer_visible ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $location->is_transfer_visible ? '表示' : '非表示' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $location->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">最終更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $location->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 連絡先情報 -->
            @if($location->tel1 || $location->tel2 || $location->fax || $location->email1 || $location->email2)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">連絡先情報</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($location->tel1)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">
                                        電話1
                                        @if($location->tel1_name)
                                            ({{ $location->tel1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="tel:{{ $location->tel1 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->tel1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->tel2)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">
                                        電話2
                                        @if($location->tel2_name)
                                            ({{ $location->tel2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="tel:{{ $location->tel2 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->tel2 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->fax)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">FAX</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $location->fax }}</dd>
                                </div>
                            @endif

                            @if($location->email1)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">
                                        メール1
                                        @if($location->email1_name)
                                            ({{ $location->email1_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="mailto:{{ $location->email1 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->email1 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif

                            @if($location->email2)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">
                                        メール2
                                        @if($location->email2_name)
                                            ({{ $location->email2_name }})
                                        @endif
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="mailto:{{ $location->email2 }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $location->email2 }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- 住所情報 -->
            @if($location->postal_code || $location->address)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">住所情報</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            @if($location->postal_code)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">郵便番号</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $location->postal_code }}</dd>
                                </div>
                            @endif

                            @if($location->address)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">住所</dt>
                                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $location->address }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- 備考 -->
            @if($location->note)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="whitespace-pre-wrap text-sm text-gray-900">{{ $location->note }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
