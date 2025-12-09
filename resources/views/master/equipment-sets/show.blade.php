@extends('layouts.master')

@section('title', '機材セット詳細')

@section('breadcrumb')
    > <a href="{{ route('master.equipment-sets.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">機材セットマスタ</a>
    > <span class="text-gray-800 dark:text-gray-200">{{ $equipmentSet->name }}</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $equipmentSet->name }}</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('master.equipment-sets.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>

            <div class="flex gap-2">
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <a href="{{ route('master.equipment-sets.edit', $equipmentSet) }}"
                       class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        編集
                    </a>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'editor']))
                    <form method="POST" action="{{ route('master.equipment-sets.destroy', $equipmentSet) }}" class="inline"
                          onsubmit="return confirm('機材セット「{{ $equipmentSet->name }}」を削除します。この操作は取り消せません。削除しますか？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700 cursor-pointer">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            削除
                        </button>
                    </form>
                @endif
            </div>
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
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">セット名</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white font-bold">{{ $equipmentSet->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $equipmentSet->is_active ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' }}">
                                    {{ $equipmentSet->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">ソート順</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentSet->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">機材数</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentSet->equipmentItems->count() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">作成日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentSet->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">更新日</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white">{{ $equipmentSet->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>

                    @if($equipmentSet->description)
                        <div class="mt-4 sm:mt-6">
                            <dt class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">説明</dt>
                            <dd class="mt-1 text-xs sm:text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $equipmentSet->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 機材セット内容 -->
            <div class="mt-4 sm:mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">機材セット内容 ({{ $equipmentSet->equipmentItems->count() }}件)</h3>
                    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                        <button onclick="showAddEquipmentModal()" class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 cursor-pointer">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            追加
                        </button>
                    @endif
                </div>

                @if($equipmentSet->equipmentItems->count() > 0)
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="overflow-x-auto">
                            <table id="equipment-items-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                            <th class="px-2 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-10">順序</th>
                                        @endif
                                        <th class="px-2 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">機材名</th>
                                        <th class="px-2 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-12">必須</th>
                                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                            <th class="px-2 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-16">操作</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($equipmentSet->equipmentItemsOrdered as $item)
                                        <tr data-equipment-id="{{ $item->equipment->id }}" class="@if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin') hover:bg-gray-50 dark:hover:bg-gray-700 cursor-move @endif">
                                            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 sort-order-cell">
                                                    <div class="flex items-center">
                                                        <svg class="w-3 h-3 text-gray-400 dark:text-gray-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                                                        </svg>
                                                        {{ $item->sort_order }}
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="px-2 py-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $item->equipment->name }}
                                                @if($item->equipment->company_number)
                                                    <span class="inline-block ml-1 px-1 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded">{{ $item->equipment->company_number }}</span>
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap text-center">
                                                <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full
                                                    {{ $item->is_required ? 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                                    {{ $item->is_required ? '必須' : '任意' }}
                                                </span>
                                            </td>
                                            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                                <td class="px-2 py-2 whitespace-nowrap text-xs text-center">
                                                    <button onclick="editEquipmentItem({{ $item->equipment->id }}, {{ json_encode($item) }})"
                                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 cursor-pointer mr-2">編集</button>
                                                    <button onclick="removeEquipmentItem({{ $item->equipment->id }}, '{{ $item->equipment->name }}')"
                                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 cursor-pointer">削除</button>
                                                </td>
                                            @endif
                                        </tr>
                                        @if($item->notes)
                                            <tr class="bg-gray-50 dark:bg-gray-700">
                                                <td colspan="{{ auth()->user()->role === 'editor' || auth()->user()->role === 'admin' ? '4' : '2' }}" class="px-2 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                    <strong>備考:</strong> {{ $item->notes }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="px-4 sm:px-6 py-6 sm:py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h4 class="mt-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">セット内容が登録されていません</h4>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">機材を追加してセット内容を定義してください。</p>
                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                            <div class="mt-4">
                                <button onclick="showAddEquipmentModal()"
                                        class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 cursor-pointer">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    最初の機材を追加
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 機材セットIDをJavaScriptに渡す -->
    <div data-equipment-set-id="{{ $equipmentSet->id }}" style="display: none;"></div>
@endsection

@push('scripts')
    <!-- 機材セット内容管理JavaScript -->
    <script src="{{ asset('js/master/equipment-set-items.js') }}"></script>
@endpush
