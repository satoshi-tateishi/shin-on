@extends('layouts.master')

@section('title', '機材追加')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <a href="{{ route('phases.equipment.index', $phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->name }}</a>
    > <span class="text-gray-800">機材追加</span>
@endsection

@section('header')
    <div class="w-full">
        <p class="text-base sm:text-lg text-gray-600">{{ $phase->performance->title }}</p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1">{{ $phase->name }} 機材追加</h1>
        <p class="text-xs sm:text-sm text-gray-500 mb-2">
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
            @if($phase->location)
                @ {{ $phase->location->name }}
            @endif
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('phases.equipment.index', $phase) }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6 space-y-4 sm:space-y-6">
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
        <!-- 機材追加フォーム -->
        <div class="bg-white border border-gray-200 rounded-lg xl:col-span-2">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">機材を追加</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <form method="POST" action="{{ route('phases.equipment.store', $phase) }}" id="equipmentForm">
                    @csrf

                    <!-- 機材選択方法 -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-2">選択方法</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="selection_type" value="individual" checked
                                       onchange="toggleSelectionMode()"
                                       class="form-radio h-3 w-3 text-blue-600">
                                <span class="ml-1.5 text-xs sm:text-sm text-gray-700">個別機材</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="selection_type" value="set"
                                       onchange="toggleSelectionMode()"
                                       class="form-radio h-3 w-3 text-blue-600">
                                <span class="ml-1.5 text-xs sm:text-sm text-gray-700">機材セット</span>
                            </label>
                        </div>
                    </div>

                    <!-- 個別機材選択 -->
                    <div id="individualSelection">
                        <!-- カテゴリフィルター -->
                        <div class="space-y-3">
                            <div>
                                <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1">カテゴリ</label>
                                <select id="category_id"
                                        class="w-full text-xs sm:text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">全カテゴリ</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="subcategory_id" class="block text-xs font-medium text-gray-700 mb-1">サブカテゴリ</label>
                                <select id="subcategory_id"
                                        class="w-full text-xs sm:text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">全サブカテゴリ</option>
                                    @php
                                        $groupedSubcategories = $subcategories->groupBy('category.name');
                                    @endphp
                                    @foreach($groupedSubcategories as $categoryName => $subcategoryGroup)
                                        <optgroup label="{{ $categoryName }}">
                                            @foreach($subcategoryGroup as $subcategory)
                                                <option value="{{ $subcategory->id }}"
                                                        data-category-id="{{ $subcategory->category_id }}">
                                                    {{ $subcategory->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- 機材検索 -->
                        <div class="mt-3 mb-4">
                            <label for="equipment_search" class="block text-xs font-medium text-gray-700 mb-1">機材検索</label>
                            <input type="text" id="equipment_search" placeholder="検索"
                                   class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- 選択された機材（非表示） -->
                        <div class="mb-4" id="selectedEquipmentSection" style="display: none;">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">選択された機材</label>
                            <div id="selectedEquipmentInfo" class="mt-1 p-2 sm:p-3 bg-blue-50 border border-blue-200 rounded-md text-xs sm:text-sm">
                            </div>
                        </div>

                        <!-- 数量入力（非表示） -->
                        <div class="mb-4" id="quantitySection" style="display: none;">
                            <label for="quantity" class="block text-xs sm:text-sm font-medium text-gray-700">数量</label>
                            <input type="number" name="quantity" id="quantity" min="1" value="1"
                                   class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <div id="quantityInfo" class="mt-1 text-xs text-gray-500"></div>
                        </div>
                    </div>

                    <!-- セット選択 -->
                    <div id="setSelection" style="display: none;">
                        <div class="mb-4">
                            <label for="equipment_set_id" class="block text-xs font-medium text-gray-700 mb-1">機材セット</label>
                            <select id="equipment_set_id" onchange="checkSetAvailability()"
                                    class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">セットを選択</option>
                                @foreach($equipmentSets as $set)
                                    <option value="{{ $set->id }}">{{ $set->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="setAvailabilityInfo" class="text-xs"></div>
                    </div>

                    <!-- 隠し項目 -->
                    <input type="hidden" name="equipment_id" id="equipment_id">

                    <!-- 追加ボタン（機材セット用のみ表示） -->
                    <div class="flex justify-end" id="addToListButtonContainer" style="display: none;">
                        <button type="button" id="addToListButton" disabled
                                class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            セットを追加
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 機材一覧 -->
        <div class="bg-white border border-gray-200 rounded-lg xl:col-span-3">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">利用可能な機材</h3>
                <p class="text-xs text-gray-500 mt-1">タップして選択リストに追加</p>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div id="equipmentList" class="space-y-2 max-h-72 sm:max-h-96 overflow-y-auto">
                    <div class="text-xs sm:text-sm text-gray-500 text-center py-4">
                        カテゴリまたは検索条件を指定してください
                    </div>
                </div>
            </div>
        </div>

        <!-- 選択された機材リスト -->
        <div class="bg-white border border-gray-200 rounded-lg xl:col-span-3" id="selectedEquipmentListSection">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">選択された機材</h3>
                <span class="text-xs sm:text-sm text-gray-500" id="selectedCount">0件</span>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="overflow-x-auto max-h-72 sm:max-h-96 overflow-y-auto">
                    <div id="emptyMessage" class="text-xs sm:text-sm text-gray-500 text-center py-6 sm:py-8">
                        機材を選択してください
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 hidden" id="selectedEquipmentTable">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材名</th>
                                <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-14 sm:w-16">数量</th>
                                <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12 sm:w-16">操作</th>
                            </tr>
                        </thead>
                        <tbody id="selectedEquipmentTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- 選択された機材がここに表示される -->
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-4 sm:mt-6 pt-3 sm:pt-4 border-t">
                    <form id="finalSubmitForm" action="{{ route('phases.equipment.store', $phase) }}" method="POST">
                        @csrf
                        <input type="hidden" id="selectedEquipmentData" name="equipment_data" value="">
                        <button type="submit" id="finalSubmitButton" disabled
                                class="inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 bg-green-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            すべて追加
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 登録済み機材一覧 -->
        <div class="bg-white border border-gray-200 rounded-lg xl:col-span-4">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-medium text-gray-900">登録済み機材</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                @php
                    $groupedEquipments = $phaseEquipments->groupBy(function($item) {
                        return $item->equipment->name;
                    })->map(function($group) {
                        $first = $group->first();
                        $statusCounts = $group->groupBy('status')->map(function($statusGroup) {
                            return $statusGroup->sum('quantity');
                        });
                        return (object)[
                            'equipment' => $first->equipment,
                            'total_quantity' => $group->sum('quantity'),
                            'company_numbers' => $group->map(function($item) {
                                return $item->equipment->company_number;
                            })->filter()->unique()->sort()->implode(', '),
                            'status_counts' => $statusCounts,
                            'primary_status' => $group->sortBy('status')->first()->status
                        ];
                    });
                @endphp

                @if($groupedEquipments->count() > 0)
                    <div class="overflow-auto max-h-72 sm:max-h-96">
                        <table class="min-w-full divide-y divide-gray-500">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 sm:px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材名</th>
                                    <th class="px-2 sm:px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-12 sm:w-14">数量</th>
                                    <th class="px-2 sm:px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">新音番号</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-500">
                                @foreach($groupedEquipments as $groupedEquipment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 sm:px-3 py-2">
                                            @if($groupedEquipment->equipment->manufacturer)
                                                <div class="text-xs text-gray-500">{{ $groupedEquipment->equipment->manufacturer }}</div>
                                            @endif
                                            <div class="text-xs sm:text-sm font-medium text-gray-900">{{ $groupedEquipment->equipment->name }}</div>
                                            <!-- モバイル用新音番号 -->
                                            @if($groupedEquipment->company_numbers)
                                                <div class="sm:hidden text-xs text-gray-500 mt-1 pt-1 border-t border-dashed border-gray-300">{{ $groupedEquipment->company_numbers }}</div>
                                            @endif
                                        </td>
                                        <td class="px-2 sm:px-3 py-2 text-center">
                                            <span class="text-xs sm:text-sm font-medium text-gray-900">{{ $groupedEquipment->total_quantity }}</span>
                                        </td>
                                        <td class="px-2 sm:px-3 py-2 text-left hidden sm:table-cell">
                                            <span class="text-xs text-gray-600">{{ $groupedEquipment->company_numbers ?: '-' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t">
                        <div class="text-xs sm:text-sm text-gray-500">
                            合計: {{ $groupedEquipments->count() }}種類 / {{ $phaseEquipments->sum('quantity') }}個
                        </div>
                        <a href="{{ route('phases.equipment.index', $phase) }}"
                           class="mt-2 inline-flex items-center text-xs sm:text-sm text-blue-600 hover:text-blue-800">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            機材管理画面へ
                        </a>
                    </div>
                @else
                    <div class="text-xs sm:text-sm text-gray-500 text-center py-6 sm:py-8">
                        まだ機材が登録されていません
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
