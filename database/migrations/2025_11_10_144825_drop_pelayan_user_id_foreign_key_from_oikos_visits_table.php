<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nama file akan sesuai dengan yang Anda buat
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kita hanya akan menghapus foreign key, BUKAN kolomnya
        Schema::table('oikos_visits', function (Blueprint $table) {
            
            // 1. Cek nama constraint Anda dari pesan error
            // Error: ... CONSTRAINT `oikos_visits_pelayan_user_id_foreign` ...
            // Jadi, nama constraint-nya adalah 'oikos_visits_pelayan_user_id_foreign'
            
            $table->dropForeign('oikos_visits_pelayan_user_id_foreign');
            
            // Jika Anda tidak yakin nama constraint-nya, Anda bisa
            // menggunakan nama kolom (Laravel akan menebaknya):
            // $table->dropForeign(['pelayan_user_id']); 
        });
    }

    /**
     * Reverse the migrations.
     * (Ini adalah kode untuk mengembalikan constraint-nya jika Anda 'rollback')
     */
    public function down(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            // Ini adalah constraint asli Anda (berdasarkan error log)
            $table->foreign('pelayan_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
};