<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('prescription_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('medication_name', 255);

            $table->string('presentation', 255)->nullable();
            $table->string('dose', 255)->nullable();
            $table->string('frequency', 255)->nullable();
            $table->string('duration', 255)->nullable();

            $table->text('instructions')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index([
                'tenant_id',
                'prescription_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
