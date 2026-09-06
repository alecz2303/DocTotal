<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partners', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 160);
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sales_partner_id')
                ->constrained('sales_partners')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('code', 32)->unique();
            $table->boolean('active')->default(true);
            $table->decimal('doctor_discount_percent', 5, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('commission_scope', 40)
                ->default('first_successful_payment');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_promo_attributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('promo_code_id')
                ->constrained('promo_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('sales_partner_id')
                ->constrained('sales_partners')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('code_snapshot', 32);
            $table->decimal('doctor_discount_percent_snapshot', 5, 2);
            $table->decimal('commission_percent_snapshot', 5, 2);
            $table->timestamp('attributed_at');
            $table->timestamps();
        });

        Schema::create('sales_commissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sales_partner_id')
                ->constrained('sales_partners')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('promo_code_id')
                ->constrained('promo_codes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('payment_id')
                ->unique()
                ->constrained('payments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('status', 24)->default('accrued');
            $table->unsignedBigInteger('base_amount');
            $table->decimal('commission_percent', 5, 2);
            $table->unsignedBigInteger('commission_amount');
            $table->string('currency', 3);
            $table->timestamp('accrued_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['sales_partner_id', 'status']);
            $table->index(['tenant_id', 'accrued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_commissions');
        Schema::dropIfExists('tenant_promo_attributions');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('sales_partners');
    }
};
