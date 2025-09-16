@extends('layouts.master')

@section('title', 'プロダクションマスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.productions.index') }}" class="text-blue-600 hover:text-blue-800">プロダクションマスタ</a>
    > <span class="text-gray-800">{{ $production->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">プロダクションマスタ詳細</h1>
        <p class="mt-1 text-sm text-gray-600">プロダクション「{{ $production->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('master.productions.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.productions.edit', $production) }}"
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
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $production->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $production->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">タイプ</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $production->type }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">プロダクション名</dt>
                            <dd class="mt-1 text-sm font-bold text-gray-900">{{ $production->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">郵便番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $production->postal_code ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $production->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $production->status_label }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $production->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">最終更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $production->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 住所情報 -->
            @if($production->address)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">住所情報</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="whitespace-pre-wrap text-sm text-gray-900">{{ $production->address }}</div>
                    </div>
                </div>
            @endif

            <!-- 備考 -->
            @if($production->note)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="whitespace-pre-wrap text-sm text-gray-900">{{ $production->note }}</div>
                    </div>
                </div>
            @endif

            <!-- 表示名プレビュー -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="px-6 py-4 border-b border-blue-200">
                    <h3 class="text-lg font-medium text-blue-900">表示名プレビュー</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="text-sm text-blue-700">
                        <p class="mb-2">システム内での表示名:</p>
                        <p class="font-medium text-lg text-blue-900">{{ $production->display_name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection