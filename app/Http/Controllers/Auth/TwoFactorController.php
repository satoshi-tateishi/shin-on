<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorLog;
use App\Services\ActivityLogService;
use App\Services\LineWorksBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    /**
     * OTP入力画面を表示
     */
    public function show(): View|RedirectResponse
    {
        \Log::info('TwoFactorController::show() called');
        \Log::info('Session data: '.json_encode(session()->all()));

        // 2FA未認証の場合のみアクセス可能
        if (! session()->has('two_factor:user_id')) {
            \Log::warning('No two_factor:user_id in session, redirecting to login');

            return redirect()->route('login');
        }

        \Log::info('Showing two-factor-challenge view');

        return view('auth.two-factor-challenge');
    }

    /**
     * OTPを検証
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.required' => '認証コードを入力してください。',
            'code.size' => '認証コードは6桁です。',
            'code.regex' => '認証コードは数字のみです。',
        ]);

        $userId = session('two_factor:user_id');
        if (! $userId) {
            return redirect()->route('login')
                ->withErrors(['code' => 'セッションが無効です。再度ログインしてください。']);
        }

        $user = \App\Models\User::find($userId);
        if (! $user) {
            session()->forget('two_factor:user_id');

            return redirect()->route('login')
                ->withErrors(['code' => 'ユーザーが見つかりません。']);
        }

        // アカウントロックチェック
        if ($user->two_factor_locked_until && now()->lt($user->two_factor_locked_until)) {
            TwoFactorLog::log($userId, 'locked');
            $remainingMinutes = (int) ceil(now()->diffInMinutes($user->two_factor_locked_until, true));

            return back()->withErrors([
                'code' => "アカウントがロックされています。{$remainingMinutes}分後に再試行してください。",
            ]);
        }

        // コード期限チェック
        if (! $user->two_factor_expires_at || now()->gt($user->two_factor_expires_at)) {
            TwoFactorLog::log($userId, 'failed');

            return back()->withErrors([
                'code' => '認証コードの有効期限が切れています。再送信してください。',
            ]);
        }

        // コード検証
        if (! Hash::check($request->code, $user->two_factor_code)) {
            // 失敗回数をインクリメント
            $user->increment('two_factor_attempts');
            TwoFactorLog::log($userId, 'failed');

            // 5回失敗でアカウントロック（3分）
            if ($user->two_factor_attempts >= 5) {
                $user->update([
                    'two_factor_locked_until' => now()->addMinutes(3),
                    'two_factor_attempts' => 0,
                ]);
                TwoFactorLog::log($userId, 'locked');

                return back()->withErrors([
                    'code' => '認証に5回失敗しました。アカウントが3分間ロックされます。',
                ]);
            }

            $remainingAttempts = 5 - $user->two_factor_attempts;

            return back()->withErrors([
                'code' => "認証コードが正しくありません。残り{$remainingAttempts}回試行できます。",
            ]);
        }

        // 認証成功
        TwoFactorLog::log($userId, 'verified');

        // 2FA情報クリア
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
            'two_factor_locked_until' => null,
        ]);

        // セッション再生成（セキュリティ）
        $request->session()->regenerate();

        // ログイン処理
        auth()->login($user);
        session()->forget('two_factor:user_id');

        // アクティビティログ記録
        app(ActivityLogService::class)->logUserLogin($user);

        return redirect()->intended(route('dashboard'))
            ->with('status', 'ログインしました。');
    }

    /**
     * OTPを再送信
     */
    public function resend(Request $request, LineWorksBotService $botService): RedirectResponse
    {
        $userId = session('two_factor:user_id');
        if (! $userId) {
            return redirect()->route('login')
                ->withErrors(['code' => 'セッションが無効です。']);
        }

        $user = \App\Models\User::find($userId);
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['code' => 'ユーザーが見つかりません。']);
        }

        // アカウントロックチェック
        if ($user->two_factor_locked_until && now()->lt($user->two_factor_locked_until)) {
            $remainingMinutes = (int) ceil(now()->diffInMinutes($user->two_factor_locked_until, true));

            return back()->withErrors([
                'code' => "アカウントがロックされています。{$remainingMinutes}分後に再試行してください。",
            ]);
        }

        try {
            // 新しいOTP生成
            $otp = $this->generateOTP();
            $hashedOtp = Hash::make($otp);

            // ユーザー情報更新
            $user->update([
                'two_factor_code' => $hashedOtp,
                'two_factor_expires_at' => now()->addMinutes(10),
                'two_factor_attempts' => 0,
            ]);

            // LINE WORKS Bot経由で送信
            $botService->sendOtpMessage($user->lineworks_id, $otp);

            TwoFactorLog::log($userId, 'resent');

            return back()->with('status', '認証コードを再送信しました。');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('2FA OTP resend failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'code' => '認証コードの送信に失敗しました。しばらく待ってから再試行してください。',
            ]);
        }
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
}
