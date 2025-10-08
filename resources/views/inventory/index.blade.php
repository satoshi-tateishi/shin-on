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
    {{-- 在庫管理画面: is_inventory_visible=1の倉庫のみを対象にした機材在庫表示 --}}
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- 基準日選択 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">基準日</label>
                            <input
                                type="date"
                                id="asOfDate"
                                x-model="asOfDate"
                                @change="loadInventoryData()"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm"
                            >
                        </div>

                        <!-- 倉庫フィルタ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">倉庫</label>
                            <select x-model="filters.location_id" @change="filters.category_id = ''; loadInventoryData()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                                <template x-for="location in locations" :key="location.id">
                                    <option :value="location.id" :selected="location.id == 92" x-text="location.name"></option>
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
                <div class="flex space-x-3">
                    <!-- 選択中の倉庫のPDF出力 -->
                    <a :href="`/inventory/export-pdf?as_of_date=${asOfDate}&location_id=${filters.location_id}`"
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        この倉庫をPDF出力
                    </a>

                    <!-- 全倉庫のPDF出力 -->
                    <a :href="`/inventory/export-pdf?as_of_date=${asOfDate}&all_locations=1`"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        全倉庫をPDF出力
                    </a>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                カテゴリ
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">
                                機材名
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                在庫数量
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                新音番号
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in inventoryData" :key="item.equipment_id">
                            <tr>
                                <!-- カテゴリ（縦並び） -->
                                <td class="px-4 py-4">
                                    <div class="text-xs text-gray-500" x-text="item.equipment?.subcategory?.category?.name || '未設定'"></div>
                                    <div class="text-xs text-gray-500" x-text="item.equipment?.subcategory?.name || '未設定'"></div>
                                </td>

                                <!-- 機材名 -->
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="item.equipment?.name || '機材名不明'"></div>
                                </td>

                                <!-- 在庫数量 -->
                                <td class="px-4 py-4 text-center">
                                    <div class="text-lg font-bold text-gray-900">
                                        <span x-text="item.quantity || 0"></span>
                                    </div>
                                </td>

                                <!-- 新音番号 -->
                                <td class="px-4 py-4">
                                    <div class="text-sm text-gray-900 break-words leading-relaxed" x-text="(item.quantity > 0) ? (item.equipment?.company_number || '-') : '-'"></div>
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


    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function inventoryDashboard() {
            return {
                loading: false,
                error: null,
                asOfDate: new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0'),
                inventoryData: [],
                categories: [],
                locations: [],
                filters: {
                    category_id: '',
                    location_id: 92, // デフォルトはすみだ倉庫
                    search: ''
                },

                async init() {
                    try {
                        await this.loadMasterData();
                        await this.loadInventoryData();
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
                }
            }
        }
    </script>
@endpush
