@extends('layouts.master')

@section('title', '機材スケジュール表')

@section('breadcrumb')
    > <span class="text-gray-800 dark:text-gray-200">機材スケジュール表</span>
@endsection

@push('head')
    <style>
        [x-cloak] { display: none !important; }

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
        /* 日付列の幅 */
        .schedule-table th:nth-child(n+3),
        .schedule-table td:nth-child(n+3) {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
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

        .dark .span-text {
            color: #e5e7eb;
        }

        /* アノテーションテキストスタイル */
        .annotation-text {
            font-size: 8px;
            font-weight: 600;
            color: white;
            text-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 35px;
            display: block;
            text-align: center;
        }

        /* 土曜日・日曜日の背景色 */
        .saturday-header {
            background-color: #dbeafe !important; /* 薄い青 */
        }
        .sunday-header {
            background-color: #fee2e2 !important; /* 薄い赤 */
        }

        /* ダークモード対応 */
        .dark .saturday-header {
            background-color: #1e3a5f !important; /* ダーク青 */
        }
        .dark .sunday-header {
            background-color: #5f1e1e !important; /* ダーク赤 */
        }

        /* メモ表示スタイル */
        .cell-memo {
            position: absolute;
            top: 2px;
            left: 2px;
            font-size: 7px;
            font-weight: 600;
            color: #1f2937;
            background-color: transparent;
            padding: 1px 3px;
            border-radius: 2px;
            z-index: 5;
            text-shadow: 0 0 3px rgba(255, 255, 255, 0.8), 0 0 5px rgba(255, 255, 255, 0.6);
            white-space: nowrap;
        }

        .dark .cell-memo {
            color: #f3f4f6;
            text-shadow: 0 0 3px rgba(0, 0, 0, 0.8), 0 0 5px rgba(0, 0, 0, 0.6);
        }

        /* セルにカーソルを当てたときの効果 */
        .clickable-cell {
            cursor: pointer;
            transition: all 0.2s;
        }

        .clickable-cell:hover {
            opacity: 0.8;
            box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        /* カスタムカラー用 */
        .custom-color {
            background-color: var(--custom-bg-color) !important;
        }
    </style>
@endpush

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">機材スケジュール表</h1>
    </div>
@endsection

@section('content')

    <div class="p-3 sm:p-6 space-y-4 sm:space-y-6" x-data="scheduleManager()">
        <!-- 検索・フィルター -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">検索条件</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <form @submit.prevent="loadScheduleData()" class="space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                        <!-- 期間選択 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">開始日</label>
                            <input type="date" x-model="filters.start_date" @keydown.enter="loadScheduleData()"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">終了日</label>
                            <input type="date" x-model="filters.end_date" @keydown.enter="loadScheduleData()"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- カテゴリ選択 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
                            <select x-model="filters.category_id" @change="onCategoryChange()" @keydown.enter="loadScheduleData()"
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全てのカテゴリ</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- サブカテゴリ選択 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">サブカテゴリ</label>
                            <select x-model="filters.subcategory_id" @keydown.enter="loadScheduleData()"
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全てのサブカテゴリ</option>
                                <template x-for="subcategory in subcategories" :key="subcategory.id">
                                    <option :value="subcategory.id" x-text="subcategory.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 機材名検索 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">機材名</label>
                            <input type="text" x-model="filters.equipment_name" @keydown.enter="loadScheduleData()"
                                   placeholder="機材名で検索"
                                   class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- 公演名選択 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">公演名</label>
                            <select x-model="filters.performance_id" @keydown.enter="loadScheduleData()"
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">全ての公演</option>
                                <template x-for="performance in performances" :key="performance.id">
                                    <option :value="performance.id" x-text="performance.display_name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 表示件数 -->
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">表示件数</label>
                            <select x-model="filters.per_page" @keydown.enter="loadScheduleData()"
                                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="10">10件</option>
                                <option value="20">20件</option>
                                <option value="50">50件</option>
                                <option value="100" selected>100件</option>
                            </select>
                        </div>
                    </div>

                    <!-- アクションボタン -->
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="resetFilters()"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 min-w-[70px]">
                            リセット
                        </button>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 min-w-[70px]">
                            <span x-show="!loading">表示</span>
                            <span x-show="loading">読込中...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ローディング -->
        <div x-show="loading" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 sm:p-8 text-center">
            <div class="animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-b-2 border-blue-600 mx-auto mb-3 sm:mb-4"></div>
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300">データを読み込み中...</p>
        </div>

        <!-- エラー表示 -->
        <div x-show="error" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 sm:p-4">
            <div class="flex">
                <div class="ml-2 sm:ml-3">
                    <h3 class="text-xs sm:text-sm font-medium text-red-800 dark:text-red-300">エラーが発生しました</h3>
                    <div class="mt-1 sm:mt-2 text-xs sm:text-sm text-red-700 dark:text-red-400" x-text="error"></div>
                </div>
            </div>
        </div>

        <!-- スケジュール表 -->
        <div x-show="!loading && !error && scheduleData.length > 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
                        スケジュール表
                        <span class="text-xs sm:text-sm font-normal text-gray-600 dark:text-gray-400">
                            (<span x-text="scheduleData.length"></span>件表示)
                        </span>
                    </h2>

                    <!-- ページネーション情報 -->
                    <div x-show="pagination" class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="pagination?.from || 0"></span>-<span x-text="pagination?.to || 0"></span>
                        / <span x-text="pagination?.total || 0"></span>件
                    </div>
                </div>
            </div>

            <!-- ステータス凡例 -->
            <div class="px-4 sm:px-6 py-2 sm:py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">ステータス凡例</h3>
                <div class="flex flex-wrap gap-3 sm:gap-4">
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-green-500 rounded"></div>
                        <span class="text-xs text-gray-700 dark:text-gray-300">利用可能</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-blue-500 rounded"></div>
                        <span class="text-xs text-gray-700 dark:text-gray-300">予約済み</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-orange-500 rounded"></div>
                        <span class="text-xs text-gray-700 dark:text-gray-300">使用中</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 bg-red-500 rounded"></div>
                        <span class="text-xs text-gray-700 dark:text-gray-300">修理中</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">※フェーズ期間が反映されています</p>
            </div>

            <!-- Excel風テーブル -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse schedule-table" style="min-width: max-content;">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <!-- 機材情報列 -->
                                <th class="sticky left-0 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 px-1 py-1 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider z-10">
                                    機材名
                                </th>
                                <th class="sticky bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 px-1 py-1 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider z-10" style="left: 120px;">
                                    新音番号
                                </th>

                                <!-- 日付列 -->
                                <template x-for="date in dateRange" :key="date">
                                    <th class="border border-gray-300 dark:border-gray-600 px-0 py-0.5 text-center text-xs font-medium text-gray-700 dark:text-gray-300"
                                        :class="getDayHeaderClass(date)">
                                        <div class="text-xs" x-text="formatDateHeader(date)"></div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs" x-text="formatDayOfWeek(date)"></div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="equipment in scheduleData" :key="equipment.equipment_id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <!-- 機材情報 -->
                                    <td class="sticky left-0 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs font-medium text-gray-900 dark:text-white z-10">
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="equipment.subcategory"></div>
                                        <div class="text-xs" x-text="equipment.equipment_name"></div>
                                    </td>
                                    <td class="sticky bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 px-1 py-1 text-xs text-gray-600 dark:text-gray-400 z-10" style="left: 120px;">
                                        <div class="text-xs" x-text="equipment.equipment_code"></div>
                                    </td>

                                    <!-- 日別ステータス -->
                                    <template x-for="(date, dateIndex) in dateRange" :key="`${equipment.equipment_id}-${date}`">
                                        <td x-show="!isSpanMiddle(equipment, dateIndex)"
                                            class="border border-gray-300 dark:border-gray-600 p-0 text-center relative"
                                            :class="isSpanStart(equipment, dateIndex) ? 'span-cell' : ''"
                                            :colspan="isSpanStart(equipment, dateIndex) ? getSpanLength(equipment, dateIndex) : 1">

                                            <!-- スパンセルの場合 -->
                                            <template x-if="isSpanStart(equipment, dateIndex)">
                                                <div :class="getStatusClass(equipment.daily_status[date]?.status)"
                                                     class="w-full h-5 span-content flex relative"
                                                     style="overflow: visible;">
                                                    <!-- スパンテキスト（中央表示） -->
                                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0">
                                                        <span class="span-text" x-text="formatSpanText(isSpanStart(equipment, dateIndex))"></span>
                                                    </div>

                                                    <!-- 日付ごとのクリック可能領域 -->
                                                    <template x-for="(spanDate, spanOffset) in getSpanDates(equipment, dateIndex)" :key="`span-${equipment.equipment_id}-${spanDate}`">
                                                        <div @click="openMemoModal(equipment.equipment_id, spanDate, equipment.equipment_name)"
                                                             :class="getCellCustomClass(equipment.equipment_id, spanDate)"
                                                             class="clickable-cell relative z-10"
                                                             :style="`width: 40px; min-width: 40px; ${getCellCustomStyle(equipment.equipment_id, spanDate)}`"
                                                             :title="`${spanDate} - クリックしてメモ入力`">
                                                            <!-- メモ表示 -->
                                                            <span x-show="getCellMemo(equipment.equipment_id, spanDate)"
                                                                  class="cell-memo"
                                                                  x-text="formatCellMemo(equipment.equipment_id, spanDate)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <!-- 通常セルの場合 -->
                                            <template x-if="!isSpanStart(equipment, dateIndex)">
                                                <div @click="openMemoModal(equipment.equipment_id, date, equipment.equipment_name)"
                                                     :class="[getStatusClass(equipment.daily_status[date]?.status), getCellCustomClass(equipment.equipment_id, date)]"
                                                     class="w-full h-5 clickable-cell relative"
                                                     :style="getCellCustomStyle(equipment.equipment_id, date)"
                                                     :title="getStatusTooltip(equipment.daily_status[date])">
                                                    <!-- メモ表示 -->
                                                    <span x-show="getCellMemo(equipment.equipment_id, date)"
                                                          class="cell-memo"
                                                          x-text="formatCellMemo(equipment.equipment_id, date)"></span>
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
            <div x-show="pagination.last_page > 1" class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="pagination.from"></span>-<span x-text="pagination.to"></span>
                        / <span x-text="pagination.total"></span>件
                    </div>
                    <div class="flex space-x-2">
                        <button @click="loadPage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border border-gray-300 dark:border-gray-600 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            前へ
                        </button>
                        <span class="px-2 sm:px-3 py-1 text-xs sm:text-sm dark:text-gray-200">
                            <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                        </span>
                        <button @click="loadPage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="px-2 sm:px-3 py-1 text-xs sm:text-sm border border-gray-300 dark:border-gray-600 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            次へ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- データなし -->
        <div x-show="!loading && !error && scheduleData.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 sm:p-8 text-center">
            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300">該当するデータがありません。フィルター条件を変更してください。</p>
        </div>

        <!-- メモ入力モーダル -->
        <div x-show="showMemoModal"
             x-cloak
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="closeMemoModal()">
            <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-4 sm:p-5 border border-gray-200 dark:border-gray-700 max-w-sm sm:w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-2 sm:mt-3">
                    <h3 class="text-base sm:text-lg font-medium leading-6 text-gray-900 dark:text-white mb-3 sm:mb-4">セルの編集</h3>

                    <!-- 機材・日付情報 -->
                    <div class="mb-3 sm:mb-4 p-2 sm:p-3 bg-gray-50 dark:bg-gray-700 rounded text-xs sm:text-sm">
                        <div class="font-medium text-gray-700 dark:text-gray-300" x-text="currentCell.equipmentName"></div>
                        <div class="text-gray-500 dark:text-gray-400 text-xs" x-text="currentCell.date"></div>
                    </div>

                    <!-- メモ入力 -->
                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">メモ</label>
                        <textarea
                            x-model="currentCell.memo"
                            rows="3"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-2 sm:px-3 py-1.5 sm:py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="メモを入力してください"
                        ></textarea>
                    </div>

                    <!-- 色選択 -->
                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2">背景色</label>
                        <div class="grid grid-cols-6 gap-1.5 sm:gap-2">
                            <button
                                type="button"
                                @click="currentCell.customColor = ''"
                                class="w-full h-7 sm:h-8 border-2 rounded"
                                :class="currentCell.customColor === '' ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300'"
                                title="デフォルト">
                                <span class="text-xs">デフォ</span>
                            </button>
                            <template x-for="color in colorPalette" :key="color.value">
                                <button
                                    type="button"
                                    @click="currentCell.customColor = color.value"
                                    class="w-full h-7 sm:h-8 border-2 rounded"
                                    :style="`background-color: ${color.value}`"
                                    :class="currentCell.customColor === color.value ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300'"
                                    :title="color.name">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end space-x-2 sm:space-x-3 mt-4 sm:mt-6">
                        <button
                            type="button"
                            @click="clearCellData()"
                            class="px-3 sm:px-4 py-1.5 sm:py-2 bg-red-500 text-white text-xs sm:text-sm font-medium rounded-md hover:bg-red-600">
                            クリア
                        </button>
                        <button
                            type="button"
                            @click="closeMemoModal()"
                            class="px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs sm:text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                            キャンセル
                        </button>
                        <button
                            type="button"
                            @click="saveCellData()"
                            class="px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-md hover:bg-blue-700">
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- Alpine.jsスコープ終了 -->

