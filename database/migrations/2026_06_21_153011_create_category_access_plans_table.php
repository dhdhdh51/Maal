<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_access_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();

            // one_time | monthly | quarterly | yearly | lifetime | bundle
            $table->enum('type', ['one_time', 'monthly', 'quarterly', 'yearly', 'lifetime', 'bundle'])->index();

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable(); // for showing discounts
            $table->string('currency', 3)->default('INR');
            $table->unsignedInteger('validity_days')->nullable(); // null = lifetime

            // For bundle plans: list of category ids covered
            $table->json('bundle_category_ids')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Free trial support
            $table->unsignedInteger('trial_days')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_access_plans');
    }
};
