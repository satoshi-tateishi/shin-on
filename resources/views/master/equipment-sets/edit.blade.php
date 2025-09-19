@extends('layouts.master')

@section('title', '機材セット編集')

@section('breadcrumb')
    > <a href="{{ route('equipment-sets.index') }}" class="text-blue-600 hover:text-blue-800">機材セットマスタ</a>
    > <span class="text-gray-800">{{ $equipmentSet->name }}</span>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材セット編集</h1>
        <p class="mt-1 text-sm text-gray-600">機材セット「{{ $equipmentSet->name }}」を編集します。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('equipment-sets.index') }}"
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
        <form method="POST" action="{{ route('master.equipment-sets.update', $equipmentSet) }}" class="max-w-2xl">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- セット名 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        セット名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $equipmentSet->name) }}" required
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 説明 -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        説明
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                     @error('description') border-red-300 @enderror">{{ old('description', $equipmentSet->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 有効フラグ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">状態</label>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', $equipmentSet->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            有効
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">無効にすると新規登録などで選択できなくなります。</p>
                </div>

                <!-- ボタン -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('equipment-sets.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        更新する
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
