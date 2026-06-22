<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Media artifacts (paths within the media disk / served via CDN)
            $table->string('poster_path')->nullable();
            $table->string('teaser_path')->nullable();      // custom teaser video
            $table->string('hls_master_path')->nullable();  // master.m3u8

            // Source / probe metadata
            $table->string('original_filename')->nullable();
            $table->string('original_path')->nullable();     // private original
            $table->string('checksum', 128)->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->unsignedInteger('duration')->default(0);     // seconds
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('source_format')->nullable();
            $table->json('available_qualities')->nullable();     // ["360p","720p",...]

            // Access / locking
            $table->boolean('is_locked')->default(true)->index(); // locked unless category/plan unlocks
            $table->boolean('is_free_preview')->default(true);
            $table->unsignedInteger('preview_seconds')->nullable(); // overrides category/global
            $table->unsignedInteger('preview_start')->nullable();   // custom preview start time

            // Lifecycle
            $table->enum('processing_status', [
                'uploading', 'uploaded', 'processing', 'generating_preview',
                'encoding', 'ready', 'failed',
            ])->default('uploading')->index();
            $table->unsignedTinyInteger('processing_progress')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_disabled')->default(false)->index(); // instant disable (moderation)
            $table->timestamp('published_at')->nullable();

            // Per-video overrides
            $table->boolean('watermark_enabled')->nullable();

            // Stats
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('preview_plays_count')->default(0);

            // SEO
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('noindex')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_published', 'is_disabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
