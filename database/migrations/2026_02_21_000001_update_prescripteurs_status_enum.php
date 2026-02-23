<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE prescripteurs SET status = 'Medecin' WHERE status = 'BiologieSolidaire'");
        DB::statement("ALTER TABLE prescripteurs MODIFY status ENUM('Medecin','Professeur') NOT NULL DEFAULT 'Medecin'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE prescripteurs MODIFY status ENUM('Medecin','Professeur','BiologieSolidaire') NOT NULL DEFAULT 'Medecin'");
    }
};
