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
        Schema::table('prelevements', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->decimal('prix_promotion', 10, 2)->after('prix')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prelevements', function (Blueprint $table) {
            $table->string('code', 10)->after('id')->nullable();
            $table->dropColumn('prix_promotion');
        });
    }
};
