@extends('layouts.app')

@section('title', '機材追加')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800">公演管理</a>
    > <a href="{{ route('performances.show', $phase->performance) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->performance->title }}</a>
    > <a href="{{ route('phases.equipment.index', $phase) }}" class="text-blue-600 hover:text-blue-800">{{ $phase->name }} - 機材管理</a>
    > <span class="text-gray-800">機材追加</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材追加</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $phase->performance->title }} - {{ $phase->name }} |
            {{ $phase->start_date->format('Y/m/d') }} ～ {{ $phase->end_date->format('Y/m/d') }}
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
                                   class="form-radio h-4 w-4 text-blue-600" onchange="toggleSelectionMode()">
                            <span class="ml-2 text-sm text-gray-700">個別機材</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="selection_type" value="set"
                                   class="form-radio h-4 w-4 text-blue-600" onchange="toggleSelectionMode()">
                            <span class="ml-2 text-sm text-gray-700">機材セット</span>
                        </label>
                    </div>
                </div>

                <!-- 個別機材選択 -->
                <div id="individualSelection">
                    <!-- カテゴリフィルター -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">大分類</label>
                            <select id="category_id" onchange="updateSubcategories()"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">全カテゴリ</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="subcategory_id" class="block text-sm font-medium text-gray-700">中分類</label>
                            <select id="subcategory_id" onchange="searchEquipments()"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">全サブカテゴリ</option>
                            </select>
                        </div>
                    </div>

                    <!-- 機材検索 -->
                    <div class="mb-4">
                        <label for="equipment_search" class="block text-sm font-medium text-gray-700">機材検索</label>
                        <input type="text" id="equipment_search" placeholder="機材名、新音番号、型番で検索"
                               onkeyup="searchEquipments()"
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

                <!-- 備考 -->
                <div class="mb-6">
                    <label for="note" class="block text-sm font-medium text-gray-700">備考</label>
                    <textarea name="note" id="note" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- 送信ボタン -->
                <div class="flex justify-end">
                    <button type="submit" id="submitButton" disabled
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        機材を追加
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

<script>
let availableEquipments = [];
let selectedEquipment = null;

// サブカテゴリ更新
function updateSubcategories() {
    const categoryId = document.getElementById('category_id').value;
    const subcategorySelect = document.getElementById('subcategory_id');

    // サブカテゴリをリセット
    subcategorySelect.innerHTML = '<option value="">全サブカテゴリ</option>';

    if (categoryId) {
        @foreach($categories as $category)
            if (categoryId == '{{ $category->id }}') {
                @foreach($category->subcategories as $subcategory)
                    subcategorySelect.innerHTML += '<option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>';
                @endforeach
            }
        @endforeach
    }

    searchEquipments();
}

// 機材検索
function searchEquipments() {
    const categoryId = document.getElementById('category_id').value;
    const subcategoryId = document.getElementById('subcategory_id').value;
    const search = document.getElementById('equipment_search').value;

    const params = new URLSearchParams();
    if (categoryId) params.append('category_id', categoryId);
    if (subcategoryId) params.append('subcategory_id', subcategoryId);
    if (search) params.append('search', search);

    fetch(`{{ route('phases.available-equipment', $phase) }}?${params}`)
        .then(response => response.json())
        .then(data => {
            availableEquipments = data;
            displayEquipments(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// 機材一覧表示
function displayEquipments(equipments) {
    const container = document.getElementById('equipmentList');

    if (equipments.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-500 text-center py-4">該当する機材がありません</div>';
        return;
    }

    container.innerHTML = equipments.map(equipment => {
        const isAvailable = !equipment.has_conflict &&
            (equipment.management_type === 'individual' || equipment.available_quantity > 0);

        const statusClass = isAvailable ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
        const statusText = isAvailable ? '利用可能' : '利用不可';
        const statusTextClass = isAvailable ? 'text-green-800' : 'text-red-800';

        return `
            <div class="p-3 border rounded-lg cursor-pointer hover:bg-gray-50 ${statusClass}"
                 onclick="${isAvailable ? `selectEquipment(${equipment.id})` : ''}"
                 ${!isAvailable ? 'style="cursor: not-allowed;"' : ''}>
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${equipment.name}</div>
                        <div class="text-sm text-gray-500">${equipment.category} > ${equipment.subcategory}</div>
                        ${equipment.company_number ? `<div class="text-sm text-gray-500">新音番号: ${equipment.company_number}</div>` : ''}
                        ${equipment.model_number ? `<div class="text-sm text-gray-500">型番: ${equipment.model_number}</div>` : ''}
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-medium ${statusTextClass}">${statusText}</span>
                        ${equipment.management_type === 'quantity' ?
                            `<div class="text-xs text-gray-500">利用可能: ${equipment.available_quantity}個</div>` :
                            `<div class="text-xs text-gray-500">個体管理</div>`
                        }
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 機材選択
function selectEquipment(equipmentId) {
    selectedEquipment = availableEquipments.find(eq => eq.id === equipmentId);
    if (!selectedEquipment) return;

    // フォームに値設定
    document.getElementById('equipment_id').value = equipmentId;

    // 選択された機材情報表示
    const infoDiv = document.getElementById('selectedEquipmentInfo');
    infoDiv.innerHTML = `
        <div class="font-medium">${selectedEquipment.name}</div>
        <div class="text-sm text-gray-600">${selectedEquipment.category} > ${selectedEquipment.subcategory}</div>
        ${selectedEquipment.company_number ? `<div class="text-sm text-gray-600">新音番号: ${selectedEquipment.company_number}</div>` : ''}
    `;

    // セクション表示
    document.getElementById('selectedEquipmentSection').style.display = 'block';
    document.getElementById('quantitySection').style.display = 'block';

    // 数量設定
    const quantityInput = document.getElementById('quantity');
    const quantityInfo = document.getElementById('quantityInfo');

    if (selectedEquipment.management_type === 'quantity') {
        quantityInput.max = selectedEquipment.available_quantity;
        quantityInfo.textContent = `利用可能数量: ${selectedEquipment.available_quantity}個`;
    } else {
        quantityInput.max = 1;
        quantityInput.value = 1;
        quantityInfo.textContent = '個体管理機材（数量は1）';
    }

    // 送信ボタン有効化
    document.getElementById('submitButton').disabled = false;
}

// 選択モード切り替え
function toggleSelectionMode() {
    const selectionType = document.querySelector('input[name="selection_type"]:checked').value;

    if (selectionType === 'individual') {
        document.getElementById('individualSelection').style.display = 'block';
        document.getElementById('setSelection').style.display = 'none';
    } else {
        document.getElementById('individualSelection').style.display = 'none';
        document.getElementById('setSelection').style.display = 'block';
    }

    // リセット
    resetForm();
}

// セット使用可能性チェック
function checkSetAvailability() {
    const setId = document.getElementById('equipment_set_id').value;
    if (!setId) {
        document.getElementById('setAvailabilityInfo').innerHTML = '';
        return;
    }

    fetch(`{{ route('phases.equipment-set-availability', $phase) }}?set_id=${setId}`)
        .then(response => response.json())
        .then(data => {
            displaySetAvailability(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// セット使用可能性表示
function displaySetAvailability(data) {
    const container = document.getElementById('setAvailabilityInfo');

    const statusClass = data.all_available ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    const statusText = data.all_available ? 'セット使用可能' : 'セット使用不可';
    const statusTextClass = data.all_available ? 'text-green-800' : 'text-red-800';

    let itemsHtml = data.items.map(item => {
        const itemStatusClass = item.is_available ? 'text-green-600' : 'text-red-600';
        const itemStatus = item.is_available ? '✓' : '✗';

        return `
            <div class="flex justify-between">
                <span>${item.equipment_name}</span>
                <span class="${itemStatusClass}">${itemStatus} ${item.required_quantity}個${item.available_quantity ? ` (利用可能: ${item.available_quantity}個)` : ''}</span>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="p-3 border rounded-lg ${statusClass}">
            <div class="font-medium ${statusTextClass} mb-2">${statusText}</div>
            <div class="space-y-1 text-sm">
                ${itemsHtml}
            </div>
        </div>
    `;

    // 送信ボタンの状態
    document.getElementById('submitButton').disabled = !data.all_available;
}

// フォームリセット
function resetForm() {
    selectedEquipment = null;
    document.getElementById('equipment_id').value = '';
    document.getElementById('selectedEquipmentSection').style.display = 'none';
    document.getElementById('quantitySection').style.display = 'none';
    document.getElementById('submitButton').disabled = true;
    document.getElementById('setAvailabilityInfo').innerHTML = '';
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
    // 初期状態で個別機材選択を表示
    toggleSelectionMode();
});
</script>
@endsection