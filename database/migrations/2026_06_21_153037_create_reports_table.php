<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // Polymorphic target: video or category
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // reporter (nullable for guests)
            $table->string('reporter_email')->nullable();

            // copyright | consent | wrong_category | technical | illegal | other
            $table->enum('reason', ['copyright', 'consent', 'wrong_category', 'technical', 'illegal', 'other'])->index();
            $table->text('details')->nullable();

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->enum('status', ['open', 'reviewing', 'resolved', 'dismissed'])->default('open')->index();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
