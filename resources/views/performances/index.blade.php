@extends('layouts.app')

@section('title', '公演管理')

@section('breadcrumb')
    > <span class="text-gray-800">公演管理</span>
@endsection

@section('header')
    <div>
        <h1 class="text-3xl font-bold text-gray-900">公演管理</h1>
        <p class="mt-1 text-sm text-gray-600">演劇・ミュージカル・コンサートなどの公演を管理します。</p>
    </div>

    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
        <div class="flex space-x-3">
            <a href="{{ route('performances.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                新規公演作成
            </a>
        </div>
    @endif
@endsection

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <!-- 検索・フィルター -->
        <div class="mb-6">
            <form method="GET" action="{{ route('performances.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">公演名で検索</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="公演名またはサブタイトル">
                </div>

                <div>
                    <label for="performance_type" class="block text-sm font-medium text-gray-700">公演種別</label>
                    <select name="performance_type" id="performance_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">全て</option>
                        <option value="演劇" {{ request('performance_type') === '演劇' ? 'selected' : '' }}>演劇</option>
                        <option value="ミュージカル" {{ request('performance_type') === 'ミュージカル' ? 'selected' : '' }}>ミュージカル</option>
                        <option value="コンサート" {{ request('performance_type') === 'コンサート' ? 'selected' : '' }}>コンサート</option>
                        <option value="その他" {{ request('performance_type') === 'その他' ? 'selected' : '' }}>その他</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">ステータス</label>
                    <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">全て</option>
                        <option value="planning" {{ request('status') === 'planning' ? 'selected' : '' }}>企画中</option>
                        <option value="preparation" {{ request('status') === 'preparation' ? 'selected' : '' }}>準備中</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>進行中</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>完了</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gray-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-gray-700">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- 公演一覧テーブル -->
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">公演情報</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種別</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">期間</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">会場</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">フェーズ数</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($performances as $performance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $performance->title }}</div>
                                    @if($performance->subtitle)
                                        <div class="text-sm text-gray-500">{{ $performance->subtitle }}</div>
                                    @endif
                                    @if($performance->director)
                                        <div class="text-xs text-gray-400">演出: {{ $performance->director }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $performance->performance_type === '演劇' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $performance->performance_type === 'ミュージカル' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $performance->performance_type === 'コンサート' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $performance->performance_type === 'その他' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ $performance->performance_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>{{ $performance->start_date->format('Y/m/d') }}</div>
                                <div>{{ $performance->end_date->format('Y/m/d') }}</div>
                                @if($performance->duration_days)
                                    <div class="text-xs text-gray-500">({{ $performance->duration_days }}日間)</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $performance->venue ?? '未設定' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $performance->status === 'planning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $performance->status === 'preparation' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $performance->status === 'in_progress' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $performance->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $performance->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $performance->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <span class="mr-2">{{ $performance->phases_count ?? 0 }}</span>
                                    @if($performance->phases_count > 0)
                                        <a href="{{ route('performances.phases.index', $performance) }}"
                                           class="text-blue-600 hover:text-blue-900 text-xs">
                                            フェーズ管理
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('performances.show', $performance) }}"
                                       class="text-blue-600 hover:text-blue-900">詳細</a>
                                    @if(auth()->user()->role === 'editor' || auth()->user()->role === 'admin')
                                        <a href="{{ route('performances.edit', $performance) }}"
                                           class="text-indigo-600 hover:text-indigo-900">編集</a>
                                        <form method="POST" action="{{ route('performances.destroy', $performance) }}"
                                              class="inline"
                                              onsubmit="return confirm('本当に削除しますか？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                公演が登録されていません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        @if($performances->hasPages())
            <div class="mt-6">
                {{ $performances->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection