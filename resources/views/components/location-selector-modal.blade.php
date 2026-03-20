{{-- 共通場所選択モーダルコンポーネント --}}
@props([
    'id' => 'location-selector-modal',
    'title' => '場所選択',
    'placeholder' => '場所を選択してください...',
    'onConfirm' => null,
    'confirmText' => '確定',
    'cancelText' => 'キャンセル',
    'required' => false,
    'showAddress' => true,
])

<div
    x-data="locationSelectorModal(@js(['id' => $id, 'required' => $required]))"
    x-init="init()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    style="display: none;"
>
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <!-- ヘッダー -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
            <button @click="close()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- 検索フィルタ -->
        <div class="mb-4">
            <input
                type="text"
                x-model="searchQuery"
                @input="filterLocations()"
                placeholder="場所名で検索..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            >
        </div>

        <!-- 場所一覧 -->
        <div class="mb-4 max-h-64 overflow-y-auto">
            <div x-show="loading" class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-cyan-500"></div>
                <span class="ml-2 text-gray-600">読み込み中...</span>
            </div>

            <div x-show="!loading && filteredLocations.length === 0" class="text-center py-4 text-gray-500">
                場所が見つかりません
            </div>

            <div x-show="!loading" class="space-y-2">
                <template x-for="location in filteredLocations" :key="location.id">
                    <div
                        @click="selectLocation(location)"
                        :class="{'bg-cyan-50 border-cyan-200': selectedLocation?.id === location.id, 'hover:bg-gray-50': selectedLocation?.id !== location.id}"
                        class="p-3 border rounded-md cursor-pointer transition-colors duration-150 ease-in-out"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900" x-text="location.name"></div>
                                @if($showAddress)
                                <div x-show="location.address" class="text-sm text-gray-500 mt-1" x-text="location.address"></div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1" x-text="location.type"></div>
                            </div>
                            <div x-show="selectedLocation?.id === location.id" class="ml-2">
                                <svg class="w-5 h-5 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- エラーメッセージ -->
        <div x-show="errorMessage" x-text="errorMessage" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-md"></div>

        <!-- フッター -->
        <div class="flex justify-end space-x-3">
            <button
                @click="close()"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
            >
                {{ $cancelText }}
            </button>
            <button
                @click="confirm()"
                :disabled="!selectedLocation {{ $required ? '' : '|| false' }}"
                :class="{'opacity-50 cursor-not-allowed': !selectedLocation {{ $required ? '' : '&& false' }}}"
                class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 hover:bg-cyan-700 border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>

