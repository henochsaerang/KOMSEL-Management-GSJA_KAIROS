<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FonnteService {

    public static function send($target, $message) {
        $token = env('FONNTE_TOKEN');

        if (is_array($target)) {
            $target = implode(',', $target);
        }

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
