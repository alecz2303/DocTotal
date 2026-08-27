<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            /*
             * Importe contractual recurrente en unidades mínimas.
             *
             * Ejemplo:
             * $1,299.00 MXN = 129900
             *
             * Se mantiene nullable temporalmente porque ya existen
             * subscriptions creadas antes de DT-12.
             */
            $table->unsignedBigInteger('billing_amount')
                ->nullable()
                ->after('billing_cycle');

            $table->string('billing_currency', 3)
                ->default('MXN')
                ->after('billing_amount');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_amount',
                'billing_currency',
            ]);
        });
    }
};
