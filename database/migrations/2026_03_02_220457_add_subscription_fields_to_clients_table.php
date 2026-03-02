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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('plan_name')->nullable()->after('name');
            $table->decimal('subscription_price', 15, 2)->default(0)->after('plan_name');
            $table->integer('sms_quota')->default(0)->after('subscription_price');
            $table->integer('email_quota')->default(0)->after('sms_quota');
            $table->integer('sms_used_this_month')->default(0)->after('email_quota');
            $table->integer('email_used_this_month')->default(0)->after('sms_used_this_month');
            $table->timestamp('next_renewal_at')->nullable()->after('email_used_this_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'plan_name',
                'subscription_price',
                'sms_quota',
                'email_quota',
                'sms_used_this_month',
                'email_used_this_month',
                'next_renewal_at',
            ]);
        });
    }
};
