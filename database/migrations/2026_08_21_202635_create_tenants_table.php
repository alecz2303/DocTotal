<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 150);
            $table->string('slug', 150)->unique();

            $table->string('status', 30)->default('trial');

            $table->string('timezone', 50)->default('America/Mexico_City');
            $table->string('locale', 10)->default('es_MX');
            $table->char('currency', 3)->default('MXN');

            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('deletion_due_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
