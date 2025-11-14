<?php

namespace App\Http\Controllers; // Sesuaikan namespace Anda

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

    // Pastikan ini ada
    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman (view) login.
     */
    public function login()
    {
        return view('auth.login'); // Pastikan nama view ini benar
    }

    /**
     * Menampilkan halaman (view) signup/register.
     */
    public function signup()
    {
        return view('auth.signup'); // Pastikan nama view ini benar
    }

    /**
     * [PLACEHOLDER] Menangani pendaftaran user baru.
     */
    public function register(Request $request) 
    {
        return redirect()->route('login')->with('success', 'Registrasi belum diimplementasikan.');
    }


    /**
     * [FIXED] Menangani percobaan autentikasi
     * Menggunakan logika "enrichment" (perbandingan) yang Anda sarankan.
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Panggil API Login (yang datanya tidak lengkap)
        $apiLoginData = $this->apiService->login($credentials['email'], $credentials['password']);

        if (!$apiLoginData || !isset($apiLoginData['user'])) {
            return back()->withErrors([
                'email' => 'Email atau Password yang diberikan tidak cocok.',
            ])->onlyInput('email');
        }

        $apiUser = $apiLoginData['user']; // Data dari /login_user (roles-nya salah/kosong)
        $apiUserId = $apiUser['id'];
        $userName = $apiUser['name'] ?? $apiUser['nama'] ?? 'User Tanpa Nama';

        // [FIX 2] Logika "Perbandingan" (Enrichment) Anda
        // Kita default ke data login yang 'rusak' (roles kosong)
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; 

        // Panggil data 'getAllLeaders' (yang datanya LENGKAP)
        $allLeaders = Cache::remember('api_leader_list_v2', 3600, function () {
            return $this->apiService->getAllLeaders();
        });

        if ($allLeaders) {
            // Cari user yang login di dalam daftar leader
            $thisLeaderData = collect($allLeaders)->firstWhere('id', $apiUserId);
            
            // JIKA KETEMU (Dia adalah Leader/Admin)
            if ($thisLeaderData) {
                // Timpa data roles/komsels yang salah dengan data yang benar
                $finalRoles = $thisLeaderData['roles'] ?? [];
                $finalKomsels = $thisLeaderData['komsels'] ?? [];
            }
            // JIKA TIDAK KETEMU (Dia Jemaat asli)
            // Biarkan $finalRoles dan $finalKomsels tetap kosong.
        }

        // [FIX 3] Simpan data 'jejak' lokal (DB)
        $user = User::updateOrCreate(
            ['email' => $apiUser['email']], // Cari berdasarkan email
            [
                'id' => $apiUserId,
                'name' => $userName, 
                'password' => Hash::make($credentials['password']), 
                'roles' => $finalRoles, // Simpan roles yang BENAR
            ]
        );

        // 4. Login-kan user secara lokal
        Auth::login($user, $request->remember);
        $request->session()->regenerate();

        // 5. SIMPAN DATA YANG BENAR KE SESSION
        $request->session()->put('user_komsel_ids', $finalKomsels);
        $request->session()->put('user_roles', $finalRoles);
        $request->session()->put('is_super_admin', in_array('super_admin', $finalRoles));

        // 6. Arahkan ke dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Menghancurkan sesi.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }
}