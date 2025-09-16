@extends('layouts.master')

@section('title', 'プロダクションマスタ作成')

@section('breadcrumb')
    > <a href="{{ route('master.productions.index') }}" class="text-blue-600 hover:text-blue-800">プロダクションマスタ</a>
    > <span class="text-gray-800">新規作成</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">プロダクションマスタ作成</h1>
        <p class="mt-1 text-sm text-gray-600">新しいプロダクションを作成します。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('master.productions.index') }}"
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
        <form method="POST" action="{{ route('master.productions.store') }}" class="max-w-2xl">
            @csrf

            <div class="space-y-6">
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

                <!-- プロダクション名 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        プロダクション名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

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

                <!-- 備考 -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-2">
                        備考
                    </label>
                    <textarea name="note" id="note" rows="3"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                     @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
                    @error('note')
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

                <!-- ボタン -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('master.productions.index') }}"
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