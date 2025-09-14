<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LINE WORKS認証処理中...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .processing {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #00c73c;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error {
            color: #cc0000;
            background: #ffe6e6;
            border: 1px solid #ff9999;
            padding: 10px;
            border-radius: 4px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="processing">
        <div class="spinner"></div>
        <h2>LINE WORKS認証処理中...</h2>
        <p>お待ちください...</p>
        <div id="error" class="error hidden"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                console.log('Processing LINE WORKS Implicit Flow callback');
                console.log('Current URL:', window.location.href);

                // URLフラグメントから認証情報を取得
                const fragment = window.location.hash.substring(1);
                console.log('Fragment:', fragment);

                if (!fragment) {
                    throw new Error('認証情報が見つかりません');
                }

                // フラグメントをパラメータに変換
                const params = new URLSearchParams(fragment);
                const idToken = params.get('id_token');
                const state = params.get('state');
                const error = params.get('error');

                console.log('ID Token:', idToken ? idToken.substring(0, 20) + '...' : 'null');
                console.log('State:', state);
                console.log('Error:', error);

                if (error) {
                    throw new Error('認証エラー: ' + error);
                }

                if (!idToken) {
                    throw new Error('ID Tokenが見つかりません');
                }

                // CSRFトークンを取得
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // サーバーにID Tokenを送信
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('id_token', idToken);
                formData.append('state', state);

                console.log('Sending ID Token to server...');

                fetch('/auth/lineworks/process-id-token', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => {
                    console.log('Server response status:', response.status);
                    if (response.ok) {
                        // 成功時はダッシュボードにリダイレクト
                        window.location.href = '/dashboard';
                    } else {
                        return response.text().then(text => {
                            throw new Error(`サーバーエラー (${response.status}): ${text}`);
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    throw error;
                });

            } catch (error) {
                console.error('Authentication error:', error);
                const errorDiv = document.getElementById('error');
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';

                // 3秒後にログインページにリダイレクト
                setTimeout(() => {
                    window.location.href = '/login';
                }, 3000);
            }
        });
    </script>
</body>
</html>