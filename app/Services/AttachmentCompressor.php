<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class AttachmentCompressor
{
    /**
     * Files larger than this are compressed before being stored.
     */
    public const THRESHOLD_BYTES = 20 * 1024 * 1024; // 20 MB

    public const MAX_IMAGE_DIMENSION = 2400;

    /**
     * Prepare an uploaded file for storage. Large files are compressed:
     * - Images are resized/re-encoded with GD.
     * - Other file types are packed into a ZIP archive.
     *
     * @return array{needs_compression: bool, temp_path: ?string, name: string, size: int, width: ?int, height: ?int}
     */
    public function compressIfNeeded(UploadedFile $file): array
    {
        $size = (int) $file->getSize();
        $name = $file->getClientOriginalName();
        $path = $file->getRealPath();
        $imageInfo = $path ? @getimagesize($path) : false;

        $width  = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;

        if ($size <= self::THRESHOLD_BYTES) {
            return [
                'needs_compression' => false,
                'temp_path'         => null,
                'name'              => $name,
                'size'              => $size,
                'width'             => $width,
                'height'            => $height,
            ];
        }

        if ($path && $imageInfo) {
            return $this->compressImage($path, $name, $imageInfo);
        }

        return $this->compressArchive($path, $name, $size);
    }

    private function compressImage(string $path, string $originalName, array $info): array
    {
        // Give GD enough room for large source images.
        @ini_set('memory_limit', '512M');

        $type = $info[2];
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };

        if (! $source) {
            return $this->compressArchive($path, $originalName, (int) filesize($path));
        }

        $srcWidth  = imagesx($source);
        $srcHeight = imagesy($source);

        $outputWebp = $type === IMAGETYPE_WEBP;
        $extension  = $outputWebp ? 'webp' : 'jpg';
        $tempPath   = sys_get_temp_dir().'/protask_'.bin2hex(random_bytes(8)).'.'.$extension;

        $maxDimension = self::MAX_IMAGE_DIMENSION;
        $best = null;

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $scale = min(1, $maxDimension / max($srcWidth, $srcHeight));
            $newWidth  = max(1, (int) round($srcWidth * $scale));
            $newHeight = max(1, (int) round($srcHeight * $scale));

            $canvas = imagecreatetruecolor($newWidth, $newHeight);

            if (! $outputWebp) {
                // JPEG has no transparency; use a white background.
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
            }

            imagecopyresampled(
                $canvas, $source,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $srcWidth, $srcHeight,
            );

            $quality = max(35, 85 - ($attempt * 10));

            if ($outputWebp) {
                imagewebp($canvas, $tempPath, $quality);
            } else {
                imagejpeg($canvas, $tempPath, $quality);
            }

            imagedestroy($canvas);
            clearstatcache(true, $tempPath);

            $compressedSize = (int) filesize($tempPath);

            $copyPath = sys_get_temp_dir().'/protask_'.bin2hex(random_bytes(8)).'.'.$extension;
            copy($tempPath, $copyPath);

            if ($best === null || $compressedSize < $best['size']) {
                if ($best) {
                    @unlink($best['path']);
                }

                $best = [
                    'path'   => $copyPath,
                    'size'   => $compressedSize,
                    'width'  => $newWidth,
                    'height' => $newHeight,
                ];
            } else {
                @unlink($copyPath);
            }

            if ($compressedSize <= self::THRESHOLD_BYTES) {
                break;
            }

            $maxDimension = (int) round($maxDimension * 0.75);
        }

        imagedestroy($source);
        @unlink($tempPath);

        if (! $best) {
            return $this->compressArchive($path, $originalName, (int) filesize($path));
        }

        return [
            'needs_compression' => true,
            'temp_path'         => $best['path'],
            'name'              => pathinfo($originalName, PATHINFO_FILENAME).'.'.$extension,
            'size'              => $best['size'],
            'width'             => $best['width'],
            'height'            => $best['height'],
        ];
    }

    /**
     * Pack non-image files into a ZIP archive.
     */
    private function compressArchive(?string $path, string $originalName, int $originalSize): array
    {
        if (! $path || ! class_exists(ZipArchive::class)) {
            return $this->fallbackCopy($path, $originalName, $originalSize);
        }

        $tempPath = sys_get_temp_dir().'/protask_'.bin2hex(random_bytes(8)).'.zip';
        $zip = new ZipArchive();

        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->fallbackCopy($path, $originalName, $originalSize);
        }

        $zip->addFile($path, $originalName);
        $zip->close();

        clearstatcache(true, $tempPath);

        return [
            'needs_compression' => true,
            'temp_path'         => $tempPath,
            'name'              => $originalName.'.zip',
            'size'              => (int) filesize($tempPath),
            'width'             => null,
            'height'            => null,
        ];
    }

    private function fallbackCopy(?string $path, string $originalName, int $originalSize): array
    {
        $tempPath = sys_get_temp_dir().'/protask_'.bin2hex(random_bytes(8));
        copy($path, $tempPath);

        return [
            'needs_compression' => true,
            'temp_path'         => $tempPath,
            'name'              => $originalName,
            'size'              => $originalSize,
            'width'             => null,
            'height'            => null,
        ];
    }
}
