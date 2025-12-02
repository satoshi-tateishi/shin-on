<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - ダッシュボード</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icon-180.png') }}">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="dB">
    <meta name="theme-color" content="#3B82F6">

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
                <div class="flex justify-between items-center h-14 sm:h-16">
                    <div class="flex items-center">
                        @if($companyLogo ?? null)
                            <a href="{{ route('dashboard') }}">
                                <img src="{{ asset('storage/' . $companyLogo) }}"
                                     alt="会社ロゴ"
                                     class="h-8 sm:h-10 w-auto object-contain">
                            </a>
                        @endif
                    </div>
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <div class="flex items-center space-x-2">
                            @if(auth()->user()->icon)
                                <img src="{{ auth()->user()->icon }}" alt="Icon" class="w-6 h-6 sm:w-8 sm:h-8 rounded-full">
                            @endif
                            <span class="text-sm sm:text-base text-gray-700">{{ auth()->user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm sm:text-base text-gray-500 hover:text-gray-700 cursor-pointer">
                                ログアウト
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl px-6 py-2 sm:px-8 sm:py-3 mb-10 shadow-sm border border-blue-100">
                    <!-- System Title -->
                    <div class="flex items-baseline space-x-2 mb-4">
                        <span class="text-sm sm:text-lg font-medium text-gray-600">Equipment Management System</span>
                        <span class="text-xl sm:text-2xl font-bold text-gray-900">"dB"</span>
                    </div>
                    <div class="flex items-center flex-wrap gap-2">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">
                            ようこそ、{{ auth()->user()->name }}さん
                        </h1>
                        @if(auth()->user()->role)
                            @php
                                $roleLabels = [
                                    'viewer' => '編集権限なし',
                                    'editor' => '編集権限あり',
                                    'admin' => '管理者'
                                ];
                                $roleColors = [
                                    'viewer' => 'bg-gray-100 text-gray-800 border-gray-200',
                                    'editor' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'admin' => 'bg-red-50 text-red-700 border-red-200'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $roleColors[auth()->user()->role] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                {{ $roleLabels[auth()->user()->role] ?? auth()->user()->role }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- 管理・運用 Section -->
                <div class="mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full mr-4"></div>
                        <h2 class="text-2xl font-bold text-gray-900">機材管理</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <!-- 公演管理 -->
                        <a href="{{ route('performances.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-indigo-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-indigo-50 rounded-lg group-hover:bg-indigo-100 transition-colors">
                                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-900 transition-colors">公演使用機材</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 機材スケジュール表 -->
                        <a href="{{ route('schedule.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-emerald-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-emerald-50 rounded-lg group-hover:bg-emerald-100 transition-colors">
                                            <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-emerald-900 transition-colors">機材スケジュール表</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-emerald-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 在庫表示 -->
                        <a href="{{ route('inventory.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-cyan-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-cyan-50 rounded-lg group-hover:bg-cyan-100 transition-colors">
                                            <svg class="h-6 w-6 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-cyan-900 transition-colors">倉庫別 在庫表示</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-cyan-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 修理管理 -->
                        <a href="{{ route('repair-records.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-red-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-red-50 rounded-lg group-hover:bg-red-100 transition-colors">
                                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-red-900 transition-colors">修理管理</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-red-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 倉庫間移動 -->
                        <a href="{{ route('equipment-transfer.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-amber-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-amber-50 rounded-lg group-hover:bg-amber-100 transition-colors">
                                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-amber-900 transition-colors">倉庫間移動</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                @if(auth()->user()->role !== 'viewer')
                <!-- Master Data Section -->
                <div class="mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-1 h-6 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full mr-4"></div>
                        <h2 class="text-2xl font-bold text-gray-900">マスタ管理</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- 機材マスタ -->
                        <a href="{{ route('master.equipments.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-teal-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-teal-50 rounded-lg group-hover:bg-teal-100 transition-colors">
                                            <svg class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53L6.75 15.75H4.251a2.25 2.25 0 01-2.25-2.25v-3a2.25 2.25 0 012.25-2.25H6.75z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-teal-900 transition-colors">機材マスタ</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-teal-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 機材セット -->
                        <a href="{{ route('master.equipment-sets.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-pink-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-pink-50 rounded-lg group-hover:bg-pink-100 transition-colors">
                                            <svg class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53L6.75 15.75H4.251a2.25 2.25 0 01-2.25-2.25v-3a2.25 2.25 0 012.25-2.25H6.75z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-pink-900 transition-colors">機材セット</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-pink-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- カテゴリマスタ -->
                        <a href="{{ route('master.equipment-categories.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-blue-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-blue-50 rounded-lg group-hover:bg-blue-100 transition-colors">
                                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-900 transition-colors">カテゴリ</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- サブカテゴリマスタ -->
                        <a href="{{ route('master.equipment-subcategories.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-indigo-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-indigo-50 rounded-lg group-hover:bg-indigo-100 transition-colors">
                                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-900 transition-colors">サブカテゴリ</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- ユーザーマスタ -->
                        <a href="{{ route('master.users.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-purple-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-purple-50 rounded-lg group-hover:bg-purple-100 transition-colors">
                                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-purple-900 transition-colors">ユーザー</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-purple-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- ポジションマスタ -->
                        <a href="{{ route('master.positions.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-amber-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-amber-50 rounded-lg group-hover:bg-amber-100 transition-colors">
                                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-amber-900 transition-colors">ポジション</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 使用場所マスタ -->
                        <a href="{{ route('master.locations.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-green-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-green-50 rounded-lg group-hover:bg-green-100 transition-colors">
                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-green-900 transition-colors">使用場所</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-green-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- プロダクションマスタ -->
                        <a href="{{ route('master.productions.index') }}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-orange-200">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-orange-50 rounded-lg group-hover:bg-orange-100 transition-colors">
                                            <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-orange-900 transition-colors">プロダクション</h3>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-400 group-hover:text-orange-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Admin Section -->
                <div class="mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-1 h-6 bg-gradient-to-b from-red-500 to-red-600 rounded-full mr-4"></div>
                        <h2 class="text-2xl font-bold text-gray-900">システム管理</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-12">
                        <!-- 会社設定 -->
                        <a href="{{ route('admin.company-info.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-red-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-red-50 rounded-lg group-hover:bg-red-100 transition-colors">
                                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-red-900 transition-colors">会社設定</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-red-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        @if(auth()->user()->role === 'admin')
                        <!-- バックアップ管理 -->
                        <a href="{{ route('admin.backup.index') }}" class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-blue-200 hover:-translate-y-1">
                            <div class="px-6 py-2 sm:px-8 sm:py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-blue-50 rounded-lg group-hover:bg-blue-100 transition-colors">
                                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-900 transition-colors">バックアップ管理</h3>
                                    </div>
                                    <svg class="h-5 w-5 text-gray-400 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @if(auth()->user()->role === 'admin')
                <!-- Activity Log Section -->
                <div class="mb-12">
                    <div class="flex items-center mb-6">
                        <div class="w-1 h-6 bg-gradient-to-b from-gray-500 to-gray-600 rounded-full mr-4"></div>
                        <h2 class="text-2xl font-bold text-gray-900">アクティビティ</h2>
                    </div>
                    <a href="{{ route('activity-logs.index') }}" class="block group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-gray-200">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg group-hover:bg-gray-200 transition-colors">
                                        <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-gray-700 transition-colors">操作履歴</h3>
                                        <p class="text-sm text-gray-500">
                                            全 {{ number_format($activityStats['total']) }} 件
                                            @if($activityStats['today'] > 0)
                                                <span class="text-blue-600">（本日 {{ $activityStats['today'] }} 件）</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>

                        </div>
                    </a>
                </div>
                @endif

            </div>
        </div>
    </div>
</body>
</html>
