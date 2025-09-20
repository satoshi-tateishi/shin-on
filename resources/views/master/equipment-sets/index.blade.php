@extends('layouts.master')

@section('title', '機材セットマスタ')

@section('breadcrumb')
    > <span class="text-gray-400">機材関連マスタ</span> > <span class="text-gray-800">機材セットマスタ</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材セットマスタ</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <!-- CSV Functions -->
            <div class="flex space-x-2">
                <a href="{{ route('master.equipment-sets.export-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV出力
                </a>

                <button type="button" onclick="showImportModal('{{ route('master.equipment-sets.import-csv') }}')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    CSV取込
                </button>

                <a href="{{ route('master.equipment-sets.template-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    テンプレート
                </a>
            </div>

            <a href="{{ route('master.equipment-sets.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規作成
            </a>
        </div>
    @endif
@endsection

@section('content')
    <div class="p-6">
        <!-- Status Filter Buttons -->
        <div class="mb-6">
            <div class="flex space-x-4">
                <a href="{{ route('master.equipment-sets.index', ['is_active' => '1']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('is_active', '1') == '1' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    有効
                </a>
                <a href="{{ route('master.equipment-sets.index', ['is_active' => '0']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('is_active') == '0' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    無効
                </a>
                <a href="{{ route('master.equipment-sets.index') }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('is_active') === null ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    すべて
                </a>
            </div>
        </div>

        <!-- Results Table -->
        @if($equipmentSets->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('is_active') === null)
                                <th class="pl-6 pr-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">順序</th>
                            @endif
                            @if(request('is_active') === null)
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                    ソート
                                </th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                セット名
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                状態
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white divide-y divide-gray-200">
                        @foreach($equipmentSets as $equipmentSet)
                            <tr class="hover:bg-gray-50 @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('is_active') === null) sortable-row @endif @if(!$equipmentSet->is_active) bg-red-50 opacity-75 @endif"
                                data-id="{{ $equipmentSet->id }}"
                                @if(!in_array(auth()->user()->role, ['editor', 'admin'])) onclick="window.location.href='{{ route('master.equipment-sets.show', $equipmentSet) }}'" style="cursor: pointer;" @endif>
                                @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('is_active') === null)
                                    <td class="pl-6 pr-2 py-2 whitespace-nowrap text-center">
                                        <svg class="drag-handle w-5 h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                @if(request('is_active') === null)
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 text-center @if(in_array(auth()->user()->role, ['editor', 'admin'])) cursor-pointer @endif"
                                        @if(in_array(auth()->user()->role, ['editor', 'admin'])) onclick="window.location.href='{{ route('master.equipment-sets.show', $equipmentSet) }}'" @endif>
                                        {{ $equipmentSet->sort ?? '-' }}
                                    </td>
                                @endif
                                <td class="px-6 py-2 whitespace-nowrap @if(in_array(auth()->user()->role, ['editor', 'admin'])) cursor-pointer @endif"
                                    @if(in_array(auth()->user()->role, ['editor', 'admin'])) onclick="window.location.href='{{ route('master.equipment-sets.show', $equipmentSet) }}'" @endif>
                                    <div class="text-sm font-medium text-gray-900 @if(!$equipmentSet->is_active) line-through text-gray-500 @endif">
                                        {{ $equipmentSet->name }}
                                    </div>
                                    @if($equipmentSet->description)
                                        <div class="text-sm text-gray-500">
                                            {{ Str::limit($equipmentSet->description, 50) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap @if(in_array(auth()->user()->role, ['editor', 'admin'])) cursor-pointer @endif"
                                    @if(in_array(auth()->user()->role, ['editor', 'admin'])) onclick="window.location.href='{{ route('master.equipment-sets.show', $equipmentSet) }}'" @endif>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $equipmentSet->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $equipmentSet->is_active ? '有効' : '無効' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">機材セットが見つかりません</h3>
                <p class="mt-1 text-sm text-gray-500">フィルター条件を変更するか、新しい機材セットを作成してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('master.equipment-sets.create') }}"
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

    @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('is_active') === null)
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
                        const rows = Array.from(tbody.querySelectorAll('.sortable-row'));
                        const items = rows.map((row, index) => ({
                            id: parseInt(row.dataset.id),
                            sort: index + 1
                        }));

                        fetch('{{ route('master.equipment-sets.update-sort') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                items: items
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
