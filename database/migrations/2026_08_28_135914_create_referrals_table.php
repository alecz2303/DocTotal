<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('referrer_tenant_id')
                ->constrained('tenants');

            $table->foreignId('referred_tenant_id')
                ->constrained('tenants');

            $table->string('referral_code', 32);

            $table->string('status', 30)
                ->default('pending');

            $table->foreignId('qualifying_payment_id')
                ->nullable()
                ->constrained('payments');

            $table->timestamp('qualified_at')
                ->nullable();

            $table->timestamps();

            $table->unique('referred_tenant_id');

            $table->unique('qualifying_payment_id');

            $table->index([
                'referrer_tenant_id',
                'status',
            ]);

            $table->index([
                'referrer_tenant_id',
                'qualified_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
