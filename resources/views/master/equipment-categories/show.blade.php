@extends('layouts.master')

@section('title', '機材カテゴリ詳細')

@section('breadcrumb')
    > <a href="{{ route('master.equipment-categories.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">機材カテゴリマスタ</a>
    > <span class="text-gray-800 dark:text-gray-200">{{ $equipmentCategory->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $equipmentCategory->name }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.equipment-categories.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>

            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                <a href="{{ route('master.equipment-categories.edit', $equipmentCategory) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    編集
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <div class="grid grid-cols-2 gap-3 sm:gap-6">
                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">カテゴリ名</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white font-bold">{{ $equipmentCategory->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $equipmentCategory->is_active ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' }}">
                                    {{ $equipmentCategory->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">ソート順</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentCategory->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">サブカテゴリ数</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentCategory->subcategories_count ?? $equipmentCategory->subcategories()->count() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">作成日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentCategory->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">更新日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentCategory->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 関連サブカテゴリ -->
            @if($equipmentCategory->subcategories && $equipmentCategory->subcategories->count() > 0)
                <div class="mt-4 sm:mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">関連サブカテゴリ ({{ $equipmentCategory->subcategories->count() }}件)</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">サブカテゴリ名</th>
                                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">状態</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($equipmentCategory->subcategories->take(10) as $subcategory)
                                        <tr>
                                            <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                                <a href="{{ route('master.equipment-subcategories.show', $subcategory) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                    {{ $subcategory->name }}
                                                </a>
                                            </td>
                                            <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                                    {{ $subcategory->is_active ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' }}">
                                                    {{ $subcategory->is_active ? '有効' : '無効' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($equipmentCategory->subcategories->count() > 10)
                            <div class="mt-3 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                他 {{ $equipmentCategory->subcategories->count() - 10 }} 件のサブカテゴリがあります
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="mt-4 sm:mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">関連サブカテゴリ</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-6 sm:py-8 text-center">
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">このカテゴリに紐づくサブカテゴリはまだありません。</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
