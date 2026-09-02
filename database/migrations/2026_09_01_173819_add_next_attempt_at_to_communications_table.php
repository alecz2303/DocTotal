<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dateTime('next_attempt_at')
                ->nullable()
                ->after('failed_at');

            $table->index([
                'tenant_id',
                'status',
                'next_attempt_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex([
                'tenant_id',
                'status',
                'next_attempt_at',
            ]);

            $table->dropColumn('next_attempt_at');
        });
    }
};
