<?php

namespace Tests\Unit\Services;

use App\Services\ImageService;
use PHPUnit\Framework\TestCase;

class ImageServiceTest extends TestCase
{
    private ImageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageService;
    }

    /**
     * サムネイルパスが正しく生成されることを確認
     */
    public function test_thumbnail_path_is_generated_correctly(): void
    {
        $originalPath = 'images/photo.jpg';

        $thumbnailPath = $this->service->getThumbnailPath($originalPath);

        $this->assertEquals('images/thumbnails/photo_thumb.jpg', $thumbnailPath);
    }

    /**
     * ネストしたディレクトリでもサムネイルパスが正しく生成されることを確認
     */
    public function test_thumbnail_path_works_with_nested_directories(): void
    {
        $originalPath = 'uploads/2025/12/image.png';

        $thumbnailPath = $this->service->getThumbnailPath($originalPath);

        $this->assertEquals('uploads/2025/12/thumbnails/image_thumb.png', $thumbnailPath);
    }

    /**
     * JPEG画像のMIMEタイプが正しく判定されることを確認
     */
    public function test_jpeg_mime_type_is_recognized_as_image(): void
    {
        $this->assertTrue($this->service->isImage('image/jpeg'));
        $this->assertTrue($this->service->isImage('image/jpg'));
    }

    /**
     * PNG画像のMIMEタイプが正しく判定されることを確認
     */
    public function test_png_mime_type_is_recognized_as_image(): void
    {
        $this->assertTrue($this->service->isImage('image/png'));
    }

    /**
     * GIF画像のMIMEタイプが正しく判定されることを確認
     */
    public function test_gif_mime_type_is_recognized_as_image(): void
    {
        $this->assertTrue($this->service->isImage('image/gif'));
    }

    /**
     * WebP画像のMIMEタイプが正しく判定されることを確認
     */
    public function test_webp_mime_type_is_recognized_as_image(): void
    {
        $this->assertTrue($this->service->isImage('image/webp'));
    }

    /**
     * PDF はイメージではないと判定されることを確認
     */
    public function test_pdf_mime_type_is_not_recognized_as_image(): void
    {
        $this->assertFalse($this->service->isImage('application/pdf'));
    }

    /**
     * テキストファイルはイメージではないと判定されることを確認
     */
    public function test_text_mime_type_is_not_recognized_as_image(): void
    {
        $this->assertFalse($this->service->isImage('text/plain'));
    }

    /**
     * 空文字はイメージではないと判定されることを確認
     */
    public function test_empty_mime_type_is_not_recognized_as_image(): void
    {
        $this->assertFalse($this->service->isImage(''));
    }
}
