<?php

namespace App\Http\Controllers;

// Model Lokal (Untuk Jadwal & Absensi)
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\User;
use App\Models\GuestAttendance;

// Service & Helper
use App\Services\OldApiService;
use Illuminate\Http\Request; // [PENTING] Untuk membaca Session
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth; // [PENTING] Untuk mengambil user

class KomselController extends Controller
{
    protected $apiService;
    
    // Definisikan cache key yang unik dan terstruktur
    const CACHE_KEY = [
        'jemaat_list' => 'api_jemaat_list_v2', // Data dari getAllJemaat (yang kini memanggil getAllMembers)
        'jemaat_map'  => 'api_jemaat_map_by_id_v2',
        'leader_list' => 'api_leader_list_v2',
        'leader_map'  => 'api_leader_map_by_id_v2',
        'komsel_list' => 'api_komsel_list_std_v2',
        'komsel_map'  => 'api_komsel_map_std_by_id_v2',
    ];
    // Tentukan durasi cache
    const CACHE_TTL = 3600; // 1 jam dalam detik

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan daftar anggota, difilter berdasarkan SESSION.
     */
    public function daftar(Request $request) // Inject Request
    {
        // Ambil data scoping dari SESSION
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []); 

        // 1. Ambil data Admin/Leaders (selalu diperlukan untuk data gabungan)
        $admins = Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter() : collect();
        });

        // 2. Ambil data Jemaat
        $jemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? collect($data)->filter() : collect();
        });

        // 3. Ambil data Komsel
        $komsels = Cache::remember(self::CACHE_KEY['komsel_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });

        // [FIX] Logika Scoping (Pembatasan Data)
        $jemaatToShow = $jemaat;
        $komselsToShow = $komsels;

        if (!$isSuperAdmin) {
            // JIKA BUKAN SUPER ADMIN (cth: Leader)
            
            // 1. Filter Jemaat: Tampilkan hanya jemaat dari komsel yang dia pimpin
            $jemaatToShow = $jemaat->whereIn('komsel_id', $leaderKomselIds);
            
            // 2. Filter Dropdown Komsel: Tampilkan hanya komsel yang dia pimpin
            $komselsToShow = $komsels->whereIn('id', $leaderKomselIds);
        }

        // 4. Gabungkan (Leaders + Jemaat yang sudah difilter)
        $allUsers = $admins->concat($jemaatToShow);

        // 5. Filter HANYA untuk 'Anggota' (roles kosong)
        $anggotaOnly = $allUsers->filter(function ($user) {
            return empty($user['roles']);
        });

        // 6. Urutkan
        $sortedUsers = $anggotaOnly->sortBy('nama');

        return view('KOMSEL.daftarkomsel', [
            'users' => $sortedUsers,   // Kirim jemaat yang sudah difilter
            'komsels' => $komselsToShow, // Kirim komsel yang sudah difilter
        ]);
    }

    /**
     * Menampilkan halaman manajemen jadwal komsel.
     * [FIX] Difilter berdasarkan Session
     */
    public function jadwal(Request $request)
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []); 

        $komselsApi = Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        // Query dasar untuk jadwal LOKAL
        $schedulesQuery = Schedule::orderBy('created_at', 'desc');

        // [FIX] Terapkan scoping jika bukan Super Admin
        if (!$isSuperAdmin) {
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
        }
        
        $schedules = $schedulesQuery->get();

        // Gabungkan data
        foreach ($schedules as $schedule) {
            if ($komselsApi->has($schedule->komsel_id)) {
                $schedule->komsel_name = $komselsApi->get($schedule->komsel_id)['nama'] ?? 'Komsel API';
            } else {
                $schedule->komsel_name = 'Komsel Tdk Ditemukan';
            }
        }
        
        // [FIX] Filter dropdown komsel jika bukan Super Admin
        $komselsForDropdown = $komselsApi;
        if (!$isSuperAdmin) {
            $komselsForDropdown = $komselsApi->whereIn('id', $leaderKomselIds);
        }

        return view('KOMSEL.jadwalKomsel', [
            'schedules' => $schedules,
            'komsels' => $komselsForDropdown->sortBy('nama') 
        ]);
    }

    /**
     * [PLACEHOLDER] Menetapkan komsel.
     * Ini harus dihubungkan ke endpoint API baru di Aplikasi Lama.
     */
    public function assignKomsel(Request $request, $userId)
    {
        // TODO: Implementasi panggilan API ke Aplikasi Lama
        // $response = $this->apiService->assignKomselToUser($userId, $request->komsel_id);
        // if ($response) {
        //     Cache::forget(self::CACHE_KEY['jemaat_list']); 
        //     Cache::forget(self::CACHE_KEY['leader_list']); 
        //     return back()->with('success', 'Komsel berhasil ditetapkan!');
        // }
        
        return back()->with('error', 'Fungsi "Assign Komsel" belum terhubung ke API lama.');
    }

    /**
     * Menyimpan jadwal baru (Logika LOKAL - Sudah Benar)
     */
    public function storeJadwal(Request $request)
    {
        $validated = $request->validate([
            'komsel_id' => 'required|integer', 
            'day_of_week' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Menunggu,Berlangsung,Selesai,Gagal',
        ]);
        
        // [SECURITY FIX] Pastikan Leader tidak membuat jadwal untuk komsel lain
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !in_array($validated['komsel_id'], $leaderKomselIds)) {
            return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang membuat jadwal untuk komsel ini.');
        }

        Schedule::create($validated);
        return redirect()->route('jadwal')->with('success', 'Jadwal baru berhasil ditambahkan!');
    }

    /**
     * Memperbarui data jadwal (Logika LOKAL - Sudah Benar)
     */
    public function updateJadwal(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'komsel_id' => 'required|integer', 
            'day_of_week' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Menunggu,Berlanglang,Selesai,Gagal',
        ]);

        // [SECURITY FIX] Pastikan Leader tidak mengedit jadwal komsel lain
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !in_array($schedule->komsel_id, $leaderKomselIds)) {
             return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang mengubah jadwal ini.');
        }

        $schedule->update($validated);
        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil diperbarui!');
    }

    /**
     * Menghapus jadwal dari database lokal. (Logika LOKAL - Sudah Benar)
     */
    public function destroyJadwal(Request $request, Schedule $schedule)
    {
        // [SECURITY FIX] Pastikan Leader tidak menghapus jadwal komsel lain
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !in_array($schedule->komsel_id, $leaderKomselIds)) {
             return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang menghapus jadwal ini.');
        }

        $schedule->delete();
        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dihapus!');
    }


    // --- METODE-METODE API UNTUK JAVASCRIPT ---

    /**
     * Mengambil user untuk Komsel dari API
     * (Logika API - Sudah Benar)
     */
    public function getUsersForKomsel(Request $request, $komselId) 
    {
        // [SECURITY FIX] Pastikan Leader hanya mengambil data dari komselnya
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !in_array($komselId, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        $admins = Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter() : collect();
        });
        
        $jemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter() : collect();
        });

        $allUsers = $admins->concat($jemaat);
        
        $usersInKomsel = $allUsers->filter(function ($user) use ($komselId) {
            return !empty($user) && isset($user['komsel_id']) && $user['komsel_id'] == $komselId;
        })
        ->map(function($user) {
            return [
                'id' => $user['id'],
                'nama' => $user['nama']
            ];
        })
        ->sortBy('nama')
        ->values(); 

        return response()->json(['users' => $usersInKomsel]);
    }

    /**
     * Mengambil data absensi
     * (Logika LOKAL + API - Sudah Benar)
     */
    public function getAttendance(Request $request, Schedule $schedule)
    {
        // [SECURITY FIX] Pastikan Leader hanya mengambil data dari komselnya
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !in_array($schedule->komsel_id, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });
        
        $adminList = Cache::remember(self::CACHE_KEY['leader_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });
        
        $allApiUsers = $adminList->union($jemaatList);

        $presentUserIds = $schedule->attendances()->pluck('user_id');
        $guestNames = $schedule->guestAttendances()->pluck('name');

        $present_users = [];
        foreach ($presentUserIds as $id) {
            if ($allApiUsers->has($id)) {
                $user = $allApiUsers->get($id);
                $present_users[] = [
                    'id' => $user['id'],
                    'nama' => $user['nama']
                ];
            }
        }
        
        return response()->json(['present_users' => $present_users, 'guests' => $guestNames]);
    }

    /**
     * Menyimpan absensi (Logika LOKAL - Sudah Benar)
     */
    public function storeAttendance(Request $request, Schedule $schedule)
    {
        // [SECURITY FIX] Pastikan Leader hanya menyimpan data ke komselnya
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !in_array($schedule->komsel_id, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        $validated = $request->validate([
            'present_users' => 'present|array',
            'present_users.*' => 'integer', 
            'guest_names' => 'present|array',
            'guest_names.*' => 'string|max:255',
        ]);

        DB::transaction(function () use ($schedule, $validated) {
            $schedule->attendances()->delete();
            $schedule->guestAttendances()->delete();
            
            foreach ($validated['present_users'] as $userId) {
                Attendance::create(['schedule_id' => $schedule->id, 'user_id' => $userId]);
            }
            foreach ($validated['guest_names'] as $guestName) {
                GuestAttendance::create(['schedule_id' => $schedule->id, 'name' => $guestName]);
            }
        });

        $message = sprintf('Absensi berhasil disimpan: %d anggota dan %d tamu.', count($validated['present_users']), count($validated['guest_names']));
        return response()->json(['message' => $message]);
    }
}