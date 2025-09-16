@extends('layouts.master')

@section('title', 'ユーザーマスタ')

@section('breadcrumb')
    > <span class="text-gray-400">マスタ管理</span> > <span class="text-gray-800">ユーザー</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">ユーザーマスタ</h1>
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <!-- CSV Functions -->
            <div class="flex space-x-2">
                <a href="{{ route('master.users.export-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSVエクスポート
                </a>

                <a href="{{ route('master.users.template-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSVテンプレート
                </a>

                <button onclick="document.getElementById('csv-import-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    CSVインポート
                </button>
            </div>

            <a href="{{ route('master.users.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規登録
            </a>
        </div>
    @endif
@endsection

@section('content')
    <div class="p-6">
        <!-- Status Filter Buttons -->
        <div class="mb-6">
            <div class="flex space-x-4">
                <a href="{{ route('master.users.index', ['status' => 'active']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status', 'active') == 'active' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    在職中
                </a>
                <a href="{{ route('master.users.index', ['status' => 'on_leave']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') == 'on_leave' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    休職中
                </a>
                <a href="{{ route('master.users.index', ['status' => 'resigned']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition-colors {{ request('status') == 'resigned' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                    退職済み
                </a>
            </div>
        </div>

        <!-- Results Table -->
        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(auth()->user()->role === 'admin')
                                <th class="pl-6 pr-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">順序</th>
                            @endif
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                ソート
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                氏名
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                年齢
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                勤続年数
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                所属
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50 @if(auth()->user()->role === 'admin') sortable-row @endif @if($user->is_resigned) bg-red-50 opacity-75 @elseif($user->is_on_leave) bg-yellow-50 @endif" data-id="{{ $user->id }}" @if(auth()->user()->role !== 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" style="cursor: pointer;" @endif>
                                @if(auth()->user()->role === 'admin')
                                    <td class="pl-6 pr-2 py-2 whitespace-nowrap text-center">
                                        <svg class="drag-handle w-5 h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 text-center @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" @endif>
                                    {{ $user->sort ?? '-' }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" @endif>
                                    <div class="flex items-center">
                                        @if($user->icon)
                                            <img class="h-10 w-10 rounded-full mr-3" src="{{ $user->icon }}" alt="{{ $user->name }}">
                                        @else
                                            <div class="w-10 h-10 bg-gray-300 rounded-full mr-3 flex items-center justify-center">
                                                <span class="text-gray-600 text-sm">{{ mb_substr($user->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 @if($user->is_resigned) line-through text-gray-500 @endif">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" @endif>
                                    @if($user->birthday)
                                        {{ \Carbon\Carbon::parse($user->birthday)->age }}歳
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500 @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" @endif>
                                    @if($user->hired_at)
                                        @if($user->resigned_at)
                                            {{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::parse($user->resigned_at))->format('%y年%mヶ月') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($user->hired_at)->diff(\Carbon\Carbon::now())->format('%y年%mヶ月') }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap @if(auth()->user()->role === 'admin') cursor-pointer @endif" @if(auth()->user()->role === 'admin') onclick="window.location.href='{{ route('master.users.show', $user) }}'" @endif>
                                    @switch($user->affiliation)
                                        @case('employee')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                社員
                                            </span>
                                            @break
                                        @case('partner')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                パートナー
                                            </span>
                                            @break
                                        @default
                                            <span class="text-gray-400 text-xs">-</span>
                                            @break
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">ユーザーが見つかりません</h3>
                <p class="mt-1 text-sm text-gray-500">検索条件を変更してください。新しいユーザーはLINE WORKS認証で自動登録されます。</p>
            </div>
        @endif
    </div>

    @if(auth()->user()->role === 'admin')
        <!-- CSV Import Modal -->
        <div id="csv-import-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">CSVインポート</h3>
                        <button onclick="document.getElementById('csv-import-modal').classList.add('hidden')"
                                class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('master.users.import-csv') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">CSVファイル</label>
                            <input type="file" name="csv_file" accept=".csv,.txt" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500">
                                ※ まずテンプレートをダウンロードしてご利用ください
                            </p>
                        </div>

                        <div class="bg-blue-50 p-3 rounded-md mb-4">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">インポート仕様</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• name, email, role, affiliation は必須項目です</li>
                                <li>• role: admin, editor, viewer</li>
                                <li>• affiliation: employee, partner</li>
                                <li>• ブール項目: 1, true, はい, Yes で有効</li>
                                <li>• 同じemailのユーザーは更新されます</li>
                                <li>• ヘッダーはusersテーブルのカラム名と一致します</li>
                            </ul>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                    onclick="document.getElementById('csv-import-modal').classList.add('hidden')"
                                    class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                キャンセル
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                インポート実行
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if(auth()->user()->role === 'admin')
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
                            const userIds = Array.from(tbody.querySelectorAll('.sortable-row')).map(row => row.dataset.id);

                            fetch('{{ route('master.users.update-sort') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    user_ids: userIds
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // ソート番号の表示を更新
                                    tbody.querySelectorAll('.sortable-row').forEach((row, index) => {
                                        const sortCell = row.querySelector('td:nth-child({{ auth()->user()->role === 'admin' ? '2' : '1' }})');
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
                @endif
            });
        </script>
    @endif
@endsection
