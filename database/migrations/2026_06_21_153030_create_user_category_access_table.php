<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_category_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('category_access_plans')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            // payment | trial | admin_grant | referral_reward | coupon
            $table->string('source')->default('payment');

            $table->enum('status', ['active', 'expired', 'revoked'])->default('active')->index();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index(); // null = lifetime
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete(); // admin grant
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_access');
    }
};
