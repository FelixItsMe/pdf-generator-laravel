<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PdfTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'html_content',
        'variables',
        'paper_size',
        'orientation',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function pdfJobs(): HasMany
    {
        return $this->hasMany(PdfJob::class);
    }
}
