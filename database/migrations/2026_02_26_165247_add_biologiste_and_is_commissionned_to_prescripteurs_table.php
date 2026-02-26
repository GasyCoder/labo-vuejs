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
        // Alter ENUM to add 'Biologiste' safely using raw statement
        DB::statement("ALTER TABLE prescripteurs MODIFY COLUMN status ENUM('Medecin', 'Professeur', 'Biologiste') DEFAULT 'Medecin'");

        Schema::table('prescripteurs', function (Blueprint $table) {
            $table->boolean('is_commissionned')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescripteurs', function (Blueprint $table) {
            $table->dropColumn('is_commissionned');
        });

        // Reverting enum safely, though this might fail if a row still has 'Biologiste'
        DB::statement("ALTER TABLE prescripteurs MODIFY COLUMN status ENUM('Medecin', 'Professeur') DEFAULT 'Medecin'");
    }
};
