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

        <h2 class="text-2xl font-bold text-gray-900 mb-2">二段階認証</h2>
        <p class="text-gray-600 mb-6">LINE WORKSに送信された6桁の認証コードを入力してください</p>

        @if (session('status'))
            <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-2 rounded-md mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-md mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify') }}" class="max-w-md mx-auto">
            @csrf

            <div class="mb-6" x-data="otpInput()">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">認証コード</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    x-model="code"
                    x-on:input="formatCode"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="w-full px-4 py-3 text-center text-2xl font-mono tracking-widest border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="000000"
                    required
                    autofocus
                >
                <p class="mt-2 text-sm text-gray-500">有効期限: 10分</p>
            </div>

            <button
                type="submit"
                class="w-full bg-green-500 text-white px-6 py-3 rounded-md font-bold transition-colors hover:bg-green-600 mb-4"
            >
                認証する
            </button>
        </form>

        <form method="POST" action="{{ route('two-factor.resend') }}" class="max-w-md mx-auto">
            @csrf
            <button
                type="submit"
                class="w-full bg-gray-200 text-gray-700 px-6 py-3 rounded-md font-medium transition-colors hover:bg-gray-300"
            >
                認証コードを再送信
            </button>
        </form>

        <div class="text-gray-500 text-sm mt-6">
            <p>認証コードが届かない場合は、再送信ボタンを押してください</p>
        </div>
    </div>

    @push('scripts')
    <script>
        function otpInput() {
            return {
                code: '',
                formatCode() {
                    // 数字のみを許可
                    this.code = this.code.replace(/[^0-9]/g, '');
                }
            }
        }
    </script>
    @endpush
</x-guest-layout>
