<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('referral_code', 16)
                ->nullable()
                ->unique()
                ->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique([
                'referral_code',
            ]);

            $table->dropColumn(
                'referral_code'
            );
        });
    }
};
