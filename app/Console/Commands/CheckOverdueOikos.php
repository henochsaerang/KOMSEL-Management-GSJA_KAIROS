<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OikosVisit; // [WAJIB] Import model OikosVisit
use Carbon\Carbon;          // [WAJIB] Import Carbon untuk manajemen tanggal

class CheckOverdueOikos extends Command
{
    /**
     * Nama dan signature dari console command.
     * Ini adalah nama yang Anda panggil, misal: 'php artisan app:check-overdue-oikos'
     */
    protected $signature = 'app:check-overdue-oikos';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Cek dan tandai Oikos visit yang kedaluwarsa (status: Direncanakan/Berlangsung) menjadi Gagal';

    /**
     * Jalankan console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan Oikos kedaluwarsa...');

        // Dapatkan tanggal hari ini (awal hari, jam 00:00:00)
        $today = Carbon::today();

        // Cari Oikos yang:
        // 1. Tanggal akhirnya (end_date) adalah SEBELUM hari ini.
        // 2. Statusnya masih 'Direncanakan' ATAU 'Berlangsung'.
        
        $count = OikosVisit::where('end_date', '<', $today)
                           ->whereIn('status', ['Direncanakan', 'Berlangsung'])
                           ->update(['status' => 'Gagal']);

        if ($count > 0) {
            $this->info("Berhasil: $count kunjungan Oikos yang kedaluwarsa telah ditandai sebagai 'Gagal'.");
        } else {
            $this->info("Tidak ada Oikos kedaluwarsa yang ditemukan.");
        }

        $this->info('Pengecekan Oikos kedaluwarsa selesai.');
        return 0;
    }
}