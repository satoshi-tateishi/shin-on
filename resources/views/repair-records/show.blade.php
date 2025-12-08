@extends('layouts.master')

@section('title', '修理記録詳細')

@section('breadcrumb')
    > <a href="{{ route('repair-records.index') }}" class="text-blue-600 hover:text-blue-800">修理管理</a>
    > <span class="text-gray-800">修理記録詳細</span>
@endsection

@section('header')
    <div class="w-full">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-2">修理記録詳細</h1>
        <div class="flex items-center justify-between">
            <a href="{{ route('repair-records.index') }}"
               class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                一覧
            </a>

            <div class="flex flex-wrap gap-1 sm:gap-2">
                <!-- ワークフローボタン -->
                @if($repairRecord->status === 'reported')
                    <button onclick="openStartModal()"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h1m4 0h1m-6 4h.01M15 10h.01M12 14h.01M9 14h.01M12 10h.01" />
                        </svg>
                        <span class="hidden sm:inline">修理</span>開始
                    </button>
                @elseif($repairRecord->status === 'in_progress')
                    <button onclick="openCompleteModal()"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-green-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-green-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">修理</span>完了
                    </button>
                @endif

                @if($repairRecord->status !== 'completed')
                    <button onclick="openCancelModal()"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="hidden sm:inline">キャンセル</span>
                    </button>
                @endif

                <a href="{{ route('repair-records.edit', $repairRecord) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    編集
                </a>

                <!-- 削除ボタン（admin のみ） -->
                @if(auth()->user()->role === 'admin')
                    <button type="button" onclick="openDeleteModal()"
                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="hidden sm:inline">削除</span>
                    </button>
                @endif

                <!-- PDF伝票ダウンロードボタン -->
                <a href="{{ route('repair-records.export-pdf', $repairRecord) }}"
                   class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                   target="_blank">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 13l-4 4m0 0l-4-4m4 4V9" />
                    </svg>
                    <span class="hidden sm:inline">PDF</span>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-3 sm:p-6">
        <div class="max-w-4xl space-y-4 sm:space-y-6">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">基本情報</h3>
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                        @if($repairRecord->status === 'reported') bg-yellow-100 text-yellow-800
                        @elseif($repairRecord->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($repairRecord->status === 'completed') bg-green-100 text-green-800
                        @elseif($repairRecord->status === 'cancelled') bg-gray-100 text-gray-800
                        @endif">
                        {{ $repairRecord->status_display }}
                    </span>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <table class="w-full text-xs sm:text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 w-28 sm:w-32 align-top">機材</th>
                                <td class="py-2 text-gray-900">
                                    <div class="text-gray-500">{{ $repairRecord->equipment->subcategory->category->name }} > {{ $repairRecord->equipment->subcategory->name }}</div>
                                    <div class="font-medium flex flex-wrap items-center gap-1 sm:gap-2">
                                        {{ $repairRecord->equipment->name }}
                                        @if($repairRecord->equipment->company_number)
                                            <span class="px-1 sm:px-2 py-0.5 text-xs border border-gray-300 rounded bg-gray-50">{{ $repairRecord->equipment->company_number }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">故障発生日</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->failure_occurred_at?->format('Y/m/d') ?? '---' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">報告日</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->reported_at->format('Y/m/d') }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">報告者</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->reportedBy->name ?? '---' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">公演名</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->performance_name ?? '---' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">使用場所</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->usage_location ?? '---' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">担当者</th>
                                <td class="py-2 text-gray-900">{{ $repairRecord->staffUser->name ?? '---' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 問題内容 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">問題内容</h3>
                </div>
                <div class="px-4 sm:px-6 py-3 sm:py-4">
                    <div class="text-xs sm:text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->problem_description }}</div>
                </div>
            </div>

            <!-- 故障箇所写真 -->
            @if($repairRecord->photos && count($repairRecord->photos) > 0)
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">故障箇所写真</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            @php
                                $validPaths = [];
                                if ($repairRecord->photos && is_array($repairRecord->photos)) {
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
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">修理詳細</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <table class="w-full text-xs sm:text-sm">
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 w-28 sm:w-32 align-top">修理業者</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->repair_company ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">社内担当者</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->repaired_by ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">修理開始日</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->started_at?->format('Y/m/d') ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">修理完了日</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->completed_at?->format('Y/m/d') ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">修理期間</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->repair_duration ? $repairRecord->repair_duration . '日' : '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">修理費用(税別)</th>
                                    <td class="py-2 text-gray-900 font-medium">{{ $repairRecord->repair_cost ? '¥' . number_format($repairRecord->repair_cost) : '---' }}</td>
                                </tr>
                                <tr>
                                    <th class="py-2 pr-4 text-left font-medium text-gray-500 align-top">保証期限</th>
                                    <td class="py-2 text-gray-900">{{ $repairRecord->warranty_until?->format('Y/m/d') ?? '---' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        @if($repairRecord->repair_description)
                            <div class="mt-4 sm:mt-6 pt-4 border-t border-gray-100">
                                <div class="text-xs sm:text-sm font-medium text-gray-500 mb-2">修理内容</div>
                                <div class="text-xs sm:text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->repair_description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 備考 -->
            @if($repairRecord->note)
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">備考</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="text-xs sm:text-sm text-gray-900 whitespace-pre-wrap">{{ $repairRecord->note }}</div>
                    </div>
                </div>
            @endif

            <!-- 将来予約と代替機管理 -->
            @if($futureReservations->count() > 0 && ($repairRecord->status === 'reported' || $repairRecord->status === 'in_progress'))
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            将来の使用予約があります
                        </h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <!-- 将来予約一覧 -->
                        <div class="mb-4 sm:mb-6">
                            <div class="space-y-2 sm:space-y-3">
                                @foreach($futureReservations as $reservation)
                                    <div class="p-2 sm:p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                        <div>
                                            <div class="text-xs sm:text-sm font-medium text-gray-900">{{ $reservation->phase->performance->display_name ?? '公演名不明' }}</div>
                                            <div class="text-xs sm:text-sm text-gray-600">{{ $reservation->phase->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $reservation->phase->start_date->format('Y/m/d') }} ～ {{ $reservation->phase->end_date->format('m/d') }}
                                                ({{ ucfirst($reservation->status) }})
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 代替機選択フォーム -->
                            @if($alternatives->count() > 0)
                                <div class="border-t pt-4 sm:pt-6">
                                    <h4 class="text-sm sm:text-md font-medium text-gray-900 mb-3 sm:mb-4">代替機への置換</h4>

                                    <form action="{{ route('repair-records.substitute-equipment', $repairRecord) }}" method="POST" class="space-y-3 sm:space-y-4">
                                        @csrf
                                        @method('PATCH')

                                        <div>
                                            <label for="substitute_equipment_id" class="block text-xs sm:text-sm font-medium text-gray-700">代替機材を選択</label>
                                            <select name="substitute_equipment_id" id="substitute_equipment_id"
                                                    class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                                <option value="">代替機材を選択してください</option>
                                                @foreach($alternatives as $alternative)
                                                    <option value="{{ $alternative->id }}">
                                                        {{ $alternative->display_name }} ({{ $alternative->subcategory->name ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex space-x-2 sm:space-x-3">
                                            <button type="submit"
                                                    class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                </svg>
                                                代替機に置換
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif

                            <!-- 予約解除フォーム -->
                            <div class="border-t pt-4 sm:pt-6 mt-4 sm:mt-6">
                                <h4 class="text-sm sm:text-md font-medium text-gray-900 mb-3 sm:mb-4">予約解除</h4>
                                <form action="{{ route('repair-records.cancel-future-reservations', $repairRecord) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            onclick="return confirm('将来の予約をすべて解除します。この操作は取り消せません。よろしいですか？')"
                                            class="inline-flex items-center px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        全予約を解除
                                    </button>
                                </form>
                            </div>
                    </div>
                </div>
            @endif

            <!-- 関連修理履歴 -->
            @if($relatedRepairs->count() > 0)
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">同じ機材の修理履歴</h3>
                    </div>
                    <div class="px-4 sm:px-6 py-3 sm:py-4">
                        <div class="space-y-2 sm:space-y-3">
                            @foreach($relatedRepairs as $related)
                                <div class="flex items-center justify-between p-2 sm:p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1 min-w-0 mr-2">
                                        <div class="text-xs sm:text-sm font-medium text-gray-900">修理記録</div>
                                        <div class="text-xs sm:text-sm text-gray-500 truncate">{{ Str::limit($related->problem_description, 40) }}</div>
                                        <div class="text-xs text-gray-400">{{ $related->reported_at->format('Y/m/d') }}</div>
                                    </div>
                                    <div class="flex flex-col sm:flex-row items-end sm:items-center gap-1 sm:gap-2">
                                        <span class="inline-flex px-1.5 sm:px-2 py-0.5 sm:py-1 text-xs font-semibold rounded-full
                                            @if($related->status === 'completed') bg-green-100 text-green-800
                                            @elseif($related->status === 'in_progress') bg-blue-100 text-blue-800
                                            @elseif($related->status === 'reported') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $related->status_display }}
                                        </span>
                                        <a href="{{ route('repair-records.show', $related) }}" class="text-blue-600 hover:text-blue-900 text-xs sm:text-sm">詳細</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

<!-- ワークフローモーダル -->
<!-- 修理開始モーダル -->
    <div id="startModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-3 sm:p-5 border w-auto sm:w-96 max-w-sm sm:max-w-none shadow-lg rounded-md bg-white">
            <div class="mt-2 sm:mt-3">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">修理開始</h3>
                <form action="{{ route('repair-records.start', $repairRecord) }}" method="POST" class="space-y-3 sm:space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="start_started_at" class="block text-xs sm:text-sm font-medium text-gray-700">開始日</label>
                        <input type="date" name="started_at" id="start_started_at" value="{{ now()->format('Y-m-d') }}" required
                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="start_repair_company" class="block text-xs sm:text-sm font-medium text-gray-700">修理業者</label>
                        <input type="text" name="repair_company" id="start_repair_company"
                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="start_repaired_by" class="block text-xs sm:text-sm font-medium text-gray-700">社内修理担当者</label>
                        <input type="text" name="repaired_by" id="start_repaired_by"
                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex justify-end space-x-2 sm:space-x-3 pt-3 sm:pt-4">
                        <button type="button" onclick="closeStartModal()"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 bg-blue-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-blue-700">
                            修理開始
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 修理完了モーダル -->
    <div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-6 sm:top-10 mx-3 sm:mx-auto p-3 sm:p-5 border shadow-lg rounded-md bg-white max-w-sm sm:max-w-2xl">
            <div class="mt-2 sm:mt-3">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">修理完了</h3>
                <form action="{{ route('repair-records.complete', $repairRecord) }}" method="POST" class="space-y-3 sm:space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="complete_completed_at" class="block text-xs sm:text-sm font-medium text-gray-700">完了日 <span class="text-red-500">*</span></label>
                        <input type="date" name="completed_at" id="complete_completed_at" value="{{ now()->format('Y-m-d') }}" required
                               class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label for="complete_repair_description" class="block text-xs sm:text-sm font-medium text-gray-700">修理内容 <span class="text-red-500">*</span></label>
                        <textarea name="repair_description" id="complete_repair_description" rows="3" required
                                  class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label for="complete_repair_cost" class="block text-xs sm:text-sm font-medium text-gray-700">修理費用(税別)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">¥</span>
                                </div>
                                <input type="text" name="repair_cost_display" id="complete_repair_cost_display"
                                       class="block w-full pl-7 text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                       placeholder="0">
                                <input type="hidden" name="repair_cost" id="complete_repair_cost_hidden" value="">
                            </div>
                        </div>

                        <div>
                            <label for="complete_warranty_until" class="block text-xs sm:text-sm font-medium text-gray-700">保証期限</label>
                            <input type="date" name="warranty_until" id="complete_warranty_until"
                                   class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <div>
                        <label for="complete_note" class="block text-xs sm:text-sm font-medium text-gray-700">備考</label>
                        <textarea name="note" id="complete_note" rows="2"
                                  class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 sm:space-x-3 pt-3 sm:pt-4">
                        <button type="button" onclick="closeCompleteModal()"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 bg-green-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-green-700">
                            修理完了
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- キャンセルモーダル -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-3 sm:p-5 border w-auto sm:w-96 max-w-sm sm:max-w-none shadow-lg rounded-md bg-white">
            <div class="mt-2 sm:mt-3">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">修理キャンセル</h3>
                <form action="{{ route('repair-records.cancel', $repairRecord) }}" method="POST" class="space-y-3 sm:space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="cancel_note" class="block text-xs sm:text-sm font-medium text-gray-700">キャンセル理由 <span class="text-red-500">*</span></label>
                        <textarea name="note" id="cancel_note" rows="3" required
                                  placeholder="キャンセルの理由を入力してください"
                                  class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 sm:space-x-3 pt-3 sm:pt-4">
                        <button type="button" onclick="closeCancelModal()"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            戻る
                        </button>
                        <button type="submit"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700">
                            キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 削除確認モーダル（admin のみ） -->
    @if(auth()->user()->role === 'admin')
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 sm:top-20 mx-3 sm:mx-auto p-3 sm:p-5 border w-auto sm:w-96 max-w-sm sm:max-w-none shadow-lg rounded-md bg-white">
            <div class="mt-2 sm:mt-3">
                <h3 class="text-base sm:text-lg font-medium text-red-600 mb-3 sm:mb-4">修理記録の削除</h3>
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">この修理記録を削除しますか？</p>
                    <div class="p-3 bg-gray-50 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">{{ $repairRecord->equipment->name }}</div>
                        <div class="text-gray-500">{{ Str::limit($repairRecord->problem_description, 50) }}</div>
                    </div>
                    <p class="text-xs text-red-500 mt-2">※ この操作は取り消せません。</p>
                </div>
                <div class="mb-4">
                    <label for="delete_confirmation" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">確認のため「delete」と入力してください</label>
                    <input type="text" id="delete_confirmation"
                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                           placeholder="delete">
                </div>
                <form action="{{ route('repair-records.destroy', $repairRecord) }}" method="POST" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end space-x-2 sm:space-x-3 pt-3 sm:pt-4">
                        <button type="button" onclick="closeDeleteModal()"
                                class="px-2 sm:px-4 py-1.5 sm:py-2 border border-gray-300 text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            キャンセル
                        </button>
                        <button type="submit" id="deleteSubmitBtn" disabled
                                class="px-2 sm:px-4 py-1.5 sm:py-2 bg-red-600 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            削除する
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

// 削除モーダル（admin のみ）
function openDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('delete_confirmation').value = '';
        document.getElementById('deleteSubmitBtn').disabled = true;
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// 削除確認入力の監視
document.addEventListener('DOMContentLoaded', function() {
    const deleteConfirmInput = document.getElementById('delete_confirmation');
    const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');

    if (deleteConfirmInput && deleteSubmitBtn) {
        deleteConfirmInput.addEventListener('input', function() {
            deleteSubmitBtn.disabled = this.value.toLowerCase() !== 'delete';
        });

        // Enterキーで削除実行
        deleteConfirmInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.toLowerCase() === 'delete') {
                e.preventDefault();
                document.getElementById('deleteForm').submit();
            }
        });
    }
});

// モーダル外クリックで閉じる
document.addEventListener('click', function(event) {
    const modals = ['startModal', 'completeModal', 'cancelModal', 'deleteModal'];
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
