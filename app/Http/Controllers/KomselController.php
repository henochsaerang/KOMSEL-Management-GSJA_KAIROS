<?php

namespace App\Http\Controllers;

// Model Lokal (Untuk Jadwal & Absensi)
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\User;
use App\Models\GuestAttendance;

// Service & Helper
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KomselController extends Controller
{
    protected $apiService;
    
    // Definisikan cache key yang unik dan terstruktur
    const CACHE_KEY = [
        // [BENAR] Menggunakan jemaat_list
        'jemaat_list' => 'api_jemaat_list_v2',
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
     * Menampilkan daftar gabungan Admin dan Jemaat dari API.
     * [PERUBAHAN] Sekarang HANYA menampilkan user dengan peran 'Anggota'.
     */
    public function daftar()
    {
        // 1. Ambil data Admin/Leaders (Sudah bersih dari API)
        $admins = Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter() : collect();
        });

        // 2. Ambil data Jemaat (Sudah bersih dari API)
        // [BENAR] Memanggil getAllJemaat()
        $jemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? collect($data)->filter() : collect();
        });

        // 3. Ambil data Komsel (Sudah bersih dari API)
        $komsels = Cache::remember(self::CACHE_KEY['komsel_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });

        // 4. Gabungkan
        $allUsers = $admins->concat($jemaat);

        // 5. [FIX] Filter HANYA untuk 'Anggota' (roles kosong)
        //    Ini akan menyembunyikan $admins dan menampilkan $jemaat
        //    (karena $jemaat dari SyncController punya 'roles' => [])
        $anggotaOnly = $allUsers->filter(function ($user) {
            return empty($user['roles']);
        });

        // 6. Urutkan data yang sudah difilter
        $sortedUsers = $anggotaOnly->sortBy('nama');

        return view('KOMSEL.daftarkomsel', [
            'users' => $sortedUsers, // Data murni dari API (hanya Anggota)
            'komsels' => $komsels,   // Data murni dari API
        ]);
    }

    /**
     * Menampilkan halaman manajemen jadwal komsel.
     */
    public function jadwal()
    {
        $komselsApi = Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        $schedules = Schedule::orderBy('created_at', 'desc')->get();

        foreach ($schedules as $schedule) {
            if ($komselsApi->has($schedule->komsel_id)) {
                $schedule->komsel_name = $komselsApi->get($schedule->komsel_id)['nama'] ?? 'Komsel API';
            } else {
                $schedule->komsel_name = 'Komsel Tdk Ditemukan';
            }
        }
        
        $komselsForDropdown = $komselsApi->sortBy('nama');

        return view('KOMSEL.jadwalKomsel', [
            'schedules' => $schedules,
            'komsels' => $komselsForDropdown 
        ]);
    }

    /**
     * [PLACEHOLDER] Menetapkan komsel.
     * Ini harus dihubungkan ke endpoint API baru di Aplikasi Lama.
     */
    public function assignKomsel(Request $request, $userId)
    {
        // TODO: Logika ini harus diubah.
        // Kita perlu endpoint API baru di aplikasi lama, misal:
        // $response = $this->apiService->assignKomselToUser($userId, $request->komsel_id);
        // if ($response) {
        //     Cache::forget(self::CACHE_KEY['jemaat_list']); // Hapus cache
        //     Cache::forget(self::CACHE_KEY['leader_list']); // Hapus cache
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
            'status' => 'required|in:Menunggu,Berlangsung,Selesai,Gagal',
        ]);
        $schedule->update($validated);
        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil diperbarui!');
    }

    /**
     * Menghapus jadwal dari database lokal. (Logika LOKAL - Sudah Benar)
     */
    public function destroyJadwal(Schedule $schedule)
    {
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
        $admins = Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter() : collect();
        });
        
        // [BENAR] Memanggil getAllJemaat()
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
    public function getAttendance(Schedule $schedule)
    {
        // [BENAR] Memanggil getAllJemaat()
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