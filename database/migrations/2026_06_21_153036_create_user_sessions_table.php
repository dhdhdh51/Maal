<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application-level session/playback tracking, distinct from the framework
     * `sessions` table. Used for device-limit enforcement, concurrent-playback
     * detection and "force logout" actions.
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('token_id')->nullable()->index(); // sanctum token / session id reference
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_playing')->default(false);   // active playback flag
            $table->foreignId('playing_video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
