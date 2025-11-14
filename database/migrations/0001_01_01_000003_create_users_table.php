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
        // Tabel "jejak" untuk user yang login via API
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // ID lokal
            $table->unsignedBigInteger('old_api_id')->unique()->nullable(); // ID dari API lama
            
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable(); // Dibuat nullable, kita tidak pakai
            $table->json('roles')->nullable();      // Untuk menyimpan roles dari API
            $table->string('status')->default('aktif'); // <-- KOLOM YANG HILANG
            $table->text('api_token')->nullable(); // Untuk menyimpan token (jika API nanti diamankan)

            $table->rememberToken();
            $table->timestamps();
        });

        // Tabel 'sessions' bawaan Laravel
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};