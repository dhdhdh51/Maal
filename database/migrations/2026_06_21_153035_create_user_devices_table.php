<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 64)->index();   // fingerprint
            $table->string('name')->nullable();          // e.g. "Chrome on Android"
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('device_type')->nullable();   // mobile, tablet, desktop
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('is_trusted')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
