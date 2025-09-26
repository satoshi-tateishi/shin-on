@extends('layouts.master')

@section('title', '倉庫別 在庫表示')

@section('breadcrumb')
    > <span class="text-gray-400">倉庫別 在庫表示</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">倉庫別 在庫表示</h1>
    </div>
@endsection

@push('styles')
    <style>
        .inventory-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@section('content')
    <div x-data="inventoryDashboard()" x-init="init()" class="p-6">

        <!-- Loading Spinner -->
        <div x-show="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-cyan-100">
                        <svg class="animate-spin h-6 w-6 text-cyan-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">データを読み込み中...</h3>
                    <p class="text-sm text-gray-500 mt-1">在庫データを計算しています</p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-transition class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">エラーが発生しました</h3>
                        <p class="mt-1 text-sm text-red-700" x-text="error"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div x-show="!loading && !error">


            <!-- Filters and Controls -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- 再計算ボタン -->
                        <div class="flex items-end">
                            <button
                                @click="generateSnapshot()"
                                :disabled="loading"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-cyan-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                再計算
                            </button>
                        </div>

                        <!-- 基準日選択 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">基準日</label>
                            <input
                                type="date"
                                id="asOfDate"
                                x-model="asOfDate"
                                @change="loadInventoryData()"
                                @keydown.prevent
                                :min="new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0')"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
                            >
                        </div>

                        <!-- 倉庫フィルタ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">倉庫</label>
                            <select x-model="filters.location_id" @change="filters.category_id = ''; loadInventoryData()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                                <template x-for="location in locations" :key="location.id">
                                    <option :value="location.id" :selected="location.id == 90" x-text="location.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- カテゴリフィルタ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">カテゴリ</label>
                            <select x-model="filters.category_id" @change="loadInventoryData()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                                <option value="">全てのカテゴリ</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 検索 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">機材名検索</label>
                            <input
                                type="text"
                                x-model="filters.search"
                                @input.debounce.500ms="loadInventoryData()"
                                placeholder="機材名を入力..."
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="flex justify-between items-center mb-6">
                <div class="text-sm text-gray-500">
                    表示件数: <span x-text="inventoryData.length"></span>件
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                カテゴリ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">
                                機材名
                            </th>
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20 border-l border-gray-300">
                                在庫数
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-l border-gray-300" style="width: 50%;">
                                新音番号
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in inventoryData" :key="item.id">
                            <tr class="hover:bg-gray-50 cursor-pointer" @click="viewEquipmentUsage(item.equipment_id)">
                                <!-- カテゴリ -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-400" x-text="item.equipment?.subcategory?.category?.name || '未設定'"></div>
                                    <div class="text-xs text-gray-400" x-text="item.equipment?.subcategory?.name || '未設定'"></div>
                                </td>

                                <!-- 機材名 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="item.equipment?.name || '機材名不明'"></div>
                                </td>

                                <!-- 在庫数 -->
                                <td class="px-2 py-4 whitespace-nowrap text-center border-l border-gray-200">
                                    <div class="text-sm font-medium text-gray-900">
                                        <span x-text="item.quantity || 0"></span>
                                    </div>
                                </td>

                                <!-- 新音番号 -->
                                <td class="px-6 py-4 border-l border-gray-200">
                                    <div class="text-sm text-gray-900 break-words" x-text="item.equipment?.company_number || '-'"></div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State Row -->
                        <tr x-show="inventoryData.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">在庫データがありません</h3>
                                <p class="mt-1 text-sm text-gray-500">検索条件を変更するか、データを再計算してください。</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 使用状況詳細モーダル -->
        <div x-show="showUsageModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showUsageModal = false">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" @click.stop>
                <div class="mt-3">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between pb-3 border-b">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">機材使用状況</h3>
                        <button @click="showUsageModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 機材情報 -->
                    <div class="py-4" x-show="selectedEquipment">
                        <h4 class="text-md font-semibold text-gray-800 mb-2">機材情報</h4>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm"><span class="font-medium">機材名:</span> <span x-text="selectedEquipment?.name"></span></p>
                            <p class="text-sm"><span class="font-medium">カテゴリ:</span> <span x-text="selectedEquipment?.subcategory"></span></p>
                            <p class="text-sm"><span class="font-medium">保管場所:</span> <span x-text="selectedEquipment?.location"></span></p>
                        </div>
                    </div>

                    <!-- 使用状況一覧 -->
                    <div class="py-4">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">
                            使用状況 <span class="text-sm font-normal text-gray-500">（基準日: <span x-text="formatDate(asOfDate)"></span>）</span>
                        </h4>

                        <div x-show="usageDetails.length === 0" class="text-center py-6 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p>修理中・使用中の機材はありません</p>
                            <p class="text-sm text-gray-400 mt-1">利用可能な機材は表の在庫数に表示されています</p>
                        </div>


                        <div class="space-y-3">
                            <template x-for="usage in usageDetails" :key="usage.company_number + usage.type + usage.status">
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-mono text-gray-600 bg-gray-50"
                                                      x-text="usage.company_number">
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-red-100 text-red-800': usage.type === 'repair',
                                                          'bg-blue-100 text-blue-800': usage.type === 'phase',
                                                          'bg-green-100 text-green-800': usage.type === 'available'
                                                      }"
                                                      x-text="usage.status">
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-900" x-text="usage.details"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- フッター -->
                    <div class="flex justify-end pt-4 border-t">
                        <button @click="showUsageModal = false" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function inventoryDashboard() {
            return {
                loading: false,
                error: null,
                asOfDate: new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0'),
                inventoryData: [],
                stats: {},
                meta: {},
                categories: [],
                locations: [],
                filters: {
                    category_id: '',
                    location_id: 90, // デフォルトをすみだ倉庫に設定（数値型）
                    search: ''
                },
                showUsageModal: false,
                selectedEquipment: null,
                usageDetails: [],

                async init() {
                    try {
                        await this.loadMasterData();
                        await this.loadInventoryData();
                        await this.loadInventoryStats();
                    } catch (error) {
                        console.error('Initialization error:', error);
                        this.error = 'システムの初期化中にエラーが発生しました: ' + error.message;
                    }
                },

                async loadMasterData() {
                    try {
                        // カテゴリ一覧取得
                        const categoryResponse = await fetch('/api/schedule/categories');
                        if (categoryResponse.ok) {
                            const categoryData = await categoryResponse.json();
                            this.categories = categoryData.categories || [];
                        }

                        // 倉庫一覧取得（倉庫のみに限定）
                        this.locations = @json($locationStats ?? []);
                    } catch (error) {
                        console.error('Master data loading error:', error);
                    }
                },

                async loadInventoryData() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const params = new URLSearchParams({
                            as_of_date: this.asOfDate,
                            ...Object.fromEntries(
                                Object.entries(this.filters).filter(([key, value]) => value !== '')
                            )
                        });

                        console.log('🔍 API Request URL:', `/inventory/api/inventory?${params}`);
                        console.log('🔍 Request params:', params.toString());
                        const response = await fetch(`/inventory/api/inventory?${params}`);
                        console.log('🔍 Response status:', response.status);
                        const data = await response.json();
                        console.log('🔍 Response data:', data);

                        if (data.success) {
                            this.inventoryData = data.data || [];
                            console.log('✅ Data assigned to inventoryData:', this.inventoryData);
                            if (this.inventoryData.length > 0) {
                                console.log('✅ First item detailed:', this.inventoryData[0]);
                                console.log('✅ Equipment object:', this.inventoryData[0].equipment);
                            }
                        } else {
                            throw new Error(data.error || '在庫データの取得に失敗しました');
                        }
                    } catch (error) {
                        console.error('Error loading inventory data:', error);
                        this.error = error.message;
                        this.inventoryData = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async loadInventoryStats() {
                    try {
                        const response = await fetch(`/inventory/api/inventory/stats?as_of_date=${this.asOfDate}`);
                        const data = await response.json();

                        if (data.success) {
                            this.stats = data.stats || {};
                        }
                    } catch (error) {
                        console.error('Stats loading error:', error);
                    }
                },

                async generateSnapshot() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch('/inventory/api/inventory/generate-snapshot', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                snapshot_date: this.asOfDate
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            await this.loadInventoryData();
                            await this.loadInventoryStats();
                        } else {
                            throw new Error(data.error || 'データの再計算に失敗しました');
                        }
                    } catch (error) {
                        this.error = error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async viewEquipmentUsage(equipmentId) {
                    try {
                        this.loading = true;
                        this.error = null;

                        const response = await fetch(`/test-api/equipment/${equipmentId}/usage?as_of_date=${this.asOfDate}`);

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.selectedEquipment = data.data.equipment;
                            this.usageDetails = data.data.usage_info;
                            this.showUsageModal = true;
                        } else {
                            throw new Error(data.error || '使用状況の取得に失敗しました');
                        }
                    } catch (error) {
                        console.error('Error loading equipment usage:', error);
                        this.error = error.message || 'ネットワークエラーが発生しました';
                    } finally {
                        this.loading = false;
                    }
                },

                viewEquipmentDetail(equipmentId) {
                    // 機材詳細モーダルまたはページに遷移
                    window.open(`/master/equipments/${equipmentId}`, '_blank');
                },


                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('ja-JP', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'short'
                    });
                },

            }
        }
    </script>
@endpush
