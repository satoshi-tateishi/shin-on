@extends('layouts.master')

@section('title', 'ユーザーマスタ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.users.index') }}" class="text-blue-600 hover:text-blue-800">ユーザーマスタ</a>
    > <span class="text-gray-800">{{ $user->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2 @if($user->is_resigned) line-through text-gray-500 @endif">{{ $user->name }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.users.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>

            @if(auth()->user()->role === 'admin')
                <a href="{{ route('master.users.edit', $user) }}"
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
                    <div class="flex items-center justify-between">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
                        @if($user->is_resigned)
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                退職済み
                            </span>
                        @elseif($user->is_on_leave)
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                休職中
                            </span>
                        @else
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                在職中
                            </span>
                        @endif
                    </div>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <!-- プロフィール画像 -->
                    <div class="mb-4 sm:mb-6 flex items-center">
                        @if($user->icon)
                            <img class="h-14 w-14 sm:h-20 sm:w-20 rounded-full" src="{{ $user->icon }}" alt="{{ $user->name }}">
                        @else
                            <div class="h-14 w-14 sm:h-20 sm:w-20 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-600 text-lg sm:text-2xl font-medium">{{ mb_substr($user->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="ml-3 sm:ml-4">
                            <h2 class="text-base sm:text-xl font-semibold text-gray-900 @if($user->is_resigned) line-through text-gray-500 @endif">
                                {{ $user->name }}
                                @if($user->furigana)
                                    <span class="ml-1 sm:ml-2 text-xs sm:text-sm font-normal text-gray-500">{{ $user->furigana }}</span>
                                @endif
                            </h2>
                            <p class="text-xs sm:text-sm text-gray-600">{{ $user->email }}</p>
                        </div>
                    </div>

                    <!-- テーブル形式 -->
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50 w-1/3 sm:w-1/4">所属</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                        @if($user->affiliation === 'employee') bg-blue-100 text-blue-800
                                        @elseif($user->affiliation === 'partner') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $user->affiliation_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">入社日</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">
                                    @if($user->hired_at)
                                        {{ $user->hired_at->format('Y-m-d') }}
                                        <span class="ml-1 text-xs text-gray-500">
                                            @if($user->resigned_at)
                                                ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::parse($user->resigned_at))->format('%y年%mヶ月') }})
                                            @else
                                                ({{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::now())->format('%y年%mヶ月') }})
                                            @endif
                                        </span>
                                    @else
                                        ---
                                    @endif
                                </td>
                            </tr>
                            @if($user->resigned_at)
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">退職日</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">
                                    <span class="text-red-600 font-medium">{{ $user->resigned_at->format('Y-m-d') }}</span>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">生年月日</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">
                                    @if($user->birthday)
                                        {{ $user->birthday->format('Y-m-d') }}
                                        <span class="ml-1 text-xs text-gray-500">
                                            ({{ \Carbon\Carbon::parse($user->birthday)->age }}歳)
                                        </span>
                                    @else
                                        ---
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">職種</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if($user->is_staff)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                スタッフ
                                            </span>
                                        @endif
                                        @if($user->is_designer)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                デザイナー
                                            </span>
                                        @endif
                                        @if($user->is_driver)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ドライバー
                                            </span>
                                        @endif
                                        @if(!$user->is_staff && !$user->is_designer && !$user->is_driver)
                                            <span class="text-gray-500 text-xs">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 連絡先情報 -->
            <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">連絡先情報</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50 w-1/3 sm:w-1/4">携帯電話</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->mobile_phone ?: '---' }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">郵便番号</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->postal_code ?: '---' }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">住所</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->address ?: '---' }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">緊急連絡先</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->emergency_contact_name ?: '---' }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">緊急連絡先TEL</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->emergency_contact_phone ?: '---' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- システム情報 -->
            <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">システム情報</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50 w-1/3 sm:w-1/4">権限</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
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
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">状態</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                        {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $user->is_active ? '有効' : '無効' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">登録日</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->created_at->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 font-medium text-gray-500 bg-gray-50">更新日</td>
                                <td class="px-2 sm:px-3 py-2 sm:py-3 text-gray-900">{{ $user->updated_at->format('Y-m-d') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- メモ -->
            @if($user->notes)
            <div class="mt-4 sm:mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">メモ</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <p class="text-xs sm:text-sm text-gray-900 whitespace-pre-wrap">{{ $user->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection