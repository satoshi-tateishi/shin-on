@extends('layouts.master')

@section('title', '倉庫間移動')

@section('breadcrumb')
    > <span class="text-gray-800">倉庫間移動</span>
@endsection

@push('head')
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">倉庫間移動</h1>
        <p class="text-sm text-gray-600 mt-1">個別機材の倉庫間移動を実行できます</p>
    </div>
@endsection

@section('content')
    <div class="p-6" x-data="transferManager()">
        <!-- フィルター -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- 現在地フィルタ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">現在の保管場所</label>
                    <select x-model="filters.location_id" @change="loadEquipmentData()"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">すべての場所</option>
                        <template x-for="location in locations" :key="location.id">
                            <option :value="location.id" x-text="location.display_name"></option>
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

                <!-- 状態フィルタ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">状態</label>
                    <select x-model="filters.status" @change="loadEquipmentData()"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">すべての状態</option>
                        <option value="available">利用可能</option>
                        <option value="maintenance">メンテナンス中</option>
                        <option value="repair">修理中</option>
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
                                機材情報
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                新音番号
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                現在地
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                状態
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                アクション
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="equipment in equipmentData" :key="equipment.id">
                            <tr class="hover:bg-gray-50">
                                <!-- 機材情報 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="equipment.name"></div>
                                    <div class="text-sm text-gray-500">
                                        <span x-text="equipment.subcategory.category.name"></span> > <span x-text="equipment.subcategory.name"></span>
                                    </div>
                                    <div x-show="equipment.manufacturer" class="text-xs text-gray-400" x-text="equipment.manufacturer"></div>
                                </td>

                                <!-- 新音番号 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="equipment.company_number || '-'"></div>
                                </td>

                                <!-- 現在地 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="equipment.location.display_name"></div>
                                </td>

                                <!-- 状態 -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="getStatusClass(equipment.status)">
                                        <span x-text="getStatusText(equipment.status)"></span>
                                    </span>
                                </td>

                                <!-- アクション -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button @click="openTransferModal(equipment)"
                                            :disabled="equipment.status !== 'available'"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        移動
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- データなし -->
                        <tr x-show="equipmentData.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
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

@push('scripts')
<script>
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

            // モーダル用データ
            selectedEquipment: null,
            selectedToLocation: '',
            transferNote: '',

            // フィルター
            filters: {
                location_id: '',
                category_id: '',
                search: '',
                status: 'available'
            },

            init() {
                this.loadMasterData();
                this.loadEquipmentData();
            },

            async loadMasterData() {
                try {
                    // 場所一覧取得
                    const locationResponse = await fetch('/api/locations');
                    if (locationResponse.ok) {
                        const locationData = await locationResponse.json();
                        this.locations = locationData.locations || [];
                    }

                    // カテゴリ一覧取得
                    const categoryResponse = await fetch('/api/schedule/categories');
                    if (categoryResponse.ok) {
                        const categoryData = await categoryResponse.json();
                        this.categories = categoryData.categories || [];
                    }
                } catch (error) {
                    console.error('Master data loading error:', error);
                }
            },

            async loadEquipmentData() {
                this.loading = true;
                this.error = null;

                try {
                    const params = new URLSearchParams({
                        ...Object.fromEntries(
                            Object.entries(this.filters).filter(([key, value]) => value !== '')
                        )
                    });

                    const response = await fetch(`/equipment-transfer/api/equipment?${params}`);
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

            openTransferModal(equipment) {
                this.selectedEquipment = equipment;
                this.selectedToLocation = '';
                this.transferNote = '';
                this.showModal = true;
            },

            get availableDestinations() {
                if (!this.selectedEquipment) return [];
                return this.locations.filter(location =>
                    location.id !== this.selectedEquipment.location.id
                );
            },

            async executeTransfer() {
                if (!this.selectedEquipment || !this.selectedToLocation) return;

                try {
                    const response = await fetch('/equipment-transfer/api/transfer', {
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
                        // 成功メッセージを表示
                        alert(`${this.selectedEquipment.name}の移動が完了しました`);

                        // データを再読み込み
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

            getStatusClass(status) {
                const classes = {
                    'available': 'bg-green-100 text-green-800',
                    'maintenance': 'bg-yellow-100 text-yellow-800',
                    'repair': 'bg-red-100 text-red-800',
                    'lost': 'bg-gray-100 text-gray-800',
                    'retired': 'bg-gray-100 text-gray-800'
                };
                return classes[status] || 'bg-gray-100 text-gray-800';
            },

            getStatusText(status) {
                const texts = {
                    'available': '利用可能',
                    'maintenance': 'メンテナンス中',
                    'repair': '修理中',
                    'lost': '紛失',
                    'retired': '廃棄'
                };
                return texts[status] || '不明';
            }
        };
    }
</script>
@endpush
@endsection