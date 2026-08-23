<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_catalogs', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique();
            $table->string('description', 500);

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index([
                'active',
                'description',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_catalogs');
    }
};
