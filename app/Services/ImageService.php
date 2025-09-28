<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * 画像のサムネイルを生成（GD使用）
     */
    public function generateThumbnail(string $originalPath, string $thumbnailPath, int $width = 200, int $height = 200): bool
    {
        try {
            $fullOriginalPath = Storage::disk('public')->path($originalPath);
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);

            // ディレクトリが存在しない場合は作成
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $imageInfo = getimagesize($fullOriginalPath);
            if (!$imageInfo) {
                return false;
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $imageType = $imageInfo[2];

            // 元画像を読み込み
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $originalImage = imagecreatefromjpeg($fullOriginalPath);
                    break;
                case IMAGETYPE_PNG:
                    $originalImage = imagecreatefrompng($fullOriginalPath);
                    break;
                case IMAGETYPE_GIF:
                    $originalImage = imagecreatefromgif($fullOriginalPath);
                    break;
                default:
                    return false;
            }

            if (!$originalImage) {
                return false;
            }

            // アスペクト比を保持してサイズ計算
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);

            // サムネイル画像作成
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // PNG透明度対応
            if ($imageType == IMAGETYPE_PNG) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }

            // リサイズ
            imagecopyresampled($thumbnail, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // 保存
            $result = match ($imageType) {
                IMAGETYPE_JPEG => imagejpeg($thumbnail, $fullThumbnailPath, 80),
                IMAGETYPE_PNG => imagepng($thumbnail, $fullThumbnailPath),
                IMAGETYPE_GIF => imagegif($thumbnail, $fullThumbnailPath),
                default => false,
            };

            // メモリ解放
            imagedestroy($originalImage);
            imagedestroy($thumbnail);

            return $result;
        } catch (\Exception $e) {
            Log::error('サムネイル生成エラー: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * サムネイルパスを生成
     */
    public function getThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }

    /**
     * サムネイルが存在するかチェック
     */
    public function thumbnailExists(string $thumbnailPath): bool
    {
        return Storage::disk('public')->exists($thumbnailPath);
    }

    /**
     * 画像ファイルかどうかチェック
     */
    public function isImage(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
    }
}