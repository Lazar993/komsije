<?php

declare(strict_types=1);

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resizes an UploadedFile image in-place (rewriting the temp file) so the
 * existing $file->store(...) flow can persist the smaller version unchanged.
 *
 * Uses GD (no external dependencies). Supports JPEG, PNG, WEBP. GIFs are
 * skipped to preserve animation. Non-image uploads are returned as-is.
 */
final class ImageResizer
{
    public function __construct(
        private readonly int $maxWidth = 1920,
        private readonly int $maxHeight = 1920,
        private readonly int $jpegQuality = 82,
        private readonly int $webpQuality = 82,
        private readonly int $pngCompression = 6,
    ) {
    }

    public function resize(UploadedFile $file): UploadedFile
    {
        if (! extension_loaded('gd')) {
            return $file;
        }

        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $file;
        }

        $sourcePath = $file->getRealPath();
        if ($sourcePath === false || ! is_readable($sourcePath)) {
            return $file;
        }

        try {
            $info = @getimagesize($sourcePath);
            if ($info === false) {
                return $file;
            }

            [$width, $height] = $info;
            if ($width <= 0 || $height <= 0) {
                return $file;
            }

            $scale = min($this->maxWidth / $width, $this->maxHeight / $height, 1.0);
            $needsResize = $scale < 1.0;
            $needsReorient = $mime === 'image/jpeg' && $this->exifOrientation($sourcePath) > 1;

            if (! $needsResize && ! $needsReorient) {
                return $file;
            }

            $source = $this->createImage($sourcePath, $mime);
            if ($source === null) {
                return $file;
            }

            try {
                if ($needsReorient) {
                    $source = $this->applyExifOrientation($source, $this->exifOrientation($sourcePath));
                    $width = imagesx($source);
                    $height = imagesy($source);
                    $scale = min($this->maxWidth / $width, $this->maxHeight / $height, 1.0);
                    $needsResize = $scale < 1.0;
                }

                if ($needsResize) {
                    $newWidth = max(1, (int) floor($width * $scale));
                    $newHeight = max(1, (int) floor($height * $scale));

                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    if ($resized === false) {
                        return $file;
                    }

                    $this->preserveTransparency($resized, $mime);

                    if (! imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                        imagedestroy($resized);

                        return $file;
                    }

                    imagedestroy($source);
                    $source = $resized;
                }

                if (! $this->writeImage($source, $sourcePath, $mime)) {
                    return $file;
                }
            } finally {
                if (is_object($source)) {
                    imagedestroy($source);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Image resize failed; uploading original.', [
                'error' => $e->getMessage(),
                'mime' => $mime,
                'name' => $file->getClientOriginalName(),
            ]);

            return $file;
        }

        clearstatcache(true, $sourcePath);

        return new UploadedFile(
            $sourcePath,
            $file->getClientOriginalName(),
            $mime,
            null,
            true,
        );
    }

    private function createImage(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    private function writeImage(\GdImage $image, string $path, string $mime): bool
    {
        return match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, $this->jpegQuality),
            'image/png' => imagepng($image, $path, $this->pngCompression),
            'image/webp' => imagewebp($image, $path, $this->webpQuality),
            default => throw new RuntimeException("Unsupported mime: {$mime}"),
        };
    }

    private function preserveTransparency(\GdImage $image, string $mime): void
    {
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($image, 0, 0, imagesx($image) - 1, imagesy($image) - 1, $transparent);
            }
        }
    }

    private function exifOrientation(string $path): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($path);

        return is_array($exif) && isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
    }

    private function applyExifOrientation(\GdImage $image, int $orientation): \GdImage
    {
        return match ($orientation) {
            2 => imageflip($image, IMG_FLIP_HORIZONTAL) ? $image : $image,
            3 => imagerotate($image, 180, 0) ?: $image,
            4 => imageflip($image, IMG_FLIP_VERTICAL) ? $image : $image,
            5 => $this->flipAndRotate($image, IMG_FLIP_HORIZONTAL, -90),
            6 => imagerotate($image, -90, 0) ?: $image,
            7 => $this->flipAndRotate($image, IMG_FLIP_HORIZONTAL, 90),
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }

    private function flipAndRotate(\GdImage $image, int $flipMode, int $angle): \GdImage
    {
        imageflip($image, $flipMode);
        $rotated = imagerotate($image, $angle, 0);

        return $rotated ?: $image;
    }
}
