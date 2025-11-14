<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oikos_visits', function (Blueprint $table) {
            $table->id();
            
            // Data Penjadwalan
            $table->string('oikos_name'); 

            // [PERBAIKAN] Kolom ini untuk PELAYAN (dari tabel 'users' LOKAL)
            $table->foreignId('pelayan_user_id')
                  ->nullable()
                  ->constrained('users') // <-- Relasi ke tabel 'users'
                  ->onDelete('set null');

            // [BENAR] Kolom untuk Jemaat (hanya angka dari API)
            $table->unsignedBigInteger('jemaat_id')->nullable();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Direncanakan');

            // Data Laporan
            $table->date('realisasi_date')->nullable();
            $table->boolean('is_doa_5_jari')->default(false);
            $table->date('realisasi_doa_5_jari_date')->nullable();
            $table->boolean('is_doa_syafaat')->default(false);
            $table->date('realisasi_doa_syafaat_date')->nullable();
            $table->text('tindakan_cinta_desc')->nullable();
            $table->string('tindakan_cinta_photo_path')->nullable();
            $table->text('tindakan_peduli_desc')->nullable();
            $table->string('respon_injil')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oikos_visits');
    }
};