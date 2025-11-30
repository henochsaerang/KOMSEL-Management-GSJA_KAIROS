<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pic_id'); // ID User (Leader) yang login
            $table->unsignedBigInteger('member_id'); // ID Jemaat dari API Lama
            $table->string('nama_anggota_snapshot'); // Simpan nama text jaga-jaga data API berubah
            $table->dateTime('tanggal');
            $table->string('jenis_kunjungan'); // Pastoral, HUT, Sakit, dll
            $table->text('catatan')->nullable();
            $table->string('status')->default('Terjadwal'); // Terjadwal, Selesai, Batal
            $table->timestamps();

            $table->foreign('pic_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kunjungans');
    }
};