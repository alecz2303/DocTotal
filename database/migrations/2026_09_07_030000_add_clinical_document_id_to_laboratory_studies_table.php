<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_studies', function (Blueprint $table): void {
            $table->foreignId('clinical_document_id')
                ->nullable()
                ->after('consultation_id')
                ->constrained('clinical_documents')
                ->nullOnDelete();

            $table->index(['tenant_id', 'clinical_document_id']);
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_studies', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'clinical_document_id']);
            $table->dropConstrainedForeignId('clinical_document_id');
        });
    }
};
