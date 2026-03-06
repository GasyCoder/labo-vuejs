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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('remise_type')->default('PERCENT')->after('remise')->comment('PERCENT or AMOUNT');
            $table->decimal('remise_valeur', 15, 2)->default(0)->after('remise_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['remise_type', 'remise_valeur']);
        });
    }
};
