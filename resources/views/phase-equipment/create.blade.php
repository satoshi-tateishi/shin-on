@extends('layouts.app')

@section('title', '機材追加')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演一覧</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <a href="{{ route('phases.equipment.index', $phase) }}" class="text-blue-600 hover:text-blue-800">【{{ $phase->name }}】使用機材管理</a>
    > <span class="text-gray-800">使用機材追加</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $phase->performance->title }}【{{ $phase->name }}】使用機材追加</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
            @if($phase->location)
                @ {{ $phase->location->name }}
            @endif
        </p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('phases.equipment.index', $phase) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent text-sm font-medium rounded-md text-gray-700 hover:bg-gray-400">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-full mx-auto grid grid-cols-1 xl:grid-cols-12 gap-4">
    <!-- 機材追加フォーム（狭いカラム） -->
    <div class="bg-white shadow rounded-lg xl:col-span-2">
        <div class="px-3 py-4 sm:p-4">
            <h3 class="text-base font-medium text-gray-900 mb-3">機材を追加</h3>

            <form method="POST" action="{{ route('phases.equipment.store', $phase) }}" id="equipmentForm">
                @csrf

                <!-- 機材選択方法 -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-2">選択方法</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="selection_type" value="individual" checked
                                   onchange="toggleSelectionMode()"
                                   class="form-radio h-3 w-3 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">個別機材</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="selection_type" value="set"
                                   onchange="toggleSelectionMode()"
                                   class="form-radio h-3 w-3 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">機材セット</span>
                        </label>
                    </div>
                </div>

                <!-- 個別機材選択 -->
                <div id="individualSelection">
                    <!-- カテゴリフィルター（縦配置） -->
                    <div class="space-y-3">
                        <div>
                            <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1">カテゴリ</label>
                            <select id="category_id"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全カテゴリ</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="subcategory_id" class="block text-xs font-medium text-gray-700 mb-1">サブカテゴリ</label>
                            <select id="subcategory_id"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <label class="block text-sm font-medium text-gray-700">選択された機材</label>
                        <div id="selectedEquipmentInfo" class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        </div>
                    </div>

                    <!-- 数量入力（非表示） -->
                    <div class="mb-4" id="quantitySection" style="display: none;">
                        <label for="quantity" class="block text-sm font-medium text-gray-700">数量</label>
                        <input type="number" name="quantity" id="quantity" min="1" value="1"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <div id="quantityInfo" class="mt-1 text-sm text-gray-500"></div>
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
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        セットをリストに追加
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 機材一覧 -->
    <div class="bg-white shadow rounded-lg xl:col-span-3">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">利用可能な機材</h3>
            <p class="text-sm text-gray-500 mb-4">機材をクリックして選択リストに追加できます</p>
            <div id="equipmentList" class="space-y-2 max-h-96 overflow-y-auto">
                <div class="text-sm text-gray-500 text-center py-4">
                    カテゴリまたは検索条件を指定してください
                </div>
            </div>
        </div>
    </div>

    <!-- 選択された機材リスト -->
    <div class="bg-white shadow rounded-lg xl:col-span-3" id="selectedEquipmentListSection">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">選択された機材</h3>
                <span class="text-sm text-gray-500" id="selectedCount">0件</span>
            </div>

            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <div id="emptyMessage" class="text-sm text-gray-500 text-center py-8">
                    機材を選択してください
                </div>
                <table class="min-w-full divide-y divide-gray-200 hidden" id="selectedEquipmentTable">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材名</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">数量</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody id="selectedEquipmentTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- 選択された機材がここに表示される -->
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t">
                <form id="finalSubmitForm" action="{{ route('phases.equipment.store', $phase) }}" method="POST">
                    @csrf
                    <input type="hidden" id="selectedEquipmentData" name="equipment_data" value="">
                    <button type="submit" id="finalSubmitButton" disabled
                            class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        すべての機材を追加
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 登録済み機材一覧（広いカラム） -->
    <div class="bg-white shadow rounded-lg xl:col-span-4">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">登録済み機材</h3>

            @php
                // 同じ機材名でグループ化
                $groupedEquipments = $phaseEquipments->groupBy(function($item) {
                    return $item->equipment->name;
                })->map(function($group) {
                    $first = $group->first();
                    // ステータス別に集計
                    $statusCounts = $group->groupBy('status')->map(function($statusGroup) {
                        return $statusGroup->sum('quantity');
                    });
                    return (object)[
                        'equipment' => $first->equipment,
                        'total_quantity' => $group->sum('quantity'),
                        'company_numbers' => $group->map(function($item) {
                            return $item->equipment->company_number;
                        })->filter()->unique()->implode(', '),
                        'status_counts' => $statusCounts,
                        'primary_status' => $group->sortBy('status')->first()->status
                    ];
                });
            @endphp

            @if($groupedEquipments->count() > 0)
                <div class="overflow-auto max-h-96" style="overflow-x: auto; overflow-y: auto;">
                    <table class="divide-y divide-gray-200" style="min-width: 100%;">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" style="width: 40%;">
                                    機材名
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" style="width: 10%;">
                                    数量
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" style="width: 50%;">
                                    新音番号
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($groupedEquipments as $groupedEquipment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2" style="min-width: 200px;">
                                        @if($groupedEquipment->equipment->manufacturer)
                                            <div class="text-xs text-gray-500">
                                                {{ $groupedEquipment->equipment->manufacturer }}
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $groupedEquipment->equipment->name }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ $groupedEquipment->total_quantity }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-left" style="min-width: 250px;">
                                        <span class="text-xs text-gray-600">{{ $groupedEquipment->company_numbers ?: '-' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-4 border-t">
                    <div class="text-sm text-gray-500">
                        合計: {{ $groupedEquipments->count() }}種類 / {{ $phaseEquipments->sum('quantity') }}個
                    </div>
                    <a href="{{ route('phases.equipment.index', $phase) }}"
                       class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        機材管理画面へ
                    </a>
                </div>
            @else
                <div class="text-sm text-gray-500 text-center py-8">
                    まだ機材が登録されていません
                </div>
            @endif
        </div>
    </div>
</div>

<!-- カテゴリ・サブカテゴリフィルタリングはresources/js/phase-equipment.jsで処理 -->
@endsection
