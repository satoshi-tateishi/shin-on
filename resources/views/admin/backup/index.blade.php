@extends('layouts.master')

@section('title', 'バックアップ管理')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-4">
    <div class="max-w-6xl mx-auto">
        <!-- ヘッダー -->
        <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-2">
                        <i class="fas fa-cloud-upload-alt text-blue-600 mr-2"></i>
                        バックアップ管理
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600">データベースとファイルのDropboxバックアップを管理します</p>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-xs sm:text-sm text-gray-500">管理者専用機能</div>
                    <div class="text-xs text-gray-400">{{ now()->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <!-- Dropbox認証状態 -->
            <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4">
                    <i class="fab fa-dropbox text-blue-600 mr-2"></i>
                    Dropbox認証状態
                </h2>

                <div id="auth-status" class="space-y-4">
                    @if($isAuthenticated)
                        <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-green-800">Dropbox認証済み</p>
                                @if($authStatus && $authStatus['success'])
                                    <p class="text-sm text-green-700">
                                        アカウント: {{ $authStatus['account_info']['account_name'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if($authStatus && $authStatus['success'] && isset($authStatus['token_info']))
                            <!-- トークン期限情報 -->
                            <div class="space-y-3 mt-4">
                                <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                                    <h3 class="text-sm font-medium text-blue-900 mb-3">
                                        <i class="fas fa-key text-blue-600 mr-2"></i>
                                        トークン情報
                                    </h3>

                                    @php $tokenInfo = $authStatus['token_info']; @endphp

                                    <!-- アクセストークン期限 -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-blue-800">アクセストークン期限:</span>
                                            @if($tokenInfo['access_token_expires_at'])
                                                @php
                                                    $isExpired = $tokenInfo['is_access_token_expired'];
                                                    $willExpireSoon = $tokenInfo['will_expire_soon'];
                                                    $statusClass = $isExpired ? 'text-red-600' : ($willExpireSoon ? 'text-yellow-600' : 'text-green-600');
                                                @endphp
                                                <span class="text-sm {{ $statusClass }} font-medium" data-token-expiry>
                                                    {{ $tokenInfo['access_token_expires_at_formatted'] }}
                                                    @if($isExpired)
                                                        <i class="fas fa-exclamation-triangle ml-1"></i>
                                                    @elseif($willExpireSoon)
                                                        <i class="fas fa-clock ml-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-500">無期限</span>
                                            @endif
                                        </div>


                                        <!-- 最終更新日時 -->
                                        @if($tokenInfo['last_refreshed_at'])
                                            <div class="flex items-center justify-between border-t border-blue-200 pt-2 mt-2">
                                                <span class="text-sm text-blue-700">最終更新:</span>
                                                <span class="text-sm text-blue-800">
                                                    {{ $tokenInfo['last_refreshed_at_formatted'] }}
                                                </span>
                                            </div>
                                        @endif

                                        <!-- リフレッシュトークン状態 -->
                                        <div class="flex items-center justify-between border-t border-blue-200 pt-2">
                                            <span class="text-sm text-blue-700">リフレッシュトークン:</span>
                                            <span class="text-sm {{ $tokenInfo['has_refresh_token'] ? 'text-green-600' : 'text-red-600' }}">
                                                @if($tokenInfo['has_refresh_token'])
                                                    <i class="fas fa-check mr-1"></i>利用可能
                                                @else
                                                    <i class="fas fa-times mr-1"></i>利用不可
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 期限切れ警告 -->
                                @if($tokenInfo['is_access_token_expired'])
                                    <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-200">
                                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                        <span class="text-sm text-red-800">
                                            アクセストークンが期限切れです。自動でリフレッシュを試行しますが、問題が続く場合は再認証してください。
                                        </span>
                                    </div>
                                @elseif($tokenInfo['will_expire_soon'])
                                    <div class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                        <i class="fas fa-clock text-yellow-500 mr-2"></i>
                                        <span class="text-sm text-yellow-800">
                                            アクセストークンが30分以内に期限切れになります。
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-2 sm:flex sm:space-x-3">
                            <button id="test-connection" class="px-2 sm:px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-link sm:mr-2"></i><span class="hidden sm:inline">接続テスト</span><span class="sm:hidden">テスト</span>
                            </button>
                            <button id="refresh-token" class="px-2 sm:px-4 py-2 bg-green-600 text-white text-xs sm:text-sm rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-sync sm:mr-2"></i><span class="hidden sm:inline">トークン更新</span><span class="sm:hidden">更新</span>
                            </button>
                            <button id="revoke-auth" class="px-2 sm:px-4 py-2 bg-red-600 text-white text-xs sm:text-sm rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-sign-out-alt sm:mr-2"></i><span class="hidden sm:inline">認証解除</span><span class="sm:hidden">解除</span>
                            </button>
                        </div>
                    @else
                        <div class="flex items-center p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">Dropbox認証が必要です</p>
                                <p class="text-sm text-yellow-700">バックアップを実行するには認証を完了してください</p>
                            </div>
                        </div>

                        <a href="{{ route('dropbox.redirect') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fab fa-dropbox mr-2"></i>Dropbox認証を開始
                        </a>
                    @endif
                </div>
            </div>

            <!-- バックアップ実行 -->
            <div class="bg-white rounded-lg shadow-lg p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-play-circle text-green-600 mr-2"></i>
                    バックアップ実行
                </h2>

                <div class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h3 class="font-medium text-blue-900 mb-2">バックアップに含まれるもの:</h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li><i class="fas fa-database mr-2"></i>データベース (MySQL)</li>
                            <li><i class="fas fa-folder mr-2"></i>アプリケーションファイル</li>
                            <li><i class="fas fa-file mr-2"></i>アップロードファイル</li>
                        </ul>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="upload-to-dropbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="upload-to-dropbox" class="text-sm text-gray-700">Dropboxにアップロード</label>
                    </div>

                    <button id="run-backup" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium" @if(!$isAuthenticated) disabled @endif>
                        <i class="fas fa-rocket mr-2"></i>バックアップ実行
                    </button>

                    @if(!$isAuthenticated)
                        <p class="text-sm text-gray-500 text-center">※ Dropbox認証が必要です</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- バックアップ結果 -->
        <div id="backup-results" class="hidden bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                バックアップ結果
            </h2>
            <div id="backup-results-content"></div>
        </div>

        <!-- タブナビゲーション -->
        <div class="bg-white rounded-lg shadow-lg mt-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 sm:space-x-8 px-4 sm:px-6">
                    <button id="backup-tab" class="tab-button active py-3 sm:py-4 px-1 border-b-2 border-blue-500 font-medium text-xs sm:text-sm text-blue-600 whitespace-nowrap">
                        <i class="fas fa-cloud-upload-alt mr-1 sm:mr-2"></i>バックアップ履歴
                    </button>
                    <button id="restore-tab" class="tab-button py-3 sm:py-4 px-1 border-b-2 border-transparent font-medium text-xs sm:text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-cloud-download-alt mr-1 sm:mr-2"></i>データ復元
                    </button>
                </nav>
            </div>

            <!-- バックアップ履歴タブ -->
            <div id="backup-content" class="tab-content p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                        <i class="fas fa-history text-indigo-600 mr-1 sm:mr-2"></i>
                        バックアップ履歴
                    </h2>
                    <button id="refresh-backups" class="px-3 sm:px-4 py-2 bg-indigo-600 text-white text-xs sm:text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-sync mr-1 sm:mr-2"></i>更新
                    </button>
                </div>

                <div id="backup-list" class="space-y-2">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>バックアップ履歴を読み込み中...</p>
                    </div>
                </div>
            </div>

            <!-- データ復元タブ -->
            <div id="restore-content" class="tab-content p-4 sm:p-6 hidden">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                        <i class="fas fa-cloud-download-alt text-red-600 mr-1 sm:mr-2"></i>
                        データ復元
                    </h2>
                    <button id="refresh-restorable" class="px-3 sm:px-4 py-2 bg-red-600 text-white text-xs sm:text-sm rounded-lg hover:bg-red-700 transition-colors whitespace-nowrap">
                        <i class="fas fa-sync mr-1 sm:mr-2"></i><span class="hidden sm:inline">復元可能なバックアップを更新</span><span class="sm:hidden">更新</span>
                    </button>
                </div>

                <!-- 警告メッセージ -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">重要な注意事項</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>復元処理は現在のすべてのデータを<strong>完全に置き換えます</strong></li>
                                    <li>復元前に現在のデータの自動バックアップを作成することを<strong>強く推奨</strong>します</li>
                                    <li>復元中はシステムが一時的に利用できなくなります</li>
                                    <li>現在はMySQLデータベースの復元のみ対応しています</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 復元可能なバックアップ一覧 -->
                <div id="restorable-backups" class="space-y-2">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>復元可能なバックアップを読み込み中...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ローディングモーダル -->
    <div id="loading-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-blue-600 text-3xl mb-4"></i>
                <p id="loading-message" class="text-gray-900 font-medium mb-2">バックアップ実行中...</p>
                <p class="text-gray-600 text-sm">しばらくお待ちください</p>
            </div>
        </div>
    </div>

    <!-- 復元確認モーダル -->
    <div id="restore-confirm-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-lg mx-4">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">データ復元の確認</h3>
            </div>

            <div id="restore-confirm-content" class="mb-6">
                <!-- 復元内容がここに動的に挿入されます -->
            </div>


            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" id="create-backup-first" checked
                           class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">復元前に現在のデータをバックアップする（強く推奨）</span>
                </label>
            </div>

            <div class="flex space-x-3">
                <button id="cancel-restore" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    キャンセル
                </button>
                <button id="confirm-restore" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    復元を実行
                </button>
            </div>
        </div>
    </div>

    <!-- 環境設定(.env)バックアップセクション -->
    <div class="env-backup-card bg-red-50 border-2 border-red-200 rounded-lg p-4 sm:p-6 mt-8">
        <h3 class="text-base sm:text-lg font-semibold text-red-800 mb-4">
            <i class="fas fa-lock mr-2"></i>環境設定(.env)の暗号化バックアップ
        </h3>

        <!-- 警告メッセージ -->
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>重要:</strong> パスワードは安全な場所に保管してください。
                        パスワードを紛失した場合、バックアップは復元できません。
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- バックアップセクション -->
            <div class="backup-section">
                <h4 class="font-medium text-gray-900 mb-3">新規バックアップ作成</h4>
                <button id="create-env-backup" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-shield-alt mr-2"></i>暗号化バックアップ
                </button>
            </div>

            <!-- 復元セクション -->
            <div class="restore-section">
                <h4 class="font-medium text-gray-900 mb-3">バックアップから復元</h4>
                <button id="restore-env-backup" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>環境設定復元
                </button>
            </div>
        </div>

        <!-- 最新バックアップ情報 -->
        <div id="latest-env-backup" class="mt-4 text-sm text-gray-600">
            <i class="fas fa-info-circle mr-1"></i>読み込み中...
        </div>
    </div>

    <!-- 環境設定バックアップモーダル -->
    <div id="env-backup-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md mx-4 w-full">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-lock text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">環境設定の暗号化バックアップ</h3>
            </div>

            <div class="space-y-4">
                <!-- パスワード入力 -->
                <div>
                    <label for="env-backup-password" class="block text-sm font-medium text-gray-700 mb-2">
                        暗号化パスワード
                    </label>
                    <input type="password" id="env-backup-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                           placeholder="12文字以上の強力なパスワード"
                           autocomplete="off"
                           data-lpignore="true"
                           data-1p-ignore="true">
                    <div id="password-requirements" class="mt-2 text-xs text-gray-500">
                        <p>パスワード要件:</p>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>12文字以上</li>
                            <li>大文字・小文字・数字・特殊文字を含む</li>
                        </ul>
                    </div>
                </div>

                <!-- パスワード強度メーター -->
                <div>
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span>パスワード強度</span>
                        <span id="password-strength-text">-</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="password-strength-bar" class="bg-gray-300 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>

                <!-- パスワード確認 -->
                <div>
                    <label for="env-backup-password-confirm" class="block text-sm font-medium text-gray-700 mb-2">
                        パスワード確認
                    </label>
                    <input type="password" id="env-backup-password-confirm"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                           placeholder="パスワードを再入力"
                           autocomplete="off">
                </div>

                <!-- エラーメッセージ -->
                <div id="env-backup-error" class="hidden text-sm text-red-600"></div>
            </div>

            <!-- ボタン -->
            <div class="flex space-x-3 mt-6">
                <button id="cancel-env-backup" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    キャンセル
                </button>
                <button id="confirm-env-backup" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors" disabled>
                    バックアップ実行
                </button>
            </div>
        </div>
    </div>

    <!-- 環境設定復元モーダル -->
    <div id="env-restore-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl mx-4 w-full max-h-screen overflow-y-auto">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 mb-4">
                    <i class="fas fa-download text-orange-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">環境設定の復元</h3>
            </div>

            <div class="space-y-4">
                <!-- バックアップファイル選択 -->
                <div>
                    <label for="env-restore-file" class="block text-sm font-medium text-gray-700 mb-2">
                        復元するバックアップファイル
                    </label>
                    <select id="env-restore-file" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                        <option value="">読み込み中...</option>
                    </select>
                </div>

                <!-- パスワード入力 -->
                <div id="env-restore-password-section">
                    <label for="env-restore-password" class="block text-sm font-medium text-gray-700 mb-2">
                        復号化パスワード
                    </label>
                    <input type="password" id="env-restore-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                           placeholder="バックアップ時のパスワード"
                           autocomplete="off">
                    <div id="restore-attempts-warning" class="hidden mt-2 text-sm text-red-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <span id="attempts-remaining">3</span>回まで試行可能です
                    </div>
                </div>

                <!-- オプション -->
                <div class="space-y-3">

                    <label class="flex items-center">
                        <input type="checkbox" id="env-preview-only"
                               class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">プレビューのみ（適用しない）</span>
                    </label>
                </div>

                <!-- プレビュー表示エリア -->
                <div id="env-preview-section" class="hidden">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">環境設定プレビュー</h4>
                    <div class="border border-gray-200 rounded-md p-3 bg-gray-50 max-h-60 overflow-y-auto">
                        <div id="env-preview-content" class="text-sm font-mono"></div>
                    </div>
                </div>

                <!-- エラーメッセージ -->
                <div id="env-restore-error" class="hidden text-sm text-red-600"></div>
            </div>

            <!-- ボタン -->
            <div class="flex space-x-3 mt-6">
                <button id="cancel-env-restore" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    キャンセル
                </button>
                <button id="confirm-env-restore" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors" disabled>
                    復元実行
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // バックアップ実行
    document.getElementById('run-backup').addEventListener('click', function() {
        const uploadToDropbox = document.getElementById('upload-to-dropbox').checked;

        document.getElementById('loading-modal').classList.remove('hidden');

        fetch('{{ route("admin.backup.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                upload_to_dropbox: uploadToDropbox
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-modal').classList.add('hidden');

            if (data.success) {
                showBackupResults(data);
                showAlert('success', 'バックアップが正常に完了しました');
                loadBackupList();
            } else {
                showAlert('error', 'バックアップに失敗しました: ' + data.error);
            }
        })
        .catch(error => {
            document.getElementById('loading-modal').classList.add('hidden');
            showAlert('error', 'エラーが発生しました: ' + error.message);
        });
    });

    // バックアップ履歴読み込み
    function loadBackupList() {
        fetch('{{ route("admin.backup.list") }}')
        .then(response => response.json())
        .then(data => {
            const listContainer = document.getElementById('backup-list');

            if (data.success && data.backups.length > 0) {
                listContainer.innerHTML = data.backups.map(backup => `
                    <div class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 rounded-lg border">
                        <div class="font-medium text-gray-900 text-sm sm:text-base">
                            <i class="fas fa-folder text-blue-500 mr-2"></i>${backup.name}
                        </div>
                    </div>
                `).join('');
            } else if (data.success) {
                listContainer.innerHTML = '<div class="text-center text-gray-500 py-8">バックアップが見つかりません</div>';
            } else {
                listContainer.innerHTML = '<div class="text-center text-red-500 py-8">エラー: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('backup-list').innerHTML = '<div class="text-center text-red-500 py-8">読み込みエラー: ' + error.message + '</div>';
        });
    }

    // バックアップ結果表示
    function showBackupResults(data) {
        const resultsContainer = document.getElementById('backup-results');
        const contentContainer = document.getElementById('backup-results-content');

        let html = `<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <span class="font-medium text-green-800">バックアップ完了: ${data.timestamp}</span>
            </div>
        </div>`;

        if (data.results) {
            html += '<div class="space-y-3">';
            Object.entries(data.results).forEach(([type, result]) => {
                if (typeof result === 'object' && result.success !== undefined) {
                    const icon = result.success ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500';
                    const bgColor = result.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';

                    html += `<div class="p-3 ${bgColor} border rounded">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="${icon} mr-2"></i>
                                <span class="font-medium">${type.toUpperCase()}</span>
                            </div>
                            ${result.size ? `<span class="text-sm text-gray-600">${(result.size / 1024 / 1024).toFixed(2)} MB</span>` : ''}
                        </div>
                        ${result.dropbox_path ? `<div class="text-sm text-gray-600 mt-1">Dropbox: ${result.dropbox_path}</div>` : ''}
                    </div>`;
                }
            });
            html += '</div>';
        }

        contentContainer.innerHTML = html;
        resultsContainer.classList.remove('hidden');
    }

    // 日付フォーマット
    function formatBackupDate(backupName) {
        const match = backupName.match(/(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})/);
        if (match) {
            return `${match[1]}/${match[2]}/${match[3]} ${match[4]}:${match[5]}:${match[6]}`;
        }
        return backupName;
    }

    // アラート表示
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';

        alertDiv.className = `fixed top-4 right-4 z-50 p-4 border rounded-lg ${bgColor} max-w-md`;
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <i class="${icon} mr-2"></i>
                <span>${message}</span>
                <button class="ml-auto" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    // バックアップ履歴更新ボタン
    document.getElementById('refresh-backups').addEventListener('click', loadBackupList);

    // トークン期限チェック機能
    function checkTokenExpiration() {
        @if($isAuthenticated && $authStatus && $authStatus['success'] && isset($authStatus['token_info']))
            const tokenInfo = @json($authStatus['token_info']);

            if (tokenInfo.access_token_expires_at) {
                const now = new Date();
                const expiresAt = new Date(tokenInfo.access_token_expires_at);
                const minutesUntilExpiry = Math.floor((expiresAt - now) / (1000 * 60));

                // 期限切れまたは10分以内の場合は警告
                if (minutesUntilExpiry <= 0) {
                    showTokenWarning('danger', 'アクセストークンが期限切れです。再認証が必要な場合があります。');
                } else if (minutesUntilExpiry <= 10) {
                    showTokenWarning('warning', `アクセストークンが${minutesUntilExpiry}分後に期限切れになります。`);
                }

                // 残り時間を更新
                updateRemainingTime(minutesUntilExpiry);
            }
        @endif
    }

    function showTokenWarning(type, message) {
        const existingAlert = document.getElementById('token-warning');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alertDiv = document.createElement('div');
        alertDiv.id = 'token-warning';

        const bgColor = type === 'danger' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800';
        const icon = type === 'danger' ? 'fas fa-exclamation-triangle' : 'fas fa-clock';

        alertDiv.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 p-4 border rounded-lg ${bgColor} max-w-md shadow-lg`;
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <i class="${icon} mr-2"></i>
                <span class="text-sm">${message}</span>
                <button class="ml-auto text-lg hover:opacity-70" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(alertDiv);

        // 30秒後に自動で削除
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 30000);
    }

    function updateRemainingTime(minutesRemaining) {
        const timeElements = document.querySelectorAll('[data-remaining-time]');
        timeElements.forEach(element => {
            if (minutesRemaining <= 0) {
                element.textContent = '期限切れ';
                element.className = 'text-sm text-red-600 font-mono';
            } else if (minutesRemaining >= 60) {
                const hours = Math.floor(minutesRemaining / 60);
                const mins = minutesRemaining % 60;
                element.textContent = `${hours}時間${mins}分`;
            } else {
                element.textContent = `${minutesRemaining}分`;
            }
        });
    }

    // 接続テストボタンの機能追加
    @if($isAuthenticated)
        document.getElementById('test-connection').addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>テスト中...';
            button.disabled = true;

            fetch('{{ route("admin.backup.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', '接続テストが成功しました');
                    // トークン情報を更新
                    if (data.token_info) {
                        updateTokenDisplay(data.token_info);
                    }
                } else {
                    showAlert('error', '接続テストに失敗しました: ' + data.error);
                }
            })
            .catch(error => {
                showAlert('error', '接続テスト中にエラーが発生しました: ' + error.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });

        // トークン更新ボタンの機能追加
        document.getElementById('refresh-token').addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>更新中...';
            button.disabled = true;

            fetch('{{ route("admin.backup.refresh-token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'トークンが正常に更新されました');
                    // トークン情報を更新
                    if (data.token_info) {
                        updateTokenDisplay(data.token_info);
                        // ページをリロードしてトークン情報を完全に更新
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showAlert('error', 'トークン更新に失敗しました: ' + data.error);
                }
            })
            .catch(error => {
                showAlert('error', 'トークン更新中にエラーが発生しました: ' + error.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });

        // トークン情報表示を更新する関数
        function updateTokenDisplay(tokenInfo) {
            // トークン期限表示を更新
            const expiryElement = document.querySelector('[data-token-expiry]');
            if (expiryElement && tokenInfo.access_token_expires_at_formatted) {
                expiryElement.textContent = tokenInfo.access_token_expires_at_formatted;
            }

            // 残り時間表示を更新
            const remainingElement = document.querySelector('[data-remaining-time]');
            if (remainingElement && tokenInfo.expires_in_minutes) {
                updateRemainingTime(tokenInfo.expires_in_minutes);
            }
        }

        // 30秒ごとにトークン期限をチェック
        setInterval(checkTokenExpiration, 30000);

        // 初回チェック
        setTimeout(checkTokenExpiration, 1000);
    @endif

    // タブ切り替え機能
    function initializeTabs() {
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetId = this.id.replace('-tab', '-content');

                // タブスタイルの切り替え
                tabs.forEach(t => {
                    t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                    t.classList.add('border-transparent', 'text-gray-500');
                });

                this.classList.add('active', 'border-blue-500', 'text-blue-600');
                this.classList.remove('border-transparent', 'text-gray-500');

                // コンテンツの切り替え
                contents.forEach(content => {
                    content.classList.add('hidden');
                });

                document.getElementById(targetId).classList.remove('hidden');

                // データ復元タブがアクティブになった時に復元可能なバックアップを読み込み
                if (targetId === 'restore-content') {
                    loadRestorableBackups();
                }
            });
        });
    }

    // 復元可能なバックアップ一覧を読み込み
    function loadRestorableBackups() {
        const listContainer = document.getElementById('restorable-backups');
        listContainer.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>復元可能なバックアップを読み込み中...</p></div>';

        fetch('{{ route("admin.backup.restorable") }}')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Restorable backups response:', data);

            if (data.success && data.backups.length > 0) {
                listContainer.innerHTML = data.backups.map(backup => `
                    <div class="p-3 sm:p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 text-sm sm:text-base">
                                    <i class="fas fa-folder text-blue-500 mr-2"></i>${backup.name}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    ${backup.files && backup.files.length > 0 ? backup.files.map(file => `${file.type}: ${(file.size / 1024 / 1024).toFixed(2)} MB`).join(' | ') : ''}
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                ${(backup.files && backup.files.some(f => f.type === 'database')) ? `
                                    <button class="restore-button px-3 sm:px-4 py-2 bg-red-600 text-white text-xs sm:text-sm rounded-lg hover:bg-red-700 transition-colors"
                                            data-timestamp="${backup.name}">
                                        <i class="fas fa-download mr-1 sm:mr-2"></i>復元
                                    </button>
                                ` : `
                                    <span class="px-3 sm:px-4 py-2 bg-gray-300 text-gray-500 text-xs sm:text-sm rounded-lg cursor-not-allowed">
                                        復元不可
                                    </span>
                                `}
                            </div>
                        </div>
                    </div>
                `).join('');

                // 復元ボタンのイベントリスナーを追加
                document.querySelectorAll('.restore-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const timestamp = this.getAttribute('data-timestamp');
                        showRestoreConfirmation(timestamp);
                    });
                });

            } else if (data.success) {
                listContainer.innerHTML = '<div class="text-center text-gray-500 py-8">復元可能なバックアップが見つかりません</div>';
            } else {
                console.error('API error:', data.error);
                listContainer.innerHTML = '<div class="text-center text-red-500 py-8">エラー: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            listContainer.innerHTML = '<div class="text-center text-red-500 py-8">読み込みエラー: ' + error.message + '</div>';
        });
    }

    // 復元確認モーダルを表示
    function showRestoreConfirmation(timestamp) {
        // バックアップ詳細を取得
        fetch('{{ route("admin.backup.validate-restore") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ timestamp: timestamp })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const backup = data.backup_info;
                const confirmContent = document.getElementById('restore-confirm-content');

                confirmContent.innerHTML = `
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">復元するバックアップ</h4>
                            <p class="text-sm text-gray-600">日時: ${backup.date || backup.name}</p>
                            <p class="text-sm text-gray-600">ファイル数: ${backup.files.length}</p>
                            <p class="text-sm text-gray-600">合計サイズ: ${(backup.total_size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>

                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-medium text-yellow-800 mb-2">警告事項</h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                ${data.warnings.map(warning => `<li>• ${warning}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `;

                // モーダルを表示
                document.getElementById('restore-confirm-modal').classList.remove('hidden');

                // 復元実行ボタンにイベントリスナーを追加
                document.getElementById('confirm-restore').onclick = function() {
                    executeRestore(timestamp);
                };

            } else {
                showAlert('error', 'バックアップ情報の取得に失敗しました: ' + data.error);
            }
        })
        .catch(error => {
            showAlert('error', 'エラーが発生しました: ' + error.message);
        });
    }

    // 復元を実行
    function executeRestore(timestamp) {
        const createBackupFirst = document.getElementById('create-backup-first').checked;

        // 確認モーダルを閉じて、ローディングモーダルを表示
        document.getElementById('restore-confirm-modal').classList.add('hidden');
        document.getElementById('loading-modal').classList.remove('hidden');
        document.getElementById('loading-message').textContent = 'データ復元中...';

        fetch('{{ route("admin.backup.restore") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                timestamp: timestamp,
                create_backup_first: createBackupFirst
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-modal').classList.add('hidden');

            if (data.success) {
                showAlert('success', 'データの復元が完了しました');
                // 成功時は少し待ってからページをリロード（データが更新されるため）
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('error', 'データの復元に失敗しました: ' + data.error);
            }
        })
        .catch(error => {
            document.getElementById('loading-modal').classList.add('hidden');
            showAlert('error', '復元中にエラーが発生しました: ' + error.message);
        });
    }

    // 復元可能なバックアップ更新ボタン
    document.getElementById('refresh-restorable').addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>更新中...';
        button.disabled = true;

        loadRestorableBackups();

        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1000);
    });

    // モーダルのキャンセルボタン
    document.getElementById('cancel-restore').addEventListener('click', function() {
        document.getElementById('restore-confirm-modal').classList.add('hidden');
    });

    // タブの初期化
    initializeTabs();

    // 環境設定バックアップ関連の初期化
    initializeEnvBackup();

    // 初期読み込み
    @if($isAuthenticated)
        loadBackupList();
        loadEnvBackupList();
    @else
        document.getElementById('backup-list').innerHTML = '<div class="text-center text-gray-500 py-8">Dropbox認証後に履歴を表示します</div>';
        document.getElementById('restorable-backups').innerHTML = '<div class="text-center text-gray-500 py-8">Dropbox認証後に復元機能を利用できます</div>';
        document.getElementById('latest-env-backup').innerHTML = '<i class="fas fa-info-circle mr-1"></i>Dropbox認証後に情報を表示します';
    @endif

    // 環境設定バックアップの初期化
    function initializeEnvBackup() {
        // 環境設定バックアップボタン
        document.getElementById('create-env-backup').addEventListener('click', function() {
            document.getElementById('env-backup-modal').classList.remove('hidden');
            document.getElementById('env-backup-password').focus();
        });

        // 環境設定復元ボタン
        document.getElementById('restore-env-backup').addEventListener('click', function() {
            loadEnvBackupList();
            document.getElementById('env-restore-modal').classList.remove('hidden');
        });

        // バックアップモーダルのキャンセル
        document.getElementById('cancel-env-backup').addEventListener('click', function() {
            resetEnvBackupModal();
            document.getElementById('env-backup-modal').classList.add('hidden');
        });

        // 復元モーダルのキャンセル
        document.getElementById('cancel-env-restore').addEventListener('click', function() {
            resetEnvRestoreModal();
            document.getElementById('env-restore-modal').classList.add('hidden');
        });

        // パスワード入力時のリアルタイム検証
        const passwordInput = document.getElementById('env-backup-password');
        const confirmInput = document.getElementById('env-backup-password-confirm');
        const confirmButton = document.getElementById('confirm-env-backup');

        passwordInput.addEventListener('input', function() {
            validateEnvBackupPassword();
        });

        confirmInput.addEventListener('input', function() {
            validateEnvBackupPassword();
        });

        // バックアップ実行
        confirmButton.addEventListener('click', function() {
            executeEnvBackup();
        });

        // 復元ファイル選択時
        document.getElementById('env-restore-file').addEventListener('change', function() {
            const selectedFile = this.value;
            if (selectedFile) {
                const isEncrypted = selectedFile.includes('encrypted');
                document.getElementById('env-restore-password-section').style.display = isEncrypted ? 'block' : 'none';
                validateEnvRestore();
            }
        });

        // 復元パスワード入力時
        document.getElementById('env-restore-password').addEventListener('input', function() {
            validateEnvRestore();
        });

        // 復元実行
        document.getElementById('confirm-env-restore').addEventListener('click', function() {
            executeEnvRestore();
        });

        // プレビューオプション
        document.getElementById('env-preview-only').addEventListener('change', function() {
            const confirmButton = document.getElementById('confirm-env-restore');
            if (this.checked) {
                confirmButton.textContent = 'プレビュー表示';
            } else {
                confirmButton.textContent = '復元実行';
            }
            validateEnvRestore();
        });
    }

    // パスワード検証とUI更新
    function validateEnvBackupPassword() {
        const password = document.getElementById('env-backup-password').value;
        const confirm = document.getElementById('env-backup-password-confirm').value;
        const confirmButton = document.getElementById('confirm-env-backup');
        const errorDiv = document.getElementById('env-backup-error');

        if (password.length >= 3) {
            // パスワード強度をサーバーで検証
            fetch('{{ route("admin.backup.env.validate-password") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ password: password })
            })
            .then(response => response.json())
            .then(data => {
                updatePasswordStrengthMeter(data.score, data.strength);

                if (!data.valid) {
                    showEnvBackupError('パスワード要件: ' + data.errors.join(', '));
                    confirmButton.disabled = true;
                } else if (password !== confirm && confirm.length > 0) {
                    showEnvBackupError('パスワードが一致しません');
                    confirmButton.disabled = true;
                } else if (password === confirm && confirm.length > 0) {
                    hideEnvBackupError();
                    confirmButton.disabled = false;
                } else {
                    hideEnvBackupError();
                    confirmButton.disabled = true;
                }
            })
            .catch(error => {
                console.error('Password validation error:', error);
            });
        } else {
            updatePasswordStrengthMeter(0, '-');
            confirmButton.disabled = true;
        }
    }

    // パスワード強度メーター更新
    function updatePasswordStrengthMeter(score, strength) {
        const bar = document.getElementById('password-strength-bar');
        const text = document.getElementById('password-strength-text');

        text.textContent = strength;
        bar.style.width = score + '%';

        // 色の設定
        if (score >= 80) {
            bar.className = 'bg-green-500 h-2 rounded-full transition-all';
        } else if (score >= 60) {
            bar.className = 'bg-yellow-500 h-2 rounded-full transition-all';
        } else {
            bar.className = 'bg-red-500 h-2 rounded-full transition-all';
        }
    }

    // 環境設定バックアップ実行
    function executeEnvBackup() {
        const password = document.getElementById('env-backup-password').value;

        document.getElementById('env-backup-modal').classList.add('hidden');
        document.getElementById('loading-modal').classList.remove('hidden');
        document.getElementById('loading-message').textContent = '環境設定を暗号化中...';

        fetch('{{ route("admin.backup.env.backup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ password: password })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-modal').classList.add('hidden');

            if (data.success) {
                showAlert('success', '環境設定のバックアップが完了しました');
                loadEnvBackupList();
            } else {
                showAlert('error', data.error || 'バックアップに失敗しました');
            }
        })
        .catch(error => {
            document.getElementById('loading-modal').classList.add('hidden');
            showAlert('error', 'エラーが発生しました: ' + error.message);
        })
        .finally(() => {
            resetEnvBackupModal();
        });
    }

    // 環境設定バックアップ一覧の読み込み
    function loadEnvBackupList() {
        fetch('{{ route("admin.backup.env.list") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLatestEnvBackupInfo(data.backups);
                updateEnvRestoreFileList(data.backups);
            } else {
                document.getElementById('latest-env-backup').innerHTML =
                    '<i class="fas fa-exclamation-triangle mr-1"></i>バックアップ一覧の取得に失敗しました';
            }
        })
        .catch(error => {
            document.getElementById('latest-env-backup').innerHTML =
                '<i class="fas fa-exclamation-triangle mr-1"></i>エラーが発生しました';
        });
    }

    // 最新バックアップ情報の更新
    function updateLatestEnvBackupInfo(backups) {
        const latestDiv = document.getElementById('latest-env-backup');

        if (backups.length === 0) {
            latestDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i>環境設定のバックアップなし';
        } else {
            const latest = backups[0];
            const typeIcon = latest.encrypted ? '🔒' : '📄';
            latestDiv.innerHTML =
                `<i class="fas fa-info-circle mr-1"></i>最新バックアップ: ${typeIcon} ${latest.modified_formatted} (${latest.size_formatted})`;
        }
    }

    // 復元ファイル一覧の更新
    function updateEnvRestoreFileList(backups) {
        const select = document.getElementById('env-restore-file');
        select.innerHTML = '<option value="">バックアップファイルを選択してください</option>';

        backups.forEach(backup => {
            const option = document.createElement('option');
            option.value = backup.path;
            option.dataset.encrypted = backup.encrypted;

            const typeIcon = backup.encrypted ? '🔒' : '📄';
            option.textContent = `${typeIcon} ${backup.name} (${backup.size_formatted}) - ${backup.modified_formatted}`;

            select.appendChild(option);
        });
    }

    // 環境設定復元実行
    function executeEnvRestore() {
        const backupFile = document.getElementById('env-restore-file').value;
        const password = document.getElementById('env-restore-password').value;
        const isEncrypted = document.getElementById('env-restore-file').selectedOptions[0]?.dataset.encrypted === 'true';
        const createBackupFirst = false;
        const previewOnly = document.getElementById('env-preview-only').checked;

        document.getElementById('env-restore-modal').classList.add('hidden');
        document.getElementById('loading-modal').classList.remove('hidden');
        document.getElementById('loading-message').textContent = previewOnly ? 'プレビュー生成中...' : '環境設定を復元中...';

        fetch('{{ route("admin.backup.env.restore") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                backup_file: backupFile,
                password: password,
                is_encrypted: isEncrypted,
                create_backup_first: createBackupFirst,
                preview_only: previewOnly
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading-modal').classList.add('hidden');

            if (data.success) {
                if (previewOnly) {
                    showEnvPreview(data.preview);
                    document.getElementById('env-restore-modal').classList.remove('hidden');
                } else {
                    showAlert('success', '環境設定の復元が完了しました');
                    resetEnvRestoreModal();
                }
            } else {
                if (data.remaining_attempts !== undefined) {
                    document.getElementById('attempts-remaining').textContent = data.remaining_attempts;
                    document.getElementById('restore-attempts-warning').classList.remove('hidden');
                }
                showEnvRestoreError(data.error);
                document.getElementById('env-restore-modal').classList.remove('hidden');
            }
        })
        .catch(error => {
            document.getElementById('loading-modal').classList.add('hidden');
            showAlert('error', 'エラーが発生しました: ' + error.message);
            document.getElementById('env-restore-modal').classList.remove('hidden');
        });
    }

    // プレビュー表示
    function showEnvPreview(preview) {
        const previewSection = document.getElementById('env-preview-section');
        const previewContent = document.getElementById('env-preview-content');

        let html = '';
        preview.entries.forEach(entry => {
            const secretClass = entry.is_secret ? 'text-red-600' : 'text-gray-800';
            html += `<div class="${secretClass}">${entry.key}=${entry.value}</div>`;
        });

        if (preview.secret_count > 0) {
            html += `<div class="text-xs text-gray-500 mt-2">${preview.secret_count}個の機密値がマスクされています</div>`;
        }

        previewContent.innerHTML = html;
        previewSection.classList.remove('hidden');
    }

    // 復元フォームの検証
    function validateEnvRestore() {
        const backupFile = document.getElementById('env-restore-file').value;
        const password = document.getElementById('env-restore-password').value;
        const isEncrypted = document.getElementById('env-restore-file').selectedOptions[0]?.dataset.encrypted === 'true';
        const confirmButton = document.getElementById('confirm-env-restore');

        if (!backupFile) {
            confirmButton.disabled = true;
            return;
        }

        // 暗号化ファイルの場合はパスワード必須（プレビューでも復号化が必要）
        if (isEncrypted && !password) {
            confirmButton.disabled = true;
            return;
        }

        confirmButton.disabled = false;
    }

    // モーダルリセット
    function resetEnvBackupModal() {
        document.getElementById('env-backup-password').value = '';
        document.getElementById('env-backup-password-confirm').value = '';
        document.getElementById('confirm-env-backup').disabled = true;
        updatePasswordStrengthMeter(0, '-');
        hideEnvBackupError();
    }

    function resetEnvRestoreModal() {
        document.getElementById('env-restore-file').value = '';
        document.getElementById('env-restore-password').value = '';
        document.getElementById('env-preview-only').checked = false;
        document.getElementById('confirm-env-restore').disabled = true;
        document.getElementById('confirm-env-restore').textContent = '復元実行';
        document.getElementById('env-preview-section').classList.add('hidden');
        document.getElementById('restore-attempts-warning').classList.add('hidden');
        hideEnvRestoreError();
    }

    // エラー表示/非表示
    function showEnvBackupError(message) {
        const errorDiv = document.getElementById('env-backup-error');
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function hideEnvBackupError() {
        document.getElementById('env-backup-error').classList.add('hidden');
    }

    function showEnvRestoreError(message) {
        const errorDiv = document.getElementById('env-restore-error');
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function hideEnvRestoreError() {
        document.getElementById('env-restore-error').classList.add('hidden');
    }
});
</script>
@endpush
@endsection