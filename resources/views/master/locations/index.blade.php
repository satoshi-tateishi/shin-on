@extends('layouts.master')

@section('title', '使用場所マスタ')

@section('breadcrumb')
    > <span class="text-gray-400 dark:text-gray-500">マスタ管理</span> > <span class="text-gray-800 dark:text-gray-200">使用場所</span>
@endsection

@section('header')
    <div>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">使用場所マスタ</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex gap-2 sm:gap-3 ml-auto">
            @if(request('sort_mode') === 'all')
                <a href="{{ route('master.locations.index', array_merge(request()->query(), ['sort_mode' => null])) }}"
                   class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-gray-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="hidden sm:inline">ページ表示に戻る</span>
                    <span class="sm:hidden">戻る</span>
                </a>
            @else
                <a href="{{ route('master.locations.index', array_merge(request()->query(), ['sort_mode' => 'all'])) }}"
                   class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-purple-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-purple-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    <span class="hidden sm:inline">全件表示してソート</span>
                    <span class="sm:hidden">ソート</span>
                </a>
            @endif
            <a href="{{ route('master.locations.create') }}"
               class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規作成
            </a>

            @if(auth()->user()->role === 'admin')
            <!-- CSV Menu (Dropdown) - Admin Only -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 z-50">
                    <div class="py-1">
                        <a href="{{ route('master.locations.export-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVエクスポート
                        </a>
                        <button type="button" @click="open = false; window.dispatchEvent(new CustomEvent('open-csv-import-modal', { detail: { actionUrl: '{{ route('master.locations.import-csv') }}' }}))"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            CSVインポート
                        </button>
                        <a href="{{ route('master.locations.template-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVテンプレート
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    @endif
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <!-- Search and Filters -->
        <div class="mb-4 sm:mb-6 bg-gray-50 dark:bg-gray-700 p-3 sm:p-4 rounded-lg">
            <form method="GET" class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">検索</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="場所名で検索" autocomplete="off"
                           class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">有効状態</label>
                    <select name="is_active" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>有効のみ</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>無効のみ</option>
                    </select>
                </div>

                <div class="flex items-end col-span-2 sm:col-span-1">
                    <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 text-sm rounded-md hover:bg-gray-700">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        @if($locations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                <th class="pl-4 sm:pl-6 pr-2 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12 sm:w-16">順序</th>
                            @endif
                            @if(request('sort_mode') !== 'all')
                                <th class="px-2 py-2 sm:py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12 sm:w-16">
                                    No.
                                </th>
                            @endif
                            <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                場所名
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($locations as $location)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all') sortable-row @endif @if(auth()->user()->role !== 'admin') cursor-pointer @endif" data-id="{{ $location->id }}" @if(auth()->user()->role !== 'admin') onclick="window.location.href='{{ route('master.locations.show', $location) }}'" @endif>
                                @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                    <td class="pl-4 sm:pl-6 pr-2 py-2 whitespace-nowrap text-center">
                                        <svg class="drag-handle w-4 h-4 sm:w-5 sm:h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                @if(request('sort_mode') !== 'all')
                                    <td class="px-2 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-900 dark:text-white text-center @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.locations.show', $location) }}'" @endif>
                                        {{ $loop->iteration + (method_exists($locations, 'currentPage') ? ($locations->currentPage() - 1) * $locations->perPage() : 0) }}
                                    </td>
                                @endif
                                <td class="px-3 sm:px-4 py-2 @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.locations.show', $location) }}'" @endif>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $location->name }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(request('sort_mode') !== 'all' && method_exists($locations, 'hasPages') && $locations->hasPages())
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{ $locations->currentPage() }} / {{ ceil($totalLocations / 50) }} ページ
                    </span>
                    <div>
                        {{ $locations->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif

            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M8 14v20c0 4.418 7.163 8 16 8 1.381 0 2.721-.087 4-.252M8 14c0 4.418 7.163 8 16 8s16-3.582 16-8M8 14c0-4.418 7.163-8 16-8s16 3.582 16 8m0 0v14m0-4c0 4.418-7.163 8-16 8S8 28.418 8 24m32 10v6c0 2.21-1.79 4-4 4H12c-2.21 0-4-1.79-4-4v-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">データがありません</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">検索条件を変更するか、新しい場所・倉庫を作成してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('master.locations.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            新規作成
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tbody = document.getElementById('sortable-tbody');
                if (tbody) {
                    let draggedElement = null;

                    // 各行にドラッグイベントリスナーを追加
                    tbody.querySelectorAll('.sortable-row').forEach(row => {
                        row.setAttribute('draggable', 'true');

                        row.addEventListener('dragstart', function(e) {
                            draggedElement = this;
                            this.style.opacity = '0.5';
                        });

                        row.addEventListener('dragend', function(e) {
                            this.style.opacity = '';
                            draggedElement = null;
                        });

                        row.addEventListener('dragover', function(e) {
                            e.preventDefault();
                        });

                        row.addEventListener('drop', function(e) {
                            e.preventDefault();
                            if (draggedElement && draggedElement !== this) {
                                const allRows = Array.from(tbody.querySelectorAll('.sortable-row'));
                                const draggedIndex = allRows.indexOf(draggedElement);
                                const targetIndex = allRows.indexOf(this);

                                if (draggedIndex < targetIndex) {
                                    this.parentNode.insertBefore(draggedElement, this.nextSibling);
                                } else {
                                    this.parentNode.insertBefore(draggedElement, this);
                                }

                                updateSortOrder();
                            }
                        });

                        // ドラッグハンドルのマウスイベント
                        const dragHandle = row.querySelector('.drag-handle');
                        if (dragHandle) {
                            dragHandle.addEventListener('mouseenter', function() {
                                this.style.color = '#6b7280';
                            });

                            dragHandle.addEventListener('mouseleave', function() {
                                this.style.color = '#9ca3af';
                            });
                        }
                    });

                    // ソート順をサーバーに送信
                    function updateSortOrder() {
                        const locationIds = Array.from(tbody.querySelectorAll('.sortable-row')).map(row => row.dataset.id);

                        fetch('{{ route('master.locations.update-sort') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                location_ids: locationIds
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // ソート番号の表示を更新
                                tbody.querySelectorAll('.sortable-row').forEach((row, index) => {
                                    const sortCell = row.querySelector('td:nth-child(2)');
                                    if (sortCell) {
                                        sortCell.textContent = index + 1;
                                    }
                                });
                            } else {
                                alert('ソート順の更新に失敗しました: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('ソート順の更新中にエラーが発生しました。');
                        });
                    }
                }
            });
        </script>
    @endif
@endsection
