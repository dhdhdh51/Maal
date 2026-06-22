<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 32)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');

            // Account state
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active')->index();
            $table->boolean('is_premium')->default(false)->index();
            $table->timestamp('premium_until')->nullable();

            // Profile
            $table->string('avatar')->nullable();
            $table->string('country', 2)->nullable()->index();
            $table->string('locale', 8)->default('en');

            // Compliance / consent
            $table->boolean('age_confirmed')->default(false);
            $table->timestamp('age_confirmed_at')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();

            // Security
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->unsignedSmallInteger('device_limit')->nullable(); // null = use global default
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            // Referral / wallet
            $table->string('referral_code', 32)->nullable()->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('wallet_balance', 12, 2)->default(0);

            // Marketing
            $table->boolean('newsletter_opt_in')->default(false);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
