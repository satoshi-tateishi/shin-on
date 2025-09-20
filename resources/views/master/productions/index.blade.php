@extends('layouts.master')

@section('title', 'プロダクションマスタ')

@section('breadcrumb')
    > <span class="text-gray-400">マスタ管理</span> > <span class="text-gray-800">プロダクション</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">プロダクションマスタ</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <!-- CSV Functions -->
            <div class="flex space-x-2">
                <a href="{{ route('master.productions.export-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV出力
                </a>

                <button type="button" onclick="showImportModal('{{ route('master.productions.import-csv') }}')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    CSV取込
                </button>

                <a href="{{ route('master.productions.template-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    テンプレート
                </a>
            </div>

            <a href="{{ route('master.productions.create') }}"
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
        <!-- Search and Filters -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">検索</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="プロダクション名で検索" autocomplete="off"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">タイプ</label>
                    <select name="type" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">有効状態</label>
                    <select name="is_active" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>有効のみ</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>無効のみ</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        @if($productions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(in_array(auth()->user()->role, ['editor', 'admin']) && !request('search') && !request('type') && !request('is_active'))
                                <th class="pl-6 pr-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">順序</th>
                            @endif
                            @if(!request('search') && !request('type') && !request('is_active'))
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                    ソート
                                </th>
                            @endif
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                タイプ
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                プロダクション名
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                住所
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white divide-y divide-gray-200">
                        @foreach($productions as $production)
                            <tr class="hover:bg-gray-50 @if(in_array(auth()->user()->role, ['editor', 'admin']) && !request('search') && !request('type') && !request('is_active')) sortable-row @endif @if(auth()->user()->role !== 'admin') cursor-pointer @endif" data-id="{{ $production->id }}" @if(auth()->user()->role !== 'admin') onclick="window.location.href='{{ route('master.productions.show', $production) }}'" @endif>
                                @if(in_array(auth()->user()->role, ['editor', 'admin']) && !request('search') && !request('type') && !request('is_active'))
                                    <td class="pl-6 pr-2 py-2 whitespace-nowrap text-center">
                                        <svg class="drag-handle w-5 h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                @if(!request('search') && !request('type') && !request('is_active'))
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 text-center @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.productions.show', $production) }}'" @endif>
                                        {{ $production->sort }}
                                    </td>
                                @endif
                                <td class="px-2 py-2 whitespace-nowrap @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.productions.show', $production) }}'" @endif>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @switch($production->type)
                                            @case('株式会社') bg-blue-100 text-blue-800 @break
                                            @case('有限会社') bg-green-100 text-green-800 @break
                                            @case('合同会社') bg-purple-100 text-purple-800 @break
                                            @case('財団法人') bg-orange-100 text-orange-800 @break
                                            @case('公益財団法人') bg-red-100 text-red-800 @break
                                            @case('その他') bg-gray-100 text-gray-800 @break
                                            @default bg-gray-100 text-gray-800 @break
                                        @endswitch">
                                        {{ $production->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.productions.show', $production) }}'" @endif>
                                    <div class="text-sm font-medium text-gray-900 truncate">
                                        {{ $production->name }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.productions.show', $production) }}'" @endif>
                                    {{ $production->address ?: '---' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">データがありません</h3>
                <p class="mt-1 text-sm text-gray-500">検索条件を変更するか、新しいプロダクションを作成してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('master.productions.create') }}"
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

    @if(in_array(auth()->user()->role, ['editor', 'admin']) && !request('search') && !request('type') && !request('is_active'))
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
                        const productionIds = Array.from(tbody.querySelectorAll('.sortable-row')).map(row => row.dataset.id);

                        fetch('{{ route('master.productions.update-sort') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                production_ids: productionIds
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