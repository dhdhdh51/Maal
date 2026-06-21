<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->string('quality');                 // 360p, 480p, 720p, 1080p, 4k
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->string('bitrate')->nullable();
            $table->string('playlist_path')->nullable(); // rendition .m3u8
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['video_id', 'quality']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_files');
    }
};
