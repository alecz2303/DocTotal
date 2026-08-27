<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Guardamos dinero en unidades mínimas.
             *
             * Ejemplo:
             * $1,299.00 MXN => 129900 centavos.
             *
             * Evitamos floats/decimales ambiguos para billing.
             */
            $table->unsignedBigInteger('amount');

            $table->string('currency', 3)
                ->default('MXN');

            $table->string('status', 30)
                ->default('pending');

            /*
             * attempted_at representa el instante exacto
             * en que se intentó realizar el cobro.
             */
            $table->dateTime('attempted_at');

            $table->dateTime('paid_at')
                ->nullable();

            $table->dateTime('failed_at')
                ->nullable();

            $table->string('failure_code', 100)
                ->nullable();

            $table->text('failure_message')
                ->nullable();

            /*
             * Estos campos permitirán integrar posteriormente
             * Stripe, Mercado Pago u otro proveedor sin cambiar
             * el dominio base.
             */
            $table->string('provider', 50)
                ->nullable();

            $table->string('provider_payment_id', 255)
                ->nullable();

            /*
             * La idempotency key pertenece a DocTotal.
             *
             * Debe impedir que el mismo intento lógico de cobro
             * sea registrado/procesado dos veces.
             */
            $table->string('idempotency_key', 255)
                ->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'status',
                'attempted_at',
            ]);

            $table->index([
                'subscription_id',
                'status',
                'attempted_at',
            ]);

            $table->index([
                'provider',
                'provider_payment_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
