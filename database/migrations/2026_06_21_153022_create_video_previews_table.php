<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->string('hls_path')->nullable();   // preview HLS playlist
            $table->string('mp4_path')->nullable();    // fallback mp4 preview (authorized only)
            $table->unsignedInteger('start_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->default(20);
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_previews');
    }
};
