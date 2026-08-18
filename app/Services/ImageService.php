<?php

namespace App\Services;

use App\Models\UploadedImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    private int $maxWidth;
    private int $maxHeight;
    private int $quality;

    public function __construct()
    {
        $this->maxWidth  = (int) config('pdf.image.max_width', 1920);
        $this->maxHeight = (int) config('pdf.image.max_height', 1920);
        $this->quality   = (int) config('pdf.image.quality', 80);
    }

    /**
     * Upload, optimize and persist a single image.
     */
    public function upload(UploadedFile $file, ?int $userId = null): UploadedImage
    {
        $optimized = $this->optimize($file);

        $filename  = Str::uuid() . '.jpg';
        $directory = 'images/' . date('Y/m');
        $path      = $directory . '/' . $filename;

        $disk = config('filesystems.default', 'local');
        Storage::disk($disk)->put($path, $optimized['data'], 'public');

        $cdnUrl = $this->buildCdnUrl($disk, $path);

        return UploadedImage::create([
            'user_id'           => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'disk'              => $disk,
            'path'              => $path,
            'cdn_url'           => $cdnUrl,
            'size'              => strlen($optimized['data']),
            'width'             => $optimized['width'],
            'height'            => $optimized['height'],
            'mime_type'         => 'image/jpeg',
        ]);
    }

    /**
     * Upload multiple files at once and return the created models.
     *
     * @param  UploadedFile[]  $files
     * @return UploadedImage[]
     */
    public function uploadBatch(array $files, ?int $userId = null): array
    {
        $results = [];
        foreach ($files as $file) {
            try {
                $results[] = $this->upload($file, $userId);
            } catch (\Throwable $e) {
                Log::error('Image upload failed', [
                    'file'      => $file->getClientOriginalName(),
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Resize and compress an uploaded file.
     *
     * @return array{data: string, width: int, height: int}
     */
    public function optimize(UploadedFile $file): array
    {
        $image = Image::read($file->getRealPath());

        // Downscale only if larger than limits
        $image->scaleDown($this->maxWidth, $this->maxHeight);

        $encoded = $image->toJpeg($this->quality);

        return [
            'data'   => (string) $encoded,
            'width'  => $image->width(),
            'height' => $image->height(),
        ];
    }

    /**
     * Delete an uploaded image from storage and database.
     */
    public function delete(UploadedImage $image): void
    {
        Storage::disk($image->disk)->delete($image->path);
        $image->delete();
    }

    private function buildCdnUrl(string $disk, string $path): ?string
    {
        if ($disk === 's3') {
            $cloudfront = config('filesystems.disks.s3.cloudfront_url')
                ?? config('pdf.cloudfront_url');
            if ($cloudfront) {
                return rtrim($cloudfront, '/') . '/' . ltrim($path, '/');
            }

            return Storage::disk('s3')->url($path);
        }

        return null;
    }
}
