<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();

            // validation | thumbnails | preview | transcode | subtitles
            $table->string('stage')->index();
            $table->string('quality')->nullable(); // for transcode stages

            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('queued')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->text('log')->nullable();        // human-readable progress log
            $table->text('error_message')->nullable(); // secure technical detail (admin only)
            $table->string('queue_job_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['video_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_processing_jobs');
    }
};
