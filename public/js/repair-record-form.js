/**
 * 修理記録作成フォーム用JavaScript
 *
 * 機能:
 * - 画像プレビュー・削除
 * - カテゴリ・サブカテゴリ・機材の段階的選択
 * - 将来予約チェック
 */

class RepairRecordForm {
    constructor() {
        this.initializeElements();
        this.initializeImageUpload();
        this.initializeCascadingSelects();
        this.initializeFutureReservationCheck();
    }

    /**
     * DOM要素の初期化
     */
    initializeElements() {
        // 画像アップロード関連
        this.photosInput = document.getElementById('photos');
        this.previewContainer = document.getElementById('image-preview');
        this.warningContainer = document.getElementById('file-limit-warning');
        this.allFiles = [];
        this.MAX_FILES = 2;

        // カテゴリ選択関連
        this.categoryFilter = document.getElementById('category_filter');
        this.subcategoryFilter = document.getElementById('subcategory_filter');
        this.equipmentSelect = document.getElementById('equipment_id');

        // 将来予約警告関連
        this.futureReservationsWarning = document.getElementById('future-reservations-warning');
        this.reservationsList = document.getElementById('reservations-list');
    }

    /**
     * 画像アップロード機能の初期化
     */
    initializeImageUpload() {
        if (!this.photosInput) return;

        this.photosInput.addEventListener('change', (e) => {
            this.handleFileSelection(e);
        });

        this.previewContainer.addEventListener('click', (e) => {
            this.handleImageRemoval(e);
        });
    }

    /**
     * ファイル選択処理
     */
    handleFileSelection(event) {
        console.log('ファイル選択イベント発生');

        const newFiles = Array.from(event.target.files);
        console.log('新しく選択されたファイル数:', newFiles.length);

        if (newFiles.length === 0) return;

        // 警告を非表示
        this.warningContainer.classList.add('hidden');

        // 新しいファイルを既存のファイル配列に追加（制限内のみ）
        newFiles.forEach(file => {
            if (file.type.startsWith('image/')) {
                if (this.allFiles.length < this.MAX_FILES) {
                    this.allFiles.push(file);
                } else {
                    // 制限に達した場合は警告を表示
                    this.warningContainer.classList.remove('hidden');
                    console.log('ファイル数制限に達しました');
                }
            }
        });

        console.log('総ファイル数:', this.allFiles.length);

        this.updatePreviews();
        this.updateFileInput();
    }

    /**
     * 画像プレビューの更新
     */
    updatePreviews() {
        this.previewContainer.innerHTML = '';

        if (this.allFiles.length === 0) {
            this.previewContainer.classList.add('hidden');
            return;
        }

        this.previewContainer.classList.remove('hidden');

        this.allFiles.forEach((file, index) => {
            console.log('ファイル処理中:', file.name, 'タイプ:', file.type);

            const reader = new FileReader();

            reader.onload = (e) => {
                console.log('ファイル読み込み完了:', file.name);

                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group mb-2';

                previewDiv.innerHTML = `
                    <div class="w-full h-40 bg-gray-100 rounded-lg border border-gray-300 overflow-hidden flex items-center justify-center">
                        <img src="${e.target.result}"
                             alt="プレビュー ${index + 1}"
                             class="max-w-full max-h-full object-contain"
                             onload="console.log('画像表示成功: ${file.name}')"
                             onerror="console.error('画像表示エラー: ${file.name}')">
                    </div>
                    <div class="absolute top-2 right-2">
                        <button type="button"
                                class="remove-image bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs"
                                data-index="${index}"
                                title="画像を削除">
                            ×
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                `;

                this.previewContainer.appendChild(previewDiv);
            };

            reader.onerror = () => {
                console.error('ファイル読み込みエラー:', file.name);
            };

            reader.readAsDataURL(file);
        });
    }

    /**
     * ファイルインプットの更新
     */
    updateFileInput() {
        const dt = new DataTransfer();
        this.allFiles.forEach(file => {
            dt.items.add(file);
        });
        this.photosInput.files = dt.files;
    }

    /**
     * 画像削除処理
     */
    handleImageRemoval(event) {
        const removeBtn = event.target.closest('.remove-image');
        if (!removeBtn) return;

        const index = parseInt(removeBtn.dataset.index);
        console.log('画像削除:', index);

        // 配列から該当ファイルを削除
        this.allFiles.splice(index, 1);

        // 警告を非表示にする（削除によって制限以下になった場合）
        if (this.allFiles.length < this.MAX_FILES) {
            this.warningContainer.classList.add('hidden');
        }

        // プレビューとFileInputを更新
        this.updatePreviews();
        this.updateFileInput();
    }

    /**
     * カスケード選択機能の初期化
     */
    initializeCascadingSelects() {
        if (!this.categoryFilter || !this.subcategoryFilter || !this.equipmentSelect) return;

        this.categoryFilter.addEventListener('change', () => {
            this.handleCategoryChange();
        });

        this.subcategoryFilter.addEventListener('change', () => {
            this.handleSubcategoryChange();
        });
    }

    /**
     * カテゴリ変更処理
     */
    handleCategoryChange() {
        const selectedCategory = this.categoryFilter.value;

        // サブカテゴリと機材をリセット
        this.resetSubcategorySelect();
        this.resetEquipmentSelect();

        if (selectedCategory) {
            this.loadSubcategories(selectedCategory);
        } else {
            this.disableSubcategorySelect();
            this.disableEquipmentSelect();
        }

        this.hideFutureReservationWarning();
    }

    /**
     * サブカテゴリ変更処理
     */
    handleSubcategoryChange() {
        const selectedSubcategory = this.subcategoryFilter.value;

        if (selectedSubcategory) {
            this.enableEquipmentSelect();
            this.loadEquipments();
        } else {
            this.disableEquipmentSelect();
            this.hideFutureReservationWarning();
        }
    }

    /**
     * サブカテゴリ読み込み
     */
    async loadSubcategories(categoryName) {
        try {
            // ローディング表示
            this.subcategoryFilter.innerHTML = '<option value="">読み込み中...</option>';

            const response = await fetch(`/api/subcategories/by-category?category=${encodeURIComponent(categoryName)}`);
            const data = await response.json();

            this.subcategoryFilter.innerHTML = '<option value="">サブカテゴリを選択してください</option>';

            if (data.success && data.subcategories && data.subcategories.length > 0) {
                console.log('サブカテゴリ取得結果:', data.subcategories.length + '件');

                data.subcategories.forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.name;
                    option.textContent = subcategory.name;
                    this.subcategoryFilter.appendChild(option);
                });

                this.enableSubcategorySelect();
            } else {
                this.showNoSubcategoriesMessage();
            }
        } catch (error) {
            console.error('サブカテゴリ取得エラー:', error);
            this.subcategoryFilter.innerHTML = '<option value="">エラーが発生しました</option>';
        }
    }

    /**
     * 機材読み込み
     */
    async loadEquipments() {
        const selectedCategory = this.categoryFilter.value;
        const selectedSubcategory = this.subcategoryFilter.value;

        if (!selectedCategory || !selectedSubcategory) {
            console.log('カテゴリまたはサブカテゴリが未選択のため、機材は表示しません');
            this.disableEquipmentSelect();
            return;
        }

        try {
            // ローディング表示
            this.equipmentSelect.innerHTML = '<option value="">読み込み中...</option>';

            const response = await fetch(`/api/equipments/by-subcategory?category=${encodeURIComponent(selectedCategory)}&subcategory=${encodeURIComponent(selectedSubcategory)}`);
            const data = await response.json();

            this.equipmentSelect.innerHTML = '<option value="">機材を選択してください</option>';

            if (data.success && data.equipments && data.equipments.length > 0) {
                console.log('API取得結果:', data.equipments.length + '件');

                data.equipments.forEach(equipment => {
                    const option = document.createElement('option');
                    option.value = equipment.id;
                    option.textContent = equipment.name +
                        (equipment.company_number ? ' [ ' + equipment.company_number + ' ]' : '');
                    this.equipmentSelect.appendChild(option);
                });

                this.enableEquipmentSelect();
            } else {
                this.showNoEquipmentsMessage();
            }
        } catch (error) {
            console.error('機材データ取得エラー:', error);
            this.equipmentSelect.innerHTML = '<option value="">エラーが発生しました</option>';
        }

        this.equipmentSelect.value = '';
        this.hideFutureReservationWarning();
    }

    /**
     * UI状態管理メソッド群
     */
    resetSubcategorySelect() {
        this.subcategoryFilter.innerHTML = '<option value="">サブカテゴリを選択してください</option>';
        this.subcategoryFilter.value = '';
    }

    resetEquipmentSelect() {
        this.equipmentSelect.innerHTML = '<option value="">サブカテゴリを先に選択してください</option>';
        this.equipmentSelect.value = '';
    }

    enableSubcategorySelect() {
        this.subcategoryFilter.disabled = false;
        this.subcategoryFilter.className = this.subcategoryFilter.className.replace('disabled:bg-gray-100 disabled:text-gray-500', '');
    }

    disableSubcategorySelect() {
        this.subcategoryFilter.disabled = true;
        this.subcategoryFilter.innerHTML = '<option value="">カテゴリを先に選択してください</option>';
    }

    enableEquipmentSelect() {
        this.equipmentSelect.disabled = false;
        this.equipmentSelect.className = this.equipmentSelect.className.replace('disabled:bg-gray-100 disabled:text-gray-500', '');
    }

    disableEquipmentSelect() {
        this.equipmentSelect.disabled = true;
        this.equipmentSelect.innerHTML = '<option value="">サブカテゴリを先に選択してください</option>';
        this.equipmentSelect.value = '';
    }

    showNoSubcategoriesMessage() {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = '該当するサブカテゴリがありません';
        option.disabled = true;
        this.subcategoryFilter.appendChild(option);
    }

    showNoEquipmentsMessage() {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = '該当する機材がありません';
        option.disabled = true;
        this.equipmentSelect.appendChild(option);
    }

    /**
     * 将来予約チェック機能の初期化
     */
    initializeFutureReservationCheck() {
        if (!this.equipmentSelect || !this.futureReservationsWarning) return;

        this.equipmentSelect.addEventListener('change', () => {
            this.checkFutureReservations();
        });
    }

    /**
     * 将来予約チェック
     */
    async checkFutureReservations() {
        const equipmentId = this.equipmentSelect.value;

        if (!equipmentId) {
            this.hideFutureReservationWarning();
            return;
        }

        try {
            const response = await fetch(`/api/equipment/${equipmentId}/future-reservations`);
            const data = await response.json();

            if (data.success && data.reservations && data.reservations.length > 0) {
                this.showFutureReservationWarning(data.reservations);
            } else {
                this.hideFutureReservationWarning();
            }
        } catch (error) {
            console.error('将来予約チェックエラー:', error);
            this.hideFutureReservationWarning();
        }
    }

    /**
     * 将来予約警告表示
     */
    showFutureReservationWarning(reservations) {
        let reservationsHtml = '<ul class="text-xs space-y-1">';
        reservations.forEach(reservation => {
            reservationsHtml += `
                <li class="flex justify-between">
                    <span>${reservation.phase_name} (${reservation.performance_title || '公演名不明'})</span>
                    <span class="font-mono">${reservation.start_date} ～ ${reservation.end_date}</span>
                </li>
            `;
        });
        reservationsHtml += '</ul>';

        this.reservationsList.innerHTML = reservationsHtml;
        this.futureReservationsWarning.classList.remove('hidden');
    }

    /**
     * 将来予約警告非表示
     */
    hideFutureReservationWarning() {
        if (this.futureReservationsWarning) {
            this.futureReservationsWarning.classList.add('hidden');
        }
    }
}

// ページ読み込み完了後に初期化
document.addEventListener('DOMContentLoaded', function() {
    new RepairRecordForm();
});