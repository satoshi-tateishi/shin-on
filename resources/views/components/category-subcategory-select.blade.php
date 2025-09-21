@props([
    'categories',
    'categoryId' => null,
    'subcategoryId' => null,
    'categoryName' => 'category_id',
    'subcategoryName' => 'subcategory_id',
    'categoryLabel' => 'カテゴリ',
    'subcategoryLabel' => 'サブカテゴリ',
    'categoryPlaceholder' => '全カテゴリ',
    'subcategoryPlaceholder' => '全サブカテゴリ',
    'required' => false,
    'gridCols' => 'grid-cols-1 md:grid-cols-2',
    'onSubcategoryChange' => null
])

<div class="grid {{ $gridCols }} gap-4">
    <!-- カテゴリ選択 -->
    <div>
        <label for="{{ $categoryName }}" class="block text-sm font-medium text-gray-700">
            {{ $categoryLabel }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
        <select id="{{ $categoryName }}" name="{{ $categoryName }}"
                @if($required) required @endif
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="">{{ $categoryPlaceholder }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

    </div>

    <!-- サブカテゴリ選択 -->
    <div>
        <label for="{{ $subcategoryName }}" class="block text-sm font-medium text-gray-700">
            {{ $subcategoryLabel }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
        <select id="{{ $subcategoryName }}" name="{{ $subcategoryName }}"
                @if($required) required @endif
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="">{{ $subcategoryPlaceholder }}</option>
            @foreach($categories as $category)
                @if($category->subcategories && $category->subcategories->count() > 0)
                    <optgroup label="{{ $category->name }}">
                        @foreach($category->subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}"
                                    data-category-id="{{ $category->id }}"
                                    {{ $subcategoryId == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
        </select>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    initCategorySubcategoryFilter{{ $categoryName }}{{ $subcategoryName }}();
});

function initCategorySubcategoryFilter{{ $categoryName }}{{ $subcategoryName }}() {
    const categorySelect = document.getElementById('{{ $categoryName }}');
    const subcategorySelect = document.getElementById('{{ $subcategoryName }}');
    const onSubcategoryChangeCallback = '{{ $onSubcategoryChange }}';

    if (!categorySelect || !subcategorySelect) {
        return;
    }

    const allSubcategoryOptions = Array.from(subcategorySelect.children);

    const filterSubcategories = () => {
        const selectedCategoryId = categorySelect.value;

        // 既存のオプションを削除（「全サブカテゴリ」は残す）
        while (subcategorySelect.children.length > 1) {
            subcategorySelect.removeChild(subcategorySelect.lastChild);
        }

        if (!selectedCategoryId) {
            // カテゴリが「全カテゴリ」の場合、全サブカテゴリを表示
            allSubcategoryOptions.slice(1).forEach(element => {
                subcategorySelect.appendChild(element.cloneNode(true));
            });
        } else {
            // 選択されたカテゴリに属するサブカテゴリのみを表示
            allSubcategoryOptions.slice(1).forEach(element => {
                if (element.tagName === 'OPTGROUP') {
                    const optgroup = element.cloneNode(false);
                    const options = Array.from(element.children);
                    let hasValidOptions = false;

                    options.forEach(option => {
                        if (option.dataset.categoryId === selectedCategoryId) {
                            optgroup.appendChild(option.cloneNode(true));
                            hasValidOptions = true;
                        }
                    });

                    if (hasValidOptions) {
                        subcategorySelect.appendChild(optgroup);
                    }
                }
            });
        }

        // サブカテゴリの選択をリセット
        subcategorySelect.value = '';

        // コールバック実行
        if (onSubcategoryChangeCallback && window[onSubcategoryChangeCallback]) {
            window[onSubcategoryChangeCallback]();
        }
    };

    const onSubcategoryChange = () => {
        if (onSubcategoryChangeCallback && window[onSubcategoryChangeCallback]) {
            window[onSubcategoryChangeCallback]();
        }
    };

    // イベントリスナー
    categorySelect.addEventListener('change', filterSubcategories);
    subcategorySelect.addEventListener('change', onSubcategoryChange);
}
</script>