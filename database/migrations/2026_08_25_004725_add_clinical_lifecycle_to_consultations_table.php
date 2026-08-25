<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('draft')
                ->change();

            $table->dateTime('completed_at')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('completed')
                ->change();

            $table->dropColumn('completed_at');
        });
    }
};
