@extends('layouts.master')

@section('title', '機材マスタ')

@section('breadcrumb')
    > <span class="text-gray-400">機材関連マスタ</span> > <span class="text-gray-800">機材マスタ 一覧</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材マスタ 一覧</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <!-- CSV Functions -->
            <div class="flex space-x-2">
                <a href="{{ route('master.equipments.export-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV出力
                </a>

                <button onclick="document.getElementById('csv-import-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    CSV取込
                </button>

                <a href="{{ route('master.equipments.template-csv') }}"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    テンプレート
                </a>
            </div>

            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                @if(request('sort_mode') === 'all')
                    <a href="{{ route('master.equipments.index', array_merge(request()->query(), ['sort_mode' => null])) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        ページ表示に戻る
                    </a>
                @else
                    <a href="{{ route('master.equipments.index', array_merge(request()->query(), ['sort_mode' => 'all'])) }}"
                       class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-orange-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                        全件表示してソート
                    </a>
                @endif
            @endif

            <a href="{{ route('master.equipments.create') }}"
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
        <div class="mb-6 bg-gray-50 p-4 rounded-lg @if(request('sort_mode') === 'all') opacity-50 pointer-events-none @endif">
            @if(request('sort_mode') === 'all')
                <div class="mb-2 p-2 bg-orange-100 border border-orange-200 rounded-md">
                    <p class="text-sm text-orange-800">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        ソートモード中です。検索・フィルターを使用するには「ページ表示に戻る」をクリックしてください。
                    </p>
                </div>
            @endif
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4" autocomplete="off">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">検索</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="機材名・モデル名・シリアル番号で検索"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                    <select name="category_id" id="category-select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">サブカテゴリ</label>
                    <select name="subcategory_id" id="subcategory-select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @php
                            $groupedSubcategories = $subcategories->groupBy('category.name');
                        @endphp
                        @foreach($groupedSubcategories as $categoryName => $subcategoryGroup)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($subcategoryGroup as $subcategory)
                                    <option value="{{ $subcategory->id }}"
                                            data-category-id="{{ $subcategory->category_id }}"
                                            {{ request('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">場所</label>
                    <select name="location_id" id="location-select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">状態</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>利用可能</option>
                        <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>使用中</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>メンテナンス中</option>
                        <option value="broken" {{ request('status') == 'broken' ? 'selected' : '' }}>故障</option>
                        <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>廃止</option>
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
        @if($equipments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">順序</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                分類
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材名
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                新音番号
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                数量
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                場所
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white divide-y divide-gray-200">
                        @foreach($equipments as $equipment)
                            <tr class="hover:bg-gray-50 cursor-pointer @if(in_array(auth()->user()->role, ['editor', 'admin'])) sortable-row @endif"
                                data-id="{{ $equipment->id }}"
                                onclick="window.location.href='{{ route('master.equipments.show', $equipment) }}'">
                                @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                    <td class="px-6 py-4 whitespace-nowrap text-center" onclick="event.stopPropagation();">
                                        <svg class="drag-handle w-5 h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="text-sm text-gray-500">{{ $equipment->category->name ?? '---' }}</div>
                                    @if($equipment->subcategory)
                                        <div class="text-sm text-gray-500">{{ $equipment->subcategory->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($equipment->manufacturer)
                                        <div class="text-xs text-gray-400">{{ $equipment->manufacturer }}</div>
                                    @endif
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $equipment->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $equipment->company_number ?: '---' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $equipment->quantity }}{{ $equipment->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $equipment->location->name ?? '---' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination (通常モード時のみ) -->
            @if(request('sort_mode') !== 'all' && method_exists($equipments, 'links'))
                <div class="mt-6">
                    {{ $equipments->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">データがありません</h3>
                <p class="mt-1 text-sm text-gray-500">検索条件を変更するか、新しい機材を作成してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-6">
                        <a href="{{ route('master.equipments.create') }}"
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

    <!-- CSV Import Modal -->
    @if(in_array(auth()->user()->role, ['editor', 'admin']))
    <div id="csv-import-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">CSVファイル取込</h3>

                <form action="{{ route('master.equipments.import-csv') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">CSVファイルを選択</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            ※ CSVファイル（UTF-8形式）を選択してください。<br>
                            ※ ファイルサイズは2MB以内でお願いします
                        </p>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('csv-import-modal').classList.add('hidden')"
                                class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 mr-3">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-700">
                            取込実行
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- 機材マスタ専用JavaScript -->
    <script src="{{ asset('js/master/equipment-index.js') }}"></script>
@endsection
