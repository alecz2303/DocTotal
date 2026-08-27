<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Proveedor que almacena realmente
             * el método de pago.
             *
             * Inicialmente:
             *
             * stripe
             */
            $table->string('provider', 50);

            /*
             * Identificador del método de pago
             * dentro del proveedor.
             *
             * Stripe:
             * pm_xxxxxxxxx
             */
            $table->string(
                'provider_payment_method_id',
                255
            );

            /*
             * Tipo general del método.
             *
             * Inicialmente esperamos:
             * card
             *
             * Pero dejamos el dominio abierto a
             * otros métodos futuros.
             */
            $table->string('type', 50);

            /*
             * Datos NO sensibles que podemos
             * mostrar en la interfaz.
             *
             * Ejemplo:
             *
             * Visa terminada en 4242
             * vence 12/2029
             */
            $table->string('brand', 50)
                ->nullable();

            $table->string('last_four', 4)
                ->nullable();

            $table->unsignedTinyInteger(
                'expires_month'
            )->nullable();

            $table->unsignedSmallInteger(
                'expires_year'
            )->nullable();

            /*
             * Método preferido para cobros
             * automáticos del tenant.
             */
            $table->boolean('is_default')
                ->default(false);

            /*
             * Permite conservar historial sin
             * intentar volver a utilizar un método
             * retirado o inválido.
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
            $table->softDeletes();

            /*
             * Un mismo método externo no puede
             * registrarse dos veces para el mismo
             * proveedor.
             */
            $table->unique([
                'provider',
                'provider_payment_method_id',
            ]);

            $table->index([
                'tenant_id',
                'is_active',
                'is_default',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'payment_methods'
        );
    }
};
