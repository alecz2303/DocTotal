<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laboratory_study_id')
                ->constrained('laboratory_studies')
                ->cascadeOnDelete();
            $table->string('parameter_name', 180);
            $table->string('value', 255);
            $table->string('unit', 80)->nullable();
            $table->string('reference_range', 180)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'laboratory_study_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_results');
    }
};
