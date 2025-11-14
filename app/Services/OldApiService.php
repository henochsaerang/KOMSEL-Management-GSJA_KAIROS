<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OldApiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('OLD_API_BASE_URL');
    }

    public function login(string $email, string $password): ?array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/login_user", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->failed() || $response->json('status') !== true) {
                Log::error('API Login Gagal (login_user)', [
                    'email' => $email, 
                    'response' => $response->json() ?? $response->body()
                ]);
                return null;
            }
            return ['user' => $response->json('user')];
        } catch (ConnectionException $e) {
            Log::error('API Login Connection Error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function logout(): void
    {
        // Tidak perlu call API
    }

    // --- Endpoint Data ---

    public function getAllJemaat(): ?array
    {
        try {
            $response = Http::timeout(180)
                            ->post("{$this->baseUrl}/get-all-jemaat-for-sync");
            
            if ($response->failed() || $response->json('status') !== true) {
                 Log::error('API getAllJemaat Gagal', ['response' => $response->json() ?? $response->body()]);
                return null;
            }
            
            // [FIX] Kembalikan ke key JSON yang benar: 'data_jemaat'
            $data = $response->json('data_jemaat'); 
            return is_array($data) ? $data : null; 

        } catch (\Exception $e) {
            Log::error('API getAllJemaat Error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getAllKomsels(): ?array
    {
        try {
            $response = Http::timeout(30)
                            ->post("{$this->baseUrl}/get-all-komsels-for-sync");

            if ($response->failed() || $response->json('status') !== true) {
                Log::error('API getAllKomsels Gagal', ['response' => $response->json() ?? $response->body()]);
                return null;
            }
            $data = $response->json('data_komsel');
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            Log::error('API getAllKomsels Error', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    public function getAllLeaders(): ?array
    {
        try {
            $response = Http::timeout(60) 
                            ->post("{$this->baseUrl}/get-all-leaders-for-sync");

            if ($response->failed() || $response->json('status') !== true) {
                Log::error('API getAllLeaders Gagal', ['response' => $response->json() ?? $response->body()]);
                return null;
            }
            $data = $response->json('data_pemimpin');
            return is_array($data) ? $data : null;
        } catch (\Exception $e) { 
            Log::error('API getAllLeaders Error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getAllOikosPelayan(): ?array
    {
        try {
            $response = Http::timeout(60) 
                            ->post("{$this->baseUrl}/get-oikos-pelayan-for-sync");

            if ($response->failed() || $response->json('status') !== true) {
                Log::error('API getAllOikosPelayan Gagal', ['response' => $response->json() ?? $response->body()]);
                return null;
            }
            $data = $response->json('data_pelayan');
            return is_array($data) ? $data : null;
        } catch (\Exception $e) { 
            Log::error('API getAllOikosPelayan Error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}