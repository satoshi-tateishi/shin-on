/**
 * Equipment management utilities
 */

/**
 * Open checkout modal for equipment
 * @param {number} phaseEquipmentId - ID of the phase equipment
 * @param {string} baseRoute - Base route for checkout action
 */
function openCheckoutModal(phaseEquipmentId, baseRoute = null) {
    const modal = document.getElementById('checkoutModal');
    const form = document.getElementById('checkoutForm');

    if (modal && form && baseRoute) {
        const actionUrl = baseRoute.replace('__ID__', phaseEquipmentId);
        form.action = actionUrl;
        modal.classList.remove('hidden');
    }
}

/**
 * Close checkout modal
 */
function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Open checkin modal for equipment
 * @param {number} phaseEquipmentId - ID of the phase equipment
 * @param {string} baseRoute - Base route for checkin action
 */
async function openCheckinModal(phaseEquipmentId, baseRoute = null) {
    const modal = document.getElementById('checkinModal');
    const form = document.getElementById('checkinForm');

    if (!modal || !form || !baseRoute) return;

    const actionUrl = baseRoute.replace('__ID__', phaseEquipmentId);
    form.action = actionUrl;

    // フォームをリセット
    form.reset();
    const checkinDate = document.getElementById('checkin_date');
    if (checkinDate) {
        checkinDate.value = new Date().toISOString().split('T')[0];
    }

    // 場所選択のリセット
    const toLocationId = document.getElementById('to_location_id');
    const selectedLocationText = document.getElementById('selectedLocationText');
    const locationError = document.getElementById('locationError');

    if (toLocationId) toLocationId.value = '';
    if (selectedLocationText) selectedLocationText.textContent = '返却先倉庫を選択してください...';
    if (locationError) locationError.classList.add('hidden');

    // 機材情報を取得してlocation_id=92-94かチェック
    try {
        const equipmentData = await getEquipmentData(phaseEquipmentId);
        const requiresLocationSelection = equipmentData &&
            (equipmentData.location_id >= 92 && equipmentData.location_id <= 94);

        const locationSelectDiv = document.getElementById('locationSelectDiv');
        if (locationSelectDiv) {
            if (requiresLocationSelection) {
                locationSelectDiv.classList.remove('hidden');
            } else {
                locationSelectDiv.classList.add('hidden');
            }
        }
    } catch (error) {
        console.error('機材情報の取得に失敗:', error);
        // エラーの場合は安全側に倒して場所選択を非表示
        const locationSelectDiv = document.getElementById('locationSelectDiv');
        if (locationSelectDiv) {
            locationSelectDiv.classList.add('hidden');
        }
    }

    modal.classList.remove('hidden');
}

/**
 * Close checkin modal
 */
function closeCheckinModal() {
    const modal = document.getElementById('checkinModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Get equipment data from table row
 * @param {number} phaseEquipmentId - ID of the phase equipment
 * @returns {Object|null} Equipment data
 */
async function getEquipmentData(phaseEquipmentId) {
    // 既存のテーブル行から機材情報を取得
    const equipmentRows = document.querySelectorAll('[data-equipment-location]');
    for (const row of equipmentRows) {
        const rowPhaseEquipmentId = row.getAttribute('data-phase-equipment-id');
        if (rowPhaseEquipmentId == phaseEquipmentId) {
            return {
                location_id: parseInt(row.getAttribute('data-equipment-location'))
            };
        }
    }
    return null;
}

/**
 * Handle equipment return with location check
 * @param {number} phaseEquipmentId - ID of the phase equipment
 * @param {number} phaseId - ID of the phase
 */
async function handleEquipmentReturn(phaseEquipmentId, phaseId) {
    try {
        // 機材情報をAPIから取得してlocation_idをチェック
        const response = await fetch(`/phases/${phaseId}/equipment/${phaseEquipmentId}/equipment-info`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('機材情報の取得に失敗しました');
        }

        const data = await response.json();
        const equipment = data.equipment;

        // location_id が 92-94 の場合は返却先選択画面へ遷移
        if (equipment.location_id >= 92 && equipment.location_id <= 94) {
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

        // 通常の返却処理（location_id が 92-94 以外）
        // 既存のモーダルを開く
        const baseRoute = `/phases/${phaseId}/equipment/__ID__/checkin`;
        openCheckinModal(phaseEquipmentId, baseRoute);

    } catch (error) {
        console.error('Equipment info fetch error:', error);
        alert('機材情報の取得中にエラーが発生しました: ' + error.message);
    }
}

/**
 * Handle bulk return of equipment
 * @param {number} phaseId - ID of the phase
 * @param {number} equipmentCount - Number of equipment to return
 */
async function handleBulkReturn(phaseId, equipmentCount) {
    if (!confirm(`出庫中の機材 ${equipmentCount}件を一括で返却済みに変更しますか？`)) {
        return;
    }

    try {
        // 出庫中のPhaseEquipmentと機材情報を取得
        const checkedOutEquipments = await getCheckedOutEquipments(phaseId);

        // location_id 92-94の機材と通常機材を分類
        const requiresLocationSelection = checkedOutEquipments.filter(item =>
            item.equipment.location_id >= 92 && item.equipment.location_id <= 94
        );
        const normalEquipments = checkedOutEquipments.filter(item =>
            item.equipment.location_id < 92 || item.equipment.location_id > 94
        );

        // まず通常機材（92-94以外）を自動返却
        if (normalEquipments.length > 0) {
            const normalEquipmentIds = normalEquipments.map(item => item.id);

            // 通常機材の自動返却API呼び出し
            const response = await fetch(`/phases/${phaseId}/equipment/bulk-checkin`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    equipment_ids: normalEquipmentIds
                })
            });

            if (!response.ok) {
                throw new Error('通常機材の返却処理に失敗しました');
            }
        }

        // 92-94の機材がある場合は返却先選択画面に遷移
        if (requiresLocationSelection.length > 0) {
            const bulkReturnData = requiresLocationSelection.map(item => ({
                phaseEquipmentId: item.id,
                phaseId: phaseId,
                equipmentId: item.equipment.id,
                equipmentName: item.equipment.name,
                companyNumber: item.equipment.company_number,
                locationId: item.equipment.location_id
            }));

            // 92-94機材のみを返却先選択画面に送る
            sessionStorage.setItem('bulkReturnData', JSON.stringify(bulkReturnData));

            // 返却先選択画面へ遷移
            window.location.href = '/equipment-transfer/return-select';
            return;
        }

        // 92-94機材がなく、通常機材のみの場合はページをリロード
        if (normalEquipments.length > 0 && requiresLocationSelection.length === 0) {
            window.location.reload();
            return;
        }

        // どちらもない場合（すべて返却済み）
        alert('返却対象の機材がありません。');

    } catch (error) {
        console.error('Bulk return error:', error);
        alert('一括返却処理中にエラーが発生しました: ' + error.message);
    }
}

/**
 * Get checked out equipments from API
 * @param {number} phaseId - ID of the phase
 * @returns {Array} Array of checked out equipments
 */
async function getCheckedOutEquipments(phaseId) {
    const url = `/phases/${phaseId}/equipment/checked-out-equipments`;

    const response = await fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error('出庫中機材の取得に失敗しました');
    }

    const data = await response.json();
    return data.equipments || [];
}

/**
 * Open location selector modal
 */
function openLocationSelector() {
    const modalEvent = new CustomEvent('open-checkin-location-modal', {
        detail: {
            onConfirm: (location) => {
                selectReturnLocation(location);
            }
        }
    });
    window.dispatchEvent(modalEvent);
}

/**
 * Select return location
 * @param {Object} location - Selected location object
 */
function selectReturnLocation(location) {
    if (location) {
        const toLocationId = document.getElementById('to_location_id');
        const selectedLocationText = document.getElementById('selectedLocationText');
        const locationError = document.getElementById('locationError');

        if (toLocationId) toLocationId.value = location.id;
        if (selectedLocationText) selectedLocationText.textContent = location.name;
        if (locationError) locationError.classList.add('hidden');
    }
}

/**
 * Initialize equipment management event listeners
 */
function initializeEquipmentManagement() {
    // フォーム送信時のバリデーション
    const checkinForm = document.getElementById('checkinForm');
    if (checkinForm) {
        checkinForm.addEventListener('submit', function(e) {
            const locationSelectDiv = document.getElementById('locationSelectDiv');
            const toLocationId = document.getElementById('to_location_id');
            const locationError = document.getElementById('locationError');

            // location_id=92-94の機材で返却先が選択されていない場合
            if (locationSelectDiv && !locationSelectDiv.classList.contains('hidden') &&
                toLocationId && !toLocationId.value) {
                e.preventDefault();
                if (locationError) {
                    locationError.classList.remove('hidden');
                }
                return false;
            }
        });
    }

    // モーダル外クリックで閉じる
    document.addEventListener('click', function(event) {
        const checkoutModal = document.getElementById('checkoutModal');
        const checkinModal = document.getElementById('checkinModal');

        if (event.target === checkoutModal) {
            closeCheckoutModal();
        }
        if (event.target === checkinModal) {
            closeCheckinModal();
        }
    });
}

// DOM loaded時に初期化
document.addEventListener('DOMContentLoaded', initializeEquipmentManagement);