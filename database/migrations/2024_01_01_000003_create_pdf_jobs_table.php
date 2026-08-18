<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pdf_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending')
                ->comment('pending|processing|completed|failed');
            $table->json('image_ids')->nullable()->comment('UploadedImage IDs');
            $table->json('variables')->nullable()->comment('Dynamic text variables');
            $table->string('disk')->default('s3');
            $table->string('pdf_path')->nullable();
            $table->string('pdf_url')->nullable();
            $table->unsignedInteger('pdf_size')->nullable()->comment('bytes');
            $table->tinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_jobs');
    }
};
