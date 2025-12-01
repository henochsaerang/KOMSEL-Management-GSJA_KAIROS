<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            // Batas waktu sampai kapan laporan ini boleh diisi di luar jadwal
            $table->dateTime('report_unlock_until')->nullable()->after('replacement_reason');
        });
    }

    public function down(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            $table->dropColumn('report_unlock_until');
        });
    }
};