<?php

namespace App\Console\Commands;

use App\Services\StorageService;
use Illuminate\Console\Command;

class CleanupOldPdfs extends Command
{
    protected $signature   = 'pdf:cleanup {--days=30 : Delete PDFs older than this many days}';
    protected $description = 'Delete old generated PDFs from storage';

    public function __construct(private readonly StorageService $storageService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days  = (int) $this->option('days');
        $count = $this->storageService->cleanupOldPdfs($days);

        $this->info("Deleted {$count} PDF(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
