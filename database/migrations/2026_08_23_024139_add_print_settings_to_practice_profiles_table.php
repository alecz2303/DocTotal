<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_profiles', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('description');

            $table->string('legal_name', 190)
                ->nullable()
                ->after('public_name');

            $table->string('tax_id', 30)
                ->nullable()
                ->after('legal_name');

            $table->string('print_footer', 255)
                ->nullable()
                ->after('booking_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('practice_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'legal_name',
                'tax_id',
                'print_footer',
            ]);
        });
    }
};
