@extends('layouts.app')

@section('title', '修理記録詳細')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800">修理管理</a>
    > <span class="text-gray-800">修理記録詳細</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">修理記録詳細</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $repairRecord->equipment->name }} の修理記録</p>
    </div>

    <div class="flex space-x-3">
        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <!-- ワークフローボタン -->
            @if($repairRecord->status === 'reported')
                <button onclick="openStartModal()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h1m4 0h1m-6 4h.01M15 10h.01M12 14h.01M9 14h.01M12 10h.01" />
                    </svg>
                    修理開始
                </button>
            @elseif($repairRecord->status === 'in_progress')
                <button onclick="openCompleteModal()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-green-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    修理完了
                </button>
            @endif

            @if($repairRecord->status !== 'completed')
                <button onclick="openCancelModal()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    修理キャンセル
                </button>
            @endif

            <a href="{{ route('repair-records.edit', $repairRecord) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                編集
            </a>
        @endif

        <!-- PDF伝票ダウンロードボタン -->
        <a href="{{ route('repair-records.export-pdf', $repairRecord) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
           target="_blank">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 13l-4 4m0 0l-4-4m4 4V9" />
            </svg>
            修理伝票PDF
        </a>

        <a href="{{ route('repair-records.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            戻る
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- 基本情報 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">基本情報</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500">機材</label>
                    <div class="mt-1">
                        <div class="text-sm text-gray-500">{{ $repairRecord->equipment->subcategory->category->name }} > {{ $repairRecord->equipment->subcategory->name }}</div>
                        <div class="text-sm font-medium text-gray-900 flex items-center">
                            {{ $repairRecord->equipment->name }}
                            @if($repairRecord->equipment->company_number)
                                <span class="ml-2 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50">{{ $repairRecord->equipment->company_number }}</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">ステータス</label>
                    <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full
                        @if($repairRecord->status === 'reported') bg-yellow-100 text-yellow-800
                        @elseif($repairRecord->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($repairRecord->status === 'completed') bg-green-100 text-green-800
                        @elseif($repairRecord->status === 'cancelled') bg-gray-100 text-gray-800
                        @endif">
                        {{ $repairRecord->status_display }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">担当者</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->staffUser->name ?? '不明' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">報告者</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->reportedBy->name ?? '不明' }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @if($repairRecord->failure_occurred_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">故障発生日</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->failure_occurred_at->format('Y年m月d日') }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-500">報告日</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->reported_at->format('Y年m月d日') }}</p>
                </div>

                @if($repairRecord->performance_name)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">公演名</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->performance_name }}</p>
                    </div>
                @endif

                @if($repairRecord->usage_location)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">使用場所</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->usage_location }}</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- 問題内容 -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">問題内容</h3>
            <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->problem_description }}</div>
        </div>
    </div>

    <!-- 故障箇所写真 -->
    @if($repairRecord->photos && count($repairRecord->photos) > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">故障箇所写真</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $validPaths = [];
                        if ($repairRecord->photos && is_array($repairRecord->photos)) {
                            // 直接配列として処理（Eloquentのcastで配列になっている）
                            foreach ($repairRecord->photos as $item) {
                                if (is_string($item) && !empty(trim($item))) {
                                    $validPaths[] = $item;
                                }
                            }
                        }
                    @endphp
                    @foreach($validPaths as $photoPath)
                        @if(is_string($photoPath) && !empty(trim($photoPath)))
                            <div class="relative group">
                                <div class="w-full h-40 bg-gray-100 rounded-lg border overflow-hidden flex items-center justify-center">
                                    <img src="{{ Storage::disk('public')->url($photoPath) }}"
                                         alt="故障箇所写真"
                                         class="max-w-full max-h-full object-contain cursor-pointer hover:opacity-90 transition-opacity"
                                         onclick="openImageModal('{{ Storage::disk('public')->url($photoPath) }}')">
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 画像拡大表示用モーダル -->
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50" onclick="closeImageModal()">
            <div class="max-w-4xl max-h-4xl p-4">
                <img id="modalImage" src="" alt="拡大表示" class="max-w-full max-h-full object-contain">
                <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">×</button>
            </div>
        </div>
    @endif

    <!-- 修理詳細 -->
    @if($repairRecord->status !== 'reported' || $repairRecord->repair_description || $repairRecord->repair_company)
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">修理詳細</h3>

                <div class="space-y-4 mb-6">
                    @if($repairRecord->repair_company)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理業者</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->repair_company }}</p>
                        </div>
                    @endif

                    @if($repairRecord->repaired_by)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理担当者</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->repaired_by }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-4 mb-6">
                    @if($repairRecord->started_at)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理開始日</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->started_at->format('Y年m月d日') }}</p>
                        </div>
                    @endif

                    @if($repairRecord->completed_at)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理完了日</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->completed_at->format('Y年m月d日') }}</p>
                        </div>
                    @endif

                    @if($repairRecord->repair_duration)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理期間</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->repair_duration }}日</p>
                        </div>
                    @endif
                </div>

                @if($repairRecord->repair_description)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-500 mb-2">修理内容</label>
                        <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->repair_description }}</div>
                    </div>
                @endif


                <div class="space-y-4">
                    @if($repairRecord->repair_cost)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">修理費用(税別)</label>
                            <p class="mt-1 text-lg font-medium text-gray-900">¥{{ number_format($repairRecord->repair_cost) }}</p>
                        </div>
                    @endif

                    @if($repairRecord->warranty_until)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">保証期限</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $repairRecord->warranty_until->format('Y年m月d日') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- 備考 -->
    @if($repairRecord->note)
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">備考</h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->note }}</div>
            </div>
        </div>
    @endif

    <!-- 将来予約と代替機管理 -->
    @if($futureReservations->count() > 0 && ($repairRecord->status === 'reported' || $repairRecord->status === 'in_progress'))
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <svg class="w-5 h-5 inline mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    将来の使用予約があります
                </h3>

                <!-- 将来予約一覧 -->
                <div class="mb-6">
                    <div class="space-y-3">
                        @foreach($futureReservations as $reservation)
                            <div class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $reservation->phase->performance->display_name ?? '公演名不明' }}</div>
                                    <div class="text-sm text-gray-600">{{ $reservation->phase->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $reservation->phase->start_date->format('Y/m/d') }} ～ {{ $reservation->phase->end_date->format('Y/m/d') }}
                                        ({{ ucfirst($reservation->status) }})
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                    <!-- 代替機選択フォーム -->
                    @if($alternatives->count() > 0)
                        <div class="border-t pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">代替機への置換</h4>

                            <form action="{{ route('repair-records.substitute-equipment', $repairRecord) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <label for="substitute_equipment_id" class="block text-sm font-medium text-gray-700">代替機材を選択</label>
                                    <select name="substitute_equipment_id" id="substitute_equipment_id"
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required>
                                        <option value="">代替機材を選択してください</option>
                                        @foreach($alternatives as $alternative)
                                            <option value="{{ $alternative->id }}">
                                                {{ $alternative->display_name }} ({{ $alternative->subcategory->name ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex space-x-3">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        全予約を代替機に置換
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- 予約解除フォーム -->
                    <div class="border-t pt-6 mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">予約解除</h4>
                        <form action="{{ route('repair-records.cancel-future-reservations', $repairRecord) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    onclick="return confirm('将来の予約をすべて解除します。この操作は取り消せません。よろしいですか？')"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                全予約を解除
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- 関連修理履歴 -->
    @if($relatedRepairs->count() > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">同じ機材の修理履歴</h3>
                <div class="space-y-3">
                    @foreach($relatedRepairs as $related)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-gray-900">修理記録</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($related->problem_description, 60) }}</div>
                                <div class="text-xs text-gray-400">{{ $related->reported_at->format('Y/m/d') }}</div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($related->status === 'completed') bg-green-100 text-green-800
                                    @elseif($related->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($related->status === 'reported') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $related->status_display }}
                                </span>
                                <a href="{{ route('repair-records.show', $related) }}" class="text-blue-600 hover:text-blue-900 text-sm">詳細</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<!-- ワークフローモーダル -->
@if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
    <!-- 修理開始モーダル -->
    <div id="startModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">修理開始</h3>
                <form action="{{ route('repair-records.start', $repairRecord) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="start_started_at" class="block text-sm font-medium text-gray-700">開始日</label>
                        <input type="date" name="started_at" id="start_started_at" value="{{ now()->format('Y-m-d') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="start_repair_company" class="block text-sm font-medium text-gray-700">修理業者</label>
                        <input type="text" name="repair_company" id="start_repair_company"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="start_repaired_by" class="block text-sm font-medium text-gray-700">社内修理担当者</label>
                        <input type="text" name="repaired_by" id="start_repaired_by"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeStartModal()"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            修理開始
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 修理完了モーダル -->
    <div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-2xl shadow-lg rounded-md bg-white max-w-2xl">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">修理完了</h3>
                <form action="{{ route('repair-records.complete', $repairRecord) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="complete_completed_at" class="block text-sm font-medium text-gray-700">完了日 <span class="text-red-500">*</span></label>
                        <input type="date" name="completed_at" id="complete_completed_at" value="{{ now()->format('Y-m-d') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="complete_repair_description" class="block text-sm font-medium text-gray-700">修理内容 <span class="text-red-500">*</span></label>
                        <textarea name="repair_description" id="complete_repair_description" rows="3" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="complete_repair_cost" class="block text-sm font-medium text-gray-700">修理費用(税別)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">¥</span>
                                </div>
                                <input type="text" name="repair_cost_display" id="complete_repair_cost_display"
                                       class="block w-full pl-7 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                       placeholder="0">
                                <input type="hidden" name="repair_cost" id="complete_repair_cost_hidden" value="">
                            </div>
                        </div>

                        <div>
                            <label for="complete_warranty_until" class="block text-sm font-medium text-gray-700">保証期限</label>
                            <input type="date" name="warranty_until" id="complete_warranty_until"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        </div>
                    </div>


                    <div>
                        <label for="complete_note" class="block text-sm font-medium text-gray-700">備考</label>
                        <textarea name="note" id="complete_note" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeCompleteModal()"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-green-700">
                            修理完了
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- キャンセルモーダル -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">修理キャンセル</h3>
                <form action="{{ route('repair-records.cancel', $repairRecord) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="cancel_note" class="block text-sm font-medium text-gray-700">キャンセル理由 <span class="text-red-500">*</span></label>
                        <textarea name="note" id="cancel_note" rows="3" required
                                  placeholder="キャンセルの理由を入力してください"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeCancelModal()"
                                class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            戻る
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-red-700">
                            キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
// 画像拡大表示用関数
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    document.getElementById('modalImage').src = imageSrc;
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}

function openStartModal() {
    document.getElementById('startModal').classList.remove('hidden');
}

function closeStartModal() {
    document.getElementById('startModal').classList.add('hidden');
}

function openCompleteModal() {
    document.getElementById('completeModal').classList.remove('hidden');
}

function closeCompleteModal() {
    document.getElementById('completeModal').classList.add('hidden');
}

function openCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.addEventListener('click', function(event) {
    const modals = ['startModal', 'completeModal', 'cancelModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
});

// 修理完了モーダルの修理費用のカンマ区切り処理
document.addEventListener('DOMContentLoaded', function() {
    const completeRepairCostDisplay = document.getElementById('complete_repair_cost_display');
    const completeRepairCostHidden = document.getElementById('complete_repair_cost_hidden');

    if (completeRepairCostDisplay && completeRepairCostHidden) {
        // 入力時のカンマ区切り処理
        completeRepairCostDisplay.addEventListener('input', function(e) {
            // 数字以外を除去
            let value = e.target.value.replace(/[^\d]/g, '');

            // 空の場合は隠しフィールドも空にする
            if (value === '') {
                completeRepairCostHidden.value = '';
                e.target.value = '';
                return;
            }

            // 数値に変換
            let numValue = parseInt(value);

            // カンマ区切りで表示
            e.target.value = numValue.toLocaleString();

            // 隠しフィールドに数値を設定
            completeRepairCostHidden.value = numValue;
        });

        // フォーカス時に全選択
        completeRepairCostDisplay.addEventListener('focus', function(e) {
            e.target.select();
        });
    }
});
</script>
@endpush
@endsection
