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
        Schema::create('yandex_auth_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['pending', 'running', 'ready', 'failed'])->default('pending');
            $table->json('cookies')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_auth_tasks');
    }
};
