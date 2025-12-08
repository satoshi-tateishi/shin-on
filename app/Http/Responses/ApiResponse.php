<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * 成功レスポンス
     *
     * @param  string|null  $message  成功メッセージ
     * @param  array  $data  追加データ
     * @param  int  $status  HTTPステータスコード
     */
    public static function success(?string $message = null, array $data = [], int $status = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json(array_merge($response, $data), $status);
    }

    /**
     * エラーレスポンス
     *
     * @param  string  $message  エラーメッセージ
     * @param  array  $errors  詳細エラー情報
     * @param  int  $status  HTTPステータスコード
     */
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * 認証エラーレスポンス (401)
     */
    public static function unauthorized(string $message = '認証が必要です。'): JsonResponse
    {
        return self::error($message, [], 401);
    }

    /**
     * 権限エラーレスポンス (403)
     */
    public static function forbidden(string $message = 'この操作を実行する権限がありません。'): JsonResponse
    {
        return self::error($message, [], 403);
    }

    /**
     * Not Foundレスポンス (404)
     */
    public static function notFound(string $message = 'リソースが見つかりません。'): JsonResponse
    {
        return self::error($message, [], 404);
    }

    /**
     * バリデーションエラーレスポンス (422)
     */
    public static function validationError(string $message = 'バリデーションエラー', array $errors = []): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    /**
     * サーバーエラーレスポンス (500)
     */
    public static function serverError(string $message = 'サーバーエラーが発生しました。'): JsonResponse
    {
        return self::error($message, [], 500);
    }

    /**
     * 作成成功レスポンス (201)
     */
    public static function created(string $message = '正常に作成されました。', array $data = []): JsonResponse
    {
        return self::success($message, $data, 201);
    }

    /**
     * データレスポンス（メッセージなし）
     *
     * @param  array  $data  レスポンスデータ
     * @param  int  $status  HTTPステータスコード
     */
    public static function data(array $data, int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $data), $status);
    }
}
