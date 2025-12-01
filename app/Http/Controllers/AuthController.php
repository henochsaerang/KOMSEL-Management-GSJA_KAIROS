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
        // 1. MAGIC LOGIN (PERBAIKAN LOGIKA & SINKRONISASI DATA DUMMY)
        // =================================================================
        if (in_array($credentials['email'], ['admin@local.test', 'anggota@local.test'])) {
            
            // Validasi password sederhana untuk dummy
            if ($credentials['password'] !== 'password') {
                return back()->withErrors(['email' => 'Password salah untuk akun dummy.']);
            }

            $isAdmin = $credentials['email'] === 'admin@local.test';
            
            // Data Dummy Lengkap (Termasuk no_hp dan komsel_id)
            $dummyData = $isAdmin ? [
                'id' => 999,
                'name' => 'Super Admin (Lokal)',
                'roles' => ['super_admin'],
                'no_hp' => '081234567890', // Nomor dummy admin
                'komsel_id' => 1,          // ID Komsel dummy admin
            ] : [
                'id' => 1000,
                'name' => 'Anggota Biasa (Lokal)',
                'roles' => [], // Kosong = Jemaat
                'no_hp' => '089876543210', // Nomor dummy anggota
                'komsel_id' => 1,          // ID Komsel dummy anggota (sama dgn admin agar bisa dites)
            ];

            // Simpan ke DB Lokal
            $user = User::updateOrCreate(
                ['email' => $credentials['email']], 
                [
                    'id' => $dummyData['id'],
                    'name' => $dummyData['name'],
                    'password' => Hash::make($credentials['password']),
                    'roles' => $dummyData['roles'],
                    'no_hp' => $dummyData['no_hp'],       // Simpan
                    'komsel_id' => $dummyData['komsel_id'] // Simpan
                ]
            );

            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            // Mocking Session Data
            if ($isAdmin) {
                // Load data komsel dummy atau asli untuk dropdown
                $allKomsels = collect(Cache::remember('api_komsel_list_std_v2', 3600, function () {
                     return $this->apiService->getAllKomsels();
                }));
                // Jika API kosong, buat dummy komsel ID
                $komselIds = $allKomsels->isEmpty() ? [1] : $allKomsels->pluck('id')->toArray();
                
                $request->session()->put('user_komsel_ids', $komselIds);
                $request->session()->put('is_super_admin', true);
            } else {
                $request->session()->put('user_komsel_ids', []); 
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

        // --- PENCARIAN DATA DETAIL (NO HP & KOMSEL) ---
        $userNoHp = null;
        $userKomselId = null;
        $finalRoles = $apiUser['roles'] ?? []; 
        $finalKomsels = []; 

        // Cek di Data LEADER API
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
            // Cek di Data JEMAAT API
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

        // Sinkronisasi User ke Database Lokal (LENGKAP)
        $user = User::updateOrCreate(
            ['id' => $apiUserId], 
            [
                'name' => $userName,
                'email' => $credentials['email'], 
                'password' => Hash::make($credentials['password']),
                'roles' => $finalRoles,
                'no_hp' => $userNoHp,          // Penting untuk WA
                'komsel_id' => $userKomselId,  // Penting untuk Filter WA
            ]
        );
        
        // [FITUR BARU] AUTO-SYNC ANGGOTA KOMSEL (JIKA LEADER)
        // Agar saat leader login, data anggota komselnya juga masuk ke DB lokal (siap di-WA)
        if (!empty($finalKomsels)) {
            $allJemaatSync = Cache::remember('api_jemaat_list_v2', 3600, function () {
                return $this->apiService->getAllJemaat();
            });

            if ($allJemaatSync) {
                $myMembers = collect($allJemaatSync)->whereIn('komsel_id', $finalKomsels);
                foreach ($myMembers as $member) {
                    try {
                        User::updateOrCreate(
                            ['id' => $member['id']],
                            [
                                'name' => $member['nama'] ?? 'Anggota',
                                'email' => $member['email'] ?? null,
                                'password' => Hash::make('default123'), // Password dummy
                                'no_hp' => $member['no_hp'] ?? null,
                                'komsel_id' => $member['komsel_id'],
                                // roles default kosong
                            ]
                        );
                    } catch (\Exception $e) {
                        // Lanjut jika error, jangan hentikan login
                        Log::warning("Sync member error: " . $e->getMessage());
                    }
                }
            }
        }

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