@extends('layouts.master')

@section('title', '倉庫間移動')

@section('breadcrumb')
    > <span class="text-gray-800 dark:text-gray-200">倉庫間移動</span>
@endsection

@push('head')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('header')
    <div>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">倉庫間移動</h1>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6" x-data="transferManager()">
        {{-- ====================================== --}}
        {{-- フィルターセクション --}}
        {{-- ====================================== --}}
        <section class="mb-4 sm:mb-6 bg-gray-50 dark:bg-gray-800 p-3 sm:p-4 rounded-lg" aria-label="機材検索フィルタ">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                <!-- 現在の保管場所フィルタ -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        現在の保管場所
                    </label>
                    <select x-model="filters.location_id"
                            @change="onLocationChange($event)"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <template x-for="location in locations" :key="location.id">
                            <option :value="location.id" x-text="location.name"></option>
                        </template>
                    </select>
                </div>

                <!-- カテゴリフィルタ -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        カテゴリ
                    </label>
                    <select x-model="filters.category_id"
                            @change="loadEquipmentData()"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">すべてのカテゴリ</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>

                <!-- 機材名検索 -->
                <div class="sm:col-span-2 md:col-span-1">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        機材名検索
                    </label>
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="loadEquipmentData()"
                           placeholder="機材名を入力..."
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </section>

        {{-- ====================================== --}}
        {{-- 状態表示セクション --}}
        {{-- ====================================== --}}
        <!-- ローディング状態 -->
        <section x-show="loading"
                 class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 aria-live="polite">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">データを読み込み中...</p>
        </section>

        <!-- エラー表示 -->
        <section x-show="error"
                 class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 role="alert"
                 aria-live="assertive">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-400 dark:text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-300">エラーが発生しました</h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-400" x-text="error"></div>
                </div>
            </div>
        </section>

        {{-- ====================================== --}}
        {{-- 機材一覧セクション --}}
        {{-- ====================================== --}}
        <section x-show="!loading && !error"
                 class="bg-white dark:bg-gray-800 shadow rounded-lg"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 aria-label="移動可能機材一覧">
            <!-- テーブルヘッダー -->
            <header class="px-3 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
                        移動可能機材一覧
                        <span class="text-xs sm:text-sm font-normal text-gray-600 dark:text-gray-400">
                            (<span x-text="equipmentData.length"></span>件)
                        </span>
                    </h2>
                </div>
            </header>

            <!-- 機材一覧テーブル -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" role="table">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                機材名
                            </th>
                            <th scope="col"
                                class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                                基本倉庫
                            </th>
                            <th scope="col"
                                class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">
                                現在地
                            </th>
                            <th scope="col"
                                class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                移動先
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="equipment in equipmentData" :key="equipment.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                :class="getTransferDestination(equipment.id) ? 'bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-400' : ''"
                                role="row">
                                <!-- 機材名セル -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4" role="gridcell">
                                    <div class="space-y-0.5 sm:space-y-1">
                                        <!-- カテゴリ情報 -->
                                        <div class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">
                                            <span x-text="equipment.subcategory.category.name"></span>
                                            <span class="mx-1">></span>
                                            <span x-text="equipment.subcategory.name"></span>
                                        </div>

                                        <!-- メーカー情報 -->
                                        <div x-show="equipment.manufacturer"
                                             class="text-xs text-gray-400 dark:text-gray-500 hidden sm:block"
                                             x-text="equipment.manufacturer">
                                        </div>

                                        <!-- 機材名と管理番号 -->
                                        <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white"
                                                 x-text="equipment.name">
                                            </div>
                                            <div x-show="equipment.company_number"
                                                 class="inline-flex items-center px-1 sm:px-2 py-0.5 sm:py-1 rounded text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                                                 x-text="equipment.company_number">
                                            </div>
                                        </div>
                                        <!-- モバイル用現在地表示 -->
                                        <div class="text-xs text-gray-500 dark:text-gray-400 sm:hidden">
                                            現在地: <span x-text="locations.find(loc => loc.id === equipment.now_location_id)?.name || '不明'"></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 基本倉庫セル -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell" role="gridcell">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white"
                                         x-text="equipment.location.name">
                                    </div>
                                </td>

                                <!-- 現在地セル -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4 whitespace-nowrap hidden sm:table-cell" role="gridcell">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white"
                                         x-text="locations.find(loc => loc.id === equipment.now_location_id)?.name || '不明'">
                                    </div>
                                </td>

                                <!-- 移動先選択セル -->
                                <td class="px-2 sm:px-4 py-2 sm:py-4 whitespace-nowrap" role="gridcell">
                                    <select :value="getTransferDestination(equipment.id)"
                                            @change="updateTransferDestination(equipment.id, $event.target.value)"
                                            :aria-label="`${equipment.name}の移動先を選択`"
                                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="">移動先を選択...</option>
                                        <template x-for="location in getAvailableDestinations(equipment)" :key="location.id">
                                            <option :value="location.id" x-text="location.name"></option>
                                        </template>
                                    </select>
                                </td>
                            </tr>
                        </template>

                        <!-- データなし状態 -->
                        <tr x-show="equipmentData.length === 0" role="row">
                            <td colspan="4" class="px-4 sm:px-6 py-8 sm:py-12 text-center" role="gridcell">
                                <div class="flex flex-col items-center space-y-2 sm:space-y-3">
                                    <svg class="h-10 w-10 sm:h-12 sm:w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M9 5v4M15 5v4M9 15v4M15 15v4"></path>
                                    </svg>
                                    <div class="text-center">
                                        <h3 class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">移動可能な機材がありません</h3>
                                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">検索条件を変更してください。</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>


        {{-- ====================================== --}}
        {{-- モーダルセクション --}}
        {{-- ====================================== --}}
        <!-- 一括移動確認モーダル -->
        <div x-show="showBulkModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
             @click="showBulkModal = false"
             role="dialog"
             aria-modal="true"
             aria-labelledby="bulk-transfer-title">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-4 sm:p-5 border dark:border-gray-700 w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800"
                 @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">
                <div class="mt-2 sm:mt-3">
                    <!-- モーダルヘッダー -->
                    <header class="flex items-center justify-between pb-2 sm:pb-3 border-b dark:border-gray-700">
                        <h3 id="bulk-transfer-title" class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            倉庫間移動の確認
                        </h3>
                        <button @click="showBulkModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                                aria-label="モーダルを閉じる">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </header>

                    <!-- 移動予定機材一覧 -->
                    <div class="py-3 sm:py-4">
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-4">
                            以下の機材を移動します。よろしいですか？
                        </p>

                        <div class="max-h-48 sm:max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md">
                            <table class="min-w-full" role="table">
                                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                    <tr>
                                        <th scope="col" class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            機材
                                        </th>
                                        <th scope="col" class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            移動先
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <template x-for="transfer in pendingTransfers" :key="transfer.equipmentId">
                                        <tr role="row">
                                            <td class="px-2 sm:px-4 py-2" role="gridcell">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2">
                                                    <span class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white" x-text="transfer.equipmentName"></span>
                                                    <span x-show="transfer.companyNumber !== '-'"
                                                          class="inline-flex items-center px-1 sm:px-2 py-0.5 sm:py-1 rounded text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                                                          x-text="transfer.companyNumber">
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-2 sm:px-4 py-2" role="gridcell">
                                                <div class="flex flex-wrap items-center gap-1 sm:gap-2 text-xs sm:text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400 hidden sm:inline" x-text="transfer.currentLocationName"></span>
                                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="font-medium text-blue-600 dark:text-blue-400" x-text="transfer.toLocationName"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- モーダルフッター -->
                    <footer class="flex space-x-2 sm:space-x-3 pt-3 sm:pt-4 border-t dark:border-gray-700">
                        <button @click="showBulkModal = false"
                                type="button"
                                class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            キャンセル
                        </button>
                        <button @click="executeBulkTransfer()"
                                :disabled="isBulkTransferring"
                                type="button"
                                class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed transition-colors duration-200">
                            <span x-show="!isBulkTransferring" class="flex items-center justify-center">
                                実行
                            </span>
                            <span x-show="isBulkTransferring" class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                処理中...
                            </span>
                        </button>
                    </footer>
                </div>
            </div>
        </div>

        <!-- 移動先選択モーダル -->
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50" @click="showModal = false">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-4 sm:p-5 border dark:border-gray-700 w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800" @click.stop>
                <div class="mt-2 sm:mt-3">
                    <!-- ヘッダー -->
                    <div class="flex items-center justify-between pb-2 sm:pb-3 border-b dark:border-gray-700">
                        <h3 class="text-base sm:text-lg leading-6 font-medium text-gray-900 dark:text-white">移動先選択</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- 機材情報 -->
                    <div class="py-3 sm:py-4" x-show="selectedEquipment">
                        <div class="bg-gray-50 dark:bg-gray-700 p-2 sm:p-3 rounded">
                            <p class="text-xs sm:text-sm text-gray-900 dark:text-white"><span class="font-medium">機材:</span> <span x-text="selectedEquipment?.name"></span></p>
                            <p class="text-xs sm:text-sm text-gray-900 dark:text-white"><span class="font-medium">新音番号:</span> <span x-text="selectedEquipment?.company_number || '-'"></span></p>
                            <p class="text-xs sm:text-sm text-gray-900 dark:text-white"><span class="font-medium">現在地:</span> <span x-text="selectedEquipment?.location?.display_name"></span></p>
                        </div>
                    </div>

                    <!-- 移動先選択 -->
                    <div class="py-3 sm:py-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">移動先を選択してください</label>
                        <select x-model="selectedToLocation" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 text-sm">
                            <option value="">移動先を選択...</option>
                            <template x-for="location in availableDestinations" :key="location.id">
                                <option :value="location.id" x-text="location.display_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 備考 -->
                    <div class="py-3 sm:py-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">備考（任意）</label>
                        <textarea x-model="transferNote" rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-md px-3 py-2 text-sm"
                                placeholder="移動理由や備考があれば入力してください"></textarea>
                    </div>

                    <!-- フッター -->
                    <div class="flex space-x-2 sm:space-x-3 pt-3 sm:pt-4 border-t dark:border-gray-700">
                        <button @click="showModal = false"
                                class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            キャンセル
                        </button>
                        <button @click="executeTransfer()" :disabled="!selectedToLocation"
                                class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700 disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed">
                            移動実行
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- ====================================== --}}
{{-- フローティングアクションボタン --}}
{{-- ====================================== --}}
<div class="fixed bottom-3 sm:bottom-4 right-3 sm:right-4 z-50">
    <button id="floatingButton"
            type="button"
            onclick="handleFloatingButtonClick()"
            aria-label="選択した機材を一括移動する"
            class="relative px-3 sm:px-4 h-10 sm:h-14 bg-gray-400 text-white rounded-full shadow-lg hover:shadow-xl font-bold cursor-pointer transition-all duration-200 flex items-center justify-center opacity-70 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <span class="text-xs sm:text-sm font-medium">移動実行</span>

        <!-- バッジ -->
        <span id="transferBadge"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full border-2 border-white min-w-5 sm:min-w-6 h-5 sm:h-6 items-center justify-center"
              style="display: none;"
              aria-hidden="true">
            0
        </span>
    </button>
</div>

@endsection
