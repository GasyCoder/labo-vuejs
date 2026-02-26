<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('feature_key')->index(); // Matches keys in config('features.list')
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            // Ensure a feature can only be defined once per client
            $table->unique(['client_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_features');
    }
};
