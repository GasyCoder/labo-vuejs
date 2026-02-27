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
        Schema::create('pdf_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('exam_color')->default('#d10000');
            $table->string('highlight_color')->default('#d10000');
            $table->string('signature_image_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_brandings');
    }
};
