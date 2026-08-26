<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('billing_cycle', 20);

            $table->string('status', 30)
                ->default('active');

            $table->dateTime('starts_at');

            $table->dateTime('current_period_starts_at');

            $table->dateTime('current_period_ends_at');

            $table->dateTime('next_billing_at')
                ->nullable();

            $table->boolean('cancel_at_period_end')
                ->default(false);

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'current_period_ends_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
