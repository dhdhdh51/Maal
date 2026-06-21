<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // percentage | flat | free_access
            $table->enum('type', ['percentage', 'flat', 'free_access'])->default('percentage');
            $table->decimal('value', 10, 2)->default(0); // % or flat amount (0 for free_access)
            $table->decimal('max_discount', 10, 2)->nullable(); // cap for percentage
            $table->decimal('min_order_amount', 10, 2)->nullable();

            // Scope: null category_id = all categories
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('category_ids')->nullable(); // multi-category scope

            // Usage limits
            $table->unsignedInteger('usage_limit')->nullable();   // total redemptions allowed
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);

            // Validity window
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            // first-purchase only / flash offer flags
            $table->boolean('first_purchase_only')->default(false);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
