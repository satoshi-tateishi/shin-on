@extends('layouts.master')

@section('title', '機材セット詳細')

@section('breadcrumb')
    > <a href="{{ route('equipment-sets.index') }}" class="text-blue-600 hover:text-blue-800">機材セット</a>
    > <span class="text-gray-800">{{ $equipmentSet->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材セット詳細</h1>
        <p class="mt-1 text-sm text-gray-600">機材セット「{{ $equipmentSet->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('equipment-sets.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.equipment-sets.edit', $equipmentSet) }}"
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
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">セット名</dt>
                            <dd class="mt-1 text-sm font-gray-900 font-bold">{{ $equipmentSet->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $equipmentSet->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $equipmentSet->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>

                    @if($equipmentSet->description)
                        <div class="mt-6">
                            <dt class="text-sm font-medium text-gray-500">説明</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $equipmentSet->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 機材セット内容 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">機材セット内容</h3>
                </div>
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h4 class="mt-2 text-sm font-medium text-gray-900">機材セット内容管理機能</h4>
                    <p class="mt-1 text-sm text-gray-500">
                        機材セットに含まれる機材の管理機能は今後実装予定です。<br>
                        現在は基本的なセット情報の管理のみ対応しています。
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection