<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('gateway');

            // initiate | webhook | callback | refund | capture | verify
            $table->string('event')->index();
            $table->string('status')->nullable();

            // Idempotency / replay protection for webhooks
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('gateway_event_id')->nullable()->index();
            $table->boolean('signature_verified')->default(false);

            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
