<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - ダッシュボード</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        @if($companyLogo ?? null)
                            <img src="{{ asset('storage/' . $companyLogo) }}"
                                 alt="会社ロゴ"
                                 class="h-10 w-auto object-contain">
                        @endif
                        <h1 class="text-xl font-semibold text-gray-900">
                            shin-on 業務アプリポータル
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            @if(auth()->user()->icon)
                                <img src="{{ auth()->user()->icon }}" alt="Icon" class="w-8 h-8 rounded-full">
                            @endif
                            <span class="text-gray-700">{{ auth()->user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-gray-700">
                                ログアウト
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                            ようこそ、{{ auth()->user()->name }}さん
                        </h2>
                        <div class="flex items-center space-x-4">

                            @if(auth()->user()->role)
                                @php
                                    $roleLabels = [
                                        'viewer' => '編集権限なし',
                                        'editor' => '編集権限あり',
                                        'admin' => '管理者'
                                    ];
                                    $roleColors = [
                                        'viewer' => 'bg-gray-100 text-gray-800',
                                        'editor' => 'bg-blue-100 text-blue-800',
                                        'admin' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleColors[auth()->user()->role] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $roleLabels[auth()->user()->role] ?? auth()->user()->role }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 機材管理 -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-teal-500" fill="currentColor" viewBox="0 0 24 24">
                                        <!-- スピーカー本体 -->
                                        <polygon points="1,9 1,15 4,15 9,19 9,5 4,9" />
                                        <!-- 音波 -->
                                        <path d="M12,8 C13.5,9 14,10 14,12 C14,14 13.5,15 12,16" stroke="currentColor" fill="none" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M15,6 C17.5,7.5 18.5,9.5 18.5,12 C18.5,14.5 17.5,16.5 15,18" stroke="currentColor" fill="none" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M18,4 C21.5,6 22.5,8.5 22.5,12 C22.5,15.5 21.5,18 18,20" stroke="currentColor" fill="none" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">機材関連マスタ</h3>

                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('master.equipments.index') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700">
                                        機材マスタ
                                    </a>
                                    <a href="{{ route('master.equipment-sets.index') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                        機材セットマスタ
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('master.equipment-categories.index') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        カテゴリマスタ
                                    </a>
                                    <a href="{{ route('master.equipment-subcategories.index') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        サブカテゴリマスタ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ユーザーマスタ -->
                    <br>
                    <div>
                    <a href="{{ route('master.users.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">ユーザーマスタ</h3>

                                </div>
                            </div>
                        </div>
                    </a>
                    </div>

                    <!-- ポジションマスタ -->
                    <a href="{{ route('master.positions.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">ポジションマスタ</h3>

                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- 使用場所マスタ -->
                    <a href="{{ route('master.locations.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">使用場所マスタ</h3>

                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- プロダクションマスタ -->
                    <a href="{{ route('master.productions.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">プロダクションマスタ</h3>

                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- 修理管理 -->
                    <a href="{{ route('repair-records.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">修理管理</h3>
                                    <p class="text-sm text-gray-500">機材の修理・メンテナンス記録</p>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- 公演管理 -->
                    <a href="{{ route('performances.index') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow cursor-pointer block">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">公演管理</h3>
                                    <p class="text-sm text-gray-500">公演・フェーズ・機材使用管理</p>
                                </div>
                            </div>
                        </div>
                    </a>


                </div>

            </div>
        </div>
    </div>
</body>
</html>
