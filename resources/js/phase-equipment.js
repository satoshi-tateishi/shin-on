/**
 * Phase Equipment Management JavaScript
 * 機材使用管理のフロントエンド機能
 */

class PhaseEquipmentManager {
    constructor() {
        this.availableEquipments = [];
        this.selectedEquipment = null;
        this.phaseId = null;

        this.init();
    }

    init() {
        // DOM要素が存在する場合のみ初期化
        if (document.getElementById('equipmentForm')) {
            this.initCreateForm();
        }

        if (document.getElementById('equipmentList')) {
            this.initEquipmentList();
        }

        this.initModalHandlers();
    }

    /**
     * 機材追加フォームの初期化
     */
    initCreateForm() {
        // 初期状態で個別機材選択を表示
        this.toggleSelectionMode();

        // フェーズIDを取得
        const form = document.getElementById('equipmentForm');
        if (form) {
            const actionUrl = form.action;
            const matches = actionUrl.match(/phases\/(\d+)\/equipment/);
            if (matches) {
                this.phaseId = matches[1];
            }
        }
    }

    /**
     * 機材一覧の初期化
     */
    initEquipmentList() {
        // 検索・フィルター機能の初期化
        this.initSearchAndFilter();
    }

    /**
     * 検索・フィルター機能の初期化
     */
    initSearchAndFilter() {
        const searchInput = document.getElementById('equipment_search');
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        if (searchInput) {
            // デバウンス処理付きの検索
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchEquipments();
                }, 300);
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                this.updateSubcategories();
            });
        }

        if (subcategorySelect) {
            subcategorySelect.addEventListener('change', () => {
                this.searchEquipments();
            });
        }
    }

    /**
     * サブカテゴリ更新
     */
    updateSubcategories() {
        const categoryId = document.getElementById('category_id')?.value;
        const subcategorySelect = document.getElementById('subcategory_id');

        if (!subcategorySelect) return;

        // サブカテゴリをリセット
        subcategorySelect.innerHTML = '<option value="">全サブカテゴリ</option>';

        if (categoryId && window.categoriesData) {
            const category = window.categoriesData.find(cat => cat.id == categoryId);
            if (category && category.subcategories) {
                category.subcategories.forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.id;
                    option.textContent = subcategory.name;
                    subcategorySelect.appendChild(option);
                });
            }
        }

        this.searchEquipments();
    }

    /**
     * 機材検索
     */
    async searchEquipments() {
        if (!this.phaseId) return;

        const categoryId = document.getElementById('category_id')?.value || '';
        const subcategoryId = document.getElementById('subcategory_id')?.value || '';
        const search = document.getElementById('equipment_search')?.value || '';

        const params = new URLSearchParams();
        if (categoryId) params.append('category_id', categoryId);
        if (subcategoryId) params.append('subcategory_id', subcategoryId);
        if (search) params.append('search', search);

        try {
            const response = await fetch(`/phases/${this.phaseId}/available-equipment?${params}`);
            if (!response.ok) throw new Error('検索に失敗しました');

            const data = await response.json();
            this.availableEquipments = data;
            this.displayEquipments(data);
        } catch (error) {
            console.error('Equipment search error:', error);
            this.showError('機材検索中にエラーが発生しました');
        }
    }

    /**
     * 機材一覧表示
     */
    displayEquipments(equipments) {
        const container = document.getElementById('equipmentList');
        if (!container) return;

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
                     onclick="${isAvailable ? `phaseEquipmentManager.selectEquipment(${equipment.id})` : ''}"
                     ${!isAvailable ? 'style="cursor: not-allowed;"' : ''}>
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">${this.escapeHtml(equipment.name)}</div>
                            <div class="text-sm text-gray-500">${this.escapeHtml(equipment.category)} > ${this.escapeHtml(equipment.subcategory)}</div>
                            ${equipment.company_number ? `<div class="text-sm text-gray-500">新音番号: ${this.escapeHtml(equipment.company_number)}</div>` : ''}
                            ${equipment.model_number ? `<div class="text-sm text-gray-500">型番: ${this.escapeHtml(equipment.model_number)}</div>` : ''}
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

    /**
     * 機材選択
     */
    selectEquipment(equipmentId) {
        this.selectedEquipment = this.availableEquipments.find(eq => eq.id === equipmentId);
        if (!this.selectedEquipment) return;

        // フォームに値設定
        const equipmentIdInput = document.getElementById('equipment_id');
        if (equipmentIdInput) {
            equipmentIdInput.value = equipmentId;
        }

        // 選択された機材情報表示
        this.displaySelectedEquipment();

        // セクション表示
        this.showQuantitySection();

        // 送信ボタン有効化
        const submitButton = document.getElementById('submitButton');
        if (submitButton) {
            submitButton.disabled = false;
        }
    }

    /**
     * 選択された機材情報表示
     */
    displaySelectedEquipment() {
        const infoDiv = document.getElementById('selectedEquipmentInfo');
        if (!infoDiv || !this.selectedEquipment) return;

        infoDiv.innerHTML = `
            <div class="font-medium">${this.escapeHtml(this.selectedEquipment.name)}</div>
            <div class="text-sm text-gray-600">${this.escapeHtml(this.selectedEquipment.category)} > ${this.escapeHtml(this.selectedEquipment.subcategory)}</div>
            ${this.selectedEquipment.company_number ? `<div class="text-sm text-gray-600">新音番号: ${this.escapeHtml(this.selectedEquipment.company_number)}</div>` : ''}
        `;

        const selectedSection = document.getElementById('selectedEquipmentSection');
        if (selectedSection) {
            selectedSection.style.display = 'block';
        }
    }

    /**
     * 数量セクション表示
     */
    showQuantitySection() {
        const quantitySection = document.getElementById('quantitySection');
        if (!quantitySection || !this.selectedEquipment) return;

        const quantityInput = document.getElementById('quantity');
        const quantityInfo = document.getElementById('quantityInfo');

        if (this.selectedEquipment.management_type === 'quantity') {
            if (quantityInput) {
                quantityInput.max = this.selectedEquipment.available_quantity;
            }
            if (quantityInfo) {
                quantityInfo.textContent = `利用可能数量: ${this.selectedEquipment.available_quantity}個`;
            }
        } else {
            if (quantityInput) {
                quantityInput.max = 1;
                quantityInput.value = 1;
            }
            if (quantityInfo) {
                quantityInfo.textContent = '個体管理機材（数量は1）';
            }
        }

        quantitySection.style.display = 'block';
    }

    /**
     * 選択モード切り替え
     */
    toggleSelectionMode() {
        const selectionType = document.querySelector('input[name="selection_type"]:checked')?.value;

        const individualSelection = document.getElementById('individualSelection');
        const setSelection = document.getElementById('setSelection');

        if (selectionType === 'individual') {
            if (individualSelection) individualSelection.style.display = 'block';
            if (setSelection) setSelection.style.display = 'none';
        } else {
            if (individualSelection) individualSelection.style.display = 'none';
            if (setSelection) setSelection.style.display = 'block';
        }

        // リセット
        this.resetForm();
    }

    /**
     * セット使用可能性チェック
     */
    async checkSetAvailability() {
        if (!this.phaseId) return;

        const setId = document.getElementById('equipment_set_id')?.value;
        if (!setId) {
            const infoContainer = document.getElementById('setAvailabilityInfo');
            if (infoContainer) {
                infoContainer.innerHTML = '';
            }
            return;
        }

        try {
            const response = await fetch(`/phases/${this.phaseId}/equipment-set-availability?set_id=${setId}`);
            if (!response.ok) throw new Error('セット可用性チェックに失敗しました');

            const data = await response.json();
            this.displaySetAvailability(data);
        } catch (error) {
            console.error('Set availability check error:', error);
            this.showError('セット可用性チェック中にエラーが発生しました');
        }
    }

    /**
     * セット使用可能性表示
     */
    displaySetAvailability(data) {
        const container = document.getElementById('setAvailabilityInfo');
        if (!container) return;

        const statusClass = data.all_available ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
        const statusText = data.all_available ? 'セット使用可能' : 'セット使用不可';
        const statusTextClass = data.all_available ? 'text-green-800' : 'text-red-800';

        const itemsHtml = data.items.map(item => {
            const itemStatusClass = item.is_available ? 'text-green-600' : 'text-red-600';
            const itemStatus = item.is_available ? '✓' : '✗';

            return `
                <div class="flex justify-between">
                    <span>${this.escapeHtml(item.equipment_name)}</span>
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
        const submitButton = document.getElementById('submitButton');
        if (submitButton) {
            submitButton.disabled = !data.all_available;
        }
    }

    /**
     * フォームリセット
     */
    resetForm() {
        this.selectedEquipment = null;

        const equipmentIdInput = document.getElementById('equipment_id');
        if (equipmentIdInput) {
            equipmentIdInput.value = '';
        }

        const selectedSection = document.getElementById('selectedEquipmentSection');
        if (selectedSection) {
            selectedSection.style.display = 'none';
        }

        const quantitySection = document.getElementById('quantitySection');
        if (quantitySection) {
            quantitySection.style.display = 'none';
        }

        const submitButton = document.getElementById('submitButton');
        if (submitButton) {
            submitButton.disabled = true;
        }

        const setInfo = document.getElementById('setAvailabilityInfo');
        if (setInfo) {
            setInfo.innerHTML = '';
        }
    }

    /**
     * モーダル処理の初期化
     */
    initModalHandlers() {
        // モーダル外クリックで閉じる
        const modal = document.getElementById('actionModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    /**
     * アクションモーダル表示
     */
    showActionModal(title, action, buttonClass, buttonText, showNote = false) {
        const modal = document.getElementById('actionModal');
        const titleElement = document.getElementById('modalTitle');
        const form = document.getElementById('actionForm');
        const button = document.getElementById('confirmButton');
        const noteField = document.getElementById('checkinNoteField');

        if (!modal || !titleElement || !form || !button) return;

        titleElement.textContent = title;
        form.action = action;
        button.textContent = buttonText;
        button.className = `px-4 py-2 rounded-md text-white ${buttonClass}`;

        if (noteField) {
            noteField.style.display = showNote ? 'block' : 'none';
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /**
     * モーダルを閉じる
     */
    closeModal() {
        const modal = document.getElementById('actionModal');
        const noteInput = document.getElementById('checkin_note');

        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        if (noteInput) {
            noteInput.value = '';
        }
    }

    /**
     * エラー表示
     */
    showError(message) {
        // 既存のエラーメッセージを削除
        const existingError = document.getElementById('error-message');
        if (existingError) {
            existingError.remove();
        }

        // エラーメッセージを作成
        const errorDiv = document.createElement('div');
        errorDiv.id = 'error-message';
        errorDiv.className = 'fixed top-4 right-4 bg-red-50 border border-red-200 rounded-md p-4 shadow-lg z-50';
        errorDiv.innerHTML = `
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-red-800">${this.escapeHtml(message)}</p>
                </div>
            </div>
        `;

        document.body.appendChild(errorDiv);

        // 5秒後に自動削除
        setTimeout(() => {
            if (errorDiv && errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }

    /**
     * 成功メッセージ表示
     */
    showSuccess(message) {
        // 既存のメッセージを削除
        const existingMessage = document.getElementById('success-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // 成功メッセージを作成
        const successDiv = document.createElement('div');
        successDiv.id = 'success-message';
        successDiv.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 rounded-md p-4 shadow-lg z-50';
        successDiv.innerHTML = `
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-green-800">${this.escapeHtml(message)}</p>
                </div>
            </div>
        `;

        document.body.appendChild(successDiv);

        // 3秒後に自動削除
        setTimeout(() => {
            if (successDiv && successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 3000);
    }

    /**
     * HTMLエスケープ
     */
    escapeHtml(text) {
        if (typeof text !== 'string') return text;
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 機材貸出処理
     */
    async checkoutEquipment(phaseEquipmentId, phaseId) {
        this.showActionModal(
            '貸出実行の確認',
            `/phases/${phaseId}/equipment/${phaseEquipmentId}/checkout`,
            'bg-green-600 hover:bg-green-700',
            '貸出実行',
            false
        );
    }

    /**
     * 機材返却処理
     */
    async checkinEquipment(phaseEquipmentId, phaseId) {
        this.showActionModal(
            '返却実行の確認',
            `/phases/${phaseId}/equipment/${phaseEquipmentId}/checkin`,
            'bg-purple-600 hover:bg-purple-700',
            '返却実行',
            true
        );
    }

    /**
     * 機材使用キャンセル処理
     */
    async cancelEquipment(phaseEquipmentId, phaseId) {
        this.showActionModal(
            'キャンセルの確認',
            `/phases/${phaseId}/equipment/${phaseEquipmentId}/cancel`,
            'bg-red-600 hover:bg-red-700',
            'キャンセル',
            false
        );
    }
}

// グローバル関数（テンプレートから呼び出し用）
let phaseEquipmentManager;

function toggleSelectionMode() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.toggleSelectionMode();
    }
}

function updateSubcategories() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.updateSubcategories();
    }
}

function searchEquipments() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.searchEquipments();
    }
}

function checkSetAvailability() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkSetAvailability();
    }
}

function checkoutEquipment(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkoutEquipment(phaseEquipmentId, phaseId);
    }
}

function checkinEquipment(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkinEquipment(phaseEquipmentId, phaseId);
    }
}

function cancelEquipment(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.cancelEquipment(phaseEquipmentId, phaseId);
    }
}

function closeModal() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.closeModal();
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
    phaseEquipmentManager = new PhaseEquipmentManager();
});

export default PhaseEquipmentManager;