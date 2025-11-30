<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Tambahkan Log untuk debugging

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
        
        $userNoHp = null;
        $userKomselId = null;
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; // Array ID Komsel yang dipegang leader

        // Cek Data LEADER dari API
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
            // Jika BUKAN Leader, cek Data JEMAAT
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

        // 2. SINKRONISASI USER YANG LOGIN (LEADER/DIRI SENDIRI)
        $user = User::updateOrCreate(
            ['id' => $apiUserId], 
            [
                'name' => $userName,
                'email' => $credentials['email'], 
                'password' => Hash::make($credentials['password']),
                'roles' => $finalRoles,
                'no_hp' => $userNoHp,          
                'komsel_id' => $userKomselId,  
            ]
        );

        // =================================================================
        // 3. [FITUR BARU] SINKRONISASI MASSAL ANGGOTA KOMSEL
        // Jika User adalah LEADER (punya komsel), kita tarik semua anggota komselnya
        // ke database lokal supaya bisa dikirimi Notifikasi WA.
        // =================================================================
        if (!empty($finalKomsels)) {
            // Ambil semua jemaat (menggunakan cache agar cepat)
            $allJemaatSync = Cache::remember('api_jemaat_list_v2', 3600, function () {
                return $this->apiService->getAllJemaat();
            });

            if ($allJemaatSync) {
                // Filter hanya jemaat yang berada di komsel-komsel milik leader ini
                $myMembers = collect($allJemaatSync)->whereIn('komsel_id', $finalKomsels);

                foreach ($myMembers as $member) {
                    // Masukkan/Update data anggota ke tabel 'users' lokal
                    // Password di-set dummy karena mereka mungkin tidak login, tapi butuh data no_hp
                    try {
                        User::updateOrCreate(
                            ['id' => $member['id']],
                            [
                                'name' => $member['nama'] ?? 'Anggota',
                                'email' => $member['email'] ?? null, // Email bisa null di tabel user jika diset nullable
                                'password' => Hash::make('default123'), // Password default
                                'no_hp' => $member['no_hp'] ?? null,    // PENTING: No HP masuk sini
                                'komsel_id' => $member['komsel_id'],    // PENTING: ID Komsel masuk sini
                                // 'roles' => [] // Default kosong (Jemaat)
                            ]
                        );
                    } catch (\Exception $e) {
                        // Log error diam-diam jika ada data anggota yang bermasalah (misal email duplikat)
                        // Lanjut ke anggota berikutnya
                        Log::warning("Gagal sync anggota ID {$member['id']}: " . $e->getMessage());
                        continue; 
                    }
                }
            }
        }
        // =================================================================

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