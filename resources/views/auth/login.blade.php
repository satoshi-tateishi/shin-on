<x-guest-layout>
    <div class="text-center">
        @php
            $companyLogo = \App\Models\CompanyLogo::getActiveLogo();
        @endphp

        @if($companyLogo)
            <div class="mb-2">
                <img src="{{ asset('storage/' . $companyLogo->file_path) }}"
                     alt="会社ロゴ"
                     class="h-19 w-auto mx-auto object-contain">
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-md mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-md mb-4">
                {{ session('error') }}
            </div>
        @endif

        <a href="{{ route('lineworks.redirect') }}" class="inline-block bg-green-500 text-white px-6 py-3 no-underline rounded-md font-bold my-4 transition-colors hover:bg-green-600">
            LINE WORKS でログイン
        </a>

        {{--<div class="text-gray-500 text-sm mt-4">
            <p>shin·on 社員の方のみアクセス可能です</p>
            <p>LINE WORKSアカウントでログインしてください</p>
        </div>--}}
    </div>
</x-guest-layout>
