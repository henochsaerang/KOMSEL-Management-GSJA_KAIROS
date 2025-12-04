<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

        // =================================================================
        // 1. MAGIC LOGIN (UNTUK TESTING LOKAL)
        // =================================================================
        $magicEmails = ['admin@local.test', 'anggota@local.test', 'kordinator@local.test'];

        if (in_array($credentials['email'], $magicEmails)) {
            
            if ($credentials['password'] !== 'password') {
                return back()->withErrors(['email' => 'Password salah untuk akun dummy.']);
            }

            // Setup Data Dummy Berdasarkan Email
            if ($credentials['email'] === 'admin@local.test') {
                // SUPER ADMIN (GEMBALA)
                $dummyData = [
                    'id' => 999,
                    'name' => 'Super Admin (Gembala)',
                    'roles' => ['super_admin'], 
                    'no_hp' => '081234567890', 
                    'komsel_id' => 1,
                    'is_coordinator' => false          
                ];
            } elseif ($credentials['email'] === 'kordinator@local.test') {
                // KOORDINATOR (3 Role Gabungan)
                $dummyData = [
                    'id' => 888,
                    'name' => 'Koordinator Pusat',
                    'roles' => ['super_admin', 'panel_user', 'Leaders'], // Kombinasi Koordinator
                    'no_hp' => '081299998888', 
                    'komsel_id' => 1,  
                    'is_coordinator' => true // Flag eksplisit        
                ];
            } else {
                // ANGGOTA BIASA
                $dummyData = [
                    'id' => 1000,
                    'name' => 'Anggota Biasa',
                    'roles' => [], 
                    'no_hp' => '089876543210', 
                    'komsel_id' => 1,
                    'is_coordinator' => false          
                ];
            }

            // Simpan User ke DB Lokal
            $user = User::updateOrCreate(
                ['email' => $credentials['email']], 
                [
                    'id' => $dummyData['id'],
                    'name' => $dummyData['name'],
                    'password' => Hash::make($credentials['password']),
                    'roles' => $dummyData['roles'],
                    'no_hp' => $dummyData['no_hp'],       
                    'komsel_id' => $dummyData['komsel_id'],
                    'is_coordinator' => $dummyData['is_coordinator']
                ]
            );

            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            // Setup Session (Penting untuk Logic Dashboard)
            // Admin & Koordinator butuh akses semua data (is_super_admin = true)
            if (in_array('super_admin', $dummyData['roles'])) {
                $allKomsels = collect(Cache::remember('api_komsel_list_std_v2', 3600, function () {
                     return $this->apiService->getAllKomsels() ?: [];
                }));
                $komselIds = $allKomsels->isEmpty() ? [1] : $allKomsels->pluck('id')->toArray();
                
                $request->session()->put('user_komsel_ids', $komselIds);
                $request->session()->put('is_super_admin', true);
            } else {
                $request->session()->put('user_komsel_ids', [$dummyData['komsel_id']]); 
                $request->session()->put('is_super_admin', false);
            }
            
            $request->session()->put('user_roles', $dummyData['roles']);

            return redirect()->intended(route('dashboard'));
        }

        // =================================================================
        // 2. REAL API LOGIN (PRODUKSI)
        // =================================================================
        
        // A. Login ke API Lama
        $apiLoginData = $this->apiService->login($credentials['email'], $credentials['password']);

        if (!$apiLoginData || !isset($apiLoginData['user'])) {
            return back()->withErrors([
                'email' => 'Email atau Password yang diberikan tidak cocok.',
            ])->onlyInput('email');
        }

        $apiUser = $apiLoginData['user'];
        $apiUserId = $apiUser['id'];
        $userName = $apiUser['name'] ?? $apiUser['nama'] ?? 'User Tanpa Nama';

        // B. Cari Data Detail (No HP & Komsel) dari Cache API
        $userNoHp = null;
        $userKomselId = null;
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; 

        // Cek di Data LEADER
        $allLeaders = Cache::remember('api_leader_list_v2', 3600, function () {
            return $this->apiService->getAllLeaders();
        });

        $leaderData = null;
        if ($allLeaders) {
            $leaderData = collect($allLeaders)->firstWhere('id', $apiUserId);
        }

        if ($leaderData) {
            $finalRoles = $leaderData['roles'] ?? $finalRoles;
            $userNoHp = $leaderData['no_hp'] ?? null;
            
            $rawKomsels = $leaderData['komsels'] ?? [];
            $finalKomsels = is_string($rawKomsels) ? json_decode($rawKomsels, true) : $rawKomsels;
            if (!is_array($finalKomsels)) $finalKomsels = [];
        } else {
            // Cek di Data JEMAAT
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

        // C. Simpan HANYA User yang sedang Login ke Database Lokal
        // Kita tidak lagi mensinkronkan seluruh anggota komsel agar login cepat
        // is_coordinator di-set false by default utk login API (kecuali di-set manual di DB)
        $user = User::updateOrCreate(
            ['id' => $apiUserId], 
            [
                'name' => $userName,
                'email' => $credentials['email'], 
                'password' => Hash::make($credentials['password']),
                'roles' => $finalRoles,
                'no_hp' => $userNoHp,          
                'komsel_id' => $userKomselId,
                // Jangan overwrite is_coordinator jika sudah di-set true manual di DB lokal
                // Tapi untuk user baru, default false.
            ]
        );
        
        // ==================================================================
        // [OPTIMIZED] AUTO-SYNC REMOVED
        // Loop sinkronisasi anggota dihapus agar login instan.
        // Broadcast WA sekarang menggunakan data langsung dari API Cache.
        // ==================================================================

        // D. Finalisasi Login
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