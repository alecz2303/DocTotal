<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
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

            $table->dateTime('consultation_at');

            $table->string('reason', 500)->nullable();

            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();

            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();

            $table->unsignedSmallInteger('systolic_bp')->nullable();
            $table->unsignedSmallInteger('diastolic_bp')->nullable();

            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();

            $table->decimal('temperature_c', 4, 1)->nullable();

            $table->unsignedSmallInteger('oxygen_saturation')->nullable();

            $table->string('status', 30)->default('completed');

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'patient_id',
                'consultation_at',
            ]);

            $table->index([
                'tenant_id',
                'doctor_profile_id',
                'consultation_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
