@extends('layouts.master')

@section('title', 'ポジションマスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.positions.index') }}" class="text-blue-600 hover:text-blue-800">ポジションマスタ</a>
    > <span class="text-gray-800">{{ $position->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ポジションマスタ詳細</h1>
        <p class="mt-1 text-sm text-gray-600">ポジション「{{ $position->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('positions.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.positions.edit', $position) }}"
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
                            <dd class="mt-1 text-sm text-gray-900">{{ $position->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ポジション名</dt>
                            <dd class="mt-1 text-sm font-gray-900 font-bold">{{ $position->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $position->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $position->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $position->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $position->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $position->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 関連情報 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">関連情報</h3>
                </div>
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h4 class="mt-2 text-sm font-medium text-gray-900">ポジション関連機能</h4>
                    <p class="mt-1 text-sm text-gray-500">
                        このポジションに関連するユーザーや公演情報の管理機能は今後実装予定です。<br>
                        現在は基本的なポジション情報の管理のみ対応しています。
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection