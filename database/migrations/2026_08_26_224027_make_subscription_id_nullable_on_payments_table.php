<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign([
                'subscription_id',
            ]);

            $table->unsignedBigInteger(
                'subscription_id'
            )
                ->nullable()
                ->change();

            $table->string(
                'billing_cycle',
                20
            )
                ->nullable()
                ->after('subscription_id');

            $table->foreign(
                'subscription_id'
            )
                ->references('id')
                ->on('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign([
                'subscription_id',
            ]);

            $table->dropColumn(
                'billing_cycle'
            );

            $table->unsignedBigInteger(
                'subscription_id'
            )
                ->nullable(false)
                ->change();

            $table->foreign(
                'subscription_id'
            )
                ->references('id')
                ->on('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
