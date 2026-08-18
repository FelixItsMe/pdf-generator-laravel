<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Store a PDF binary on the configured disk and return the path.
     */
    public function storePdf(string $pdfContent, ?int $userId = null): string
    {
        $directory = 'pdfs/' . date('Y/m') . ($userId ? '/' . $userId : '');
        $filename  = Str::uuid() . '.pdf';
        $path      = $directory . '/' . $filename;

        $disk = config('filesystems.default', 'local');
        Storage::disk($disk)->put($path, $pdfContent, 'public');

        return $path;
    }

    /**
     * Build the publicly accessible URL for a stored file.
     */
    public function publicUrl(string $disk, string $path): string
    {
        if ($disk === 's3') {
            $cloudfront = config('filesystems.disks.s3.cloudfront_url')
                ?? config('pdf.cloudfront_url');
            if ($cloudfront) {
                return rtrim($cloudfront, '/') . '/' . ltrim($path, '/');
            }
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $disk, string $path): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete old PDFs that have not been accessed since $days ago.
     * This is called from the scheduler.
     */
    public function cleanupOldPdfs(int $days = 30): int
    {
        $count = 0;
        $disk  = config('filesystems.default', 'local');

        $cutoff = now()->subDays($days)->timestamp;

        $files = Storage::disk($disk)->allFiles('pdfs');
        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);
            if ($lastModified < $cutoff) {
                Storage::disk($disk)->delete($file);
                $count++;
            }
        }

        return $count;
    }
}
