<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // payment | access | playback | refund | account | other
            $table->enum('category', ['payment', 'access', 'playback', 'refund', 'account', 'other'])->index();
            $table->string('subject');

            // open | in_progress | waiting_user | resolved | closed
            $table->enum('status', ['open', 'in_progress', 'waiting_user', 'resolved', 'closed'])
                ->default('open')->index();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_staff')->default(false);
            $table->text('message');
            $table->json('attachments')->nullable(); // array of stored screenshot paths
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
