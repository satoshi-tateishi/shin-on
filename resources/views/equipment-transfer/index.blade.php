@extends('layouts.master')

@section('title', '倉庫間移動')

@section('breadcrumb')
    > <span class="text-gray-800">倉庫間移動</span>
@endsection

@push('head')
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">倉庫間移動</h1>
    </div>
@endsection

@section('content')
    <div class="p-6" x-data="transferManager()">
        <!-- フィルター -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- 現在地フィルタ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">現在の保管場所</label>
                    <select x-model="filters.location_id" @change="onLocationChange($event)"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <template x-for="location in locations" :key="location.id">
                            <option :value="location.id" x-text="location.name"></option>
                        </template>
                    </select>
                </div>

                <!-- カテゴリフィルタ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                    <select x-model="filters.category_id" @change="loadEquipmentData()"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">すべてのカテゴリ</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <!-- 検索 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">機材名検索</label>
                    <input type="text" x-model="filters.search" @input.debounce.500ms="loadEquipmentData()"
                           placeholder="機材名を入力..."
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>

            </div>
        </div>

        <!-- ローディング -->
        <div x-show="loading" class="bg-white shadow-sm rounded-lg p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">データを読み込み中...</p>
        </div>

        <!-- エラー表示 -->
        <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
                    <div class="mt-2 text-sm text-red-700" x-text="error"></div>
                </div>
            </div>
        </div>

        <!-- 機材一覧テーブル -->
        <div x-show="!loading && !error" class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        移動可能機材一覧
                        <span class="text-sm font-normal text-gray-600">
                            (<span x-text="equipmentData.length"></span>件表示)
                        </span>
                    </h2>
                </div>
            </div>

            <!-- テーブル -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材名
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                基本倉庫
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                現在地
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                移動先選択
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="equipment in equipmentData" :key="equipment.id">
                            <tr class="hover:bg-gray-50"
                                :class="getTransferDestination(equipment.id) ? 'bg-yellow-50 border-l-4 border-yellow-400' : ''">
                                <!-- 機材名 -->
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-xs text-gray-500">
                                            <span x-text="equipment.subcategory.category.name"></span> > <span x-text="equipment.subcategory.name"></span>
                                        </div>
                                        <div x-show="equipment.manufacturer" class="text-xs text-gray-400" x-text="equipment.manufacturer"></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-sm font-medium text-gray-900" x-text="equipment.name"></div>
                                            <div x-show="equipment.company_number" class="border border-gray-300 px-2 py-1 rounded text-xs text-gray-700 bg-gray-50" x-text="equipment.company_number"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 基本倉庫 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="equipment.location.name"></div>
                                </td>

                                <!-- 現在地 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="locations.find(loc => loc.id === equipment.now_location_id)?.name || '不明'"></div>
                                </td>

                                <!-- 移動先選択 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select :value="getTransferDestination(equipment.id)"
                                            @change="updateTransferDestination(equipment.id, $event.target.value)"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                        <option value="">移動先を選択...</option>
                                        <template x-for="location in getAvailableDestinations(equipment)" :key="location.id">
                                            <option :value="location.id" x-text="location.name"></option>
                                        </template>
                                    </select>
                                </td>
                            </tr>
                        </template>

                        <!-- データなし -->
                        <tr x-show="equipmentData.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">移動可能な機材がありません</h3>
                                <p class="mt-1 text-sm text-gray-500">検索条件を変更してください。</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- 一括移動確認モーダル -->
        <div x-show="showBulkModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showBulkModal = false">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between pb-3 border-b">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">倉庫間移動の確認</h3>
                        <button @click="showBulkModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 移動予定一覧 -->
                    <div class="py-4">
                        <p class="text-sm text-gray-600 mb-4">以下の機材を移動します。よろしいですか？</p>

                        <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md">
                            <table class="min-w-full">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="transfer in pendingTransfers" :key="transfer.equipmentId">
                                        <tr>
                                            <td class="px-4 py-2 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <span x-text="transfer.equipmentName"></span>
                                                    <span x-show="transfer.companyNumber !== '-'"
                                                          class="border border-gray-300 px-2 py-1 rounded text-xs text-gray-700 bg-gray-50"
                                                          x-text="transfer.companyNumber"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <div class="flex items-center gap-2">
                                                    <span x-text="transfer.currentLocationName"></span>
                                                    <span class="text-blue-500 font-bold">→</span>
                                                    <span x-text="transfer.toLocationName"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- フッター -->
                    <div class="flex space-x-3 pt-4 border-t">
                        <button @click="showBulkModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button @click="executeBulkTransfer()" :disabled="isBulkTransferring"
                                class="flex-1 px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <span x-show="!isBulkTransferring">実行</span>
                            <span x-show="isBulkTransferring">処理中...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 移動先選択モーダル -->
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showModal = false">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between pb-3 border-b">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">移動先選択</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 機材情報 -->
                    <div class="py-4" x-show="selectedEquipment">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-medium">機材:</span> <span x-text="selectedEquipment?.name"></span></p>
                            <p class="text-sm"><span class="font-medium">新音番号:</span> <span x-text="selectedEquipment?.company_number || '-'"></span></p>
                            <p class="text-sm"><span class="font-medium">現在地:</span> <span x-text="selectedEquipment?.location?.display_name"></span></p>
                        </div>
                    </div>

                    <!-- 移動先選択 -->
                    <div class="py-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">移動先を選択してください</label>
                        <select x-model="selectedToLocation" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">移動先を選択...</option>
                            <template x-for="location in availableDestinations" :key="location.id">
                                <option :value="location.id" x-text="location.display_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 備考 -->
                    <div class="py-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">備考（任意）</label>
                        <textarea x-model="transferNote" rows="3"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                placeholder="移動理由や備考があれば入力してください"></textarea>
                    </div>

                    <!-- フッター -->
                    <div class="flex space-x-3 pt-4 border-t">
                        <button @click="showModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button @click="executeTransfer()" :disabled="!selectedToLocation"
                                class="flex-1 px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            移動実行
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- フローティングボタン（バッジ付き） - Tailwind CSS v4 -->
<button id="floatingButton"
        class="fixed bottom-4 right-4 w-30 h-14 bg-gray-400 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center z-50 opacity-70"
        onclick="if(window.transferManagerData && window.transferManagerData.openBulkTransferModal) { window.transferManagerData.openBulkTransferModal(); } else { alert('移動データが選択されていません'); }">
    移動実行
    <!-- バッジ -->
    <span id="transferBadge"
          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full border-2 border-white min-w-6 h-6 flex items-center justify-center"
          style="display: none;">0</span>
</button>

@push('scripts')
<script>
// API設定
const API_CONFIG = {
    warehouses: '/inventory/api/warehouses',
    categories: '/equipment-transfer/api/categories',
    equipment: '/equipment-transfer/api/equipment',
    transfer: '/equipment-transfer/api/transfer',
    bulkTransfer: '/equipment-transfer/api/bulk-transfer'
};

// バッジ更新用の関数（Tailwind CSS v4対応）
function updateFloatingButton() {
    const button = document.getElementById('floatingButton');
    const badge = document.getElementById('transferBadge');

    if (window.transferManagerData) {
        const pendingCount = window.transferManagerData.pendingTransferCount || 0;
        const hasPending = window.transferManagerData.hasPendingTransfers || false;

        // ボタンの状態更新
        if (hasPending) {
            // アクティブ状態: 青背景、透明度100%
            button.className = 'fixed bottom-4 right-4 w-30 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center z-50';
        } else {
            // 非アクティブ状態: グレー背景、透明度70%
            button.className = 'fixed bottom-4 right-4 w-30 h-14 bg-gray-400 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center z-50 opacity-70';
        }

        // バッジの表示・更新
        if (hasPending && pendingCount > 0) {
            badge.style.display = 'flex';
            badge.textContent = pendingCount;
        } else {
            badge.style.display = 'none';
        }
    }
}

// 定期的にバッジを更新
setInterval(updateFloatingButton, 500);

function transferManager() {
    return {
        // データ
        equipmentData: [],
        locations: [],
        categories: [],

        // UI状態
        loading: false,
        error: null,
        showModal: false,
        showBulkModal: false,
        isBulkTransferring: false,
        initialized: false,

        // モーダル用データ
        selectedEquipment: null,
        selectedToLocation: '',
        transferNote: '',

        // 一括移動用データ
        transferData: {},

        // フィルター
        filters: {
            location_id: '',
            category_id: '',
            search: ''
        },

        // 初期化
        async init() {
            // グローバル参照を設定
            window.transferManagerData = this;

            try {
                await this.loadMasterData();
                this.setDefaultLocation();
                await this.loadEquipmentData();
                this.initialized = true;
            } catch (error) {
                console.error('Initialization failed:', error);
                this.error = error.message;
                this.initialized = true;
            }
        },

        // 計算プロパティ
        get hasPendingTransfers() {
            return this.pendingTransferCount > 0;
        },

        get pendingTransferCount() {
            return Object.keys(this.transferData).filter(equipmentId =>
                this.transferData[equipmentId] && this.transferData[equipmentId].toLocationId
            ).length;
        },

        get pendingTransfers() {
            return Object.entries(this.transferData)
                .filter(([equipmentId, data]) => data && data.toLocationId)
                .map(([equipmentId, data]) => {
                    const equipment = this.equipmentData.find(eq => eq.id == equipmentId);
                    const toLocation = this.locations.find(loc => loc.id == data.toLocationId);
                    const currentLocation = this.locations.find(loc => loc.id === equipment.now_location_id);

                    return {
                        equipmentId: equipmentId,
                        equipmentName: equipment.name,
                        companyNumber: equipment.company_number || '-',
                        currentLocationName: currentLocation?.name || '不明',
                        toLocationName: toLocation?.name || '不明'
                    };
                });
        },

        get availableDestinations() {
            if (!this.selectedEquipment) return [];
            return this.locations.filter(location =>
                location.id !== this.selectedEquipment.location.id
            );
        },

        // データ読み込みメソッド
        async loadMasterData() {
            try {
                // 倉庫一覧取得
                const locationResponse = await fetch(API_CONFIG.warehouses);
                if (locationResponse.ok) {
                    const locationData = await locationResponse.json();
                    this.locations = locationData.warehouses || [];
                } else {
                    throw new Error('倉庫データの取得に失敗しました');
                }

                // カテゴリ一覧取得
                const categoryResponse = await fetch(API_CONFIG.categories);
                if (categoryResponse.ok) {
                    const categoryData = await categoryResponse.json();
                    this.categories = categoryData.categories || [];
                }
            } catch (error) {
                console.error('Master data loading error:', error);
                throw error;
            }
        },

        async loadEquipmentData() {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams(
                    Object.fromEntries(
                        Object.entries(this.filters).filter(([key, value]) => value !== '')
                    )
                );

                const response = await fetch(`${API_CONFIG.equipment}?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.equipmentData = data.data || [];
                } else {
                    throw new Error(data.error || 'データの取得に失敗しました');
                }
            } catch (error) {
                console.error('Error loading equipment data:', error);
                this.error = error.message;
                this.equipmentData = [];
            } finally {
                this.loading = false;
            }
        },

        // 初期設定メソッド
        setDefaultLocation() {
            const sumidaWarehouse = this.locations.find(loc => loc.id === 90);
            if (sumidaWarehouse) {
                this.filters.location_id = 90;
            } else if (this.locations.length > 0) {
                this.filters.location_id = this.locations[0].id;
            }
        },

        // イベントハンドラー
        onLocationChange(event) {
            const selectedValue = parseInt(event.target.value);
            const selectedLocation = this.locations.find(loc => loc.id === selectedValue);

            if (selectedLocation) {
                this.filters.location_id = selectedValue;
                this.loadEquipmentData();
            } else {
                this.setDefaultLocation();
            }
        },

        // モーダル管理
        openTransferModal(equipment) {
            this.selectedEquipment = equipment;
            this.selectedToLocation = '';
            this.transferNote = '';
            this.showModal = true;
        },

        openBulkTransferModal() {
            if (!this.hasPendingTransfers) return;
            this.showBulkModal = true;
        },

        // 一括移動用メソッド
        updateTransferDestination(equipmentId, toLocationId) {
            if (!this.transferData[equipmentId]) {
                this.transferData[equipmentId] = {};
            }
            this.transferData[equipmentId].toLocationId = toLocationId || '';
        },

        getTransferDestination(equipmentId) {
            return this.transferData[equipmentId]?.toLocationId || '';
        },

        getAvailableDestinations(equipment) {
            return this.locations.filter(location =>
                location.id !== equipment.now_location_id
            );
        },

        // 移動実行メソッド
        async executeTransfer() {
            if (!this.selectedEquipment || !this.selectedToLocation) return;

            try {
                const response = await fetch(API_CONFIG.transfer, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        equipment_id: this.selectedEquipment.id,
                        to_location_id: this.selectedToLocation,
                        note: this.transferNote || '倉庫間移動画面からの移動'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`${this.selectedEquipment.name}の移動が完了しました`);
                    this.showModal = false;
                    await this.loadEquipmentData();
                } else {
                    throw new Error(data.error || '倉庫間移動に失敗しました');
                }
            } catch (error) {
                console.error('Transfer error:', error);
                alert('移動に失敗しました: ' + error.message);
            }
        },

        async executeBulkTransfer() {
            if (!this.hasPendingTransfers) return;

            this.isBulkTransferring = true;

            try {
                const transfers = Object.entries(this.transferData)
                    .filter(([equipmentId, data]) => data && data.toLocationId)
                    .map(([equipmentId, data]) => ({
                        equipment_id: parseInt(equipmentId),
                        to_location_id: parseInt(data.toLocationId),
                        note: '一括倉庫間移動'
                    }));

                const response = await fetch(API_CONFIG.bulkTransfer, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        transfers: transfers
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`${transfers.length}件の機材移動が完了しました`);
                    this.transferData = {};
                    this.showBulkModal = false;
                    await this.loadEquipmentData();
                } else {
                    throw new Error(data.error || '一括移動に失敗しました');
                }
            } catch (error) {
                console.error('Bulk transfer error:', error);
                alert('一括移動に失敗しました: ' + error.message);
            } finally {
                this.isBulkTransferring = false;
            }
        }
    };
}
</script>
@endpush
@endsection
