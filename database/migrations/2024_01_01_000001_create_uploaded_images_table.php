<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('cdn_url')->nullable();
            $table->unsignedInteger('size')->comment('bytes');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_images');
    }
};
