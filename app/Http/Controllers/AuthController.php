<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $apiService;

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function login()
    {
        return view('auth.login');
    }

    public function signup()
    {
        return view('auth.signup');
    }

    public function register(Request $request) 
    {
        return redirect()->route('login')->with('success', 'Registrasi belum diimplementasikan.');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // =================================================================
        // 1. MAGIC LOGIN (PERBAIKAN LOGIKA)
        // =================================================================
        if (in_array($credentials['email'], ['admin@local.test', 'anggota@local.test'])) {
            
            $isAdmin = $credentials['email'] === 'admin@local.test';
            
            // Tentukan data dummy berdasarkan SIAPA yang sedang login
            $dummyData = $isAdmin ? [
                'id' => 999,
                'name' => 'Super Admin (Lokal)',
                'roles' => ['super_admin'], // Admin juga punya akses leader
            ] : [
                'id' => 1000,
                'name' => 'Anggota Biasa (Lokal)',
                'roles' => ['jemaat'],
            ];

            // Update atau Buat HANYA User yang sedang login
            $user = User::updateOrCreate(
                ['email' => $credentials['email']], // Kunci pencarian sesuai input
                [
                    'id' => $dummyData['id'],
                    'name' => $dummyData['name'],
                    'password' => Hash::make($credentials['password']),
                    'roles' => $dummyData['roles'],
                ]
            );

            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            // Mocking Session Data
            if ($isAdmin) {
                // Jika Admin, load semua komsel agar dashboard penuh
                $allKomsels = collect(Cache::remember('api_komsel_list_std_v2', 3600, function () {
                     return $this->apiService->getAllKomsels();
                }));
                $request->session()->put('user_komsel_ids', $allKomsels->pluck('id')->toArray());
                $request->session()->put('is_super_admin', true);
            } else {
                $request->session()->put('user_komsel_ids', []); // Anggota tidak punya komsel
                $request->session()->put('is_super_admin', false);
            }
            
            $request->session()->put('user_roles', $dummyData['roles']);

            return redirect()->intended(route('dashboard'));
        }
        // =================================================================
        // Akhir dari Logika "Magic Login"
        // =================================================================

        // 2. REAL API LOGIN (PRODUKSI)
        $apiLoginData = $this->apiService->login($credentials['email'], $credentials['password']);

        if (!$apiLoginData || !isset($apiLoginData['user'])) {
            return back()->withErrors([
                'email' => 'Email atau Password yang diberikan tidak cocok.',
            ])->onlyInput('email');
        }

        $apiUser = $apiLoginData['user'];
        $apiUserId = $apiUser['id'];
        $userName = $apiUser['name'] ?? $apiUser['nama'] ?? 'User Tanpa Nama';

        // Default roles kosong jika API login tidak memberikannya dengan benar
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; 

        // Fetch Detail Lengkap Leader untuk memperbaiki data roles/komsel
        $allLeaders = Cache::remember('api_leader_list_v2', 3600, function () {
            return $this->apiService->getAllLeaders();
        });

        if ($allLeaders) {
            $thisLeaderData = collect($allLeaders)->firstWhere('id', $apiUserId);
            
            if ($thisLeaderData) {
                $finalRoles = $thisLeaderData['roles'] ?? [];
                
                // Handle format komsels (bisa array atau string JSON)
                $rawKomsels = $thisLeaderData['komsels'] ?? [];
                $finalKomsels = is_string($rawKomsels) ? json_decode($rawKomsels, true) : $rawKomsels;
                
                if (!is_array($finalKomsels)) $finalKomsels = [];
            }
        }

        // Cek flag Super Admin
        $isSuperAdmin = in_array('super_admin', $finalRoles);

        // Sinkronisasi User ke Database Lokal
        $user = User::updateOrCreate(
            ['id' => $apiUserId], 
            [
                'name' => $userName,
                'email' => $credentials['email'], // Selalu update email terbaru
                'password' => Hash::make($credentials['password']),
                'roles' => $finalRoles,
            ]
        );

        // Proses Login Lokal
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        // Simpan Data Penting ke Session
        $request->session()->put('user_komsel_ids', $finalKomsels);
        $request->session()->put('user_roles', $finalRoles);
        $request->session()->put('is_super_admin', $isSuperAdmin);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }
}