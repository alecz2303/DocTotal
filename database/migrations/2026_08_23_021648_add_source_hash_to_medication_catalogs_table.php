<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->string('source_hash', 40)
                ->nullable()
                ->unique()
                ->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('medication_catalogs', function (Blueprint $table) {
            $table->dropUnique([
                'source_hash',
            ]);

            $table->dropColumn('source_hash');
        });
    }
};
