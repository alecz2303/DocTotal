<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('gross_amount')
                ->nullable()
                ->after('amount');

            $table->unsignedInteger('referral_discount_amount')
                ->default(0)
                ->after('gross_amount');

            $table->unsignedInteger('promotional_credit_amount')
                ->default(0)
                ->after('referral_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gross_amount',
                'referral_discount_amount',
                'promotional_credit_amount',
            ]);
        });
    }
};
