<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel notifikasi.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Penerima Notifikasi (Relasi ke Users)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('title'); // Judul, misal: "Jadwal Baru"
            $table->text('message'); // Pesan detail
            $table->string('type')->default('info'); // 'assignment', 'alert', 'info'
            
            // Link opsional (misal: klik notifikasi langsung ke halaman detail kunjungan)
            $table->string('action_url')->nullable(); 
            
            $table->boolean('is_read')->default(false); // Status sudah dibaca atau belum
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};