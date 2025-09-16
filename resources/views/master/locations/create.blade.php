@extends('layouts.master')

@section('title', '使用場所マスタ作成')

@section('breadcrumb')
    > <a href="{{ route('master.locations.index') }}" class="text-blue-600 hover:text-blue-800">使用場所マスタ</a>
    > <span class="text-gray-800">新規作成</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">使用場所マスタ作成</h1>
        <p class="mt-1 text-sm text-gray-600">新しい使用場所を作成します。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('locations.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <form method="POST" action="{{ route('master.locations.store') }}" class="max-w-4xl">
            @csrf

            <div class="space-y-8">
                <!-- 基本情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- タイプ -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                    タイプ <span class="text-red-500">*</span>
                                </label>
                                <select name="type" id="type" required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                               @error('type') border-red-300 @enderror">
                                    <option value="">選択してください</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 有効フラグ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">状態</label>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        有効
                                    </label>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">無効にすると新規登録などで選択できなくなります。</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 場所名 -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    場所名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ふりがな -->
                            <div>
                                <label for="furigana" class="block text-sm font-medium text-gray-700 mb-2">
                                    ふりがな
                                </label>
                                <input type="text" name="furigana" id="furigana" value="{{ old('furigana') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('furigana') border-red-300 @enderror">
                                @error('furigana')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 連絡先情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">連絡先情報</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <!-- 電話番号1 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="tel1_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    電話1名称
                                </label>
                                <input type="text" name="tel1_name" id="tel1_name" value="{{ old('tel1_name') }}"
                                       placeholder="代表"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel1_name') border-red-300 @enderror">
                                @error('tel1_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="tel1" class="block text-sm font-medium text-gray-700 mb-2">
                                    電話番号1
                                </label>
                                <input type="text" name="tel1" id="tel1" value="{{ old('tel1') }}"
                                       placeholder="03-1234-5678"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel1') border-red-300 @enderror">
                                @error('tel1')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- 電話番号2 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="tel2_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    電話2名称
                                </label>
                                <input type="text" name="tel2_name" id="tel2_name" value="{{ old('tel2_name') }}"
                                       placeholder="担当者直通"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel2_name') border-red-300 @enderror">
                                @error('tel2_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="tel2" class="block text-sm font-medium text-gray-700 mb-2">
                                    電話番号2
                                </label>
                                <input type="text" name="tel2" id="tel2" value="{{ old('tel2') }}"
                                       placeholder="03-1234-5679"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('tel2') border-red-300 @enderror">
                                @error('tel2')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- FAX -->
                        <div>
                            <label for="fax" class="block text-sm font-medium text-gray-700 mb-2">
                                FAX
                            </label>
                            <input type="text" name="fax" id="fax" value="{{ old('fax') }}"
                                   placeholder="03-1234-5680"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                          @error('fax') border-red-300 @enderror">
                            @error('fax')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- メールアドレス1 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="email1_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    メール1名称
                                </label>
                                <input type="text" name="email1_name" id="email1_name" value="{{ old('email1_name') }}"
                                       placeholder="代表"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email1_name') border-red-300 @enderror">
                                @error('email1_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="email1" class="block text-sm font-medium text-gray-700 mb-2">
                                    メールアドレス1
                                </label>
                                <input type="email" name="email1" id="email1" value="{{ old('email1') }}"
                                       placeholder="info@example.com"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email1') border-red-300 @enderror">
                                @error('email1')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- メールアドレス2 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="email2_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    メール2名称
                                </label>
                                <input type="text" name="email2_name" id="email2_name" value="{{ old('email2_name') }}"
                                       placeholder="担当者"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email2_name') border-red-300 @enderror">
                                @error('email2_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="email2" class="block text-sm font-medium text-gray-700 mb-2">
                                    メールアドレス2
                                </label>
                                <input type="email" name="email2" id="email2" value="{{ old('email2') }}"
                                       placeholder="manager@example.com"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                              @error('email2') border-red-300 @enderror">
                                @error('email2')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 住所情報 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">住所情報</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <!-- 郵便番号 -->
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                                郵便番号
                            </label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                                   placeholder="123-4567"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                          @error('postal_code') border-red-300 @enderror">
                            @error('postal_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 住所 -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                住所
                            </label>
                            <textarea name="address" id="address" rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                             @error('address') border-red-300 @enderror">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700 mb-2">
                                備考
                            </label>
                            <textarea name="note" id="note" rows="4"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                             @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
                            @error('note')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('locations.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        作成する
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection