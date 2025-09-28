<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

class PerformanceAttachment extends Model
{
    protected $fillable = [
        'performance_id',
        'original_name',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
    ];

    /**
     * Performance関係
     */
    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    /**
     * 画像ファイルかどうか
     */
    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * サムネイルURLを取得
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->isImage() || !$this->thumbnail_path) {
            return null;
        }

        return Storage::url($this->thumbnail_path);
    }

    /**
     * オリジナルファイルURLを取得
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * ファイルサイズを人間が読める形式で取得
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . 'MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . 'KB';
        }
        return $bytes . 'B';
    }

    /**
     * サムネイル生成
     */
    public function generateThumbnail(): bool
    {
        if (!$this->isImage()) {
            return false;
        }

        $imageService = app(ImageService::class);
        $thumbnailPath = $imageService->getThumbnailPath($this->file_path);

        if ($imageService->generateThumbnail($this->file_path, $thumbnailPath)) {
            $this->update(['thumbnail_path' => $thumbnailPath]);
            return true;
        }

        return false;
    }
}
