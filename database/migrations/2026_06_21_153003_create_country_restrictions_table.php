<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->index(); // ISO 3166-1 alpha-2
            $table->string('country_name')->nullable();
            // allow = only-allow list mode; block = block list mode
            $table->enum('mode', ['allow', 'block'])->default('block');
            $table->boolean('is_active')->default(true)->index();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_restrictions');
    }
};
