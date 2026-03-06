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
        Schema::table('prescriptions', function (Blueprint $blueprint) {
            $blueprint->string('labo_traitement')->default('LOCAL')->after('status');
            $blueprint->string('labo_autre_nom')->nullable()->after('labo_traitement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['labo_traitement', 'labo_autre_nom']);
        });
    }
};
