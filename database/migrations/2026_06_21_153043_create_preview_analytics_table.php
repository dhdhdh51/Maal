<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preview conversion analytics events. Each row is a preview engagement
     * that can later be aggregated by video/category/date/country/device.
     */
    public function up(): void
    {
        Schema::create('preview_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->boolean('preview_completed')->default(false);
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->boolean('clicked_unlock')->default(false);
            $table->boolean('converted')->default(false); // resulted in payment
            $table->boolean('checkout_abandoned')->default(false);

            $table->string('country', 2)->nullable()->index();
            $table->string('device_type')->nullable();   // mobile/tablet/desktop
            $table->string('traffic_source')->nullable(); // utm_source / referrer
            $table->string('session_id')->nullable();

            $table->timestamps();

            $table->index(['video_id', 'created_at']);
            $table->index(['category_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preview_analytics');
    }
};
