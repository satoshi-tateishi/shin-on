/**
 * Phase Equipment Management JavaScript
 * 機材使用管理のフロントエンド機能
 */

class PhaseEquipmentManager {
    constructor() {
        this.availableEquipments = [];
        this.selectedEquipment = null;
        this.phaseId = null;
        this.selectedEquipmentList = []; // 選択された機材のリスト

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

        // リストに追加ボタンのイベントリスナー
        const addToListButton = document.getElementById('addToListButton');
        if (addToListButton) {
            addToListButton.addEventListener('click', () => {
                this.addEquipmentToList();
            });
        }

        // 最終サブミットボタンのイベントリスナー
        const finalSubmitForm = document.getElementById('finalSubmitForm');
        if (finalSubmitForm) {
            finalSubmitForm.addEventListener('submit', (e) => {
                this.handleFinalSubmit(e);
            });
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
        console.log('🎧 [DEBUG] initSearchAndFilter called');
        const searchInput = document.getElementById('equipment_search');
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        console.log('🔍 [DEBUG] Search filter elements:', {
            searchInput: !!searchInput,
            categorySelect: !!categorySelect,
            subcategorySelect: !!subcategorySelect
        });

        if (searchInput) {
            // デバウンス処理付きの検索
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchEquipments();
                }, 300);
            });
            console.log('✅ [DEBUG] Search input listener added');
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                console.log('📡 [DEBUG] Category changed, calling updateSubcategories');
                this.updateSubcategories();
            });
            console.log('✅ [DEBUG] Category change listener added');
        }

        if (subcategorySelect) {
            subcategorySelect.addEventListener('change', () => {
                console.log('📡 [DEBUG] Subcategory changed');
                this.searchEquipments();
            });
            console.log('✅ [DEBUG] Subcategory change listener added');
        }
    }

    /**
     * サブカテゴリ更新
     */
    updateSubcategories() {
        console.log('🔄 [DEBUG] updateSubcategories called');
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        console.log('🔍 [DEBUG] DOM elements:', {
            categorySelect: !!categorySelect,
            subcategorySelect: !!subcategorySelect,
            categoryValue: categorySelect?.value,
            subcategoryChildrenCount: subcategorySelect?.children.length
        });

        if (!categorySelect || !subcategorySelect) {
            console.error('❌ [DEBUG] Required elements not found');
            return;
        }

        const selectedCategoryId = categorySelect.value;
        console.log('🎯 [DEBUG] Selected category ID:', selectedCategoryId);

        // 初期化時に全オプションを保存（初回のみ）
        if (!this.allSubcategoryOptions) {
            this.allSubcategoryOptions = Array.from(subcategorySelect.children);
            console.log('💾 [DEBUG] Saved subcategory options:', this.allSubcategoryOptions.length);
            // デバッグ用に全オプションの内容を出力
            this.allSubcategoryOptions.forEach((option, index) => {
                console.log(`📋 [DEBUG] Option ${index}:`, option.tagName, option.textContent?.substring(0, 20), option.dataset?.categoryId);
            });
        }

        // サブカテゴリの選択をリセット
        subcategorySelect.value = '';

        // 既存のオプションを削除（「全サブカテゴリ」は残す）
        while (subcategorySelect.children.length > 1) {
            subcategorySelect.removeChild(subcategorySelect.lastChild);
        }
        console.log('🗑️ [DEBUG] Cleared existing options, remaining:', subcategorySelect.children.length);

        if (!selectedCategoryId) {
            // カテゴリが「全カテゴリ」の場合、全サブカテゴリを表示
            console.log('📂 [DEBUG] Showing all subcategories');
            this.allSubcategoryOptions.slice(1).forEach(element => {
                if (element.tagName === 'OPTGROUP') {
                    subcategorySelect.appendChild(element.cloneNode(true));
                }
            });
        } else {
            // 選択されたカテゴリに属するサブカテゴリのみを表示
            console.log('🎯 [DEBUG] Filtering for category:', selectedCategoryId);
            let addedCount = 0;

            this.allSubcategoryOptions.slice(1).forEach(element => {
                console.log('🔍 [DEBUG] Processing element:', element.tagName, element.label || element.textContent?.substring(0, 20));

                if (element.tagName === 'OPTGROUP') {
                    const optgroup = element.cloneNode(false); // 空のoptgroupを作成
                    const options = Array.from(element.children);
                    let hasValidOptions = false;

                    options.forEach(option => {
                        const categoryId = option.dataset.categoryId;
                        console.log('🔎 [DEBUG] Option:', option.textContent, 'categoryId:', categoryId, 'matches:', categoryId === selectedCategoryId);

                        if (categoryId === selectedCategoryId) {
                            optgroup.appendChild(option.cloneNode(true));
                            hasValidOptions = true;
                            addedCount++;
                        }
                    });

                    if (hasValidOptions) {
                        console.log('✅ [DEBUG] Adding optgroup with', optgroup.children.length, 'options');
                        subcategorySelect.appendChild(optgroup);
                    }
                }
            });
            console.log('📈 [DEBUG] Added', addedCount, 'subcategory options');
        }

        console.log('✅ [DEBUG] Final subcategory structure:');
        Array.from(subcategorySelect.children).forEach((child, index) => {
            console.log(`  ${index}: ${child.tagName} - ${child.textContent?.substring(0, 30)} (${child.children?.length || 0} children)`);
        });

        // 機材検索も実行
        if (this.phaseId) {
            this.searchEquipments();
        }
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
            const isAvailable = equipment.management_type === 'individual'
                ? !equipment.has_conflict
                : equipment.available_quantity > 0;

            // 選択済みかチェック
            const isSelected = this.selectedEquipmentList.some(item => item.equipment.id === equipment.id);

            let statusClass, statusText, statusTextClass, clickable;

            if (isSelected) {
                statusClass = 'bg-blue-100 border-blue-300';
                statusText = '選択済み';
                statusTextClass = 'text-blue-800';
                clickable = false;
            } else if (isAvailable) {
                statusClass = 'bg-green-50 border-green-200';
                statusText = '利用可能';
                statusTextClass = 'text-green-800';
                clickable = true;
            } else {
                statusClass = 'bg-red-50 border-red-200';
                statusText = '利用不可';
                statusTextClass = 'text-red-800';
                clickable = false;
            }

            const cursorStyle = clickable ? 'cursor-pointer hover:bg-gray-50' : 'cursor-not-allowed opacity-75';
            const onClickAction = clickable ? `phaseEquipmentManager.selectEquipment(${equipment.id})` : '';

            return `
                <div class="p-3 border rounded-lg ${cursorStyle} ${statusClass}"
                     data-equipment-id="${equipment.id}"
                     ${onClickAction ? `onclick="${onClickAction}"` : ''}>
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <div class="font-medium text-gray-900">${this.escapeHtml(equipment.name)}</div>
                                ${equipment.company_number ? `<div class="px-2 py-1 border border-gray-300 rounded text-xs text-gray-600">${this.escapeHtml(equipment.company_number)}</div>` : ''}
                                ${isSelected ? '<div class="px-2 py-1 bg-blue-500 text-white rounded text-xs font-medium">選択済み</div>' : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-medium ${statusTextClass}">${statusText}</span>
                            ${equipment.management_type === 'quantity' && !isSelected ?
                                `<div class="text-xs text-gray-500">利用可能: ${equipment.available_quantity}個</div>` : ''
                            }
                            ${isSelected && equipment.management_type === 'quantity' ?
                                `<div class="text-xs text-blue-600">選択数量: ${this.getSelectedQuantity(equipment.id)}個</div>` : ''
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
        console.log('🎯 [DEBUG] selectEquipment called with ID:', equipmentId);
        this.selectedEquipment = this.availableEquipments.find(eq => eq.id === equipmentId);
        if (!this.selectedEquipment) {
            console.error('❌ [DEBUG] Equipment not found:', equipmentId);
            return;
        }

        console.log('✅ [DEBUG] Equipment selected:', this.selectedEquipment.name);

        // フォームに値設定
        const equipmentIdInput = document.getElementById('equipment_id');
        if (equipmentIdInput) {
            equipmentIdInput.value = equipmentId;
            console.log('📝 [DEBUG] Set equipment_id input to:', equipmentId);
        } else {
            console.error('❌ [DEBUG] equipment_id input not found');
        }

        // 選択された機材情報表示
        this.displaySelectedEquipment();

        // セクション表示
        this.showQuantitySection();

        // リストに追加ボタン有効化
        const addToListButton = document.getElementById('addToListButton');
        if (addToListButton) {
            addToListButton.disabled = false;
            console.log('✅ [DEBUG] Add to list button enabled');
        } else {
            console.error('❌ [DEBUG] Add to list button not found');
        }
    }

    /**
     * 選択された機材情報表示
     */
    displaySelectedEquipment() {
        const infoDiv = document.getElementById('selectedEquipmentInfo');
        if (!infoDiv || !this.selectedEquipment) return;

        infoDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <div class="font-medium">${this.escapeHtml(this.selectedEquipment.name)}</div>
                ${this.selectedEquipment.company_number ? `<div class="px-2 py-1 border border-gray-300 rounded text-xs text-gray-600">${this.escapeHtml(this.selectedEquipment.company_number)}</div>` : ''}
            </div>
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
            // 数量管理機材の場合：数量セクションを表示し、編集可能にする
            if (quantityInput) {
                quantityInput.max = this.selectedEquipment.available_quantity;
                quantityInput.disabled = false;
                quantityInput.classList.remove('bg-gray-100', 'text-gray-500');
                quantityInput.classList.add('bg-white');
            }
            if (quantityInfo) {
                quantityInfo.textContent = `利用可能数量: ${this.selectedEquipment.available_quantity}個`;
            }
            quantitySection.style.display = 'block';
        } else {
            // 個体管理機材の場合：数量セクションを非表示にし、フォーム値を1に設定
            if (quantityInput) {
                quantityInput.value = 1;
            }
            quantitySection.style.display = 'none';
        }
    }

    /**
     * 機材をリストに追加
     */
    addEquipmentToList() {
        if (!this.selectedEquipment) {
            console.error('❌ [DEBUG] No equipment selected');
            return;
        }

        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;

        // 既に同じ機材が選択されているかチェック
        const existingIndex = this.selectedEquipmentList.findIndex(item => item.equipment.id === this.selectedEquipment.id);

        if (existingIndex >= 0) {
            // 既存の機材の数量を更新
            this.selectedEquipmentList[existingIndex].quantity = quantity;
            console.log('📝 [DEBUG] Updated existing equipment quantity:', quantity);
        } else {
            // 新しい機材をリストに追加
            this.selectedEquipmentList.push({
                equipment: this.selectedEquipment,
                quantity: quantity
            });
            console.log('✅ [DEBUG] Added equipment to list:', this.selectedEquipment.name, 'quantity:', quantity);
        }

        // テーブルを更新
        this.updateSelectedEquipmentTable();

        // 機材リストの表示を更新（選択済み状態を反映）
        this.refreshEquipmentDisplay();

        // フォームをリセット
        this.resetSelectionForm();

        // 成功メッセージを表示
        this.showSuccess(`${this.selectedEquipment.name} をリストに追加しました`);
    }

    /**
     * 選択された機材テーブルの更新
     */
    updateSelectedEquipmentTable() {
        const tableBody = document.getElementById('selectedEquipmentTableBody');
        const selectedCount = document.getElementById('selectedCount');
        const selectedSection = document.getElementById('selectedEquipmentListSection');
        const finalSubmitButton = document.getElementById('finalSubmitButton');

        if (!tableBody) return;

        // カウント更新
        if (selectedCount) {
            selectedCount.textContent = `${this.selectedEquipmentList.length}件`;
        }

        // セクション表示/非表示
        if (selectedSection) {
            selectedSection.style.display = this.selectedEquipmentList.length > 0 ? 'block' : 'none';
        }

        // 最終サブミットボタンの状態
        if (finalSubmitButton) {
            finalSubmitButton.disabled = this.selectedEquipmentList.length === 0;
        }

        // テーブル内容の更新
        tableBody.innerHTML = this.selectedEquipmentList.map((item, index) => {
            const isQuantityManagement = item.equipment.management_type === 'quantity';

            return `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-medium text-gray-900">${this.escapeHtml(item.equipment.name)}</div>
                            ${item.equipment.company_number ?
                                `<div class="px-2 py-1 border border-gray-300 rounded text-xs text-gray-600">${this.escapeHtml(item.equipment.company_number)}</div>` :
                                ''
                            }
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${item.quantity}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button type="button" onclick="window.removeFromList(${index})"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            削除
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        console.log('📊 [DEBUG] Updated table with', this.selectedEquipmentList.length, 'items');
    }

    /**
     * リストから機材を削除
     */
    removeFromList(index) {
        if (index >= 0 && index < this.selectedEquipmentList.length) {
            const removedItem = this.selectedEquipmentList.splice(index, 1)[0];
            console.log('🗑️ [DEBUG] Removed equipment from list:', removedItem.equipment.name);
            this.updateSelectedEquipmentTable();

            // 機材リストの表示を更新（選択済み状態をリセット）
            this.refreshEquipmentDisplay();

            this.showSuccess(`${removedItem.equipment.name} をリストから削除しました`);
        }
    }

    /**
     * 選択フォームのリセット
     */
    resetSelectionForm() {
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

        const addToListButton = document.getElementById('addToListButton');
        if (addToListButton) {
            addToListButton.disabled = true;
        }

        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.value = 1;
        }
    }

    /**
     * 最終サブミット処理
     */
    handleFinalSubmit(event) {
        event.preventDefault();

        if (this.selectedEquipmentList.length === 0) {
            this.showError('機材が選択されていません');
            return;
        }

        // 選択された機材データをJSON形式で準備
        const equipmentData = this.selectedEquipmentList.map(item => ({
            equipment_id: item.equipment.id,
            quantity: item.quantity
        }));

        const dataInput = document.getElementById('selectedEquipmentData');
        if (dataInput) {
            dataInput.value = JSON.stringify(equipmentData);
        }

        console.log('🚀 [DEBUG] Submitting equipment data:', equipmentData);

        // フォームを送信
        event.target.submit();
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
    showActionModal(title, action, buttonClass, buttonText, showNote = false, method = 'PATCH') {
        const modal = document.getElementById('actionModal');
        const titleElement = document.getElementById('modalTitle');
        const form = document.getElementById('actionForm');
        const button = document.getElementById('confirmButton');
        const methodField = document.getElementById('methodField');

        if (!modal || !titleElement || !form || !button) return;

        titleElement.textContent = title;
        form.action = action;
        button.textContent = buttonText;
        button.className = `px-4 py-2 rounded-md text-white ${buttonClass}`;

        if (methodField) {
            methodField.value = method;
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /**
     * モーダルを閉じる
     */
    closeModal() {
        const modal = document.getElementById('actionModal');

        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
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
     * 選択された機材の数量を取得
     */
    getSelectedQuantity(equipmentId) {
        const selectedItem = this.selectedEquipmentList.find(item => item.equipment.id === equipmentId);
        return selectedItem ? selectedItem.quantity : 0;
    }

    /**
     * 機材表示を更新（選択済み状態を反映）
     */
    refreshEquipmentDisplay() {
        if (this.availableEquipments && this.availableEquipments.length > 0) {
            this.displayEquipments(this.availableEquipments);
        }
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
            '出庫実行の確認',
            `/phases/${phaseId}/equipment/${phaseEquipmentId}/checkout`,
            'bg-green-600 hover:bg-green-700',
            '出庫実行',
            false
        );
    }

    /**
     * 機材返却処理
     */
    async checkinEquipment(phaseEquipmentId, phaseId) {
        // 機材情報を取得して location_id をチェック
        try {
            const response = await fetch(`/phases/${phaseId}/equipment/${phaseEquipmentId}/equipment-info`);

            if (!response.ok) {
                throw new Error('機材情報の取得に失敗しました');
            }

            const data = await response.json();
            const equipment = data.equipment;

            // location_id が 90-92 の場合は返却先選択画面へ遷移
            if (equipment.location_id >= 90 && equipment.location_id <= 92) {
                // 機材情報をセッションストレージに保存
                sessionStorage.setItem('returnEquipmentData', JSON.stringify({
                    phaseEquipmentId: phaseEquipmentId,
                    phaseId: phaseId,
                    equipmentId: equipment.id,
                    equipmentName: equipment.name,
                    companyNumber: equipment.company_number,
                    locationId: equipment.location_id
                }));

                // 返却先選択画面へ遷移
                window.location.href = '/equipment-transfer/return-select';
                return;
            }

            // 通常の返却処理（location_id が 90-92 以外）
            this.showActionModal(
                '返却実行の確認',
                `/phases/${phaseId}/equipment/${phaseEquipmentId}/checkin`,
                'bg-purple-600 hover:bg-purple-700',
                '返却実行',
                false,
                'PATCH'
            );

        } catch (error) {
            console.error('Equipment info fetch error:', error);
            this.showError('機材情報の取得中にエラーが発生しました: ' + error.message);
        }
    }

    /**
     * 機材使用記録削除処理
     */
    async deleteEquipment(phaseEquipmentId, phaseId) {
        this.showActionModal(
            'この機材の使用記録を削除しますか？',
            `/phases/${phaseId}/equipment/${phaseEquipmentId}`,
            'bg-red-600 hover:bg-red-700',
            '削除',
            false,
            'DELETE'
        );
    }
}

// グローバル関数（テンプレートから呼び出し用）
let phaseEquipmentManager;

// Windowオブジェクトに関数を登録（Viteモジュールから呼び出し可能にする）
window.toggleSelectionMode = function() {
    console.log('🔗 [DEBUG] Global toggleSelectionMode called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.toggleSelectionMode();
    }
}

window.updateSubcategories = function() {
    console.log('🔗 [DEBUG] Global updateSubcategories called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.updateSubcategories();
    }
}

window.searchEquipments = function() {
    console.log('🔗 [DEBUG] Global searchEquipments called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.searchEquipments();
    }
}

window.selectEquipment = function(equipmentId) {
    console.log('🔗 [DEBUG] Global selectEquipment called with ID:', equipmentId);
    if (phaseEquipmentManager) {
        phaseEquipmentManager.selectEquipment(equipmentId);
    } else {
        console.error('❌ [DEBUG] phaseEquipmentManager not available');
    }
}

window.checkSetAvailability = function() {
    console.log('🔗 [DEBUG] Global checkSetAvailability called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkSetAvailability();
    }
}

window.checkoutEquipment = function(phaseEquipmentId, phaseId) {
    console.log('🔗 [DEBUG] Global checkoutEquipment called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkoutEquipment(phaseEquipmentId, phaseId);
    }
}

window.checkinEquipment = function(phaseEquipmentId, phaseId) {
    console.log('🔗 [DEBUG] Global checkinEquipment called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkinEquipment(phaseEquipmentId, phaseId);
    }
}

window.deleteEquipment = function(phaseEquipmentId, phaseId) {
    console.log('🔗 [DEBUG] Global deleteEquipment called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.deleteEquipment(phaseEquipmentId, phaseId);
    }
}

window.closeModal = function() {
    console.log('🔗 [DEBUG] Global closeModal called');
    if (phaseEquipmentManager) {
        phaseEquipmentManager.closeModal();
    }
}

window.removeFromList = function(index) {
    console.log('🔗 [DEBUG] Global removeFromList called with index:', index);
    if (phaseEquipmentManager) {
        phaseEquipmentManager.removeFromList(index);
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [DEBUG] DOMContentLoaded - Initializing PhaseEquipmentManager');
    phaseEquipmentManager = new PhaseEquipmentManager();
    window.phaseEquipmentManager = phaseEquipmentManager; // グローバルからアクセス可能に
    console.log('✅ [DEBUG] PhaseEquipmentManager initialized and available globally');
});

export default PhaseEquipmentManager;