<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->string('language', 8);        // e.g. en, hi, es
            $table->string('label')->nullable();  // human readable label
            $table->enum('format', ['srt', 'vtt'])->default('vtt');
            $table->string('path');               // stored .vtt (srt converted on processing)
            $table->boolean('is_default')->default(false);
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_subtitles');
    }
};
