<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('action', 100);

            $table->string('auditable_type', 190)
                ->nullable();

            $table->unsignedBigInteger('auditable_id')
                ->nullable();

            $table->string('description', 255)
                ->nullable();

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'created_at']
            );

            $table->index(
                ['tenant_id', 'user_id', 'created_at']
            );

            $table->index(
                ['tenant_id', 'action', 'created_at']
            );

            $table->index(
                [
                    'tenant_id',
                    'auditable_type',
                    'auditable_id',
                ],
                'audit_events_auditable_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
