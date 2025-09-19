/**
 * 機材セット内容管理 - JavaScript
 */

class EquipmentSetItems {
    constructor(equipmentSetId) {
        this.equipmentSetId = equipmentSetId;
        this.searchResults = []; // 検索結果を保存
        this.init();
    }

    init() {
        this.initModals();
        this.initDragAndDrop();
        this.setupEventListeners();
    }

    /**
     * モーダル初期化
     */
    initModals() {
        // 機材追加モーダル
        const addModalHTML = `
            <div id="add-equipment-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">機材をセットに追加</h3>
                        <form id="add-equipment-form">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">機材検索</label>
                                <input type="text" id="equipment-search" placeholder="機材名で検索..." autocomplete="off"
                                       class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <div id="equipment-search-results" class="mt-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md hidden"></div>
                                <input type="hidden" id="selected-equipment-id">
                            </div>

                            <div class="mb-4" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">数量</label>
                                <input type="number" id="equipment-quantity" min="1" value="1"
                                       class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="equipment-required" checked
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">必須機材</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                                <textarea id="equipment-notes" rows="3"
                                          class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="備考があれば入力してください"></textarea>
                            </div>


                            <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                                <button type="button" onclick="equipmentSetItems.hideAddEquipmentModal()"
                                        class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 mr-3">
                                    キャンセル
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-700">
                                    追加
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // 機材編集モーダル
        const editModalHTML = `
            <div id="edit-equipment-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">機材設定編集</h3>
                        <form id="edit-equipment-form">
                            <input type="hidden" id="edit-equipment-id">

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">機材名</label>
                                <div id="edit-equipment-name" class="p-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700"></div>
                            </div>

                            <div class="mb-4" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">数量</label>
                                <input type="number" id="edit-equipment-quantity" min="1" value="1"
                                       class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="edit-equipment-required"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">必須機材</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                                <textarea id="edit-equipment-notes" rows="3"
                                          class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>

                            <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                                <button type="button" onclick="equipmentSetItems.hideEditEquipmentModal()"
                                        class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 mr-3">
                                    キャンセル
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-700">
                                    更新
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // DOM に追加
        document.body.insertAdjacentHTML('beforeend', addModalHTML);
        document.body.insertAdjacentHTML('beforeend', editModalHTML);
    }

    /**
     * イベントリスナー設定
     */
    setupEventListeners() {
        // 機材追加フォーム
        document.getElementById('add-equipment-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addEquipment();
        });

        // 機材編集フォーム
        document.getElementById('edit-equipment-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateEquipment();
        });

        // 機材検索
        document.getElementById('equipment-search').addEventListener('input', (e) => {
            this.searchEquipments(e.target.value);
        });

        // 検索結果外クリックで非表示
        document.addEventListener('click', (e) => {
            const searchResults = document.getElementById('equipment-search-results');
            const searchInput = document.getElementById('equipment-search');

            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.classList.add('hidden');
            }
        });
    }

    /**
     * 機材検索
     */
    async searchEquipments(query) {
        const resultsDiv = document.getElementById('equipment-search-results');

        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        try {
            // 他の機材セットに登録済みの機材を除外するパラメータを追加
            const searchParams = new URLSearchParams({
                search: query,
                format: 'json',
                exclude_assigned: 'true',
                current_set_id: this.equipmentSetId
            });

            const response = await fetch(`/master/equipments?${searchParams}`);
            const data = await response.json();

            if (data.equipments && data.equipments.length > 0) {
                this.searchResults = data.equipments; // 検索結果を保存
                let html = '';
                data.equipments.forEach(equipment => {
                    const companyNumber = equipment.company_number ?
                        `<span class="inline-block ml-2 px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">${equipment.company_number}</span>` : '';

                    let categoryText = '';
                    if (equipment.category) {
                        categoryText = equipment.category.name;
                        if (equipment.subcategory) {
                            categoryText += ` > ${equipment.subcategory.name}`;
                        }
                    }

                    html += `
                        <div class="p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
                             data-equipment-id="${equipment.id}" onclick="equipmentSetItems.selectEquipmentFromSearch(this)">
                            ${categoryText ? `<div class="text-xs text-gray-500 mb-1">${categoryText}</div>` : ''}
                            <div class="font-medium text-sm text-gray-900">${equipment.name}${companyNumber}</div>
                        </div>
                    `;
                });

                resultsDiv.innerHTML = html;
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.innerHTML = '<div class="p-2 text-sm text-gray-500">該当する機材が見つかりません</div>';
                resultsDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('機材検索エラー:', error);
            resultsDiv.innerHTML = '<div class="p-2 text-sm text-red-500">検索中にエラーが発生しました</div>';
            resultsDiv.classList.remove('hidden');
        }
    }

    /**
     * 検索結果から機材選択
     */
    selectEquipmentFromSearch(element) {
        const equipmentId = element.getAttribute('data-equipment-id');
        const equipment = this.searchResults.find(eq => eq.id == equipmentId);

        if (equipment) {
            // 機材名にcompany_numberを角括弧で囲って追加
            const companyNumber = equipment.company_number ? ` [ ${equipment.company_number} ]` : '';
            this.selectEquipment(equipment.id, equipment.name + companyNumber);
        }
    }

    /**
     * 機材選択
     */
    selectEquipment(equipmentId, equipmentName) {
        document.getElementById('selected-equipment-id').value = equipmentId;
        document.getElementById('equipment-search').value = equipmentName;
        document.getElementById('equipment-search-results').classList.add('hidden');
    }

    /**
     * ドラッグ&ドロップ初期化
     */
    initDragAndDrop() {
        const tbody = document.querySelector('#equipment-items-table tbody');

        if (!tbody) return;

        let draggedElement = null;

        // 各行にドラッグイベントリスナーを追加
        tbody.querySelectorAll('tr[data-equipment-id]').forEach(row => {
            row.setAttribute('draggable', 'true');

            row.addEventListener('dragstart', (e) => {
                draggedElement = row;
                row.style.opacity = '0.5';
            });

            row.addEventListener('dragend', (e) => {
                row.style.opacity = '';
                draggedElement = null;
            });

            row.addEventListener('dragover', (e) => {
                e.preventDefault();
            });

            row.addEventListener('drop', (e) => {
                e.preventDefault();
                if (draggedElement && draggedElement !== row) {
                    const allRows = Array.from(tbody.querySelectorAll('tr[data-equipment-id]'));
                    const draggedIndex = allRows.indexOf(draggedElement);
                    const targetIndex = allRows.indexOf(row);

                    if (draggedIndex < targetIndex) {
                        row.parentNode.insertBefore(draggedElement, row.nextSibling);
                    } else {
                        row.parentNode.insertBefore(draggedElement, row);
                    }

                    this.updateItemSort();
                }
            });
        });
    }

    /**
     * 機材追加モーダル表示
     */
    showAddEquipmentModal() {
        document.getElementById('add-equipment-modal').classList.remove('hidden');
        document.getElementById('equipment-search').focus();
    }

    /**
     * 機材追加モーダル非表示
     */
    hideAddEquipmentModal() {
        document.getElementById('add-equipment-modal').classList.add('hidden');
        document.getElementById('add-equipment-form').reset();
        document.getElementById('selected-equipment-id').value = '';
        document.getElementById('equipment-search-results').classList.add('hidden');
    }

    /**
     * 機材編集モーダル表示
     */
    showEditEquipmentModal(equipmentId, equipmentItem) {
        document.getElementById('edit-equipment-id').value = equipmentId;
        document.getElementById('edit-equipment-name').textContent = equipmentItem.equipment.name;
        document.getElementById('edit-equipment-quantity').value = 1;
        document.getElementById('edit-equipment-required').checked = equipmentItem.is_required;
        document.getElementById('edit-equipment-notes').value = equipmentItem.notes || '';

        document.getElementById('edit-equipment-modal').classList.remove('hidden');
    }

    /**
     * 機材編集モーダル非表示
     */
    hideEditEquipmentModal() {
        document.getElementById('edit-equipment-modal').classList.add('hidden');
    }

    /**
     * 機材追加
     */
    async addEquipment() {
        const equipmentId = document.getElementById('selected-equipment-id').value;
        const quantity = document.getElementById('equipment-quantity').value;
        const isRequired = document.getElementById('equipment-required').checked;
        const notes = document.getElementById('equipment-notes').value;

        if (!equipmentId) {
            alert('機材を選択してください。');
            return;
        }

        try {
            const response = await fetch(`/master/equipment-sets/${this.equipmentSetId}/items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    equipment_id: parseInt(equipmentId),
                    quantity: parseInt(quantity),
                    is_required: isRequired,
                    notes: notes
                })
            });

            const data = await response.json();

            if (data.success) {
                this.hideAddEquipmentModal();
                window.location.reload(); // 簡単な実装のためリロード
            } else {
                alert(data.message || '機材の追加に失敗しました。');
            }
        } catch (error) {
            console.error('機材追加エラー:', error);
            alert('機材の追加中にエラーが発生しました。');
        }
    }

    /**
     * 機材更新
     */
    async updateEquipment() {
        const equipmentId = document.getElementById('edit-equipment-id').value;
        const quantity = document.getElementById('edit-equipment-quantity').value;
        const isRequired = document.getElementById('edit-equipment-required').checked;
        const notes = document.getElementById('edit-equipment-notes').value;

        try {
            const response = await fetch(`/master/equipment-sets/${this.equipmentSetId}/items/${equipmentId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    quantity: parseInt(quantity),
                    is_required: isRequired,
                    notes: notes
                })
            });

            const data = await response.json();

            if (data.success) {
                this.hideEditEquipmentModal();
                window.location.reload(); // 簡単な実装のためリロード
            } else {
                alert(data.message || '機材の更新に失敗しました。');
            }
        } catch (error) {
            console.error('機材更新エラー:', error);
            alert('機材の更新中にエラーが発生しました。');
        }
    }

    /**
     * 機材削除
     */
    async removeEquipment(equipmentId, equipmentName) {
        if (!confirm(`機材「${equipmentName}」をセットから削除しますか？`)) {
            return;
        }

        try {
            const response = await fetch(`/master/equipment-sets/${this.equipmentSetId}/items/${equipmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload(); // 簡単な実装のためリロード
            } else {
                alert(data.message || '機材の削除に失敗しました。');
            }
        } catch (error) {
            console.error('機材削除エラー:', error);
            alert('機材の削除中にエラーが発生しました。');
        }
    }

    /**
     * セット順序更新
     */
    async updateItemSort() {
        const tbody = document.querySelector('#equipment-items-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-equipment-id]'));

        const items = rows.map((row, index) => ({
            equipment_id: parseInt(row.getAttribute('data-equipment-id')),
            sort_order: index + 1
        }));

        try {
            const response = await fetch(`/master/equipment-sets/${this.equipmentSetId}/items/sort`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ items })
            });

            const data = await response.json();

            if (data.success) {
                // 順序番号を更新
                rows.forEach((row, index) => {
                    const sortCell = row.querySelector('.sort-order-cell');
                    if (sortCell) {
                        sortCell.textContent = index + 1;
                    }
                });
            } else {
                alert(data.message || '順序の更新に失敗しました。');
                window.location.reload();
            }
        } catch (error) {
            console.error('順序更新エラー:', error);
            alert('順序の更新中にエラーが発生しました。');
            window.location.reload();
        }
    }

    /**
     * セット使用可能性チェック
     */
    async checkAvailability() {
        try {
            const response = await fetch(`/master/equipment-sets/${this.equipmentSetId}/availability`);
            const data = await response.json();

            if (data.success) {
                this.showAvailabilityResults(data);
            } else {
                alert(data.message || '使用可能性の確認に失敗しました。');
            }
        } catch (error) {
            console.error('使用可能性チェックエラー:', error);
            alert('使用可能性の確認中にエラーが発生しました。');
        }
    }

    /**
     * 使用可能性結果表示
     */
    showAvailabilityResults(data) {
        let html = `
            <div class="mb-4">
                <h4 class="text-lg font-medium ${data.overall_available ? 'text-green-600' : 'text-red-600'}">
                    ${data.message}
                </h4>
            </div>
            <div class="space-y-2">
        `;

        data.equipment_details.forEach(item => {
            const statusColor = item.available ? 'text-green-600' : 'text-red-600';
            const requiredBadge = item.is_required
                ? '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">必須</span>'
                : '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">任意</span>';

            html += `
                <div class="flex justify-between items-center p-2 border border-gray-200 rounded">
                    <div>
                        <span class="font-medium">${item.equipment_name}</span>
                        ${requiredBadge}
                        <span class="text-sm text-gray-500 ml-2">数量: ${item.required_quantity}</span>
                    </div>
                    <div class="${statusColor}">
                        ${item.available ? '利用可能' : item.message}
                    </div>
                </div>
            `;
        });

        html += '</div>';

        // アラートの代わりにモーダルで表示
        const modalHTML = `
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="this.remove()">
                <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-2xl shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">セット使用可能性チェック結果</h3>
                        ${html}
                        <div class="flex justify-end mt-6">
                            <button onclick="this.closest('.fixed').remove()"
                                    class="px-4 py-2 bg-gray-600 text-white border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-gray-700">
                                閉じる
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

// グローバル変数・関数
let equipmentSetItems;

// ページ読み込み時に初期化
document.addEventListener('DOMContentLoaded', function() {
    const equipmentSetId = document.querySelector('[data-equipment-set-id]')?.getAttribute('data-equipment-set-id');
    if (equipmentSetId) {
        equipmentSetItems = new EquipmentSetItems(equipmentSetId);
    }
});

// グローバル関数（ビューから呼び出し）
function showAddEquipmentModal() {
    equipmentSetItems?.showAddEquipmentModal();
}

function editEquipmentItem(equipmentId, equipmentItem) {
    equipmentSetItems?.showEditEquipmentModal(equipmentId, equipmentItem);
}

function removeEquipmentItem(equipmentId, equipmentName) {
    equipmentSetItems?.removeEquipment(equipmentId, equipmentName);
}

function checkSetAvailability() {
    equipmentSetItems?.checkAvailability();
}