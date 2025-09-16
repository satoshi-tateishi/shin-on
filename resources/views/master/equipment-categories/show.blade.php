@extends('layouts.master')

@section('title', '機材大分類詳細')

@section('breadcrumb')
    > <a href="{{ route('equipment-categories.index') }}" class="text-blue-600 hover:text-blue-800">機材カテゴリ</a>
    > <span class="text-gray-800">{{ $equipmentCategory->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材カテゴリ詳細</h1>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('equipment-categories.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.equipment-categories.edit', $equipmentCategory) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                編集
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="p-6">
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentCategory->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">カテゴリ名</dt>
                            <dd class="mt-1 text-sm font-gray-900 font-bold">{{ $equipmentCategory->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentCategory->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $equipmentCategory->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $equipmentCategory->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentCategory->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentCategory->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 関連中分類 -->
            @if($equipmentCategory->subcategories && $equipmentCategory->subcategories->count() > 0)
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">関連サブカテゴリ ({{ $equipmentCategory->subcategories->count() }}件)</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">中分類名</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">機材数</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ソート順</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($equipmentCategory->subcategories->take(10) as $subcategory)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <a href="{{ route('master.equipment-subcategories.show', $subcategory) }}" class="text-blue-600 hover:text-blue-900">
                                                    {{ $subcategory->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $subcategory->equipments_count ?? $subcategory->equipments()->count() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $subcategory->sort }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($equipmentCategory->subcategories->count() > 10)
                            <div class="mt-3 text-sm text-gray-500">
                                他 {{ $equipmentCategory->subcategories->count() - 10 }} 件のサブカテゴリがあります
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">関連サブカテゴリ</h3>
                    </div>
                    <div class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">このカテゴリに紐づくサブカテゴリはまだありません。</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
