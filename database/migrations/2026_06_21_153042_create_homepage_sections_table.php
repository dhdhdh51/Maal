<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // featured_categories | trending | newest | continue_watching | recently_added
            // | recommended | because_you_watched | category_row | manual
            $table->string('type')->index();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('video_ids')->nullable();      // for manual selections
            $table->unsignedInteger('item_limit')->default(12);
            $table->string('layout')->default('carousel'); // carousel | grid | hero
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
