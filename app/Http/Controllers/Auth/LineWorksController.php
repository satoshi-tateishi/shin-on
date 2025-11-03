<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorLog;
use App\Models\User;
use App\Services\LineWorksBotService;
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
                    'icon' => $lineWorksUser->getAvatar(),
                    'is_active' => true,
                ]);
            }

            // 2FA処理: OTPを生成してLINE WORKS Botで送信
            try {
                $otp = $this->generateOTP();
                $hashedOtp = Hash::make($otp);

                // ユーザー情報に2FAコードを保存
                $user->update([
                    'two_factor_code' => $hashedOtp,
                    'two_factor_expires_at' => now()->addMinutes(10),
                    'two_factor_attempts' => 0,
                    'two_factor_locked_until' => null,
                ]);

                // LINE WORKS Bot経由でOTP送信
                // LINE WORKS内部IDを取得（.comを除去）
                $lineworksUserId = str_replace('.com', '', $user->email);

                $botService = app(LineWorksBotService::class);
                $botService->sendOtpMessage($lineworksUserId, $otp);

                // ログ記録
                TwoFactorLog::log($user->id, 'sent');

                \Log::info("2FA OTP sent to user: {$user->id}");

                // セッションにユーザーIDを保存（2FA検証用）
                session(['two_factor:user_id' => $user->id]);
                \Log::info("Session set - two_factor:user_id: {$user->id}");

                // 2FA入力画面にリダイレクト
                \Log::info("Redirecting to two-factor.show");
                return redirect()->route('two-factor.show')
                    ->with('status', '認証コードをLINE WORKSに送信しました。');
            } catch (\Exception $e) {
                \Log::error('2FA OTP send failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                // OTP送信失敗時は通常ログインにフォールバック
                Auth::login($user);
                \Log::warning("User logged in without 2FA due to OTP send failure: {$user->id}");

                return redirect()->intended('/dashboard')
                    ->with('warning', '認証コードの送信に失敗しましたが、ログインしました。');
            }
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
     * カスタムOTP生成（4種類の数字から6桁を生成）
     *
     * セキュリティ強度: 10^6 = 1,000,000通り（全組み合わせ）
     * 実際の組み合わせ: C(10,4) × 4^6 = 210 × 4,096 = 860,160通り（約86%）
     */
    private function generateOTP(): string
    {
        // 0-9から4つの数字をランダム選択
        $availableDigits = range(0, 9);
        shuffle($availableDigits);
        $selectedDigits = array_slice($availableDigits, 0, 4);

        // 選ばれた4つの数字から6桁を生成
        $otp = '';
        for ($i = 0; $i < 6; $i++) {
            $otp .= $selectedDigits[array_rand($selectedDigits)];
        }

        return $otp;
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
