@extends('layouts.master')

@section('title', '倉庫別 在庫表示')

@section('breadcrumb')
    > <span class="text-gray-400 dark:text-gray-500">倉庫別 在庫表示</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">倉庫別 在庫表示</h1>
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
    <div x-data="inventoryDashboard()" x-init="init()" class="p-3 sm:p-6">

        <!-- Loading Spinner -->
        <div x-show="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-3 sm:p-5 border border-gray-200 dark:border-gray-700 w-auto sm:w-96 max-w-sm shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-2 sm:mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-cyan-100 dark:bg-cyan-900/50">
                        <svg class="animate-spin h-5 w-5 sm:h-6 sm:w-6 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white mt-2">データを読み込み中...</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">在庫データを計算しています</p>
                </div>
            </div>
        </div>

        <!-- PDF Loading Spinner -->
        <div x-show="pdfLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-3 sm:p-5 border border-gray-200 dark:border-gray-700 w-auto sm:w-96 max-w-sm shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-2 sm:mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-red-100 dark:bg-red-900/50">
                        <svg class="animate-spin h-5 w-5 sm:h-6 sm:w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white mt-2">PDF生成中...</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="pdfMessage"></p>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-transition class="mb-4">
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-md p-3 sm:p-4">
                <div class="flex">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-red-400 dark:text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="ml-2 sm:ml-3">
                        <h3 class="text-xs sm:text-sm font-medium text-red-800 dark:text-red-300">エラーが発生しました</h3>
                        <p class="mt-1 text-xs sm:text-sm text-red-700 dark:text-red-400" x-text="error"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div x-show="!loading && !error" class="space-y-4 sm:space-y-6">

            <!-- Filters and Controls -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">検索条件</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        <!-- 基準日選択 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">基準日</label>
                            <input
                                type="date"
                                id="asOfDate"
                                x-model="asOfDate"
                                @change="loadInventoryData()"
                                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <!-- 倉庫フィルタ -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">倉庫</label>
                            <select x-model="filters.location_id" @change="filters.category_id = ''; loadInventoryData()" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <template x-for="location in locations" :key="location.id">
                                    <option :value="location.id" :selected="location.id == 92" x-text="location.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- カテゴリフィルタ -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
                            <select x-model="filters.category_id" @change="loadInventoryData()" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全て</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 検索 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">機材名</label>
                            <input
                                type="text"
                                x-model="filters.search"
                                @input.debounce.500ms="loadInventoryData()"
                                placeholder="検索..."
                                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary and PDF Buttons -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    表示件数: <span x-text="inventoryData.length" class="font-medium"></span>件
                </div>
                <div class="flex flex-wrap gap-2">
                    <!-- 選択中の倉庫のPDF出力 -->
                    <button @click="downloadPdf(false)"
                       :disabled="pdfLoading"
                       class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white text-xs sm:text-sm font-medium rounded-md shadow-sm">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">この倉庫を</span>PDF
                    </button>

                    <!-- 全倉庫のPDF出力 -->
                    <button @click="downloadPdf(true)"
                       :disabled="pdfLoading"
                       class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-xs sm:text-sm font-medium rounded-md shadow-sm">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span class="hidden sm:inline">全倉庫を</span>PDF
                    </button>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    機材
                                </th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-14 sm:w-20">
                                    数量
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="item in inventoryData" :key="item.equipment_id">
                                <tr @click="openDetailModal(item)" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 active:bg-gray-100 dark:active:bg-gray-600">
                                    <!-- カテゴリ・機材名 -->
                                    <td class="px-3 sm:px-4 py-2 sm:py-4">
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="(item.equipment?.subcategory?.category?.name || '') + (item.equipment?.subcategory?.name ? ' > ' + item.equipment?.subcategory?.name : '')"></div>
                                        <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white" x-text="item.equipment?.name || '機材名不明'"></div>
                                    </td>

                                    <!-- 在庫数量 -->
                                    <td class="px-3 sm:px-4 py-2 sm:py-4 text-center">
                                        <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">
                                            <span x-text="item.quantity || 0"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State Row -->
                            <tr x-show="inventoryData.length === 0">
                                <td colspan="2" class="px-4 sm:px-6 py-8 sm:py-12 text-center">
                                    <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                    </svg>
                                    <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900 dark:text-white">在庫データがありません</h3>
                                    <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">検索条件を変更してください。</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 詳細モーダル -->
        <div x-show="showDetailModal" x-transition class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-70 overflow-y-auto h-full w-full z-50" @click.self="closeDetailModal()">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-4 sm:p-6 border border-gray-200 dark:border-gray-700 w-auto sm:w-96 max-w-md shadow-lg rounded-lg bg-white dark:bg-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">機材詳細</h3>
                    <button @click="closeDetailModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <template x-if="selectedItem">
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">カテゴリ</div>
                            <div class="text-sm text-gray-900 dark:text-white" x-text="(selectedItem.equipment?.subcategory?.category?.name || '') + (selectedItem.equipment?.subcategory?.name ? ' > ' + selectedItem.equipment?.subcategory?.name : '')"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">機材名</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedItem.equipment?.name || '機材名不明'"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">在庫数量</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="selectedItem.quantity || 0"></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">新音番号</div>
                            <div class="text-sm text-gray-900 dark:text-white break-words" x-text="(selectedItem.quantity > 0) ? (selectedItem.equipment?.company_number || '-') : '-'"></div>
                        </div>
                    </div>
                </template>
                <div class="mt-5">
                    <button @click="closeDetailModal()" class="w-full px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        閉じる
                    </button>
                </div>
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
                pdfLoading: false,
                pdfMessage: '',
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
                showDetailModal: false,
                selectedItem: null,

                openDetailModal(item) {
                    this.selectedItem = item;
                    this.showDetailModal = true;
                },

                closeDetailModal() {
                    this.showDetailModal = false;
                    this.selectedItem = null;
                },

                async downloadPdf(allLocations) {
                    this.pdfLoading = true;
                    this.pdfMessage = allLocations ? '全倉庫の在庫データを処理中...' : '在庫データを処理中...';

                    try {
                        let url = `{{ route('inventory.export-pdf') }}?as_of_date=${this.asOfDate}`;
                        if (allLocations) {
                            url += '&all_locations=1';
                        } else {
                            url += `&location_id=${this.filters.location_id}`;
                        }

                        const response = await fetch(url);

                        if (!response.ok) {
                            throw new Error('PDF生成に失敗しました');
                        }

                        // Content-Dispositionヘッダーからファイル名を取得
                        const contentDisposition = response.headers.get('Content-Disposition');
                        let filename = allLocations ? '在庫一覧_全倉庫.pdf' : '在庫一覧.pdf';
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';\n]+)/i);
                            if (filenameMatch) {
                                filename = decodeURIComponent(filenameMatch[1]);
                            }
                        }

                        // Blobを作成してダウンロード
                        const blob = await response.blob();
                        const downloadUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(downloadUrl);
                        document.body.removeChild(a);

                    } catch (error) {
                        console.error('PDF download error:', error);
                        alert('PDF出力に失敗しました: ' + error.message);
                    } finally {
                        this.pdfLoading = false;
                        this.pdfMessage = '';
                    }
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
