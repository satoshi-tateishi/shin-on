@extends('layouts.master')

@section('title', '機材マスタ')

@section('breadcrumb')
    > <span class="text-gray-400">機材関連マスタ</span> > <span class="text-gray-800">機材マスタ 一覧</span>
@endsection

@section('header')
    <div>
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900">機材マスタ 一覧</h1>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex gap-2 sm:gap-3 ml-auto">
            @if(request('sort_mode') === 'all')
                <a href="{{ route('master.equipments.index', array_merge(request()->query(), ['sort_mode' => null])) }}"
                   class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-gray-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="hidden sm:inline">ページ表示に戻る</span>
                    <span class="sm:hidden">戻る</span>
                </a>
            @else
                <a href="{{ route('master.equipments.index', array_merge(request()->query(), ['sort_mode' => 'all'])) }}"
                   class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-orange-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-orange-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    <span class="hidden sm:inline">全件表示してソート</span>
                    <span class="sm:hidden">ソート</span>
                </a>
            @endif

            <a href="{{ route('master.equipments.create') }}"
               class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規作成
            </a>

            <!-- CSV Menu (Dropdown) -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50">
                    <div class="py-1">
                        <a href="{{ route('master.equipments.export-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVエクスポート
                        </a>
                        <button type="button" @click="open = false; document.getElementById('csv-import-modal').classList.remove('hidden')"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            CSVインポート
                        </button>
                        <a href="{{ route('master.equipments.template-csv') }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSVテンプレート
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <!-- Search and Filters -->
        <div class="mb-4 sm:mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg @if(request('sort_mode') === 'all') opacity-50 pointer-events-none @endif">
            @if(request('sort_mode') === 'all')
                <div class="mb-2 p-2 bg-orange-100 border border-orange-200 rounded-md">
                    <p class="text-xs sm:text-sm text-orange-800">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        ソートモード中です。検索・フィルターを使用するには「ページ表示に戻る」をクリックしてください。
                    </p>
                </div>
            @endif
            <form method="GET" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-4" autocomplete="off">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">検索</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="機材名・モデル名・シリアル番号で検索"
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                    <select name="category_id" id="category-select" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">サブカテゴリ</label>
                    <select name="subcategory_id" id="subcategory-select" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">基本倉庫</label>
                    <select name="location_id" id="location-select" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">状態</label>
                    <select name="status" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>利用可能</option>
                        <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>使用中</option>
                        <option value="broken" {{ request('status') == 'broken' ? 'selected' : '' }}>故障</option>
                        <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>廃止</option>
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
        @if($equipments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                <th class="pl-3 sm:pl-6 pr-2 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10 sm:w-16">順序</th>
                            @endif
                            <th class="px-1 sm:px-3 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12 sm:w-auto">
                                カテゴリ
                            </th>
                            <th class="px-1 sm:px-3 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                機材名
                            </th>
                            <th class="px-1 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-14 sm:w-auto">
                                新音番号
                            </th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                数量
                            </th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                基本倉庫
                            </th>
                            <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                現在地
                            </th>
                        </tr>
                    </thead>
                    <tbody id="sortable-tbody" class="bg-white divide-y divide-gray-200">
                        @foreach($equipments as $equipment)
                            <tr class="hover:bg-gray-50 cursor-pointer @if(in_array(auth()->user()->role, ['editor', 'admin'])) sortable-row @endif"
                                data-id="{{ $equipment->id }}"
                                onclick="window.location.href='{{ route('master.equipments.show', $equipment) }}'">
                                @if(in_array(auth()->user()->role, ['editor', 'admin']) && request('sort_mode') === 'all')
                                    <td class="pl-3 sm:pl-6 pr-2 py-2 whitespace-nowrap text-center" onclick="event.stopPropagation();">
                                        <svg class="drag-handle w-4 h-4 sm:w-5 sm:h-5 text-gray-400 cursor-move" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 2zM7 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 8zM7 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 7 14zM13 2a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 2zM13 8a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 8zM13 14a2 2 0 1 1 .001 4.001A2 2 0 0 1 13 14z"></path>
                                        </svg>
                                    </td>
                                @endif
                                <td class="px-1 sm:px-3 py-2 text-[10px] sm:text-xs text-gray-500 align-top">
                                    <div class="truncate max-w-[80px] sm:max-w-none">{{ $equipment->category->name ?? '---' }}</div>
                                    @if($equipment->subcategory)
                                        <div class="text-gray-400 truncate max-w-[80px] sm:max-w-none">{{ $equipment->subcategory->name }}</div>
                                    @endif
                                </td>
                                <td class="px-1 sm:px-3 py-2 text-[10px] sm:text-xs text-gray-500 align-top max-w-0">
                                    @if($equipment->manufacturer)
                                        <div class="truncate">{{ $equipment->manufacturer }}</div>
                                    @endif
                                    <div class="text-gray-900 truncate">{{ $equipment->name }}</div>
                                </td>
                                <td class="px-1 py-2 whitespace-nowrap text-left align-middle">
                                    @if($equipment->company_number)
                                        <span class="px-1 py-0.5 border border-gray-300 rounded text-xs text-gray-600">
                                            {{ $equipment->company_number }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-2 sm:px-4 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden sm:table-cell">
                                    {{ $equipment->quantity }}{{ $equipment->unit }}
                                </td>
                                <td class="px-2 sm:px-4 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden md:table-cell">
                                    {{ $equipment->location->name ?? '---' }}
                                </td>
                                <td class="px-2 sm:px-4 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden lg:table-cell">
                                    @if($equipment->now_location_id)
                                        @if($equipment->now_location_id !== $equipment->location_id)
                                            <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ $equipment->nowLocation->name ?? $equipment->now_location_id }}
                                            </span>
                                        @else
                                            {{ $equipment->nowLocation->name ?? $equipment->now_location_id }}
                                        @endif
                                    @else
                                        <span class="text-gray-400">---</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination (通常モード時のみ) -->
            @if(request('sort_mode') !== 'all' && method_exists($equipments, 'links'))
                <div class="mt-4 sm:mt-6">
                    {{ $equipments->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-8 sm:py-12">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-xs sm:text-sm font-medium text-gray-900">データがありません</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500">検索条件を変更するか、新しい機材を作成してください。</p>
                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <div class="mt-4 sm:mt-6">
                        <a href="{{ route('master.equipments.create') }}"
                           class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-transparent shadow-sm text-xs sm:text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
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
        <div class="relative top-10 sm:top-20 mx-4 sm:mx-auto p-4 sm:p-5 border w-full sm:w-96 shadow-lg rounded-md bg-white">
            <div class="mt-2 sm:mt-3">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">CSVファイル取込</h3>

                <form action="{{ route('master.equipments.import-csv') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                    @csrf
                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">CSVファイルを選択</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="w-full p-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            ※ CSVファイル（UTF-8形式）を選択してください。<br>
                            ※ ファイルサイズは2MB以内でお願いします
                        </p>
                    </div>

                    <div class="flex items-center justify-end pt-3 sm:pt-4 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('csv-import-modal').classList.add('hidden')"
                                class="px-3 sm:px-4 py-1.5 sm:py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium hover:bg-gray-50 mr-2 sm:mr-3">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-3 sm:px-4 py-1.5 sm:py-2 bg-blue-600 text-white border border-transparent rounded-md shadow-sm text-xs sm:text-sm font-medium hover:bg-blue-700">
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
