<?php
namespace App\Http\Controllers;

use App\Models\User; // <-- [PENTING] Import User
use Illuminate\Http\Request;
use App\Services\OldApiService;
use Illuminate\Support\Facades\Auth; // <-- [PENTING] Import Auth
use Illuminate\Support\Facades\Hash; // <-- [PENTING] Import Hash
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $apiService;

    /**
     * Inject OldApiService
     */
    public function __construct(OldApiService $apiService)
    {
        // [PERBAIKAN] Menggunakan '->' (panah) bukan '.' (titik)
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman login.
     */
    public function login() 
    {
        return view('Auth.login');
    }

    /**
     * [LOGIKA HYBRID]
     * Mengizinkan SEMUA user (Admin & Jemaat) yang valid di API.
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Coba login ke API Lama
        $apiResponse = $this->apiService->login($credentials['email'], $credentials['password']);

        // 2. Jika API menolak (login gagal)
        if ($apiResponse === null || !isset($apiResponse['user'])) {
            throw ValidationException::withMessages([
                'email' => 'Email atau Password dari API lama salah.',
            ]);
        }
        
        $apiUser = $apiResponse['user'];
        
        // 3. (Pengecekan 'roles' sudah dihapus, Jemaat BISA login)

        // 4. SUKSES! Buat atau Perbarui "jejak" user LOKAL
        $localUser = User::updateOrCreate(
            [
                'email' => $apiUser['email'] // Kunci unik
            ],
            [
                'name' => $apiUser['name'],
                'roles' => $apiUser['roles'] ?? [], // Jemaat akan menyimpan []
                'old_api_id' => $apiUser['id'], // Simpan ID dari API lama
                'status' => 'aktif', // Mengisi 'status'
                'password' => Hash::make($credentials['password']) // Opsional
            ]
        );

        // 5. [INTI] Login-kan user LOKAL ke Sesi Laravel
        Auth::login($localUser, $request->boolean('remember'));

        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    /**
     * Logout dari Sesi LOKAL
     */
    public function logout(Request $request) 
    {
        // Panggil service logout API (meskipun kosong)
        $this->apiService->logout(); 
        
        Auth::logout(); // Logout dari sesi LOKAL
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
    
    // Hapus method 'signup' dan 'register' dari controller ini
}