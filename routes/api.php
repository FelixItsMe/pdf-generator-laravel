<?php

use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\PdfGenerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Rate-limited public / guest endpoints
Route::middleware(['throttle:60,1'])->group(function () {
    // Image upload
    Route::post('/images', [ImageUploadController::class, 'store']);
    Route::delete('/images/{image}', [ImageUploadController::class, 'destroy']);

    // PDF generation
    Route::post('/pdfs', [PdfGenerationController::class, 'store']);
    Route::get('/pdfs/{uuid}', [PdfGenerationController::class, 'show']);
    Route::get('/pdfs', [PdfGenerationController::class, 'index']);
});
