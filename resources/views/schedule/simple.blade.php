<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>機材スケジュール表 - shin-on</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .schedule-table {
            border-spacing: 0;
        }
        .schedule-table th:first-child,
        .schedule-table td:first-child {
            min-width: 150px;
        }
        .schedule-table th:nth-child(2),
        .schedule-table td:nth-child(2) {
            min-width: 120px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6" x-data="scheduleManager()">
        <!-- ヘッダー -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">機材スケジュール表</h1>
                <p class="text-sm text-gray-600 mt-1">機材の使用状況をExcel風の表形式で表示します</p>
            </div>

            <!-- フィルター -->
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- 期間選択 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">開始日</label>
                        <input type="date" x-model="filters.start_date"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
                        <input type="date" x-model="filters.end_date"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>

                    <!-- 表示件数 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">表示件数</label>
                        <select x-model="filters.per_page"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="5">5件</option>
                            <option value="10">10件</option>
                            <option value="20">20件</option>
                        </select>
                    </div>

                    <!-- アクションボタン -->
                    <div class="flex items-end">
                        <button @click="loadScheduleData()"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 mr-2">
                            <span x-show="!loading">表示</span>
                            <span x-show="loading">読込中...</span>
                        </button>
                    </div>
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

        <!-- スケジュール表 -->
        <div x-show="!loading && !error && scheduleData.length > 0" class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    スケジュール表
                    <span class="text-sm font-normal text-gray-600">
                        (<span x-text="scheduleData.length"></span>件表示)
                    </span>
                </h2>
            </div>

            <!-- Excel風テーブル -->
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full">
                    <table class="min-w-full border-collapse schedule-table">
                        <thead>
                            <tr class="bg-gray-50">
                                <!-- 機材情報列 -->
                                <th class="sticky left-0 bg-gray-50 border border-gray-300 px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider z-10">
                                    機材名
                                </th>
                                <th class="sticky left-20 bg-gray-50 border border-gray-300 px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider z-10">
                                    カテゴリ
                                </th>

                                <!-- 日付列 -->
                                <template x-for="date in dateRange" :key="date">
                                    <th class="border border-gray-300 px-2 py-2 text-center text-xs font-medium text-gray-700 min-w-24">
                                        <div x-text="formatDateHeader(date)"></div>
                                        <div class="text-gray-500 text-xs" x-text="formatDayOfWeek(date)"></div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="equipment in scheduleData" :key="equipment.equipment_id">
                                <tr class="hover:bg-gray-50">
                                    <!-- 機材情報 -->
                                    <td class="sticky left-0 bg-white border border-gray-300 px-3 py-2 text-sm font-medium text-gray-900 z-10">
                                        <div x-text="equipment.equipment_name"></div>
                                        <div class="text-xs text-gray-500" x-text="equipment.equipment_code"></div>
                                    </td>
                                    <td class="sticky left-20 bg-white border border-gray-300 px-3 py-2 text-sm text-gray-600 z-10">
                                        <div x-text="equipment.category"></div>
                                        <div class="text-xs text-gray-500" x-text="equipment.subcategory"></div>
                                    </td>

                                    <!-- 日別ステータス -->
                                    <template x-for="date in dateRange" :key="`${equipment.equipment_id}-${date}`">
                                        <td class="border border-gray-300 p-1 text-center relative">
                                            <div :class="getStatusClass(equipment.daily_status[date]?.status)"
                                                 class="w-full h-8 flex items-center justify-center rounded text-xs font-medium cursor-pointer"
                                                 :title="getStatusTooltip(equipment.daily_status[date])"
                                                 @click="showStatusDetail(equipment, date, equipment.daily_status[date])">
                                                <span x-text="getStatusSymbol(equipment.daily_status[date]?.status)"></span>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- データなし -->
        <div x-show="!loading && !error && scheduleData.length === 0" class="bg-white shadow-sm rounded-lg p-8 text-center">
            <p class="text-gray-600">該当するデータがありません。フィルター条件を変更してください。</p>
        </div>

        <!-- ステータス凡例 -->
        <div class="bg-white shadow-sm rounded-lg mt-6 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ステータス凡例</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded"></div>
                    <span class="text-sm">利用可能</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-blue-500 rounded"></div>
                    <span class="text-sm">予約済み</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-orange-500 rounded"></div>
                    <span class="text-sm">使用中</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-gray-500 rounded"></div>
                    <span class="text-sm">返却済み</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-red-500 rounded"></div>
                    <span class="text-sm">修理中</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                    <span class="text-sm">メンテナンス中</span>
                </div>
            </div>
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
                dateRange: [],

                // UI状態
                loading: false,
                error: null,
                showModal: false,
                modalData: null,

                // フィルター
                filters: {
                    start_date: '2025-01-15',
                    end_date: '2025-01-25',
                    per_page: 5
                },

                init() {
                    this.loadScheduleData();
                },

                async loadScheduleData() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const params = new URLSearchParams({
                            start_date: this.filters.start_date,
                            end_date: this.filters.end_date,
                            per_page: this.filters.per_page
                        });

                        const response = await fetch(`/test-api/schedule/equipment?${params}`);
                        const data = await response.json();

                        if (data.success) {
                            this.scheduleData = data.equipment_schedules;
                            this.dateRange = data.date_range;
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
                }
            };
        }
    </script>
</body>
</html>