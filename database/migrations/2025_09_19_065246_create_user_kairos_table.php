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
        Schema::create('user_kairos', function (Blueprint $table) {
            $table->id();
            
            // KOLOM SINKRONISASI (SANGAT PENTING)
            // Menyimpan ID asli dari aplikasi lama. Bisa null jika user dibuat di aplikasi baru.
            $table->unsignedBigInteger('origin_id')->unique()->nullable();

            $table->string('email')->nullable(); // Email mungkin tidak selalu ada, jadi kita buat nullable.
            $table->string('password'); // Mengikuti konvensi Laravel.
            $table->string('nama'); // Dihilangkan ->unique() untuk menghindari error nama ganda.
            
            // RELASI KE KOMSEL (CARA TERBAIK)
            // Nanti kita akan buat tabel 'komsels' terpisah (id, nama_komsel)
            // Untuk sekarang, kita siapkan dulu kolomnya.
            // Jika Anda belum punya tabel komsels, hapus sementara ->constrained('komsels')
            $table->unsignedBigInteger('komsel_id')->nullable(); 

            // Kita tambahkan juga kolom status dari migrasi Anda yang lain
            $table->string('status')->default('aktif');

            $table->rememberToken(); // Penting untuk fitur "Ingat Saya" saat login
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_kairos');
    }
};