<?php

namespace App\Rules;

/**
 * 共通バリデーションルール定義クラス
 *
 * 使用例：
 * $request->validate([
 *     'icon' => ValidationRules::imageIcon(),
 *     'phone' => ValidationRules::phone(),
 *     ...ValidationRules::userCommon(),
 * ]);
 */
class ValidationRules
{
    /**
     * アイコン画像のバリデーションルール
     * 対象: ユーザーアイコン
     */
    public static function imageIcon(bool $required = false): string
    {
        $base = $required ? 'required' : 'nullable';

        return "{$base}|image|mimes:jpeg,png,jpg,gif,webp|max:2048";
    }

    /**
     * ロゴ画像のバリデーションルール
     * 対象: 会社ロゴ（SVG許可）
     */
    public static function imageLogo(bool $required = false): string
    {
        $base = $required ? 'required' : 'nullable';

        return "{$base}|image|mimes:jpeg,png,jpg,gif,svg|max:2048";
    }

    /**
     * 電話番号のバリデーションルール
     */
    public static function phone(): string
    {
        return 'nullable|string|max:20';
    }

    /**
     * 郵便番号のバリデーションルール
     */
    public static function postalCode(): string
    {
        return 'nullable|string|max:8';
    }

    /**
     * 日付のバリデーションルール
     */
    public static function date(bool $required = false): string
    {
        return $required ? 'required|date' : 'nullable|date';
    }

    /**
     * ソート順のバリデーションルール
     */
    public static function sortOrder(): string
    {
        return 'nullable|integer|min:0';
    }

    /**
     * 画像バリデーションのエラーメッセージ
     */
    public static function imageMessages(string $fieldName = 'icon', string $displayName = 'アイコン'): array
    {
        return [
            "{$fieldName}.image" => "{$displayName}は画像ファイルである必要があります。",
            "{$fieldName}.mimes" => "{$displayName}はJPEG、PNG、JPG、GIF、WebP形式のファイルをアップロードしてください。",
            "{$fieldName}.max" => "{$displayName}のファイルサイズは2MB以下である必要があります。",
        ];
    }

    /**
     * ロゴ画像バリデーションのエラーメッセージ（SVG許可）
     */
    public static function logoMessages(string $fieldName = 'logo', string $displayName = 'ロゴ'): array
    {
        return [
            "{$fieldName}.image" => "{$displayName}は画像ファイルである必要があります。",
            "{$fieldName}.mimes" => "{$displayName}はJPEG、PNG、JPG、GIF、SVG形式のファイルをアップロードしてください。",
            "{$fieldName}.max" => "{$displayName}のファイルサイズは2MB以下である必要があります。",
        ];
    }

    /**
     * 日付バリデーションのエラーメッセージ
     */
    public static function dateMessages(string $fieldName, string $displayName): array
    {
        return [
            "{$fieldName}.date" => "{$displayName}は正しい日付形式で入力してください。",
        ];
    }

    /**
     * ソート順バリデーションのエラーメッセージ
     */
    public static function sortMessages(string $fieldName = 'sort', string $displayName = 'ソート順'): array
    {
        return [
            "{$fieldName}.integer" => "{$displayName}は数値で入力してください。",
            "{$fieldName}.min" => "{$displayName}は0以上で入力してください。",
        ];
    }
}
