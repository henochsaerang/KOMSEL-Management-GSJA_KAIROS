<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom foto peduli, pelayan asli (untuk delegasi), dan alasan.
     */
    public function up(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            // 1. Menambahkan kolom foto khusus untuk Tindakan Peduli
            // (Agar terpisah dari tindakan_cinta_photo_path yang sudah ada)
            // Kita taruh setelah kolom deskripsi peduli
            $table->string('tindakan_peduli_photo_path')->nullable()->after('tindakan_peduli_desc');

            // 2. Kolom untuk fitur Delegasi/Penggantian Pelayan
            // Menyimpan ID pelayan awal sebelum diganti (untuk history)
            $table->foreignId('original_pelayan_user_id')
                  ->nullable()
                  ->after('pelayan_user_id')
                  ->constrained('users') // Relasi ke tabel users
                  ->onDelete('set null');

            // Alasan kenapa diganti (misal: "Sakit", "Luar Kota")
            $table->text('replacement_reason')->nullable()->after('original_pelayan_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            // Hapus foreign key dulu sebelum hapus kolom
            $table->dropForeign(['original_pelayan_user_id']);
            $table->dropColumn(['tindakan_peduli_photo_path', 'original_pelayan_user_id', 'replacement_reason']);
        });
    }
};