<?php

namespace App\Jobs;

use App\Models\PdfJob;
use App\Services\PdfService;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying after failure.
     *
     * @var int[]
     */
    public array $backoff = [60, 300, 900];

    /**
     * Timeout in seconds for a single attempt.
     */
    public int $timeout = 300;

    public function __construct(public readonly PdfJob $pdfJob)
    {
        $this->onQueue(config('pdf.queue', 'pdfs'));
    }

    public function handle(PdfService $pdfService, StorageService $storageService): void
    {
        $this->pdfJob->update([
            'status'   => 'processing',
            'attempts' => $this->pdfJob->attempts + 1,
        ]);

        try {
            $pdfContent = $pdfService->generate($this->pdfJob);

            $disk    = config('filesystems.default', 'local');
            $path    = $storageService->storePdf($pdfContent, $this->pdfJob->user_id);
            $url     = $storageService->publicUrl($disk, $path);

            $this->pdfJob->update([
                'status'       => 'completed',
                'disk'         => $disk,
                'pdf_path'     => $path,
                'pdf_url'      => $url,
                'pdf_size'     => strlen($pdfContent),
                'completed_at' => now(),
                'error_message' => null,
            ]);

            $this->notifyUser();
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', [
                'pdf_job_id' => $this->pdfJob->id,
                'attempt'    => $this->attempts(),
                'error'      => $e->getMessage(),
            ]);

            $this->pdfJob->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // Re-throw so the queue driver can handle retry logic
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->pdfJob->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('GeneratePdfJob permanently failed', [
            'pdf_job_id' => $this->pdfJob->id,
            'error'      => $exception->getMessage(),
        ]);
    }

    private function notifyUser(): void
    {
        $user = $this->pdfJob->user;
        if (! $user || ! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->send(
                new \App\Mail\PdfReadyMail($this->pdfJob)
            );
        } catch (\Throwable $e) {
            // Don't fail the job if the email fails
            Log::warning('Could not send PDF ready email', ['error' => $e->getMessage()]);
        }
    }
}
