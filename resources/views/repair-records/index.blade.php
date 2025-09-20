@extends('layouts.app')

@section('title', '修理管理')

@section('breadcrumb')
    > <span class="text-gray-800">修理管理</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">修理管理</h1>
        <p class="mt-1 text-sm text-gray-600">機材の修理・メンテナンス記録管理</p>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <a href="{{ route('repair-records.create') }}"
               class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                修理報告
            </a>
        </div>
    @endif
@endsection

@section('content')
<!-- 統計サマリー -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">総修理件数</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $stats['total'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">報告済み</dt>
                        <dd class="text-lg font-medium text-yellow-600">{{ $stats['reported'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">修理中</dt>
                        <dd class="text-lg font-medium text-blue-600">{{ $stats['in_progress'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">完了</dt>
                        <dd class="text-lg font-medium text-green-600">{{ $stats['completed'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- フィルター -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" action="{{ route('repair-records.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- ステータスフィルター -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
                    <select name="status" id="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">全て</option>
                        <option value="reported" {{ request('status') === 'reported' ? 'selected' : '' }}>報告済み</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>修理中</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>完了</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                    </select>
                </div>

                <!-- 機材カテゴリフィルター -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
                    <select name="equipment_category" id="category-select" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">すべて</option>
                        @foreach($equipmentCategories as $category)
                            <option value="{{ $category->id }}" {{ request('equipment_category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 機材サブカテゴリフィルター -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">サブカテゴリ</label>
                    <select name="equipment_subcategory" id="subcategory-select" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">すべて</option>
                        @php
                            $groupedSubcategories = $equipmentSubcategories->groupBy('category.name');
                        @endphp
                        @foreach($groupedSubcategories as $categoryName => $subcategoryGroup)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($subcategoryGroup as $subcategory)
                                    <option value="{{ $subcategory->id }}"
                                            data-category-id="{{ $subcategory->category_id }}"
                                            {{ request('equipment_subcategory') == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- 検索 -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">検索</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="機材名・問題内容・修理内容・業者名で検索"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('repair-records.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    クリア
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    検索
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 修理記録一覧 -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">修理記録一覧</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $repairRecords->total() }}件の修理記録</p>
    </div>

    @if($repairRecords->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">機材</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">修理開始日</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">修理完了日</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($repairRecords as $record)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='{{ route('repair-records.show', $record) }}';">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm text-gray-500">{{ $record->equipment->subcategory->category->name }}　{{ $record->equipment->subcategory->name }}</div>
                                        <div class="text-sm font-medium text-gray-900 flex items-center">
                                            {{ $record->equipment->name }}
                                            @if($record->equipment->company_number)
                                                <span class="ml-2 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50">{{ $record->equipment->company_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($record->status === 'reported') bg-yellow-100 text-yellow-800
                                    @elseif($record->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($record->status === 'completed') bg-green-100 text-green-800
                                    @elseif($record->status === 'cancelled') bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $record->status_display }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->started_at ? $record->started_at->format('Y/m/d') : '---' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->completed_at ? $record->completed_at->format('Y/m/d') : '---' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $repairRecords->appends(request()->query())->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">修理記録がありません</h3>
            <p class="mt-1 text-sm text-gray-500">新しい修理記録を作成してください。</p>
            @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                <div class="mt-6">
                    <a href="{{ route('repair-records.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                        修理報告
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
// カテゴリ連動のサブカテゴリフィルタリング
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category-select');
    const subcategorySelect = document.getElementById('subcategory-select');

    if (!categorySelect || !subcategorySelect) {
        return;
    }

    // 全てのサブカテゴリオプションを保存
    const allSubcategoryOptions = [];
    subcategorySelect.querySelectorAll('option[data-category-id]').forEach(option => {
        allSubcategoryOptions.push({
            value: option.value,
            text: option.textContent,
            categoryId: option.dataset.categoryId,
            selected: option.selected
        });
    });

    const filterSubcategories = () => {
        const selectedCategoryId = categorySelect.value;

        // 「すべて」オプション以外を削除
        const allOption = subcategorySelect.querySelector('option[value=""]');
        subcategorySelect.innerHTML = '';
        subcategorySelect.appendChild(allOption);

        if (!selectedCategoryId) {
            // カテゴリが「すべて」の場合、全サブカテゴリを表示
            allSubcategoryOptions.forEach(optionData => {
                const option = document.createElement('option');
                option.value = optionData.value;
                option.textContent = optionData.text;
                option.dataset.categoryId = optionData.categoryId;
                if (optionData.selected) {
                    option.selected = true;
                }
                subcategorySelect.appendChild(option);
            });
        } else {
            // 選択されたカテゴリに属するサブカテゴリのみを表示
            allSubcategoryOptions
                .filter(optionData => optionData.categoryId === selectedCategoryId)
                .forEach(optionData => {
                    const option = document.createElement('option');
                    option.value = optionData.value;
                    option.textContent = optionData.text;
                    option.dataset.categoryId = optionData.categoryId;
                    if (optionData.selected) {
                        option.selected = true;
                    }
                    subcategorySelect.appendChild(option);
                });
        }
    };

    // 初期表示時に連動フィルタリングを実行
    filterSubcategories();

    // カテゴリ変更時にサブカテゴリをフィルタリング
    categorySelect.addEventListener('change', () => {
        // サブカテゴリの選択をリセット
        subcategorySelect.value = '';
        filterSubcategories();
    });
});
</script>
@endpush
@endsection