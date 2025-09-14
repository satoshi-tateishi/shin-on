<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class LineWorksController extends Controller
{
    /**
     * Redirect the user to the LINE WORKS authentication page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('lineworks')->redirect();
    }

    /**
     * Obtain the user information from LINE WORKS.
     */
    public function callback()
    {
        try {
            \Log::info('LINE WORKS callback started (Implicit Flow)');

            // リクエストパラメータをログ出力
            \Log::info('Callback query parameters: '.json_encode(request()->query()));
            \Log::info('Callback fragment will be handled by JavaScript');

            // Implicit Flowでは、JavaScript経由でid_tokenを受け取る
            // ここでは、JavaScript処理用の中間ページを返す
            return $this->handleImplicitFlow();
        } catch (\Exception $e) {
            \Log::error('LINE WORKS callback failed: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return redirect('/login')->withErrors([
                'lineworks' => 'LINE WORKS認証に失敗しました: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Handle Implicit Flow callback with JavaScript
     */
    private function handleImplicitFlow()
    {
        // JavaScript処理用のビューを返す
        // 今回は簡易的にJavaScriptでフラグメントを処理してPOSTで送信
        return view('auth.lineworks-callback');
    }

    /**
     * Process ID Token from JavaScript (Implicit Flow)
     */
    public function processIdToken()
    {
        try {
            \Log::info('Processing ID Token from Implicit Flow');

            $idToken = request('id_token');
            $state = request('state');

            if (! $idToken) {
                throw new \Exception('ID Token not found');
            }

            \Log::info('ID Token received: '.substr($idToken, 0, 50).'...');

            // State検証
            if (session('state') !== $state) {
                throw new \Exception('State validation failed');
            }

            // LINE WORKS ProviderからID Tokenを解析
            $provider = new \App\Socialite\LineWorksProvider(
                request(),
                config('services.lineworks.client_id'),
                config('services.lineworks.client_secret'),
                config('services.lineworks.redirect')
            );

            $userData = $provider->parseIdToken($idToken);

            if (! $provider->validateDomain($userData)) {
                throw new \Exception('ドメイン検証に失敗しました');
            }

            // 日本語の姓名順に修正
            $familyName = $userData['family_name'] ?? '';
            $givenName = $userData['given_name'] ?? '';
            $displayName = '';

            if ($familyName && $givenName) {
                $displayName = $familyName.' '.$givenName; // 立石 智史
            } else {
                $displayName = $userData['name'] ?? null; // フォールバック
            }

            // メールアドレスを修正（shin-on1981 → shin-on1981.com）
            $originalEmail = $userData['email'] ?? null;
            $fixedEmail = $this->fixEmailDomain($originalEmail);

            // ユーザーオブジェクトを手動で作成
            $lineWorksUser = (new \Laravel\Socialite\Two\User)->setRaw($userData)->map([
                'id' => $userData['sub'] ?? null,
                'name' => $displayName,
                'email' => $fixedEmail,
                'avatar' => $userData['picture'] ?? null,
            ]);

            // 詳細なログ出力
            \Log::info('LINE WORKS user object details:', [
                'id' => $lineWorksUser->getId(),
                'name' => $lineWorksUser->getName(),
                'email' => $lineWorksUser->getEmail(),
                'avatar' => $lineWorksUser->getAvatar(),
                'token' => substr($lineWorksUser->token ?? 'null', 0, 20).'...',
                'refreshToken' => substr($lineWorksUser->refreshToken ?? 'null', 0, 20).'...',
                'raw_data' => json_encode($lineWorksUser->getRaw()),
            ]);

            // ドメイン検証は既にLineWorksProviderで実行済み
            // ここに到達した時点で認証済みかつドメイン検証済み

            $user = User::where('lineworks_id', $lineWorksUser->getId())
                ->orWhere('email', $lineWorksUser->getEmail())
                ->first();

            if ($user) {
                // 既存ユーザーの場合、LINE WORKS情報を更新
                \Log::info("Updating existing user: {$user->id}");
                $user->update([
                    'lineworks_id' => $lineWorksUser->getId(),
                    'lineworks_token' => $lineWorksUser->token,
                    'lineworks_refresh_token' => $lineWorksUser->refreshToken,
                    'name' => $lineWorksUser->getName() ?: $user->name,
                    'email' => $lineWorksUser->getEmail() ?: $user->email,
                    'icon' => $lineWorksUser->getAvatar(),
                ]);
            } else {
                // 新規ユーザーの場合、アカウントを作成
                \Log::info('Creating new user');
                $user = User::create([
                    'name' => $lineWorksUser->getName(),
                    'email' => $lineWorksUser->getEmail(),
                    'lineworks_id' => $lineWorksUser->getId(),
                    'lineworks_token' => $lineWorksUser->token,
                    'lineworks_refresh_token' => $lineWorksUser->refreshToken,
                    'icon' => $lineWorksUser->getAvatar(),
                    'password' => Hash::make(uniqid()), // ランダムパスワード
                    'is_active' => true,
                ]);
            }

            Auth::login($user);
            \Log::info("User logged in successfully: {$user->id}");

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            \Log::error('LINE WORKS callback failed: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            // ドメイン検証エラーの場合は特別なメッセージ
            if (strpos($e->getMessage(), 'ドメイン検証') !== false) {
                return redirect('/login')->withErrors([
                    'domain' => 'アクセス権限がありません。shin-on1981ドメインのメールアドレスでLINE WORKSにログインしてください。',
                ]);
            }

            return redirect('/login')->withErrors([
                'lineworks' => 'LINE WORKS認証に失敗しました: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Fix email domain for LINE WORKS internal domain
     */
    private function fixEmailDomain(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        // shin-on1981ドメインの場合は.comを追加
        if (str_ends_with($email, '@shin-on1981')) {
            return $email.'.com';
        }

        return $email;
    }

    /**
     * Log the user out.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect('/');
    }
}