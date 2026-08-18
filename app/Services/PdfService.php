<?php

namespace App\Services;

use App\Models\PdfJob;
use App\Models\UploadedImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfService
{
    /**
     * Render a PDF for the given job and return raw binary content.
     */
    public function generate(PdfJob $job): string
    {
        $template = $job->template;

        $html = $this->renderHtml($job);

        $paperSize   = $template?->paper_size ?? config('pdf.paper_size', 'A4');
        $orientation = $template?->orientation ?? config('pdf.orientation', 'portrait');

        $pdf = Pdf::loadHTML($html)
            ->setPaper(strtolower($paperSize), strtolower($orientation));

        return $pdf->output();
    }

    /**
     * Build the HTML that will be rendered into a PDF.
     */
    public function renderHtml(PdfJob $job): string
    {
        $template  = $job->template;
        $variables = $job->variables ?? [];
        $images    = $this->resolveImages($job->image_ids ?? []);

        if ($template) {
            return $this->renderTemplate($template->html_content, $variables, $images);
        }

        // Fallback: simple default layout
        return View::make('pdf.default', compact('variables', 'images'))->render();
    }

    /**
     * Interpolate {{variable}} placeholders and inject images.
     *
     * @param  UploadedImage[]  $images
     */
    private function renderTemplate(string $htmlContent, array $variables, array $images): string
    {
        // Replace {{key}} placeholders with variable values
        foreach ($variables as $key => $value) {
            $htmlContent = str_replace(
                ['{{' . $key . '}}', '{{ ' . $key . ' }}'],
                e((string) $value),
                $htmlContent
            );
        }

        // Replace {{images}} with <img> tags
        $imgHtml = '';
        foreach ($images as $image) {
            $dataUri  = $this->imageToDataUri($image);
            $imgHtml .= '<img src="' . $dataUri . '" style="max-width:100%;margin:8px 0;" />';
        }
        $htmlContent = str_replace(['{{images}}', '{{ images }}'], $imgHtml, $htmlContent);

        return $htmlContent;
    }

    /**
     * @param  int[]  $imageIds
     * @return UploadedImage[]
     */
    private function resolveImages(array $imageIds): array
    {
        if (empty($imageIds)) {
            return [];
        }

        return UploadedImage::whereIn('id', $imageIds)->get()->all();
    }

    /**
     * Convert an image stored on disk to a base64 data URI so dompdf can
     * embed it without making additional HTTP requests.
     */
    public function imageToDataUri(UploadedImage $image): string
    {
        try {
            $contents = Storage::disk($image->disk)->get($image->path);
            $mime     = $image->mime_type ?: 'image/jpeg';
            $base64   = base64_encode($contents);

            return 'data:' . $mime . ';base64,' . $base64;
        } catch (\Throwable $e) {
            Log::warning('Could not load image for PDF', [
                'image_id' => $image->id,
                'error'    => $e->getMessage(),
            ]);

            return '';
        }
    }
}
