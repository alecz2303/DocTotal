<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('type', 60);

            $table->string('channel', 30);

            /*
             * Se conserva el destinatario utilizado al momento
             * de generar la comunicación.
             *
             * No dependemos del valor actual en Patient porque
             * teléfono, WhatsApp o email pueden cambiar después.
             */
            $table->string('recipient', 190);

            $table->string('subject', 255)
                ->nullable();

            $table->text('body');

            $table->string('status', 30)
                ->default('pending');

            /*
             * Evita generar accidentalmente dos veces la misma
             * comunicación dentro de un tenant.
             */
            $table->string('idempotency_key', 190);

            $table->dateTime('scheduled_for')
                ->nullable();

            $table->dateTime('sent_at')
                ->nullable();

            $table->dateTime('failed_at')
                ->nullable();

            $table->unsignedInteger('attempt_count')
                ->default(0);

            $table->text('last_error')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'idempotency_key',
            ]);

            $table->index([
                'tenant_id',
                'status',
                'scheduled_for',
            ]);

            $table->index([
                'tenant_id',
                'appointment_id',
                'type',
            ]);

            $table->index([
                'tenant_id',
                'patient_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
