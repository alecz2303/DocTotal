<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_diagnoses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('consultation_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('code', 20)->nullable();
            $table->string('description', 255);

            $table->boolean('is_primary')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'consultation_id',
            ]);

            $table->index([
                'tenant_id',
                'code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_diagnoses');
    }
};
