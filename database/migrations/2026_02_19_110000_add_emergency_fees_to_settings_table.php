<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $blueprint) {
            $blueprint->decimal('tarif_urgence_nuit', 10, 2)->default(20000)->after('commission_prescripteur_quota');
            $blueprint->decimal('tarif_urgence_jour', 10, 2)->default(15000)->after('tarif_urgence_nuit');
        });

        // Mise à jour des valeurs par défaut pour l'enregistrement existant
        DB::table('settings')->update([
            'tarif_urgence_nuit' => 20000,
            'tarif_urgence_jour' => 15000,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['tarif_urgence_nuit', 'tarif_urgence_jour']);
        });
    }
};
