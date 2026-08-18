<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePdfRequest;
use App\Jobs\GeneratePdfJob;
use App\Models\PdfJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfGenerationController extends Controller
{
    /**
     * POST /api/pdfs
     *
     * Queue a PDF generation job and return the job UUID for status polling.
     */
    public function store(GeneratePdfRequest $request): JsonResponse
    {
        $pdfJob = PdfJob::create([
            'uuid'            => Str::uuid(),
            'user_id'         => $request->user()?->id,
            'pdf_template_id' => $request->input('template_id'),
            'status'          => 'pending',
            'image_ids'       => $request->input('image_ids', []),
            'variables'       => $request->input('variables', []),
        ]);

        GeneratePdfJob::dispatch($pdfJob);

        return response()->json([
            'message' => 'PDF generation queued.',
            'data'    => [
                'uuid'   => $pdfJob->uuid,
                'status' => $pdfJob->status,
            ],
        ], 202);
    }

    /**
     * GET /api/pdfs/{uuid}
     *
     * Return the current status (and URL if completed) of a PDF job.
     */
    public function show(string $uuid): JsonResponse
    {
        $pdfJob = PdfJob::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'data' => [
                'uuid'          => $pdfJob->uuid,
                'status'        => $pdfJob->status,
                'pdf_url'       => $pdfJob->pdf_url,
                'pdf_size'      => $pdfJob->pdf_size,
                'attempts'      => $pdfJob->attempts,
                'error_message' => $pdfJob->error_message,
                'completed_at'  => $pdfJob->completed_at?->toIso8601String(),
                'created_at'    => $pdfJob->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/pdfs
     *
     * List all PDF jobs for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = PdfJob::where('user_id', $request->user()?->id)
            ->latest()
            ->paginate(15);

        return response()->json($jobs);
    }
}
