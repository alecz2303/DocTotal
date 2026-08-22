<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_medical_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('allergies_text')->nullable();
            $table->text('current_medications_text')->nullable();
            $table->text('chronic_conditions_text')->nullable();
            $table->text('surgeries_text')->nullable();
            $table->text('family_history_text')->nullable();
            $table->text('personal_history_text')->nullable();
            $table->text('gynecological_history_text')->nullable();
            $table->text('habits_text')->nullable();
            $table->text('other_notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_histories');
    }
};
