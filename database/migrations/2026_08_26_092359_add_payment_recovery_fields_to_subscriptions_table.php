<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dateTime('past_due_since')
                ->nullable()
                ->after('next_billing_at');

            $table->dateTime('grace_ends_at')
                ->nullable()
                ->after('past_due_since');

            $table->dateTime('next_retry_at')
                ->nullable()
                ->after('grace_ends_at');

            $table->unsignedSmallInteger('retry_count')
                ->default(0)
                ->after('next_retry_at');

            $table->index([
                'status',
                'grace_ends_at',
            ]);

            $table->index([
                'status',
                'next_retry_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex([
                'status',
                'grace_ends_at',
            ]);

            $table->dropIndex([
                'status',
                'next_retry_at',
            ]);

            $table->dropColumn([
                'past_due_since',
                'grace_ends_at',
                'next_retry_at',
                'retry_count',
            ]);
        });
    }
};
