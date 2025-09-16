@extends('layouts.master')

@section('title', '機材詳細')

@section('breadcrumb')
    > <a href="{{ route('master.equipments.index') }}" class="text-blue-600 hover:text-blue-800">機材マスタ</a>
    > <span class="text-gray-800">{{ $equipment->name }}</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">機材詳細</h1>
        <p class="mt-1 text-sm text-gray-600">機材「{{ $equipment->name }}」の詳細情報です。</p>
    </div>

    <div class="flex space-x-3">
        <a href="{{ route('master.equipments.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            一覧に戻る
        </a>

        @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
            <a href="{{ route('master.equipments.edit', $equipment) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                編集
            </a>
        @endif
    </div>
@endsection

@section('content')
    <div class="p-6">
        <div class="max-w-4xl">
            <!-- 基本情報 -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">カテゴリ</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($equipment->category)
                                    <a href="{{ route('master.equipment-categories.show', $equipment->category) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $equipment->category->name }}
                                    </a>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">サブカテゴリ</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($equipment->subcategory)
                                    <a href="{{ route('master.equipment-subcategories.show', $equipment->subcategory) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $equipment->subcategory->name }}
                                    </a>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">メーカー名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->manufacturer ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">機材名</dt>
                            <dd class="mt-1 text-sm font-gray-900 font-bold">{{ $equipment->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">新音番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->company_number ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">管理方式</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @switch($equipment->management_type)
                                    @case('individual') 個体管理 @break
                                    @case('quantity') 数量管理 @break
                                    @default {{ $equipment->management_type }}
                                @endswitch
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">在庫数量</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->quantity }}{{ $equipment->unit }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">型番</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->model_number ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">シリアル番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->serial_number ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">仕入先</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->supplier ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">購入日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->purchase_date?->format('Y-m-d') ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">保証期限</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->warranty_expiry?->format('Y-m-d') ?: '---' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">価格</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $equipment->price ? number_format($equipment->price) . '円' : '---' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @switch($equipment->status)
                                        @case('available') bg-green-100 text-green-800 @break
                                        @case('in_use') bg-blue-100 text-blue-800 @break
                                        @case('repair') bg-orange-100 text-orange-800 @break
                                        @case('maintenance') bg-yellow-100 text-yellow-800 @break
                                        @case('retired') bg-gray-100 text-gray-800 @break
                                        @case('lost') bg-red-100 text-red-800 @break
                                    @endswitch">
                                    @switch($equipment->status)
                                        @case('available') 利用可能 @break
                                        @case('in_use') 使用中 @break
                                        @case('repair') 修理中 @break
                                        @case('maintenance') メンテナンス中 @break
                                        @case('retired') 廃棄 @break
                                        @case('lost') 紛失 @break
                                    @endswitch
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">場所</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($equipment->location)
                                    <a href="{{ route('master.locations.show', $equipment->location) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $equipment->location->name }}
                                    </a>
                                @else
                                    ---
                                @endif
                            </dd>
                        </div>

                        @if($equipment->is_discard)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">廃棄フラグ</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        廃棄
                                    </span>
                                </dd>
                            </div>
                        @endif

                        @if($equipment->discard_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">廃棄日</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $equipment->discard_at?->format('Y-m-d') }}</dd>
                            </div>
                        @endif

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ソート順</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->sort }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->created_at->format('Y-m-d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">更新日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $equipment->updated_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </div>

                    @if($equipment->notes)
                        <div class="mt-6">
                            <dt class="text-sm font-medium text-gray-500">備考</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $equipment->notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 使用履歴 -->
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">使用履歴</h3>
                </div>
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h4 class="mt-2 text-sm font-medium text-gray-900">使用履歴管理機能</h4>
                    <p class="mt-1 text-sm text-gray-500">
                        機材の使用履歴管理機能は今後実装予定です。<br>
                        現在は基本的な機材情報の管理のみ対応しています。
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection