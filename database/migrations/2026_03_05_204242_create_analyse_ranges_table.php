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
        Schema::create('analyse_ranges', function (Blueprint $綱) {
            $綱->id();
            $綱->foreignId('analyse_id')->constrained('analyses')->onDelete('cascade');
            $綱->enum('context', ['DEFAULT', 'HOMME', 'FEMME', 'ENFANT_GARCON', 'ENFANT_FILLE'])->default('DEFAULT');
            $綱->decimal('normal_min', 10, 3)->nullable();
            $綱->decimal('normal_max', 10, 3)->nullable();
            $綱->decimal('critical_min', 10, 3)->nullable();
            $綱->decimal('critical_max', 10, 3)->nullable();
            $綱->timestamps();

            $綱->unique(['analyse_id', 'context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyse_ranges');
    }
};
