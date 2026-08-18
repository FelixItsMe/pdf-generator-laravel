<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Models\UploadedImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageUploadController extends Controller
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    /**
     * POST /api/images
     *
     * Upload one or more images, optimize them, store in S3/local and return CDN URLs.
     */
    public function store(UploadImageRequest $request): JsonResponse
    {
        $userId  = $request->user()?->id;
        $uploads = $this->imageService->uploadBatch($request->file('images'), $userId);

        $data = array_map(fn (UploadedImage $img) => [
            'id'                => $img->id,
            'original_filename' => $img->original_filename,
            'url'               => $img->public_url,
            'width'             => $img->width,
            'height'            => $img->height,
            'size'              => $img->size,
        ], $uploads);

        return response()->json([
            'message' => count($data) . ' image(s) uploaded successfully.',
            'data'    => $data,
        ], 201);
    }

    /**
     * DELETE /api/images/{image}
     */
    public function destroy(Request $request, UploadedImage $image): JsonResponse
    {
        // Only the owner may delete; deny unauthenticated requests too
        if (! $request->user() || $request->user()->id !== $image->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $this->imageService->delete($image);

        return response()->json(['message' => 'Image deleted.']);
    }
}
