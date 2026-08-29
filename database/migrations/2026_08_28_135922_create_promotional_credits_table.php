<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotional_credits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants');

            $table->foreignId('referral_id')
                ->constrained('referrals');

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments');

            $table->string('kind', 30);

            $table->unsignedInteger('amount');

            $table->char('currency', 3)
                ->default('MXN');

            $table->string('status', 30)
                ->default('available');

            $table->timestamp('available_at');

            $table->timestamp('consumed_at')
                ->nullable();

            $table->string('idempotency_key', 191)
                ->unique();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'kind',
                'status',
            ]);

            $table->index('referral_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'promotional_credits'
        );
    }
};
