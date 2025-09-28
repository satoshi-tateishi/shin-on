@extends('layouts.app')

@section('title', 'バックアップ管理')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 p-4">
    <div class="max-w-6xl mx-auto">
        <!-- ヘッダー -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-cloud-upload-alt text-blue-600 mr-2"></i>
                        バックアップ管理
                    </h1>
                    <p class="text-gray-600">データベースとファイルのDropboxバックアップを管理します</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">管理者専用機能</div>
                    <div class="text-xs text-gray-400">{{ now()->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Dropbox認証状態 -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fab fa-dropbox text-blue-600 mr-2"></i>
                    Dropbox認証状態
                </h2>

                <div id="auth-status" class="space-y-4">
                    @if($isAuthenticated)
                        <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">Dropbox認証済み</p>
                                @if($authStatus && $authStatus['success'])
                                    <p class="text-sm text-green-700">
                                        アカウント: {{ $authStatus['account_info']['account_name'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button id="test-connection" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-link mr-2"></i>接続テスト
                            </button>
                            <button id="refresh-token" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-sync mr-2"></i>トークン更新
                            </button>
                            <button id="revoke-auth" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i>認証解除
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
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
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

        <!-- バックアップ履歴 -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-history text-indigo-600 mr-2"></i>
                    バックアップ履歴
                </h2>
                <button id="refresh-backups" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-sync mr-2"></i>更新
                </button>
            </div>

            <div id="backup-list" class="space-y-2">
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>バックアップ履歴を読み込み中...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ローディングモーダル -->
    <div id="loading-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-blue-600 text-3xl mb-4"></i>
                <p class="text-gray-900 font-medium mb-2">バックアップ実行中...</p>
                <p class="text-gray-600 text-sm">しばらくお待ちください</p>
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
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                        <div>
                            <div class="font-medium text-gray-900">${backup.name}</div>
                            <div class="text-sm text-gray-600">${backup.full_path}</div>
                        </div>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            ${formatBackupDate(backup.name)}
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

    // 初期読み込み
    @if($isAuthenticated)
        loadBackupList();
    @else
        document.getElementById('backup-list').innerHTML = '<div class="text-center text-gray-500 py-8">Dropbox認証後に履歴を表示します</div>';
    @endif
});
</script>
@endpush
@endsection