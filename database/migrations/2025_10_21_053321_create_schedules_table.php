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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            // [PERBAIKAN] Ubah menjadi integer biasa untuk menyimpan ID dari API
            $table->unsignedBigInteger('komsel_id'); 
            
            $table->string('day_of_week');
            $table->time('time');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('status')->default('Menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
