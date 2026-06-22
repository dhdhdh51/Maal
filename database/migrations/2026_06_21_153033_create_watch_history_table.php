<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);    // last playback position (seconds)
            $table->unsignedInteger('duration')->default(0);    // video duration snapshot
            $table->unsignedTinyInteger('percent')->default(0); // completion %
            $table->boolean('completed')->default(false);
            $table->unsignedInteger('watch_seconds')->default(0); // total time watched (analytics)
            $table->timestamp('last_watched_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
