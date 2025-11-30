<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp via Fonnte
     * Dengan fitur SAFETY LOCK untuk mode testing.
     */
    public static function send($target, $message)
    {
        $token = env('FONNTE_TOKEN');

        if (empty($token)) {
            Log::warning('Fonnte Token belum disetting di .env');
            return null;
        }

        // =================================================================
        // 🔒 SAFETY LOCK (PENGAMAN TESTING - AKTIF)
        // =================================================================
        // Logika ini akan MEMBELOKKAN semua pesan ke nomor developer.
        // Hapus blok ini jika aplikasi sudah siap dipakai Jemaat (Live).
        
        // Simpan target asli sekedar untuk catatan di pesan
        $originalTarget = is_array($target) ? implode(',', $target) : $target; 
        
        // [PENTING] NOMOR TUJUAN TESTING (Nomor Anda)
        $target = '082154325366'; 

        // Tambahkan header di pesan agar Anda tahu ini testing
        $message = "*[MODE TESTING AKTIF]*\n" . 
                   "Tujuan Asli: " . $originalTarget . "\n" . 
                   "--------------------------\n" . 
                   $message;
        // =================================================================

        // Handle jika target berupa array (jaga-jaga jika safety lock dimatikan nanti)
        if (is_array($target)) {
            $target = implode(',', $target);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Otomatis ubah 08 jadi 62
            ]);

            // Cek jika API Fonnte menolak (misal token salah/expired/device disconnect)
            if ($response->failed()) {
                Log::error('Gagal kirim WA Fonnte: ' . $response->body());
            }
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error koneksi Fonnte: ' . $e->getMessage());
            return null;
        }
    }
}