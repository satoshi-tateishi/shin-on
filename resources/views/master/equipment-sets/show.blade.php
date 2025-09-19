@extends('layouts.master')

@section('title', '機材セット詳細')

@section('breadcrumb')
    > <a href="{{ route('equipment-sets.index') }}" class="text-blue-600 hover:text-blue-800">機材セットマスタ</a>
    > <span class="text-gray-800">{{ $equipmentSet->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材セット「{{ $equipmentSet->name }}」の詳細情報です。</h1>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('equipment-sets.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.equipment-sets.edit', $equipmentSet) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                編集
            </a>
        @endif

        @if(auth()->user()->role === 'admin')
            <form method="POST" action="{{ route('master.equipment-sets.destroy', $equipmentSet) }}" class="inline"
                  onsubmit="return confirm('機材セット「{{ $equipmentSet->name }}」を削除します。この操作は取り消せません。削除しますか？')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    削除
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <div class="p-6">
        <div class="max-w-7xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->id }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">セット名</dt>
                            <dd class="mt-1 text-sm font-gray-900 font-bold">{{ $equipmentSet->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $equipmentSet->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $equipmentSet->is_active ? '有効' : '無効' }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipmentSet->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>

                    @if($equipmentSet->description)
                        <div class="mt-6">
                            <dt class="text-sm font-medium text-gray-500">説明</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $equipmentSet->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 機材セット内容 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">機材セット内容 ({{ $equipmentSet->equipmentItems->count() }}件)</h3>
                    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                        <button onclick="showAddEquipmentModal()" class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            機材追加
                        </button>
                    @endif
                </div>

                @if($equipmentSet->equipmentItems->count() > 0)
                    <div class="px-6 py-4">
                        <div class="overflow-x-auto">
                            <table id="equipment-items-table" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                                                </svg>
                                                順序
                                            </th>
                                        @endif
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">機材名</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">カテゴリ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="display: none;">数量</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">必須</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">状態</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">場所</th>
                                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($equipmentSet->equipmentItemsOrdered as $item)
                                        <tr data-equipment-id="{{ $item->equipment->id }}" class="@if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin') hover:bg-gray-50 cursor-move @endif">
                                            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 sort-order-cell">
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 16a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z"></path>
                                                        </svg>
                                                        {{ $item->sort_order }}
                                                    </div>
                                                </td>
                                            @endif
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <a href="{{ route('master.equipments.show', $item->equipment) }}" class="text-blue-600 hover:text-blue-900">
                                                    {{ $item->equipment->name }}
                                                </a>
                                                @if($item->equipment->company_number)
                                                    <span class="inline-block ml-2 px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">{{ $item->equipment->company_number }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($item->equipment->category)
                                                    <div>{{ $item->equipment->category->name }}</div>
                                                    @if($item->equipment->subcategory)
                                                        <div class="text-xs text-gray-400">{{ $item->equipment->subcategory->name }}</div>
                                                    @endif
                                                @else
                                                    ---
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" style="display: none;">
                                                {{ $item->quantity }}{{ $item->equipment->unit }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $item->is_required ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $item->is_required ? '必須' : '任意' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    @switch($item->equipment->status)
                                                        @case('available') bg-green-100 text-green-800 @break
                                                        @case('in_use') bg-blue-100 text-blue-800 @break
                                                        @case('repair') bg-orange-100 text-orange-800 @break
                                                        @case('maintenance') bg-yellow-100 text-yellow-800 @break
                                                        @case('retired') bg-gray-100 text-gray-800 @break
                                                        @case('lost') bg-red-100 text-red-800 @break
                                                    @endswitch">
                                                    {{ $item->equipment->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item->equipment->location->name ?? '---' }}
                                            </td>
                                            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button onclick="editEquipmentItem({{ $item->equipment->id }}, {{ json_encode($item) }})"
                                                            class="text-indigo-600 hover:text-indigo-900 mr-3">編集</button>
                                                    <button onclick="removeEquipmentItem({{ $item->equipment->id }}, '{{ $item->equipment->name }}')"
                                                            class="text-red-600 hover:text-red-900">削除</button>
                                                </td>
                                            @endif
                                        </tr>
                                        @if($item->notes)
                                            <tr class="bg-gray-50">
                                                <td colspan="{{ auth()->user()->role === 'editor' || auth()->user()->role === 'admin' ? '7' : '5' }}" class="px-6 py-2 text-sm text-gray-600">
                                                    <strong>備考:</strong> {{ $item->notes }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- セット使用可能性チェック -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button onclick="checkSetAvailability()"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                セット使用可能性チェック
                            </button>
                        </div>
                    </div>
                @else
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h4 class="mt-2 text-sm font-medium text-gray-900">セット内容が登録されていません</h4>
                        <p class="mt-1 text-sm text-gray-500">機材を追加してセット内容を定義してください。</p>
                        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                            <div class="mt-4">
                                <button onclick="showAddEquipmentModal()"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
