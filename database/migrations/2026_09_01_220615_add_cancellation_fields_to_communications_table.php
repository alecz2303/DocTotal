<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dateTime('cancelled_at')
                ->nullable()
                ->after('next_attempt_at');

            $table->string('cancellation_reason', 255)
                ->nullable()
                ->after('cancelled_at');

            $table->index([
                'tenant_id',
                'status',
                'cancelled_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex([
                'tenant_id',
                'status',
                'cancelled_at',
            ]);

            $table->dropColumn([
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
