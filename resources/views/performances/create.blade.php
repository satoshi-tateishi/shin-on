@extends('layouts.master')

@section('title', '新規公演作成')

@section('breadcrumb')
    > <a href="{{ route('performances.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">公演一覧</a>
    > <span class="text-gray-800 dark:text-gray-200">新規作成</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">新規公演作成</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('performances.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 dark:border-gray-600 text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>
            <button type="submit" form="create-form"
                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                作成
            </button>
        </div>
    </div>
@endsection

@section('content')
<div class="p-3 sm:p-6">
    <form id="create-form" method="POST" action="{{ route('performances.store') }}" enctype="multipart/form-data" class="space-y-4 sm:space-y-6">
        @csrf

        <!-- 基本情報 -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">基本情報</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- 公演名 -->
                    <div>
                        <label for="title" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            公演名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required
                               class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-300 @enderror">
                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 略称 -->
                    <div>
                        <label for="short_name" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">略称</label>
                        <input type="text" name="short_name" id="short_name" value="{{ old('short_name') }}" maxlength="50"
                               class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('short_name') border-red-300 @enderror"
                               placeholder="スケジュール表での表示名">
                        @error('short_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">空欄の場合は公演名が使用されます</p>
                    </div>

                    <!-- 公演種別 -->
                    <div>
                        <label for="performance_type" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            公演種別 <span class="text-red-500">*</span>
                        </label>
                        <select name="performance_type" id="performance_type" required
                                class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('performance_type') border-red-300 @enderror">
                            <option value="">選択してください</option>
                            <option value="演劇" {{ old('performance_type') === '演劇' ? 'selected' : '' }}>演劇</option>
                            <option value="ミュージカル" {{ old('performance_type') === 'ミュージカル' ? 'selected' : '' }}>ミュージカル</option>
                            <option value="リーディング" {{ old('performance_type') === 'リーディング' ? 'selected' : '' }}>リーディング</option>
                            <option value="ダンス" {{ old('performance_type') === 'ダンス' ? 'selected' : '' }}>ダンス</option>
                            <option value="イベント" {{ old('performance_type') === 'イベント' ? 'selected' : '' }}>イベント</option>
                            <option value="コンサート" {{ old('performance_type') === 'コンサート' ? 'selected' : '' }}>コンサート</option>
                            <option value="その他" {{ old('performance_type') === 'その他' ? 'selected' : '' }}>その他</option>
                        </select>
                        @error('performance_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 演出 -->
                    <div>
                        <label for="director" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">演出</label>
                        <input type="text" name="director" id="director" value="{{ old('director') }}"
                               class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('director') border-red-300 @enderror">
                        @error('director')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 説明文 -->
                    <div class="sm:col-span-2">
                        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md p-3 sm:p-4">
                            <div class="flex">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-2 sm:ml-3">
                                    <h3 class="text-xs sm:text-sm font-medium text-blue-800 dark:text-blue-300">公演期間・会場について</h3>
                                    <p class="mt-1 text-xs sm:text-sm text-blue-700 dark:text-blue-400">公演期間と会場は、この後作成するフェーズで詳細に設定します。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- プロダクション選択 -->
        @if($productions->count() > 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">プロダクション</h3>
                <button type="button" id="add-production" class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 border border-transparent text-xs sm:text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/50 dark:hover:bg-blue-900/70">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    追加
                </button>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-4">この公演に関連するプロダクションを選択してください。</p>
                <div id="productions-container" class="space-y-2 sm:space-y-3">
                    <div class="production-row flex items-center space-x-2 sm:space-x-3">
                        <select name="production_ids[]" class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">プロダクションを選択</option>
                            @foreach($productions as $production)
                                <option value="{{ $production->id }}" {{ in_array($production->id, old('production_ids', [])) ? 'selected' : '' }}>
                                    {{ $production->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="remove-production inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                @error('production_ids')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        <!-- サウンドデザイナー選択 -->
        @if($designers->count() > 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">サウンドデザイナー</h3>
                <button type="button" id="add-sound-designer" class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 border border-transparent text-xs sm:text-sm font-medium rounded-md text-purple-700 bg-purple-100 hover:bg-purple-200 dark:text-purple-300 dark:bg-purple-900/50 dark:hover:bg-purple-900/70">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    追加
                </button>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-4">この公演のサウンドデザイナーを選択してください。</p>
                <div id="sound-designers-container" class="space-y-2 sm:space-y-3">
                    <div class="sound-designer-row flex items-center space-x-2 sm:space-x-3">
                        <select name="sound_designers[]" class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">サウンドデザイナーを選択</option>
                            @foreach($designers as $designer)
                                <option value="{{ $designer->id }}">{{ $designer->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="remove-sound-designer inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                @error('sound_designers')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        <!-- 担当者・ポジション選択 -->
        @if($users->count() > 0 && $positions->count() > 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">担当者</h3>
                <button type="button" id="add-staff" class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 border border-transparent text-xs sm:text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-300 dark:bg-green-900/50 dark:hover:bg-green-900/70">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    追加
                </button>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-3 sm:mb-4">この公演の担当者とポジションを選択してください。</p>
                <div id="staff-container" class="space-y-2 sm:space-y-3">
                    <div class="staff-row flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                        <select name="staff[0][user_id]" class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">担当者を選択</option>
                            @foreach($users->sortBy('sort')->where('is_staff', true) as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <select name="staff[0][position_id]" class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">ポジションを選択</option>
                                @foreach($positions->sortBy('sort')->where('id', '!=', 1) as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="remove-staff inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @error('staff')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        @endif

        <!-- 公演チラシ画像 -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">公演チラシ画像</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <input type="file" name="attachments[]" id="attachments" multiple
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full text-xs sm:text-sm text-gray-500 dark:text-gray-400 file:mr-2 sm:file:mr-4 file:py-1.5 sm:file:py-2 file:px-3 sm:file:px-4 file:rounded-full file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/50 dark:file:text-blue-300 dark:hover:file:bg-blue-900/70">
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">PDF、JPEG、PNG形式。最大10ファイル、各5MBまで。</p>
                @error('attachments')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div id="file-preview" class="mt-3 sm:mt-4 flex flex-wrap -m-0 hidden"></div>
            </div>
        </div>

        <!-- 備考 -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white">備考</h3>
            </div>
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <textarea name="note" id="note" rows="8"
                          class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('note') border-red-300 @enderror">{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- アクティブフラグ -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                    <label for="is_active" class="ml-2 block text-xs sm:text-sm text-gray-900 dark:text-white">有効にする</label>
                </div>
                @error('is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ファイルアップロード・プレビュー機能
    const fileInput = document.getElementById('attachments');
    const filePreview = document.getElementById('file-preview');

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            filePreview.innerHTML = '';

            if (files.length === 0) {
                filePreview.classList.add('hidden');
                return;
            }

            filePreview.classList.remove('hidden');

            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'w-1/3 md:w-1/4 lg:w-1/5 xl:w-1/6';

                    const imgElement = document.createElement('img');
                    imgElement.className = 'w-full h-24 sm:h-32 object-contain';

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgElement.src = e.target.result;
                        imgElement.alt = file.name;
                    };
                    reader.readAsDataURL(file);

                    wrapper.appendChild(imgElement);
                    filePreview.appendChild(wrapper);
                }
            });
        });
    }
    // サウンドデザイナー管理
    const addSoundDesignerBtn = document.getElementById('add-sound-designer');
    const soundDesignersContainer = document.getElementById('sound-designers-container');

    if (addSoundDesignerBtn) {
        addSoundDesignerBtn.addEventListener('click', function() {
            const designerOptions = @json($designers->map(function($d) { return ['id' => $d->id, 'name' => $d->name]; }));

            const newRow = document.createElement('div');
            newRow.className = 'sound-designer-row flex items-center space-x-2 sm:space-x-3';
            newRow.innerHTML = `
                <select name="sound_designers[]" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">サウンドデザイナーを選択</option>
                    ${designerOptions.map(d => `<option value="${d.id}">${d.name}</option>`).join('')}
                </select>
                <button type="button" class="remove-sound-designer inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            `;

            soundDesignersContainer.appendChild(newRow);

            newRow.querySelector('.remove-sound-designer').addEventListener('click', function() {
                if (soundDesignersContainer.children.length > 1) {
                    newRow.remove();
                }
            });
        });
    }

    // 既存のサウンドデザイナー削除ボタン
    if (soundDesignersContainer) {
        soundDesignersContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-sound-designer')) {
                if (soundDesignersContainer.children.length > 1) {
                    e.target.closest('.sound-designer-row').remove();
                }
            }
        });
    }

    // プロダクション管理
    const addProductionBtn = document.getElementById('add-production');
    const productionsContainer = document.getElementById('productions-container');

    if (addProductionBtn) {
        addProductionBtn.addEventListener('click', function() {
            const productionOptions = @json($productions->sortBy('sort')->map(function($p) { return ['id' => $p->id, 'name' => $p->display_name]; }));

            const newRow = document.createElement('div');
            newRow.className = 'production-row flex items-center space-x-2 sm:space-x-3';
            newRow.innerHTML = `
                <select name="production_ids[]" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">プロダクションを選択</option>
                    ${productionOptions.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                </select>
                <button type="button" class="remove-production inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            `;

            productionsContainer.appendChild(newRow);

            newRow.querySelector('.remove-production').addEventListener('click', function() {
                if (productionsContainer.children.length > 1) {
                    newRow.remove();
                }
            });
        });
    }

    // 既存のプロダクション削除ボタン
    if (productionsContainer) {
        productionsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-production')) {
                if (productionsContainer.children.length > 1) {
                    e.target.closest('.production-row').remove();
                }
            }
        });
    }

    // 担当者管理
    const addStaffBtn = document.getElementById('add-staff');
    const staffContainer = document.getElementById('staff-container');

    if (addStaffBtn) {
        addStaffBtn.addEventListener('click', function() {
            const userOptions = @json($users->sortBy('sort')->where('is_staff', true)->map(function($u) { return ['id' => $u->id, 'name' => $u->name]; })->values());
            const positionOptions = @json($positions->sortBy('sort')->reject(function($p) { return $p->id == 1; })->map(function($p) { return ['id' => $p->id, 'name' => $p->name]; })->values());

            const currentIndex = staffContainer.children.length;

            const newRow = document.createElement('div');
            newRow.className = 'staff-row flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3';
            newRow.innerHTML = `
                <select name="staff[${currentIndex}][user_id]" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">担当者を選択</option>
                    ${userOptions.map(u => `<option value="${u.id}">${u.name}</option>`).join('')}
                </select>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <select name="staff[${currentIndex}][position_id]" class="flex-1 text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">ポジションを選択</option>
                        ${positionOptions.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                    </select>
                    <button type="button" class="remove-staff inline-flex items-center px-2 sm:px-3 py-1.5 sm:py-2 border border-red-300 text-xs sm:text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            `;

            staffContainer.appendChild(newRow);

            newRow.querySelector('.remove-staff').addEventListener('click', function() {
                if (staffContainer.children.length > 1) {
                    newRow.remove();
                    updateStaffIndexes();
                }
            });
        });
    }

    // 既存の担当者削除ボタン
    if (staffContainer) {
        staffContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-staff')) {
                if (staffContainer.children.length > 1) {
                    e.target.closest('.staff-row').remove();
                    updateStaffIndexes();
                }
            }
        });
    }

    // 担当者のインデックス更新
    function updateStaffIndexes() {
        const staffRows = staffContainer.querySelectorAll('.staff-row');
        staffRows.forEach((row, index) => {
            const userSelect = row.querySelector('select[name*="[user_id]"]');
            const positionSelect = row.querySelector('select[name*="[position_id]"]');

            if (userSelect) userSelect.name = `staff[${index}][user_id]`;
            if (positionSelect) positionSelect.name = `staff[${index}][position_id]`;
        });
    }
});
</script>
@endpush

@endsection
