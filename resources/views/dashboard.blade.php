<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - ダッシュボード</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            @if(auth()->user()->icon)
                                <img src="{{ auth()->user()->icon }}" alt="Icon" class="w-8 h-8 rounded-full">
                            @endif
                            <span class="text-sm text-gray-700">{{ auth()->user()->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                ログアウト
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 flex items-center justify-center">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">ダッシュボード</h2>
                        <p class="text-gray-600">LINE WORKS SSOによるログインが完了しました！</p>
                        <div class="mt-4 text-sm text-gray-500">
                            <p>ユーザー: {{ auth()->user()->name }}</p>
                            <p>メール: {{ auth()->user()->email }}</p>
                            @if(auth()->user()->department)
                                <p>部署: {{ auth()->user()->department }}</p>
                            @endif
                            @if(auth()->user()->position)
                                <p>役職: {{ auth()->user()->position }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>