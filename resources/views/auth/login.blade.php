<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - ログイン</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name') }}</h1>
                <p class="text-sm text-gray-600 mt-2">LINE WORKSでログインしてください</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-center">
                <a href="{{ route('lineworks.redirect') }}"
                   class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.2 0-.391-.084-.628-.421a2.421 2.421 0 0 0-1.907-.832c-.824 0-1.589.353-1.907.832-.233.337-.418.421-.618.421-.066 0-.135-.01-.199-.031-.258-.086-.432-.326-.432-.596 0-.565.797-1.593 3.156-1.593 2.359 0 3.166 1.028 3.166 1.593z"/>
                    </svg>
                    LINE WORKSでログイン
                </a>
            </div>
        </div>
    </div>
</body>
</html>