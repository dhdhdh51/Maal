<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            // wallet_credit | preview_extension | discount | free_access | points
            $table->string('reward_type')->default('wallet_credit');
            $table->decimal('reward_value', 10, 2)->default(0);

            // pending until referred user completes a successful payment
            $table->enum('status', ['pending', 'rewarded', 'expired', 'cancelled'])->default('pending')->index();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
