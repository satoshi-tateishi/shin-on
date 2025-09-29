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
<div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- 機材追加フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">機材を追加</h3>

            <form method="POST" action="{{ route('phases.equipment.store', $phase) }}" id="equipmentForm">
                @csrf

                <!-- 機材選択方法 -->
                <div class="mb-6">
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="selection_type" value="individual" checked
                                   onchange="toggleSelectionMode()"
                                   class="form-radio h-4 w-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">個別機材</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="selection_type" value="set"
                                   onchange="toggleSelectionMode()"
                                   class="form-radio h-4 w-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">機材セット</span>
                        </label>
                    </div>
                </div>

                <!-- 個別機材選択 -->
                <div id="individualSelection">
                    <!-- カテゴリフィルター -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">カテゴリ</label>
                            <select id="category_id"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全カテゴリ</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="subcategory_id" class="block text-sm font-medium text-gray-700">サブカテゴリ</label>
                            <select id="subcategory_id"
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    <div class="mb-4">
                        <label for="equipment_search" class="block text-sm font-medium text-gray-700">機材検索</label>
                        <input type="text" id="equipment_search" placeholder="機材名、新音番号、型番で検索"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- 選択された機材 -->
                    <div class="mb-4" id="selectedEquipmentSection" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700">選択された機材</label>
                        <div id="selectedEquipmentInfo" class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        </div>
                    </div>

                    <!-- 数量入力 -->
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
                        <label for="equipment_set_id" class="block text-sm font-medium text-gray-700">機材セット</label>
                        <select id="equipment_set_id" onchange="checkSetAvailability()"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">セットを選択してください</option>
                            @foreach($equipmentSets as $set)
                                <option value="{{ $set->id }}">{{ $set->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="setAvailabilityInfo"></div>
                </div>

                <!-- 隠し項目 -->
                <input type="hidden" name="equipment_id" id="equipment_id">

                <!-- 追加ボタン -->
                <div class="flex justify-end">
                    <button type="button" id="addToListButton" disabled
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        リストに追加
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 機材一覧 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">利用可能な機材</h3>
            <div id="equipmentList" class="space-y-2 max-h-96 overflow-y-auto">
                <div class="text-sm text-gray-500 text-center py-4">
                    カテゴリまたは検索条件を指定してください
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 選択された機材リスト -->
<div class="max-w-7xl mx-auto mt-6" id="selectedEquipmentListSection" style="display: none;">
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">選択された機材</h3>
                <span class="text-sm text-gray-500" id="selectedCount">0件</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材名</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">数量</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody id="selectedEquipmentTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- 選択された機材がここに表示される -->
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
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
</div>

<!-- カテゴリ・サブカテゴリフィルタリングはresources/js/phase-equipment.jsで処理 -->
@endsection
