@extends('layouts.master')

@section('title', 'ユーザーマスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.users.index') }}" class="text-blue-600 hover:text-blue-800">ユーザーマスタ</a>
    > <span class="text-gray-800">{{ $user->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ユーザーマスタ詳細</h1>
        <p class="mt-1 text-sm text-gray-600">ユーザー「{{ $user->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'admin')
            <a href="{{ route('master.users.edit', $user) }}"
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
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                        @if($user->is_resigned)
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                退職済み
                            </span>
                        @elseif($user->is_on_leave)
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                休職中
                            </span>
                        @else
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                在職中
                            </span>
                        @endif
                    </div>
                </div>
                <div class="px-6 py-4">
                    <!-- プロフィール画像 -->
                    <div class="mb-4">
                        @if($user->icon)
                            <img class="h-16 w-16 rounded-full" src="{{ $user->icon }}" alt="{{ $user->name }}">
                        @else
                            <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-600 text-lg">{{ mb_substr($user->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- ソート順 -->
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->sort ?? 0 }}</dd>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 氏名・フリガナ -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">氏名</dt>
                            <dd class="mt-1 text-sm text-gray-900 @if($user->is_resigned) line-through text-gray-500 @endif">
                                {{ $user->name }}
                                @if($user->furigana)
                                    <span class="ml-2 text-xs text-gray-500">{{ $user->furigana }}</span>
                                @endif
                            </dd>
                            <dd class="mt-1 text-sm text-gray-600">{{ $user->email }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">所属</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($user->affiliation === 'employee') bg-blue-100 text-blue-800
                                    @elseif($user->affiliation === 'partner') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $user->affiliation_label }}
                                </span>
                            </dd>
                        </div>


                        <div>
                            <dt class="text-sm font-medium text-gray-500">入社日</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($user->hired_at)
                                    {{ $user->hired_at->format('Y年m月d日') }}
                                    <span class="ml-2 text-xs text-gray-500">
                                        @if($user->resigned_at)
                                            ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::parse($user->resigned_at))->format('%y年%mヶ月') }})
                                        @else
                                            ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::now())->format('%y年%mヶ月') }})
                                        @endif
                                    </span>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">退職日</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($user->resigned_at)
                                    <span class="text-red-600 font-medium">{{ $user->resigned_at->format('Y年m月d日') }}</span>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">生年月日</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($user->birthday)
                                    {{ $user->birthday->format('Y年m月d日') }}
                                    <span class="ml-2 text-xs text-gray-500">
                                        ({{ \Carbon\Carbon::parse($user->birthday)->age }}歳)
                                    </span>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">LINE WORKS ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->lineworks_id ?? '---' }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 職種・役割 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">職種・役割</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                        @if($user->is_staff)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                スタッフ
                            </span>
                        @endif
                        @if($user->is_designer)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                                サウンドデザイナー
                            </span>
                        @endif
                        @if($user->is_driver)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                ドライバー
                            </span>
                        @endif
                        @if(!$user->is_staff && !$user->is_designer && !$user->is_driver)
                            <span class="text-gray-500 text-sm">職種・役割が設定されていません</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 連絡先情報 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">連絡先情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-6">
                        <!-- 携帯電話 -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">携帯電話</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->mobile_phone ?: '---' }}</dd>
                        </div>

                        <!-- 郵便番号・住所 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">郵便番号</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->postal_code ?: '---' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">住所</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->address ?: '---' }}</dd>
                            </div>
                        </div>

                        <!-- 緊急連絡先 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">緊急連絡先氏名</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->emergency_contact_name ?: '---' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">緊急連絡先電話番号</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $user->emergency_contact_phone ?: '---' }}</dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- システム情報 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">システム情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">権限</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @switch($user->role)
                                        @case('admin') bg-red-100 text-red-800 @break
                                        @case('editor') bg-blue-100 text-blue-800 @break
                                        @case('viewer') bg-green-100 text-green-800 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch">
                                    @switch($user->role)
                                        @case('admin') 管理者 @break
                                        @case('editor') 編集者 @break
                                        @case('viewer') 閲覧者 @break
                                        @default 不明 @break
                                    @endswitch
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">アカウント状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">登録日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('Y年m月d日 H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">最終更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('Y年m月d日 H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- メモ -->
            @if($user->notes)
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">メモ・備考</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $user->notes }}</p>
                </div>
            </div>
            @endif

            <!-- 権限説明 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">権限について</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">閲覧者</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• データの閲覧のみ可能</li>
                                <li>• 編集・削除は不可</li>
                                <li>• CSVエクスポート可能</li>
                            </ul>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">編集者</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• データの閲覧・作成・編集</li>
                                <li>• 削除は不可</li>
                                <li>• CSV機能利用可能</li>
                            </ul>
                        </div>

                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="flex items-center mb-2">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">管理者</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• 全ての操作が可能</li>
                                <li>• データの削除可能</li>
                                <li>• ユーザー管理可能</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 認証情報 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">認証情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-800">LINE WORKS認証</h4>
                                <div class="mt-1 text-sm text-blue-700">
                                    <p>このユーザーはLINE WORKS認証でログインします。パスワードによる認証は行いません。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection