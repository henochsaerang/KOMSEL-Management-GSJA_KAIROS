<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp via Fonnte (MODE TESTING)
     * * @param mixed $target Nomor HP asli dari database
     * @param string $message Isi pesan
     */
    public static function send($target, $message)
    {
        $token = env('FONNTE_TOKEN');

        if (empty($token)) {
            Log::warning('Fonnte Token belum disetting di .env');
            return null;
        }

        // =================================================================
        // 🔒 SAFETY LOCK (PENGAMAN TESTING)
        // Fitur ini memaksa semua pesan dikirim ke nomor developer saja.
        // Hapus atau komentari baris di bawah ini jika sudah siap Live/Produksi.
        // =================================================================
        
        $originalTarget = is_array($target) ? implode(',', $target) : $target; // Simpan target asli untuk log
        
        // GANTI NOMOR INI DENGAN NOMOR WA ANDA SENDIRI!
        $target = '082154325366'; 

        // Tambahkan info debugging ke pesan agar Anda tahu pesan ini aslinya untuk siapa
        $message = "[TESTING MODE]\nTarget Asli: " . $originalTarget . "\n\n" . $message;

        // =================================================================

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', 
            ]);

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