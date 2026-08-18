<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'disk',
        'path',
        'cdn_url',
        'size',
        'width',
        'height',
        'mime_type',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the publicly accessible URL for this image.
     */
    public function getPublicUrlAttribute(): string
    {
        if ($this->cdn_url) {
            return $this->cdn_url;
        }

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }
}
