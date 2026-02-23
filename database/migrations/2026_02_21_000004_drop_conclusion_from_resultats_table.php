<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultats', function (Blueprint $table) {
            if (Schema::hasColumn('resultats', 'conclusion')) {
                $table->dropColumn('conclusion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resultats', function (Blueprint $table) {
            $table->text('conclusion')->nullable()->after('interpretation');
        });
    }
};
