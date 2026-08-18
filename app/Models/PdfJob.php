<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'user_id',
        'pdf_template_id',
        'status',
        'image_ids',
        'variables',
        'disk',
        'pdf_path',
        'pdf_url',
        'pdf_size',
        'attempts',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'image_ids'    => 'array',
        'variables'    => 'array',
        'pdf_size'     => 'integer',
        'attempts'     => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Column used for UUID.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PdfTemplate::class, 'pdf_template_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
