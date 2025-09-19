/**
 * 機材マスタ - インデックスページのJavaScript
 */

class EquipmentIndex {
    constructor() {
        this.init();
    }

    init() {
        this.initCategorySubcategoryFilter();
        this.initDragAndDrop();
    }

    /**
     * カテゴリ連動のサブカテゴリフィルタリング
     */
    initCategorySubcategoryFilter() {
        const categorySelect = document.getElementById('category-select');
        const subcategorySelect = document.getElementById('subcategory-select');
        const locationSelect = document.getElementById('location-select');

        if (!categorySelect || !subcategorySelect || !locationSelect) {
            return;
        }

        const allSubcategoryOptions = Array.from(subcategorySelect.children);

        const filterSubcategories = () => {
            const selectedCategoryId = categorySelect.value;

            // 既存のオプションを削除（「すべて」は残す）
            while (subcategorySelect.children.length > 1) {
                subcategorySelect.removeChild(subcategorySelect.lastChild);
            }

            if (!selectedCategoryId) {
                // カテゴリが「すべて」の場合、全サブカテゴリを表示
                allSubcategoryOptions.slice(1).forEach(element => {
                    subcategorySelect.appendChild(element.cloneNode(true));
                });
            } else {
                // 選択されたカテゴリに属するサブカテゴリのみを表示
                allSubcategoryOptions.slice(1).forEach(element => {
                    if (element.tagName === 'OPTGROUP') {
                        const optgroup = element.cloneNode(false);
                        const options = Array.from(element.children);
                        let hasValidOptions = false;

                        options.forEach(option => {
                            if (option.dataset.categoryId === selectedCategoryId) {
                                optgroup.appendChild(option.cloneNode(true));
                                hasValidOptions = true;
                            }
                        });

                        if (hasValidOptions) {
                            subcategorySelect.appendChild(optgroup);
                        }
                    }
                });
            }
        };

        const resetCategoryFilters = () => {
            // カテゴリとサブカテゴリを「すべて」にリセット
            categorySelect.value = '';
            subcategorySelect.value = '';

            // サブカテゴリドロップダウンを全表示に戻す
            filterSubcategories();
        };

        // イベントリスナー
        categorySelect.addEventListener('change', filterSubcategories);

        // 場所ドロップダウンが変更されたときにカテゴリとサブカテゴリをリセット
        locationSelect.addEventListener('change', function() {
            if (this.value) { // 場所が選択された場合のみリセット
                resetCategoryFilters();
            }
        });
    }

    /**
     * ドラッグ&ドロップによるソート機能
     */
    initDragAndDrop() {
        const tbody = document.getElementById('sortable-tbody');

        if (!tbody) {
            return;
        }

        // ソートモード時のみドラッグ&ドロップを有効化
        const urlParams = new URLSearchParams(window.location.search);
        const sortMode = urlParams.get('sort_mode');

        if (sortMode !== 'all') {
            return; // 通常モード時はドラッグ&ドロップ無効
        }

        let draggedElement = null;
        const self = this; // スコープ保持のため

        // 各行にドラッグイベントリスナーを追加
        tbody.querySelectorAll('.sortable-row').forEach(row => {
            row.setAttribute('draggable', 'true');

            row.addEventListener('dragstart', function(e) {
                draggedElement = this;
                this.style.opacity = '0.5';
            });

            row.addEventListener('dragend', function(e) {
                this.style.opacity = '';
                draggedElement = null;
            });

            row.addEventListener('dragover', function(e) {
                e.preventDefault();
            });

            row.addEventListener('drop', function(e) {
                e.preventDefault();
                if (draggedElement && draggedElement !== this) {
                    const allRows = Array.from(tbody.querySelectorAll('.sortable-row'));
                    const draggedIndex = allRows.indexOf(draggedElement);
                    const targetIndex = allRows.indexOf(this);

                    if (draggedIndex < targetIndex) {
                        this.parentNode.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedElement, this);
                    }

                    self.updateSortOrder();
                }
            });

            // ドラッグハンドルのマウスイベント
            const dragHandle = row.querySelector('.drag-handle');
            if (dragHandle) {
                dragHandle.addEventListener('mouseenter', function() {
                    this.style.color = '#6b7280';
                });

                dragHandle.addEventListener('mouseleave', function() {
                    this.style.color = '#9ca3af';
                });
            }
        });
    }

    /**
     * ソート順をサーバーに送信
     */
    updateSortOrder() {
        const tbody = document.getElementById('sortable-tbody');
        const rows = Array.from(tbody.querySelectorAll('.sortable-row'));
        const items = rows.map((row, index) => ({
            id: parseInt(row.dataset.id),
            sort: index + 1
        }));

        fetch('/master/equipments/update-sort', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                items: items
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Sort order updated successfully');
            } else {
                alert('ソート順の更新に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('ソート順の更新中にエラーが発生しました。');
        });
    }
}

// DOMContentLoadedでインスタンス化
document.addEventListener('DOMContentLoaded', function() {
    new EquipmentIndex();
});