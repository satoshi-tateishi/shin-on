@extends('layouts.master')

@section('title', '返却先選択')

@section('breadcrumb')
    > <span class="text-gray-800">返却先選択</span>
@endsection

@push('head')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('header')
    <div class="w-full">
        <p id="performanceTitle" class="text-base sm:text-lg text-gray-600"></p>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1">
            <span id="phaseName"></span> 使用機材 返却先選択
        </h1>
        <p id="phasePeriod" class="text-xs sm:text-sm text-gray-500 mb-2"></p>
        <div class="flex flex-wrap items-center gap-2">
            <a id="backButton" href="/phases"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                戻る
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6" x-data="returnSelectManager()">
        <!-- フィルター（セッションストレージから来た場合は非表示） -->
        <div x-show="!returnEquipmentData" class="mb-4 sm:mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                <!-- カテゴリフィルタ -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
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
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">機材名検索</label>
                    <input type="text" x-model="filters.search" @input.debounce.500ms="loadEquipmentData()"
                           placeholder="機材名を入力..."
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>

                <!-- 基本倉庫フィルタ -->
                <div class="sm:col-span-2 md:col-span-1">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">基本倉庫ID</label>
                    <select x-model="filters.location_id" @change="loadEquipmentData()"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">すべて</option>
                        <option value="90">90 - 墨田倉庫1</option>
                        <option value="91">91 - 墨田倉庫2</option>
                        <option value="92">92 - 墨田倉庫3</option>
                    </select>
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
            <div class="px-3 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                    返却先選択対象機材
                    <span class="text-xs sm:text-sm font-normal text-gray-600">
                        (<span x-text="equipmentData.length"></span>件)
                    </span>
                </h2>
            </div>

            <!-- テーブル -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材名
                            </th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                基本倉庫
                            </th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                返却先
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="equipment in equipmentData" :key="equipment.id">
                            <tr class="hover:bg-gray-50"
                                :class="getReturnDestination(equipment.id) ? 'bg-blue-50 border-l-4 border-blue-400' : ''">
                                <!-- 機材名 -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4">
                                    <div class="space-y-0.5 sm:space-y-1">
                                        <div class="text-xs text-gray-500 hidden sm:block">
                                            <span x-text="equipment.subcategory?.category?.name || 'カテゴリなし'"></span> >
                                            <span x-text="equipment.subcategory?.name || 'サブカテゴリなし'"></span>
                                        </div>
                                        <div x-show="equipment.manufacturer" class="text-xs text-gray-400 hidden sm:block" x-text="equipment.manufacturer"></div>
                                        <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900" x-text="equipment.name"></div>
                                            <div x-show="equipment.company_number" class="border border-gray-300 px-1 sm:px-2 py-0.5 sm:py-1 rounded text-xs text-gray-700 bg-gray-50" x-text="equipment.company_number"></div>
                                        </div>
                                        <!-- モバイル用基本倉庫表示 -->
                                        <div class="text-xs text-gray-500 sm:hidden">
                                            基本倉庫: <span x-text="equipment.location?.name || 'ID:' + equipment.location_id"></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 基本倉庫 -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4 whitespace-nowrap hidden sm:table-cell">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900" x-text="equipment.location?.name || '不明'"></div>
                                </td>

                                <!-- 返却先倉庫選択 -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4 whitespace-nowrap">
                                    <select :value="getReturnDestination(equipment.id)"
                                            @change="updateReturnDestination(equipment.id, $event.target.value)"
                                            class="w-full border border-gray-300 rounded-md px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm">
                                        <!-- デフォルト選択肢：基本倉庫と同じ値 -->
                                        <option :value="equipment.location_id"
                                                :selected="!getReturnDestination(equipment.id)"
                                                x-text="`${equipment.location?.name || 'ID:' + equipment.location_id} (基本)`">
                                        </option>

                                        <!-- その他の倉庫選択肢 -->
                                        <template x-for="location in getOtherWarehouses(equipment)" :key="location.id">
                                            <option :value="location.id" x-text="location.name"></option>
                                        </template>
                                    </select>
                                </td>
                            </tr>
                        </template>

                        <!-- データなし -->
                        <tr x-show="equipmentData.length === 0">
                            <td colspan="3" class="px-4 sm:px-6 py-8 sm:py-12 text-center">
                                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                </svg>
                                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900">対象機材がありません</h3>
                                <p class="mt-1 text-xs sm:text-sm text-gray-500">基本倉庫ID 92-94の機材が見つかりません。</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 一括返却確認モーダル -->
        <div x-show="showBulkModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showBulkModal = false">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-4 sm:p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-2 sm:mt-3">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between pb-2 sm:pb-3 border-b">
                        <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900">一括返却の確認</h3>
                        <button @click="showBulkModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 返却予定一覧 -->
                    <div class="py-3 sm:py-4">
                        <p class="text-xs sm:text-sm text-gray-600 mb-3 sm:mb-4">以下の機材を指定した返却先へ移動します。よろしいですか？</p>

                        <div class="max-h-48 sm:max-h-64 overflow-y-auto border border-gray-200 rounded-md">
                            <table class="min-w-full">
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="returnData in pendingReturns" :key="returnData.equipmentId">
                                        <tr>
                                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <span x-text="returnData.equipmentName"></span>
                                                    <span x-show="returnData.companyNumber !== '-'"
                                                          class="border border-gray-300 px-1 sm:px-2 py-0.5 sm:py-1 rounded text-xs text-gray-700 bg-gray-50"
                                                          x-text="returnData.companyNumber"></span>
                                                </div>
                                            </td>
                                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-700">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <span class="text-blue-500 font-bold">返却先:</span>
                                                    <span x-text="returnData.returnLocationName"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- フッター -->
                    <div class="flex space-x-2 sm:space-x-3 pt-3 sm:pt-4 border-t">
                        <button @click="showBulkModal = false"
                                class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button @click="executeBulkReturn()" :disabled="isBulkReturning"
                                class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <span x-show="!isBulkReturning">実行</span>
                            <span x-show="isBulkReturning">処理中...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- フローティングボタン（一括返却用） -->
<button id="floatingReturnButton"
        class="fixed bottom-3 sm:bottom-4 right-3 sm:right-4 px-3 sm:px-4 h-10 sm:h-14 bg-gray-400 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center z-50 opacity-70"
        onclick="if(window.returnSelectData && window.returnSelectData.openBulkReturnModal) { window.returnSelectData.openBulkReturnModal(); } else { alert('返却データが選択されていません'); }">
    <span class="text-xs sm:text-sm">一括返却</span>
    <!-- バッジ -->
    <span id="returnBadge"
          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full border-2 border-white min-w-5 sm:min-w-6 h-5 sm:h-6 flex items-center justify-center"
          style="display: none;">0</span>
</button>

@push('scripts')
<script>
// 戻るボタンのURL設定とヘッダー情報の表示
document.addEventListener('DOMContentLoaded', async function() {
    const backButton = document.getElementById('backButton');
    const performanceTitleEl = document.getElementById('performanceTitle');
    const phaseNameEl = document.getElementById('phaseName');
    const phasePeriodEl = document.getElementById('phasePeriod');

    const returnEquipmentData = sessionStorage.getItem('returnEquipmentData');
    const bulkReturnData = sessionStorage.getItem('bulkReturnData');

    let phaseId = null;
    if (returnEquipmentData) {
        const data = JSON.parse(returnEquipmentData);
        phaseId = data.phaseId;
    } else if (bulkReturnData) {
        const data = JSON.parse(bulkReturnData);
        if (data.length > 0) {
            phaseId = data[0].phaseId;
        }
    }

    if (phaseId) {
        if (backButton) {
            backButton.href = `/phases/${phaseId}/equipment`;
        }

        // フェーズ情報を取得して表示
        try {
            const response = await fetch(`/equipment-transfer/api/phase/${phaseId}`);
            if (response.ok) {
                const data = await response.json();
                if (data.phase) {
                    performanceTitleEl.textContent = data.phase.performance?.title || '';
                    phaseNameEl.textContent = data.phase.name || '';
                    if (data.phase.start_date && data.phase.end_date) {
                        let periodText = `${data.phase.start_date} ～ ${data.phase.end_date}`;
                        if (data.phase.location?.name) {
                            periodText += ` @ ${data.phase.location.name}`;
                        }
                        phasePeriodEl.textContent = periodText;
                    }
                }
            }
        } catch (error) {
            console.error('Failed to fetch phase info:', error);
        }
    }
});

// API設定
const API_CONFIG = {
    warehouses: '/inventory/api/warehouses',
    categories: '/equipment-transfer/api/categories',
    equipment: '/equipment-transfer/api/equipment-for-return', // 新しいAPI
    bulkReturn: '/equipment-transfer/api/bulk-return' // 新しいAPI
};

// バッジ更新用の関数
function updateFloatingReturnButton() {
    const button = document.getElementById('floatingReturnButton');
    const badge = document.getElementById('returnBadge');

    if (window.returnSelectData) {
        const pendingCount = window.returnSelectData.pendingReturnsCount || 0;
        const hasPending = pendingCount > 0;

        // ボタンの状態更新
        if (hasPending) {
            button.className = 'fixed bottom-4 right-4 w-30 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center z-50';
        } else {
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
setInterval(updateFloatingReturnButton, 500);

function returnSelectManager() {
    return {
        // データ
        equipmentData: [],
        locations: [],
        categories: [],

        // UI状態
        loading: false,
        error: null,
        showBulkModal: false,
        isBulkReturning: false,
        initialized: false,

        // 返却データ
        returnData: {},

        // 一括返却用データ
        isBulkReturn: false,
        bulkReturnData: null,

        // フィルター
        filters: {
            location_id: '', // 92-94の範囲
            category_id: '',
            search: ''
        },

        // 初期化
        async init() {
            // グローバル参照を設定
            window.returnSelectData = this;

            try {
                // セッションストレージから機材情報を取得
                const returnEquipmentData = sessionStorage.getItem('returnEquipmentData');
                const bulkReturnData = sessionStorage.getItem('bulkReturnData');

                if (!returnEquipmentData && !bulkReturnData) {
                    this.error = '返却対象の機材情報が見つかりません。機材詳細画面から返却処理を開始してください。';
                    this.initialized = true;
                    return;
                }

                if (bulkReturnData) {
                    // 一括返却の場合
                    this.bulkReturnData = JSON.parse(bulkReturnData);
                    this.isBulkReturn = true;
                    await this.loadMasterData();
                    await this.loadBulkEquipmentData();
                } else {
                    // 個別返却の場合
                    this.returnEquipmentData = JSON.parse(returnEquipmentData);
                    this.isBulkReturn = false;
                    await this.loadMasterData();
                    await this.loadSpecificEquipmentData();
                }

                this.initialized = true;
            } catch (error) {
                console.error('Initialization failed:', error);
                this.error = error.message;
                this.initialized = true;
            }
        },

        // 計算プロパティ
        get pendingReturnsCount() {
            return Object.keys(this.returnData).filter(equipmentId =>
                this.returnData[equipmentId] && this.returnData[equipmentId].returnLocationId
            ).length;
        },

        get pendingReturns() {
            return Object.entries(this.returnData)
                .filter(([equipmentId, data]) => data && data.returnLocationId)
                .map(([equipmentId, data]) => {
                    const equipment = this.equipmentData.find(eq => eq.id == equipmentId);
                    const returnLocation = this.locations.find(loc => loc.id == data.returnLocationId);

                    return {
                        equipmentId: equipmentId,
                        equipmentName: equipment.name,
                        companyNumber: equipment.company_number || '-',
                        returnLocationName: returnLocation?.name || '不明'
                    };
                });
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
                // location_id 92-94の条件を追加
                const params = new URLSearchParams({
                    ...Object.fromEntries(
                        Object.entries(this.filters).filter(([key, value]) => value !== '')
                    ),
                    location_ids: '92,93,94' // 強制的に92-94のみ
                });

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

        // 特定機材の情報を読み込み（セッションストレージの機材のみ）
        async loadSpecificEquipmentData() {
            this.loading = true;
            this.error = null;

            try {
                if (!this.returnEquipmentData || !this.returnEquipmentData.equipmentId) {
                    throw new Error('機材情報が不正です');
                }

                const equipmentId = this.returnEquipmentData.equipmentId;

                // 特定の機材のみ取得
                const response = await fetch(`${API_CONFIG.equipment}?equipment_id=${equipmentId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    this.equipmentData = data.data;

                    // デフォルトの返却先を設定（基本倉庫と同じ）
                    const equipment = this.equipmentData[0];
                    if (equipment) {
                        this.returnData[equipment.id] = {
                            returnLocationId: equipment.location_id
                        };
                    }
                } else {
                    throw new Error('対象機材が見つかりません');
                }
            } catch (error) {
                console.error('Error loading specific equipment data:', error);
                this.error = error.message;
                this.equipmentData = [];
            } finally {
                this.loading = false;
            }
        },

        // 一括返却データの読み込み
        async loadBulkEquipmentData() {
            this.loading = true;
            this.error = null;

            try {
                if (!this.bulkReturnData || !Array.isArray(this.bulkReturnData)) {
                    throw new Error('一括返却データが不正です');
                }

                // 92-94の機材のみ抽出
                const filteredEquipments = this.bulkReturnData.filter(item =>
                    item.locationId >= 92 && item.locationId <= 94
                );

                // 機材データとして設定
                this.equipmentData = filteredEquipments.map(item => ({
                    id: item.equipmentId,
                    name: item.equipmentName,
                    company_number: item.companyNumber,
                    location_id: item.locationId,
                    management_type: 'individual',
                    location: {
                        id: item.locationId,
                        name: `ID: ${item.locationId}`
                    },
                    subcategory: {
                        category: {
                            name: 'カテゴリ不明'
                        },
                        name: 'サブカテゴリ不明'
                    }
                }));

                // デフォルトの返却先を設定（各機材の基本倉庫と同じ）
                filteredEquipments.forEach(item => {
                    this.returnData[item.equipmentId] = {
                        returnLocationId: item.locationId,
                        phaseEquipmentId: item.phaseEquipmentId
                    };
                });

            } catch (error) {
                console.error('Error loading bulk equipment data:', error);
                this.error = error.message;
                this.equipmentData = [];
            } finally {
                this.loading = false;
            }
        },

        // ヘルパーメソッド
        getOtherWarehouses(equipment) {
            return this.locations.filter(location =>
                location.id !== equipment.location_id
            );
        },

        // 返却先管理
        updateReturnDestination(equipmentId, returnLocationId) {
            if (!this.returnData[equipmentId]) {
                this.returnData[equipmentId] = {};
            }
            this.returnData[equipmentId].returnLocationId = returnLocationId || '';
        },

        getReturnDestination(equipmentId) {
            return this.returnData[equipmentId]?.returnLocationId || '';
        },

        // モーダル管理
        openBulkReturnModal() {
            if (this.pendingReturnsCount === 0) return;
            this.showBulkModal = true;
        },

        // 一括返却実行
        async executeBulkReturn() {
            if (this.pendingReturnsCount === 0) return;

            this.isBulkReturning = true;

            try {
                const returns = Object.entries(this.returnData)
                    .filter(([equipmentId, data]) => data && data.returnLocationId)
                    .map(([equipmentId, data]) => ({
                        equipment_id: parseInt(equipmentId),
                        return_location_id: parseInt(data.returnLocationId),
                        phase_equipment_id: data.phaseEquipmentId || this.returnEquipmentData?.phaseEquipmentId || null
                    }));

                const response = await fetch(API_CONFIG.bulkReturn, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        returns: returns
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`${returns.length}件の機材返却が完了しました`);
                    this.returnData = {};
                    this.showBulkModal = false;

                    // セッションストレージをクリア
                    sessionStorage.removeItem('returnEquipmentData');
                    sessionStorage.removeItem('bulkReturnData');

                    // 適切な画面にリダイレクト
                    const phaseId = this.isBulkReturn
                        ? this.bulkReturnData?.[0]?.phaseId
                        : this.returnEquipmentData?.phaseId;

                    if (phaseId) {
                        // フェーズ機材管理画面に戻る
                        window.location.href = `/phases/${phaseId}/equipment`;
                    } else {
                        // ダッシュボードに戻る
                        window.location.href = '/dashboard';
                    }
                } else {
                    throw new Error(data.error || '一括返却に失敗しました');
                }
            } catch (error) {
                console.error('Bulk return error:', error);
                alert('一括返却に失敗しました: ' + error.message);
            } finally {
                this.isBulkReturning = false;
            }
        }
    };
}
</script>
@endpush
@endsection