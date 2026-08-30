<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_documents', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('uuid')->unique();

            $table
                ->foreignId('patient_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('consultation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table
                ->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->string('category', 30)
                ->default('general');

            $table->string('title', 190);

            $table->date('document_date')->nullable();

            $table->string('original_name', 255);

            $table
                ->string('disk', 50)
                ->default('local');

            $table->string('path', 500);

            $table->string('mime_type', 150)->nullable();

            $table->unsignedBigInteger('size_bytes');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'patient_id',
                'created_at',
            ]);

            $table->index([
                'tenant_id',
                'consultation_id',
            ]);

            $table->index([
                'tenant_id',
                'category',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_documents');
    }
};
