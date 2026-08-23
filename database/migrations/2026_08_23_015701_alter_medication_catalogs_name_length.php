<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->dropIndex(['name']);

            $table->dropIndex([
                'active',
                'name',
            ]);
        });

        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });

        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->index('name');

            $table->index([
                'active',
                'name',
            ]);
        });
    }
};
