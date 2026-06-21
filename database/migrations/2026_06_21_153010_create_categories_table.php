<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();

            // Access model
            $table->enum('access_type', ['free', 'paid', 'subscription', 'lifetime'])->default('free')->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->unsignedInteger('validity_days')->nullable(); // null = no expiry / lifetime

            // Preview override (null = fall back to global default)
            $table->unsignedInteger('preview_seconds')->nullable();

            // Country availability (null = all). JSON array of ISO codes.
            $table->json('country_availability')->nullable();

            // Per-category overrides
            $table->unsignedSmallInteger('device_limit')->nullable();
            $table->boolean('watermark_enabled')->nullable();

            // SEO
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('noindex')->default(false);

            $table->unsignedBigInteger('videos_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
