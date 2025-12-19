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

        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">二段階認証</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">LINE WORKSに送信された6桁の認証コードを入力してください</p>

        @if (session('status'))
            <div class="bg-green-100 dark:bg-green-900/50 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-2 rounded-md mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900/50 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-2 rounded-md mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify') }}" class="max-w-md mx-auto">
            @csrf

            <div class="mb-6" x-data="{
                digits: ['', '', '', '', '', ''],
                getInput(index) {
                    return this.$root.querySelectorAll('input[data-digit]')[index];
                },
                focusNext(index) {
                    if (this.digits[index] && index < 5) {
                        this.getInput(index + 1)?.focus();
                    }
                },
                focusPrev(index, event) {
                    if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                        this.getInput(index - 1)?.focus();
                    }
                },
                handlePaste(event) {
                    event.preventDefault();
                    let paste = (event.clipboardData || window.clipboardData).getData('text');
                    let digits = paste.replace(/[^0-9]/g, '').substring(0, 6).split('');
                    digits.forEach((char, i) => {
                        this.digits[i] = char;
                    });
                    let nextIndex = Math.min(digits.length, 5);
                    this.getInput(nextIndex)?.focus();
                },
                handleInput(index, event) {
                    let value = event.target.value.replace(/[^0-9]/g, '');
                    this.digits[index] = value.substring(0, 1);
                    if (value) {
                        this.focusNext(index);
                    }
                },
                getCode() {
                    return this.digits.join('');
                }
            }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">認証コード</label>
                <div class="flex justify-center items-center gap-2">
                    <!-- 1桁目 -->
                    <input type="text" data-digit="0" x-model="digits[0]" x-on:input="handleInput(0, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(0, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="one-time-code" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" autofocus required>
                    <!-- 2桁目 -->
                    <input type="text" data-digit="1" x-model="digits[1]" x-on:input="handleInput(1, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(1, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="off" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    <!-- 3桁目 -->
                    <input type="text" data-digit="2" x-model="digits[2]" x-on:input="handleInput(2, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(2, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="off" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    <!-- 区切り -->
                    <span class="mx-1 text-gray-400 text-2xl">-</span>
                    <!-- 4桁目 -->
                    <input type="text" data-digit="3" x-model="digits[3]" x-on:input="handleInput(3, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(3, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="off" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    <!-- 5桁目 -->
                    <input type="text" data-digit="4" x-model="digits[4]" x-on:input="handleInput(4, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(4, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="off" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    <!-- 6桁目 -->
                    <input type="text" data-digit="5" x-model="digits[5]" x-on:input="handleInput(5, $event)" x-on:paste="handlePaste($event)" x-on:keydown="focusPrev(5, $event)" x-on:focus="$event.target.select()" maxlength="6" inputmode="numeric" autocomplete="off" class="w-12 h-14 text-center text-2xl font-mono border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                </div>
                <input type="hidden" name="code" x-bind:value="getCode()">
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">有効期限: 10分</p>
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
                class="w-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-6 py-3 rounded-md font-medium transition-colors hover:bg-gray-300 dark:hover:bg-gray-500"
            >
                認証コードを再送信
            </button>
        </form>

        <div class="text-gray-500 dark:text-gray-400 text-sm mt-6">
            <p>認証コードが届かない場合は、再送信ボタンを押してください</p>
        </div>
    </div>

</x-guest-layout>
