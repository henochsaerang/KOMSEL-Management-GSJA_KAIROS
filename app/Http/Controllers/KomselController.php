<?php

namespace App\Http\Controllers;

// Model Lokal (Untuk Jadwal & Absensi)
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\User;
use App\Models\GuestAttendance;

// Service & Helper
use App\Services\OldApiService;
use App\Services\FonnteService; // [BARU] Import Service Fonnte
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;

class KomselController extends Controller
{
    protected $apiService;
    
    // Definisikan cache key yang unik dan terstruktur
    const CACHE_KEY = [
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
     * Menampilkan daftar anggota, difilter berdasarkan SESSION.
     */
    public function daftar(Request $request) 
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []); 

        // [FIX 1] Bungkus hasil Cache::remember dengan collect()
        $admins = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? $data : []; // Kembalikan array kosong jika null
        }));

        // [FIX 2] Bungkus hasil Cache::remember dengan collect()
        $jemaat = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? $data : [];
        }));

        // [FIX 3] Bungkus hasil Cache::remember dengan collect()
        $komsels = collect(Cache::remember(self::CACHE_KEY['komsel_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? $data : [];
        }))->sortBy('nama')->values(); // Sorting bisa dilakukan di luar closure

        $jemaatToShow = $jemaat;
        $komselsToShow = $komsels;

        // [FIX 4] Filter HANYA jika dia Leader (bukan admin, tapi punya komsel)
        if (!$isSuperAdmin && !empty($leaderKomselIds)) {
            $jemaatToShow = $jemaat->whereIn('komsel_id', $leaderKomselIds);
            $komselsToShow = $komsels->whereIn('id', $leaderKomselIds);
        }

        // [FIX 5] Filter $admins (yang merupakan collection)
        $allUsers = $admins->concat($jemaatToShow);

        $anggotaOnly = $allUsers->filter(function ($user) {
            return empty($user['roles']);
        });

        $sortedUsers = $anggotaOnly->sortBy('nama');

        return view('KOMSEL.daftarkomsel', [
            'users' => $sortedUsers,   
            'komsels' => $komselsToShow, 
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

        // [FIX] Bungkus hasil Cache::remember dengan collect()
        $komselsApi = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels();
            return $data ? collect($data)->filter()->keyBy('id')->all() : []; // Simpan sebagai array
        }));

        $schedulesQuery = Schedule::orderBy('created_at', 'desc');

        if (!$isSuperAdmin && !empty($leaderKomselIds)) {
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
        }
        
        $schedules = $schedulesQuery->get();

        foreach ($schedules as $schedule) {
            if ($komselsApi->has($schedule->komsel_id)) {
                $schedule->komsel_name = $komselsApi->get($schedule->komsel_id)['nama'] ?? 'Komsel API';
            } else {
                $schedule->komsel_name = 'Komsel Tdk Ditemukan';
            }
        }
        
        $komselsForDropdown = $komselsApi;
        if (!$isSuperAdmin && !empty($leaderKomselIds)) {
            $komselsForDropdown = $komselsApi->whereIn('id', $leaderKomselIds);
        }

        return view('KOMSEL.jadwalKomsel', [
            'schedules' => $schedules,
            'komsels' => $komselsForDropdown->sortBy('nama') 
        ]);
    }

    /**
     * [PLACEHOLDER] Menetapkan komsel.
     */
    public function assignKomsel(Request $request, $userId)
    {
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
        
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($validated['komsel_id'], $leaderKomselIds)) {
            return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang membuat jadwal untuk komsel ini.');
        }

        // [BARU] Simpan ke variabel $schedule agar bisa dipakai untuk broadcast
        $schedule = Schedule::create($validated);

        // [BARU] Kirim Notifikasi WA via Fonnte
        // Panggil fungsi ini SEBELUM return
        $this->broadcastJadwalToKomsel($schedule);

        return redirect()->route('jadwal')->with('success', 'Jadwal baru berhasil ditambahkan dan notifikasi WA dikirim!');
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

        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
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
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
             return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang menghapus jadwal ini.');
        }

        $schedule->delete();
        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dihapus!');
    }


    // --- METODE-METODE API UNTUK JAVASCRIPT ---

    /**
     * Mengambil user untuk Komsel dari API
     */
    public function getUsersForKomsel(Request $request, $komselId) 
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($komselId, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        // [FIX] Bungkus hasil Cache::remember dengan collect()
        $admins = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? $data : [];
        }));
        
        $jemaat = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? $data : [];
        }));

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
     */
    public function getAttendance(Request $request, Schedule $schedule)
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        // [FIX] Bungkus hasil Cache::remember dengan collect()
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->keyBy('id')->all() : [];
        }));
        
        $adminList = collect(Cache::remember(self::CACHE_KEY['leader_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders();
            return $data ? collect($data)->filter()->keyBy('id')->all() : [];
        }));
        
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
     * Menyimpan absensi
     */
    public function storeAttendance(Request $request, Schedule $schedule)
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
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

    /**
     * [BARU] Helper Private untuk Broadcast Notifikasi WA
     */
    private function broadcastJadwalToKomsel($schedule)
    {
        // Cari semua anggota di komsel ini yang punya No HP
        // Asumsi: Kita mencari di tabel User lokal. 
        // Jika User berasal dari API, pastikan data mereka sudah tersinkron ke tabel users dan punya kolom no_hp.
        $anggotaList = User::where('komsel_id', $schedule->komsel_id)
                           ->whereNotNull('no_hp')
                           ->where('no_hp', '!=', '') // Pastikan tidak string kosong
                           ->pluck('no_hp')
                           ->toArray();

        if (count($anggotaList) > 0) {
            // Format Pesan
            $hariIndo = $schedule->day_of_week; 
            
            $pesan  = "*PENGUMUMAN JADWAL IBADAH KOMSEL*\n\n";
            $pesan .= "Shalom, jadwal ibadah komsel baru telah dibuat:\n";
            $pesan .= "🗓 Hari: " . $hariIndo . "\n";
            $pesan .= "⏰ Waktu: " . $schedule->time . " WITA\n";
            $pesan .= "📍 Lokasi: " . $schedule->location . "\n";
            
            if(!empty($schedule->description)) {
                $pesan .= "📝 Ket: " . $schedule->description . "\n";
            }
            
            $pesan .= "\nMohon kehadirannya tepat waktu. Tuhan Yesus Memberkati! 🙏";

            // Kirim via Fonnte Service
            FonnteService::send($anggotaList, $pesan);
        }
    }
}