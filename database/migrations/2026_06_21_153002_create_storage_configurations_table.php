<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['local', 's3', 'r2', 'b2', 'spaces'])->default('r2');
            $table->string('disk')->default('r2'); // matches config/filesystems disk key
            $table->string('bucket')->nullable();
            $table->string('region')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('cdn_url')->nullable();
            // Credentials stored encrypted at the model layer (casts => 'encrypted')
            $table->text('access_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->boolean('use_path_style')->default(true);
            $table->boolean('is_active')->default(false)->index();

            // Usage analytics (bytes)
            $table->unsignedBigInteger('bytes_used')->default(0);
            $table->unsignedBigInteger('bandwidth_used')->default(0);
            $table->decimal('cost_per_gb', 8, 4)->default(0); // estimated monthly cost per GB
            $table->timestamp('usage_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_configurations');
    }
};
