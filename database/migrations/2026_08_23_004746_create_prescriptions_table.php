<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
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

            $table->foreignId('consultation_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->dateTime('prescribed_at');

            $table->text('general_instructions')->nullable();

            $table->string('status', 30)->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'patient_id',
                'prescribed_at',
            ]);

            $table->index([
                'tenant_id',
                'consultation_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
