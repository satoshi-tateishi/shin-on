<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>機材スケジュール表 - shin-on</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .schedule-table {
            border-spacing: 0;
        }
        .schedule-table th:first-child,
        .schedule-table td:first-child {
            min-width: 120px;
            max-width: 120px;
            width: 120px;
        }
        .schedule-table th:nth-child(2),
        .schedule-table td:nth-child(2) {
            min-width: 80px;
            max-width: 80px;
            width: 80px;
        }
        /* 日付列の幅を狭く */
        .schedule-table th:nth-child(n+3),
        .schedule-table td:nth-child(n+3) {
            width: 30px;
            min-width: 30px;
            max-width: 30px;
        }
        /* テーブル内の文字サイズを小さく */
        .schedule-table {
            font-size: 11px;
        }
        .schedule-table th {
            font-size: 10px;
        }

        /* Sticky列に影を追加 */
        .schedule-table th.sticky,
        .schedule-table td.sticky {
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        /* スパンセル専用スタイル */
        .span-cell .span-content {
            border-top: 3px solid;
            border-bottom: 3px solid;
            position: relative;
        }

        /* ステータス別ボーダー色 */
        .span-cell .bg-blue-500.span-content {
            background-color: rgba(59, 130, 246, 0.2) !important;
            border-top-color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .span-cell .bg-orange-500.span-content {
            background-color: rgba(249, 115, 22, 0.2) !important;
            border-top-color: #f97316;
            border-bottom-color: #f97316;
        }

        .span-cell .bg-gray-500.span-content {
            background-color: rgba(107, 114, 128, 0.2) !important;
            border-top-color: #6b7280;
            border-bottom-color: #6b7280;
        }

        .span-cell .bg-red-500.span-content {
            background-color: rgba(239, 68, 68, 0.2) !important;
            border-top-color: #ef4444;
            border-bottom-color: #ef4444;
        }

        .span-cell .bg-yellow-500.span-content {
            background-color: rgba(234, 179, 8, 0.2) !important;
            border-top-color: #eab308;
            border-bottom-color: #eab308;
        }

        .span-cell .bg-green-500.span-content {
            background-color: rgba(34, 197, 94, 0.2) !important;
            border-top-color: #22c55e;
            border-bottom-color: #22c55e;
        }

        /* スパンテキストスタイル */
        .span-text {
            font-size: 8px;
            font-weight: 500;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            padding: 0 2px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">
                        shin-on 機材スケジュール表
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">
                        ダッシュボードに戻る
                    </a>
                    <div class="flex items-center space-x-2">
                        @if(auth()->user()->icon)
                            <img src="{{ auth()->user()->icon }}" alt="Icon" class="w-8 h-8 rounded-full">
                        @endif
                        <span class="text-gray-700">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-700">
                            ログアウト
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="w-full px-4 py-6" x-data="scheduleManager()">
        <!-- ヘッダー -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">機材スケジュール表</h1>
                <p class="text-sm text-gray-600 mt-1">機材の使用状況をExcel風の表形式で表示します</p>
            </div>

            <!-- フィルター -->
            <div class="px-6 py-4 space-y-4">
                <!-- フォーム全体でEnterキー対応 -->
                <form @submit.prevent="loadScheduleData()" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- 期間選択 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">開始日</label>
                        <input type="date" x-model="filters.start_date" @keydown.enter="loadScheduleData()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
                        <input type="date" x-model="filters.end_date" @keydown.enter="loadScheduleData()"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>

                    <!-- カテゴリ選択 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                        <select x-model="filters.category_id" @change="onCategoryChange()" @keydown.enter="loadScheduleData()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">全てのカテゴリ</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- サブカテゴリ選択 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">サブカテゴリ</label>
                        <select x-model="filters.subcategory_id" @keydown.enter="loadScheduleData()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">全てのサブカテゴリ</option>
                            <template x-for="subcategory in subcategories" :key="subcategory.id">
                                <option :value="subcategory.id" x-text="subcategory.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 表示件数 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">表示件数</label>
                        <select x-model="filters.per_page" @keydown.enter="loadScheduleData()"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="10">10件</option>
                            <option value="20">20件</option>
                            <option value="50">50件</option>
                            <option value="100" selected>100件</option>
                        </select>
                    </div>
                </div>

                <!-- アクションボタン -->
                <div class="flex space-x-3">
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                        <span x-show="!loading">表示</span>
                        <span x-show="loading">読込中...</span>
                    </button>
                    <button type="button" @click="resetFilters()"
                            class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-600">
                        リセット
                    </button>
                </div>
                </form>
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

        <!-- スケジュール表 -->
        <div x-show="!loading && !error && scheduleData.length > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">
                        スケジュール表
                        <span class="text-sm font-normal text-gray-600">
                            (<span x-text="scheduleData.length"></span>件表示)
                        </span>
                    </h2>

                    <!-- ページネーション情報 -->
                    <div x-show="pagination" class="text-sm text-gray-600">
                        <span x-text="pagination?.from || 0"></span>-<span x-text="pagination?.to || 0"></span>
                        / <span x-text="pagination?.total || 0"></span>件
                    </div>
                </div>
            </div>

            <!-- ステータス凡例 -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-2">ステータス凡例</h3>
                <div class="grid grid-cols-4 md:grid-cols-8 gap-2">
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-green-500 rounded"></div>
                        <span class="text-xs">利用可能</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-blue-500 rounded"></div>
                        <span class="text-xs">予約済み</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-orange-500 rounded"></div>
                        <span class="text-xs">使用中</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-gray-500 rounded"></div>
                        <span class="text-xs">返却済み</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-red-500 rounded"></div>
                        <span class="text-xs">修理中</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                        <span class="text-xs">メンテナンス中</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-red-800 rounded"></div>
                        <span class="text-xs">紛失</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-3 h-3 bg-gray-800 rounded"></div>
                        <span class="text-xs">廃棄</span>
                    </div>
                </div>
            </div>

            <!-- Excel風テーブル -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse schedule-table" style="min-width: max-content;">
                        <thead>
                            <tr class="bg-gray-50">
                                <!-- 機材情報列 -->
                                <th class="sticky left-0 bg-gray-50 border border-gray-300 px-1 py-1 text-left text-xs font-medium text-gray-700 uppercase tracking-wider z-10">
                                    機材名
                                </th>
                                <th class="sticky bg-gray-50 border border-gray-300 px-1 py-1 text-left text-xs font-medium text-gray-700 uppercase tracking-wider z-10" style="left: 120px;">
                                    新音番号
                                </th>

                                <!-- 日付列 -->
                                <template x-for="date in dateRange" :key="date">
                                    <th class="border border-gray-300 px-0 py-0.5 text-center text-xs font-medium text-gray-700">
                                        <div class="text-xs" x-text="formatDateHeader(date)"></div>
                                        <div class="text-gray-500 text-xs" x-text="formatDayOfWeek(date)"></div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="equipment in scheduleData" :key="equipment.equipment_id">
                                <tr class="hover:bg-gray-50">
                                    <!-- 機材情報 -->
                                    <td class="sticky left-0 bg-white border border-gray-300 px-1 py-1 text-xs font-medium text-gray-900 z-10">
                                        <div class="text-xs text-gray-500" x-text="equipment.subcategory"></div>
                                        <div class="text-xs" x-text="equipment.equipment_name"></div>
                                    </td>
                                    <td class="sticky bg-white border border-gray-300 px-1 py-1 text-xs text-gray-600 z-10" style="left: 120px;">
                                        <div class="text-xs" x-text="equipment.equipment_code"></div>
                                    </td>

                                    <!-- 日別ステータス -->
                                    <template x-for="(date, dateIndex) in dateRange" :key="`${equipment.equipment_id}-${date}`">
                                        <td x-show="!isSpanMiddle(equipment, dateIndex)"
                                            class="border border-gray-300 p-0 text-center relative"
                                            :class="isSpanStart(equipment, dateIndex) ? 'span-cell' : ''"
                                            :colspan="isSpanStart(equipment, dateIndex) ? getSpanLength(equipment, dateIndex) : 1">

                                            <!-- スパンセルの場合 -->
                                            <template x-if="isSpanStart(equipment, dateIndex)">
                                                <div :class="getStatusClass(equipment.daily_status[date]?.status)"
                                                     class="w-full h-5 cursor-pointer span-content flex items-center justify-center"
                                                     :title="getStatusTooltip(equipment.daily_status[date])"
                                                     @click="showStatusDetail(equipment, date, equipment.daily_status[date])">
                                                    <span class="span-text" x-text="formatSpanText(isSpanStart(equipment, dateIndex))"></span>
                                                </div>
                                            </template>

                                            <!-- 通常セルの場合 -->
                                            <template x-if="!isSpanStart(equipment, dateIndex)">
                                                <div :class="getStatusClass(equipment.daily_status[date]?.status)"
                                                     class="w-full h-5 cursor-pointer"
                                                     :title="getStatusTooltip(equipment.daily_status[date])"
                                                     @click="showStatusDetail(equipment, date, equipment.daily_status[date])">
                                                </div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            <!-- ページネーション -->
            <div x-show="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span x-text="pagination.from"></span>-<span x-text="pagination.to"></span>
                        / <span x-text="pagination.total"></span>件
                    </div>
                    <div class="flex space-x-2">
                        <button @click="loadPage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            前へ
                        </button>
                        <span class="px-3 py-1 text-sm">
                            <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                        </span>
                        <button @click="loadPage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            次へ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- データなし -->
        <div x-show="!loading && !error && scheduleData.length === 0" class="bg-white shadow-sm rounded-lg p-8 text-center">
            <p class="text-gray-600">該当するデータがありません。フィルター条件を変更してください。</p>
        </div>

    </div>

    <!-- ステータス詳細モーダル -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showModal = false"></div>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">ステータス詳細</h3>
                </div>

                <div x-show="modalData" class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-500">機材名：</span>
                        <span class="text-sm text-gray-900" x-text="modalData?.equipment?.equipment_name"></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">日付：</span>
                        <span class="text-sm text-gray-900" x-text="modalData?.date"></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">ステータス：</span>
                        <span class="text-sm text-gray-900" x-text="getStatusText(modalData?.status?.status)"></span>
                    </div>
                    <div x-show="modalData?.status?.phase_name">
                        <span class="text-sm font-medium text-gray-500">フェーズ：</span>
                        <span class="text-sm text-gray-900" x-text="modalData?.status?.phase_name"></span>
                    </div>
                    <div x-show="modalData?.status?.performance_title">
                        <span class="text-sm font-medium text-gray-500">公演：</span>
                        <span class="text-sm text-gray-900" x-text="modalData?.status?.performance_title"></span>
                    </div>
                    <div x-show="modalData?.status?.note">
                        <span class="text-sm font-medium text-gray-500">備考：</span>
                        <span class="text-sm text-gray-900" x-text="modalData?.status?.note"></span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        閉じる
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function scheduleManager() {
            return {
                // データ
                scheduleData: [],
                categories: [],
                subcategories: [],
                dateRange: [],
                pagination: null,

                // UI状態
                loading: false,
                error: null,
                showModal: false,
                modalData: null,

                // フィルター
                filters: {
                    start_date: new Date().toISOString().split('T')[0],
                    end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category_id: '',
                    subcategory_id: '',
                    per_page: 100
                },

                init() {
                    this.loadCategories();
                    this.loadSubcategories();
                    this.loadScheduleData();
                },

                async loadCategories() {
                    try {
                        const response = await fetch('/api/schedule/categories', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.categories = data.categories;
                        }
                    } catch (error) {
                        console.error('Failed to load categories:', error);
                    }
                },

                async loadSubcategories(categoryId = null) {
                    try {
                        const params = new URLSearchParams();
                        if (categoryId) {
                            params.append('category_id', categoryId);
                        }

                        const response = await fetch(`/api/schedule/subcategories?${params}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.subcategories = data.subcategories;
                        }
                    } catch (error) {
                        console.error('Failed to load subcategories:', error);
                    }
                },

                onCategoryChange() {
                    this.filters.subcategory_id = ''; // サブカテゴリをリセット
                    this.loadSubcategories(this.filters.category_id);
                },

                async loadScheduleData(page = 1) {
                    this.loading = true;
                    this.error = null;

                    try {
                        const params = new URLSearchParams({
                            start_date: this.filters.start_date,
                            end_date: this.filters.end_date,
                            per_page: this.filters.per_page,
                            page: page
                        });

                        if (this.filters.subcategory_id) {
                            params.append('subcategory_id', this.filters.subcategory_id);
                        } else if (this.filters.category_id) {
                            params.append('category_id', this.filters.category_id);
                        }

                        const response = await fetch(`/api/schedule/equipment?${params}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const data = await response.json();

                        if (data.success) {
                            this.scheduleData = data.equipment_schedules;
                            this.dateRange = data.date_range;
                            this.pagination = data.pagination;
                        } else {
                            this.error = data.error || 'データの取得に失敗しました';
                        }
                    } catch (error) {
                        this.error = 'ネットワークエラーが発生しました';
                        console.error('API Error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                loadPage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) {
                        this.loadScheduleData(page);
                    }
                },

                resetFilters() {
                    this.filters = {
                        start_date: new Date().toISOString().split('T')[0],
                        end_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                        category_id: '',
                        subcategory_id: '',
                        per_page: 100
                    };
                    this.loadSubcategories(); // サブカテゴリもリセット（全て表示）
                    this.loadScheduleData();
                },

                // ステータス表示関連
                getStatusClass(status) {
                    const classes = {
                        'available': 'bg-green-500 text-white',
                        'reserved': 'bg-blue-500 text-white',
                        'checked_out': 'bg-orange-500 text-white',
                        'checked_in': 'bg-gray-500 text-white',
                        'repair': 'bg-red-500 text-white',
                        'maintenance': 'bg-yellow-500 text-white',
                        'lost': 'bg-red-800 text-white',
                        'retired': 'bg-gray-800 text-white'
                    };
                    return classes[status] || 'bg-gray-200 text-gray-700';
                },

                getStatusSymbol(status) {
                    const symbols = {
                        'available': '●',
                        'reserved': '●',
                        'checked_out': '●',
                        'checked_in': '●',
                        'repair': '●',
                        'maintenance': '●',
                        'lost': '●',
                        'retired': '●'
                    };
                    return symbols[status] || '○';
                },

                getStatusText(status) {
                    const texts = {
                        'available': '利用可能',
                        'reserved': '予約済み',
                        'checked_out': '使用中',
                        'checked_in': '返却済み',
                        'repair': '修理中',
                        'maintenance': 'メンテナンス中',
                        'lost': '紛失',
                        'retired': '廃棄'
                    };
                    return texts[status] || '不明';
                },

                getStatusTooltip(statusData) {
                    if (!statusData) return '';

                    let tooltip = this.getStatusText(statusData.status);
                    if (statusData.phase_name) {
                        tooltip += `\nフェーズ: ${statusData.phase_name}`;
                    }
                    if (statusData.performance_title) {
                        tooltip += `\n公演: ${statusData.performance_title}`;
                    }
                    if (statusData.note) {
                        tooltip += `\n備考: ${statusData.note}`;
                    }
                    return tooltip;
                },

                // 日付表示関連
                formatDateHeader(date) {
                    const d = new Date(date + 'T00:00:00');
                    return `${d.getMonth() + 1}/${d.getDate()}`;
                },

                formatDayOfWeek(date) {
                    const d = new Date(date + 'T00:00:00');
                    const days = ['日', '月', '火', '水', '木', '金', '土'];
                    return days[d.getDay()];
                },

                // モーダル関連
                showStatusDetail(equipment, date, status) {
                    this.modalData = {
                        equipment: equipment,
                        date: date,
                        status: status
                    };
                    this.showModal = true;
                },

                // スパン期間検出機能
                detectSpanPeriods(equipment) {
                    const spans = [];
                    let currentSpan = null;

                    this.dateRange.forEach((date, index) => {
                        const status = equipment.daily_status[date];
                        const shouldSpan = status && (status.status === 'reserved' || status.status === 'checked_out')
                                          && status.phase_name && status.performance_title;

                        if (shouldSpan) {
                            if (!currentSpan ||
                                currentSpan.phase_name !== status.phase_name ||
                                currentSpan.performance_title !== status.performance_title) {
                                // 新しいスパン開始
                                if (currentSpan) spans.push(currentSpan);
                                currentSpan = {
                                    startIndex: index,
                                    endIndex: index,
                                    phase_name: status.phase_name,
                                    performance_title: status.performance_title,
                                    status: status.status
                                };
                            } else {
                                // 既存スパンを延長
                                currentSpan.endIndex = index;
                            }
                        } else {
                            // スパン終了
                            if (currentSpan) {
                                spans.push(currentSpan);
                                currentSpan = null;
                            }
                        }
                    });

                    // 最後のスパンを追加
                    if (currentSpan) spans.push(currentSpan);

                    return spans;
                },

                isSpanStart(equipment, dateIndex) {
                    const spans = this.detectSpanPeriods(equipment);
                    return spans.find(span => span.startIndex === dateIndex);
                },

                isSpanMiddle(equipment, dateIndex) {
                    const spans = this.detectSpanPeriods(equipment);
                    return spans.find(span => span.startIndex < dateIndex && span.endIndex >= dateIndex);
                },

                getSpanLength(equipment, dateIndex) {
                    const spans = this.detectSpanPeriods(equipment);
                    const span = spans.find(span => span.startIndex === dateIndex);
                    return span ? span.endIndex - span.startIndex + 1 : 1;
                },

                formatSpanText(span) {
                    if (!span) return '';
                    return `${span.performance_title} - ${span.phase_name}`;
                },

            };
        }
    </script>
</body>
</html>