<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom batas waktu buka kunci jadwal (emergency unlock).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom ini menyimpan batas waktu user BISA input jadwal di luar hari Minggu-Selasa.
            // Jika NULL atau masa lampau, user terkunci.
            // Jika masa depan (misal: besok jam 23:59), user bisa input.
            $table->dateTime('scheduling_unlock_until')->nullable()->after('roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('scheduling_unlock_until');
        });
    }
};