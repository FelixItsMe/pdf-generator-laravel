<?php

use App\Console\Commands\CleanupOldPdfs;
use Illuminate\Support\Facades\Schedule;

// Schedule the PDF cleanup command daily at midnight
Schedule::command(CleanupOldPdfs::class, ['--days=' . config('pdf.cleanup_days', 30)])
    ->dailyAt('00:00')
    ->withoutOverlapping();
