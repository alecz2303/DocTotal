<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->uuid('uuid')
                ->nullable()
                ->unique()
                ->after('tenant_id');

            $table->string('role', 30)
                ->default('owner')
                ->after('password');

            $table->timestamp('last_login_at')
                ->nullable()
                ->after('email_verified_at');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);

            $table->dropColumn([
                'tenant_id',
                'uuid',
                'role',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};