<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->char('public_access_token_hash', 64)
                ->nullable()
                ->unique()
                ->after('no_show_at');

            $table->dateTime('public_access_token_generated_at')
                ->nullable()
                ->after('public_access_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique([
                'public_access_token_hash',
            ]);

            $table->dropColumn([
                'public_access_token_hash',
                'public_access_token_generated_at',
            ]);
        });
    }
};
