<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE prescripteurs MODIFY status ENUM('Medecin', 'Professeur', 'Biologiste', 'Infirmier', 'Partenaires') NOT NULL DEFAULT 'Medecin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE prescripteurs MODIFY status ENUM('Medecin', 'Professeur', 'Biologiste') NOT NULL DEFAULT 'Medecin'");
    }
};
