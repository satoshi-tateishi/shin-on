<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>在庫管理 - shin-on</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .inventory-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-sufficient { @apply bg-green-100 text-green-800; }
        .status-caution { @apply bg-yellow-100 text-yellow-800; }
        .status-shortage { @apply bg-red-100 text-red-800; }
        .alert-badge {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="inventoryDashboard()" x-init="init()" class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-900">在庫管理</h1>
                        <div class="text-sm text-gray-500">
                            基準日: <span x-text="formatDate(asOfDate)" class="font-medium text-gray-700"></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- 基準日選択 -->
                        <div class="flex items-center space-x-2">
                            <label for="asOfDate" class="text-sm font-medium text-gray-700">基準日:</label>
                            <input
                                type="date"
                                id="asOfDate"
                                x-model="asOfDate"
                                @change="loadInventoryData()"
                                class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                            >
                        </div>
                        <!-- 戻るボタン -->
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            ダッシュボードに戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>

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
        <div x-show="!loading && !error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">総機材数</dt>
                                    <dd class="text-lg font-medium text-gray-900" x-text="stats.total_items || 0"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">利用可能</dt>
                                    <dd class="text-lg font-medium text-gray-900" x-text="stats.status_distribution?.sufficient || 0"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">要注意</dt>
                                    <dd class="text-lg font-medium text-gray-900" x-text="stats.status_distribution?.caution || 0"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">不足</dt>
                                    <dd class="text-lg font-medium text-gray-900" x-text="stats.status_distribution?.shortage || 0"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Controls -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">フィルタ・検索</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

                        <!-- 場所フィルタ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">場所</label>
                            <select x-model="filters.location_id" @change="loadInventoryData()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                                <option value="">全ての場所</option>
                                <template x-for="location in locations" :key="location.id">
                                    <option :value="location.id" x-text="location.display_name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- ステータスフィルタ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">在庫状況</label>
                            <select x-model="filters.status_filter" @change="loadInventoryData()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm">
                                <option value="">全ての状況</option>
                                <option value="available">十分</option>
                                <option value="low_stock">要注意</option>
                                <option value="out_of_stock">不足</option>
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

            <!-- Actions -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <button
                        @click="generateSnapshot()"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        スナップショット生成
                    </button>

                    <div class="text-sm text-gray-500">
                        表示件数: <span x-text="meta.from || 0"></span>-<span x-text="meta.to || 0"></span> / <span x-text="meta.total || 0"></span>件
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        @click="prevPage()"
                        :disabled="meta.current_page <= 1"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        前へ
                    </button>
                    <span class="px-3 py-2 text-sm text-gray-700">
                        <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span>
                    </span>
                    <button
                        @click="nextPage()"
                        :disabled="meta.current_page >= meta.last_page"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        次へ
                    </button>
                </div>
            </div>

            <!-- Inventory List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <template x-for="item in inventoryData" :key="item.equipment_id">
                        <li class="inventory-card">
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-sm font-medium text-gray-900" x-text="item.equipment_name"></h4>
                                                <span class="status-badge" :class="'status-' + item.status_color" x-text="item.status_text"></span>
                                            </div>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span x-text="item.category_name + ' > ' + item.subcategory_name"></span>
                                                <span x-text="'[' + item.location.type + '] ' + item.location.name"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-900">
                                                <span x-text="item.available_quantity"></span> / <span x-text="item.total_quantity"></span>
                                            </div>
                                            <div class="text-xs text-gray-500">利用可能 / 総数</div>
                                        </div>
                                        <button
                                            @click="viewEquipmentDetail(item.equipment_id)"
                                            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500"
                                        >
                                            詳細
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>

                <!-- Empty State -->
                <div x-show="inventoryData.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">在庫データがありません</h3>
                    <p class="mt-1 text-sm text-gray-500">検索条件を変更するか、スナップショットを生成してください。</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function inventoryDashboard() {
            return {
                loading: false,
                error: null,
                asOfDate: new Date().toISOString().split('T')[0],
                inventoryData: [],
                stats: {},
                meta: {},
                categories: [],
                locations: [],
                filters: {
                    category_id: '',
                    location_id: '',
                    status_filter: '',
                    search: '',
                    page: 1,
                    per_page: 50
                },

                async init() {
                    await this.loadMasterData();
                    await this.loadInventoryData();
                    await this.loadInventoryStats();
                },

                async loadMasterData() {
                    try {
                        // カテゴリ一覧取得
                        const categoryResponse = await fetch('/api/schedule/categories');
                        if (categoryResponse.ok) {
                            const categoryData = await categoryResponse.json();
                            this.categories = categoryData.categories || [];
                        }

                        // 場所一覧取得（既存のAPIを活用）
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
                            page: this.filters.page,
                            per_page: this.filters.per_page,
                            ...Object.fromEntries(
                                Object.entries(this.filters).filter(([key, value]) =>
                                    value !== '' && key !== 'page' && key !== 'per_page'
                                )
                            )
                        });

                        const response = await fetch(`/inventory/api/inventory?${params}`);
                        const data = await response.json();

                        if (data.success) {
                            this.inventoryData = data.data || [];
                            this.meta = data.meta || {};
                        } else {
                            throw new Error(data.error || '在庫データの取得に失敗しました');
                        }
                    } catch (error) {
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
                            throw new Error(data.error || 'スナップショットの生成に失敗しました');
                        }
                    } catch (error) {
                        this.error = error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                viewEquipmentDetail(equipmentId) {
                    // 機材詳細モーダルまたはページに遷移
                    window.open(`/master/equipments/${equipmentId}`, '_blank');
                },

                nextPage() {
                    if (this.meta.current_page < this.meta.last_page) {
                        this.filters.page = this.meta.current_page + 1;
                        this.loadInventoryData();
                    }
                },

                prevPage() {
                    if (this.meta.current_page > 1) {
                        this.filters.page = this.meta.current_page - 1;
                        this.loadInventoryData();
                    }
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('ja-JP', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'short'
                    });
                }
            }
        }
    </script>
</body>
</html>