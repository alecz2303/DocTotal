<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('doctor_profile_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status', 30)
                ->default('scheduled');

            $table->string('reason', 500)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->string('cancellation_reason', 500)
                ->nullable();

            $table->dateTime('confirmed_at')
                ->nullable();

            $table->dateTime('checked_in_at')
                ->nullable();

            $table->dateTime('started_at')
                ->nullable();

            $table->dateTime('completed_at')
                ->nullable();

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->dateTime('no_show_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'doctor_profile_id',
                'starts_at',
            ]);

            $table->index([
                'tenant_id',
                'patient_id',
                'starts_at',
            ]);

            $table->index([
                'tenant_id',
                'status',
                'starts_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
