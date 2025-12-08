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
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        if (!categorySelect || !subcategorySelect) {
            return;
        }

        const selectedCategoryId = categorySelect.value;

        // 初期化時に全オプションを保存（初回のみ）
        if (!this.allSubcategoryOptions) {
            this.allSubcategoryOptions = Array.from(subcategorySelect.children);
            // デバッグ用に全オプションの内容を出力
            this.allSubcategoryOptions.forEach((option, index) => {
            });
        }

        // サブカテゴリの選択をリセット
        subcategorySelect.value = '';

        // 既存のオプションを削除（「全サブカテゴリ」は残す）
        while (subcategorySelect.children.length > 1) {
            subcategorySelect.removeChild(subcategorySelect.lastChild);
        }

        if (!selectedCategoryId) {
            // カテゴリが「全カテゴリ」の場合、全サブカテゴリを表示
            this.allSubcategoryOptions.slice(1).forEach(element => {
                if (element.tagName === 'OPTGROUP') {
                    subcategorySelect.appendChild(element.cloneNode(true));
                }
            });
        } else {
            // 選択されたカテゴリに属するサブカテゴリのみを表示
            let addedCount = 0;

            this.allSubcategoryOptions.slice(1).forEach(element => {

                if (element.tagName === 'OPTGROUP') {
                    const optgroup = element.cloneNode(false); // 空のoptgroupを作成
                    const options = Array.from(element.children);
                    let hasValidOptions = false;

                    options.forEach(option => {
                        const categoryId = option.dataset.categoryId;

                        if (categoryId === selectedCategoryId) {
                            optgroup.appendChild(option.cloneNode(true));
                            hasValidOptions = true;
                            addedCount++;
                        }
                    });

                    if (hasValidOptions) {
                        subcategorySelect.appendChild(optgroup);
                    }
                }
            });
        }

        Array.from(subcategorySelect.children).forEach((child, index) => {
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

            let statusClass, statusText, statusTextClass, clickable, onClickAction;

            if (isSelected) {
                statusClass = 'bg-blue-100 border-blue-300 cursor-pointer hover:bg-blue-200';
                statusText = 'クリックで解除';
                statusTextClass = 'text-blue-800';
                clickable = true;
                onClickAction = `phaseEquipmentManager.deselectEquipment(${equipment.id})`;
            } else if (isAvailable) {
                statusClass = 'bg-green-50 border-green-200 cursor-pointer hover:bg-gray-50';
                statusText = '利用可能';
                statusTextClass = 'text-green-800';
                clickable = true;
                onClickAction = `phaseEquipmentManager.selectEquipment(${equipment.id})`;
            } else {
                statusClass = 'bg-red-50 border-red-200 cursor-not-allowed opacity-75';
                statusText = '利用不可';
                statusTextClass = 'text-red-800';
                clickable = false;
                onClickAction = '';
            }

            return `
                <div class="p-3 border rounded-lg ${statusClass}"
                     data-equipment-id="${equipment.id}"
                     ${onClickAction ? `onclick="${onClickAction}"` : ''}>
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <div class="font-medium text-gray-900">${this.escapeHtml(equipment.name)}</div>
                                ${equipment.company_number ? `<div class="px-2 py-1 border border-gray-300 rounded text-xs text-gray-600">${this.escapeHtml(equipment.company_number)}</div>` : ''}
                                ${isSelected ? '<div class="px-2 py-1 bg-blue-500 text-white rounded text-xs font-medium">選択</div>' : ''}
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
     * 機材選択 - クリックで直接リストに追加
     */
    selectEquipment(equipmentId) {
        this.selectedEquipment = this.availableEquipments.find(eq => eq.id === equipmentId);
        if (!this.selectedEquipment) {
            return;
        }


        // 数量管理機材の場合、数量入力ダイアログを表示
        if (this.selectedEquipment.management_type === 'quantity') {
            this.showQuantityDialog(this.selectedEquipment);
        } else {
            // 個体管理機材の場合、直接リストに追加
            this.directAddToList(this.selectedEquipment, 1);
        }
    }

    /**
     * 機材選択解除 - 選択済み機材をクリックでリストから削除
     */
    deselectEquipment(equipmentId) {

        const equipment = this.availableEquipments.find(eq => eq.id === equipmentId);
        if (!equipment) {
            return;
        }

        // 該当機材のインデックスを検索
        const index = this.selectedEquipmentList.findIndex(item => item.equipment.id === equipmentId);

        if (index >= 0) {
            // 確認ダイアログを表示
            if (confirm(`${equipment.name} を選択解除しますか？`)) {
                this.removeFromList(index);
            }
        }
    }

    /**
     * 数量入力ダイアログ表示
     */
    showQuantityDialog(equipment) {
        // 既存のダイアログを削除
        const existingDialog = document.getElementById('quantity-dialog');
        if (existingDialog) {
            existingDialog.remove();
        }

        // ダイアログを作成
        const dialog = document.createElement('div');
        dialog.id = 'quantity-dialog';
        dialog.className = 'fixed inset-0 overflow-y-auto';
        dialog.style.zIndex = '9999';
        dialog.innerHTML = `
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="window.closeQuantityDialog()"></div>
                <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">数量を入力</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-700 font-medium mb-2">${this.escapeHtml(equipment.name)}</p>
                            <p class="text-sm text-gray-500 mb-4">利用可能数量: ${equipment.available_quantity}個</p>
                            <label for="dialog-quantity" class="block text-sm font-medium text-gray-700 mb-2">数量</label>
                            <input type="number" id="dialog-quantity" min="1" max="${equipment.available_quantity}" value="1"
                                   oninput="window.validateQuantityInput(this, ${equipment.available_quantity})"
                                   onkeydown="window.preventInvalidInput(event)"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p id="quantity-error" class="mt-1 text-sm text-red-600 hidden">利用可能数量を超えています</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button onclick="window.confirmQuantityAndAdd()" type="button" id="dialog-add-button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            追加
                        </button>
                        <button onclick="window.closeQuantityDialog()" type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            キャンセル
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);

        // 数量入力にフォーカス
        setTimeout(() => {
            const quantityInput = document.getElementById('dialog-quantity');
            if (quantityInput) {
                quantityInput.focus();
                quantityInput.select();
            }
        }, 100);
    }

    /**
     * 数量ダイアログを閉じる
     */
    closeQuantityDialog() {
        const dialog = document.getElementById('quantity-dialog');
        if (dialog) {
            dialog.remove();
        }
    }

    /**
     * 数量を確認してリストに追加
     */
    confirmQuantityAndAdd() {
        const quantityInput = document.getElementById('dialog-quantity');
        if (!quantityInput || !this.selectedEquipment) {
            return;
        }

        const quantity = parseInt(quantityInput.value) || 1;
        const maxQuantity = this.selectedEquipment.available_quantity;

        // 最大値チェック
        if (quantity > maxQuantity) {
            this.showError(`利用可能数量（${maxQuantity}個）を超えています`);
            return;
        }

        // 最小値チェック
        if (quantity < 1) {
            this.showError('数量は1以上を入力してください');
            return;
        }

        this.directAddToList(this.selectedEquipment, quantity);
        this.closeQuantityDialog();
    }

    /**
     * 機材を直接リストに追加
     */
    directAddToList(equipment, quantity) {
        // 既に同じ機材が選択されているかチェック
        const existingIndex = this.selectedEquipmentList.findIndex(item => item.equipment.id === equipment.id);

        if (existingIndex >= 0) {
            // 既存の機材の数量を更新
            this.selectedEquipmentList[existingIndex].quantity = quantity;
            this.showSuccess(`${equipment.name} の数量を更新しました`);
        } else {
            // 新しい機材をリストに追加
            this.selectedEquipmentList.push({
                equipment: equipment,
                quantity: quantity
            });
            this.showSuccess(`${equipment.name} をリストに追加しました`);
        }

        // テーブルを更新
        this.updateSelectedEquipmentTable();

        // 機材リストの表示を更新（選択済み状態を反映）
        this.refreshEquipmentDisplay();

        // 選択をリセット
        this.selectedEquipment = null;
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
        // 選択モードを確認
        const selectionType = document.querySelector('input[name="selection_type"]:checked')?.value;

        if (selectionType === 'set') {
            this.addEquipmentSetToList();
        } else {
            this.addIndividualEquipmentToList();
        }
    }

    /**
     * 個別機材をリストに追加
     */
    addIndividualEquipmentToList() {
        if (!this.selectedEquipment) {
            return;
        }

        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;

        // 既に同じ機材が選択されているかチェック
        const existingIndex = this.selectedEquipmentList.findIndex(item => item.equipment.id === this.selectedEquipment.id);

        if (existingIndex >= 0) {
            // 既存の機材の数量を更新
            this.selectedEquipmentList[existingIndex].quantity = quantity;
        } else {
            // 新しい機材をリストに追加
            this.selectedEquipmentList.push({
                equipment: this.selectedEquipment,
                quantity: quantity
            });
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
     * 機材セットをリストに追加
     */
    async addEquipmentSetToList() {
        const setSelect = document.getElementById('equipment_set_id');
        if (!setSelect || !setSelect.value) {
            this.showError('機材セットが選択されていません');
            return;
        }

        const setId = setSelect.value;
        const setName = setSelect.options[setSelect.selectedIndex].text;

        try {
            // フェーズの機材可用性APIを使用してセット内機材を取得
            const response = await fetch(`/phases/${this.phaseId}/equipment-set-availability?set_id=${setId}`);
            if (!response.ok) throw new Error('セット情報の取得に失敗しました');

            const setData = await response.json();

            if (!setData.all_available) {
                this.showError('一部の機材が利用できないため、セットを追加できません');
                return;
            }

            // セット内の各機材をリストに追加
            let addedCount = 0;
            for (const item of setData.items) {
                // 機材オブジェクトを構築（APIレスポンスから）
                const equipment = {
                    id: item.equipment_id,
                    name: item.equipment_name,
                    company_number: item.company_number || null,
                    management_type: 'individual' // デフォルト値
                };

                // 既に同じ機材が選択されているかチェック
                const existingIndex = this.selectedEquipmentList.findIndex(listItem =>
                    listItem.equipment.id === equipment.id
                );

                if (existingIndex >= 0) {
                    // 既存の機材の数量を更新
                    this.selectedEquipmentList[existingIndex].quantity = item.required_quantity;
                } else {
                    // 新しい機材をリストに追加
                    this.selectedEquipmentList.push({
                        equipment: equipment,
                        quantity: item.required_quantity
                    });
                    addedCount++;
                }
            }

            // テーブルを更新
            this.updateSelectedEquipmentTable();

            // フォームをリセット
            this.resetForm();

            // 成功メッセージを表示
            this.showSuccess(`機材セット「${setName}」の${addedCount}件の機材をリストに追加しました`);

        } catch (error) {
            console.error('Set addition error:', error);
            this.showError('機材セットの追加中にエラーが発生しました');
        }
    }

    /**
     * 選択された機材テーブルの更新
     */
    updateSelectedEquipmentTable() {
        const tableBody = document.getElementById('selectedEquipmentTableBody');
        const selectedCount = document.getElementById('selectedCount');
        const finalSubmitButton = document.getElementById('finalSubmitButton');
        const emptyMessage = document.getElementById('emptyMessage');
        const selectedTable = document.getElementById('selectedEquipmentTable');

        if (!tableBody) return;

        // カウント更新
        if (selectedCount) {
            selectedCount.textContent = `${this.selectedEquipmentList.length}件`;
        }

        // テーブル/空メッセージの表示切り替え
        if (this.selectedEquipmentList.length > 0) {
            if (emptyMessage) emptyMessage.classList.add('hidden');
            if (selectedTable) selectedTable.classList.remove('hidden');
        } else {
            if (emptyMessage) emptyMessage.classList.remove('hidden');
            if (selectedTable) selectedTable.classList.add('hidden');
        }

        // 最終サブミットボタンの状態
        if (finalSubmitButton) {
            finalSubmitButton.disabled = this.selectedEquipmentList.length === 0;
        }

        // テーブル内容の更新
        tableBody.innerHTML = this.selectedEquipmentList.map((item, index) => {
            return `
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">${this.escapeHtml(item.equipment.name)}</div>
                            ${item.equipment.company_number ?
                                `<div class="text-xs text-gray-500 mt-1">${this.escapeHtml(item.equipment.company_number)}</div>` :
                                ''
                            }
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <span class="text-sm text-gray-900">${item.quantity}</span>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <button type="button" onclick="window.removeFromList(${index})"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            削除
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

    }

    /**
     * リストから機材を削除
     */
    removeFromList(index) {
        if (index >= 0 && index < this.selectedEquipmentList.length) {
            const removedItem = this.selectedEquipmentList.splice(index, 1)[0];
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
        const addToListButtonContainer = document.getElementById('addToListButtonContainer');

        if (selectionType === 'individual') {
            if (individualSelection) individualSelection.style.display = 'block';
            if (setSelection) setSelection.style.display = 'none';
            if (addToListButtonContainer) addToListButtonContainer.style.display = 'none';
        } else {
            if (individualSelection) individualSelection.style.display = 'none';
            if (setSelection) setSelection.style.display = 'block';
            if (addToListButtonContainer) addToListButtonContainer.style.display = 'block';
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

        // リストに追加ボタンの状態
        const addToListButton = document.getElementById('addToListButton');
        if (addToListButton) {
            addToListButton.disabled = !data.all_available;
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

        const addToListButton = document.getElementById('addToListButton');
        if (addToListButton) {
            addToListButton.disabled = true;
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
    showActionModal(title, action, buttonClass, buttonText, method = 'PATCH') {
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
            '出庫実行'
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
            'DELETE'
        );
    }
}

// グローバル関数（テンプレートから呼び出し用）
let phaseEquipmentManager;

// Windowオブジェクトに関数を登録（Viteモジュールから呼び出し可能にする）
window.toggleSelectionMode = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.toggleSelectionMode();
    }
}

window.updateSubcategories = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.updateSubcategories();
    }
}

window.searchEquipments = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.searchEquipments();
    }
}

window.selectEquipment = function(equipmentId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.selectEquipment(equipmentId);
    }
}

window.deselectEquipment = function(equipmentId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.deselectEquipment(equipmentId);
    }
}

window.checkSetAvailability = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkSetAvailability();
    }
}

window.checkoutEquipment = function(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkoutEquipment(phaseEquipmentId, phaseId);
    }
}

window.checkinEquipment = function(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.checkinEquipment(phaseEquipmentId, phaseId);
    }
}

window.deleteEquipment = function(phaseEquipmentId, phaseId) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.deleteEquipment(phaseEquipmentId, phaseId);
    }
}

window.closeModal = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.closeModal();
    }
}

window.removeFromList = function(index) {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.removeFromList(index);
    }
}

window.closeQuantityDialog = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.closeQuantityDialog();
    }
}

window.confirmQuantityAndAdd = function() {
    if (phaseEquipmentManager) {
        phaseEquipmentManager.confirmQuantityAndAdd();
    }
}

window.validateQuantityInput = function(input, maxQuantity) {
    const value = parseInt(input.value);
    const errorElement = document.getElementById('quantity-error');
    const addButton = document.getElementById('dialog-add-button');

    // 数値チェック
    if (isNaN(value) || value < 1) {
        input.value = 1;
        if (errorElement) errorElement.classList.add('hidden');
        if (addButton) addButton.disabled = false;
        return;
    }

    // 最大値チェック
    if (value > maxQuantity) {
        input.value = maxQuantity;
        if (errorElement) {
            errorElement.textContent = `最大値は${maxQuantity}個です`;
            errorElement.classList.remove('hidden');
        }
        setTimeout(() => {
            if (errorElement) errorElement.classList.add('hidden');
        }, 2000);
    } else {
        if (errorElement) errorElement.classList.add('hidden');
        if (addButton) addButton.disabled = false;
    }
}

window.preventInvalidInput = function(event) {

    // Enterキーで確定
    if (event.key === 'Enter') {
        event.preventDefault();
        window.confirmQuantityAndAdd();
        return;
    }

    // 数字以外の入力を防ぐ（Backspace、Tab、矢印キーなどは許可）
    const allowedKeys = ['Backspace', 'Tab', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Delete'];
    if (!allowedKeys.includes(event.key) && isNaN(parseInt(event.key))) {
        event.preventDefault();
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
    phaseEquipmentManager = new PhaseEquipmentManager();
    window.phaseEquipmentManager = phaseEquipmentManager; // グローバルからアクセス可能に
});

export default PhaseEquipmentManager;