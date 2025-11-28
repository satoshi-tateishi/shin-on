@extends('layouts.master')

@section('title', 'ポジションマスタ編集')

@section('breadcrumb')
    > <a href="{{ route('master.positions.index') }}" class="text-blue-600 hover:text-blue-800">ポジションマスタ</a>
    > <span class="text-gray-800">{{ $position->name }}</span>
    > <span class="text-gray-800">編集</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $position->name }} - 編集</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.positions.show', $position) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>
            <button type="submit" form="edit-form"
                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                保存
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <form id="edit-form" method="POST" action="{{ route('master.positions.update', $position) }}" class="max-w-2xl">
            @csrf
            @method('PUT')

            <div class="space-y-4 sm:space-y-6">
                <!-- ポジション名 -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                        ポジション名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $position->name) }}" required
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500
                                  @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 有効フラグ -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', $position->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900">
                            有効
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">無効にすると選択できなくなります</p>
                </div>
            </div>
        </form>
    </div>
@endsection
