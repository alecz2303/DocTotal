<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('reward_status', 30)
                ->nullable()
                ->after('qualified_at');

            $table->date('reward_month')
                ->nullable()
                ->after('reward_status');

            $table->index([
                'referrer_tenant_id',
                'reward_month',
                'reward_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex([
                'referrer_tenant_id',
                'reward_month',
                'reward_status',
            ]);

            $table->dropColumn([
                'reward_status',
                'reward_month',
            ]);
        });
    }
};
