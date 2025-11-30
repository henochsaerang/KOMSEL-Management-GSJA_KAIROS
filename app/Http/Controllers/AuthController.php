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
        return view('Auth.login');
    }

    public function signup()
    {
        return view('Auth.signUp');
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

        // 1. REAL API LOGIN
        $apiLoginData = $this->apiService->login($credentials['email'], $credentials['password']);

        if (!$apiLoginData || !isset($apiLoginData['user'])) {
            return back()->withErrors([
                'email' => 'Email atau Password yang diberikan tidak cocok.',
            ])->onlyInput('email');
        }

        $apiUser = $apiLoginData['user'];
        $apiUserId = $apiUser['id'];
        $userName = $apiUser['name'] ?? $apiUser['nama'] ?? 'User Tanpa Nama';

        // --- LOGIKA PENCARIAN DATA LENGKAP (NO HP & KOMSEL) ---
        // Kita butuh data detail untuk notifikasi WA & Filter Komsel
        
        $userNoHp = null;
        $userKomselId = null;
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; // Untuk session leader

        // Coba cari di data LEADER dulu
        $allLeaders = Cache::remember('api_leader_list_v2', 3600, function () {
            return $this->apiService->getAllLeaders();
        });
        
        $leaderData = collect($allLeaders)->firstWhere('id', $apiUserId);
        
        if ($leaderData) {
            // Jika dia Leader
            $finalRoles = $leaderData['roles'] ?? $finalRoles;
            $userNoHp = $leaderData['no_hp'] ?? null;
            
            // Handle array komsels
            $rawKomsels = $leaderData['komsels'] ?? [];
            $finalKomsels = is_string($rawKomsels) ? json_decode($rawKomsels, true) : $rawKomsels;
            if (!is_array($finalKomsels)) $finalKomsels = [];
            
        } else {
            // Jika BUKAN Leader, cari di data JEMAAT
            $allJemaat = Cache::remember('api_jemaat_list_v2', 3600, function () {
                return $this->apiService->getAllJemaat();
            });
            
            $jemaatData = collect($allJemaat)->firstWhere('id', $apiUserId);
            
            if ($jemaatData) {
                $userNoHp = $jemaatData['no_hp'] ?? null;
                $userKomselId = $jemaatData['komsel_id'] ?? null;
            }
        }

        $isSuperAdmin = in_array('super_admin', $finalRoles);

        // 2. SINKRONISASI KE DATABASE LOKAL (WAJIB LENGKAP)
        $user = User::updateOrCreate(
            ['id' => $apiUserId], 
            [
                'name' => $userName,
                'email' => $credentials['email'], 
                'password' => Hash::make($credentials['password']),
                'roles' => $finalRoles,
                'no_hp' => $userNoHp,          // [PENTING] Simpan No HP untuk Fonnte
                'komsel_id' => $userKomselId,  // [PENTING] Simpan ID Komsel untuk Broadcast
            ]
        );

        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

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