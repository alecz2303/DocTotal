<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_catalogs', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100)->nullable();
            $table->string('name', 255);
            $table->string('presentation', 500)->nullable();

            $table->string('therapeutic_group', 255)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('code');
            $table->index('name');
            $table->index([
                'active',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_catalogs');
    }
};
