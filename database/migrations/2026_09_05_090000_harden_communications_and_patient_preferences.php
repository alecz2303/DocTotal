<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('allow_email_communications')
                ->default(true);
            $table->boolean('allow_whatsapp_communications')
                ->default(true);
            $table->boolean('allow_sms_communications')
                ->default(true);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->dateTime('processing_started_at')
                ->nullable()
                ->after('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropColumn('processing_started_at');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'allow_email_communications',
                'allow_whatsapp_communications',
                'allow_sms_communications',
            ]);
        });
    }
};
