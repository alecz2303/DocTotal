<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_customers', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('provider', 50);

            $table->string(
                'provider_customer_id',
                255
            );

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'provider',
            ]);

            $table->unique([
                'provider',
                'provider_customer_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'billing_customers'
        );
    }
};
