<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\User;
use App\Models\GuestAttendance;

// Service
use App\Services\OldApiService;
use App\Services\FonnteService;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;

class KomselController extends Controller
{
    protected $apiService;
    
    // Cache Settings
    const CACHE_KEY = [
        'jemaat_list' => 'api_jemaat_list_v2',
        'jemaat_map'  => 'api_jemaat_map_by_id_v2',
        'leader_list' => 'api_leader_list_v2',
        'leader_map'  => 'api_leader_map_by_id_v2',
        'komsel_list' => 'api_komsel_list_std_v2',
        'komsel_map'  => 'api_komsel_map_std_by_id_v2',
    ];
    const CACHE_TTL = 3600; 

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan daftar anggota.
     * [FIXED] Normalisasi data & Hydration ke Object User.
     */
    public function daftar(Request $request) 
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []); 

        // 1. Ambil Data dari API (Cache)
        $jemaatArray = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat() ?: [];
        });

        // 2. Ambil & Normalisasi Data Komsel (Pastikan key 'nama' ada)
        $komselCollection = collect(Cache::remember(self::CACHE_KEY['komsel_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllKomsels() ?: [];
        }))->map(function($item) {
            $item = (array) $item;
            // Fallback: jika 'nama' kosong, cari 'nama_time'
            if (!isset($item['nama']) || empty($item['nama'])) {
                $item['nama'] = $item['nama_time'] ?? 'Komsel Tanpa Nama';
            }
            return $item;
        });

        // Buat Map [id => nama] untuk lookup cepat
        $komselMap = $komselCollection->pluck('nama', 'id')->toArray();
        $komselsToShow = $komselCollection->sortBy('nama')->values();

        // 3. Normalisasi User (Jemaat) -> Ubah Array jadi Object User
        $processUser = function($item) use ($komselMap) {
            $item = (array) $item; 
            
            // Fix Nama: Prioritas 'nama' dari API, jika tidak ada pakai 'name'
            if (isset($item['nama']) && !isset($item['name'])) {
                $item['name'] = $item['nama'];
            }

            // Hydrate ke Model User
            $userObj = new User($item);
            
            // Set atribut manual
            $userObj->id = $item['id'] ?? null;
            $userObj->name = $item['nama'] ?? $item['name'] ?? 'Tanpa Nama';
            $userObj->email = $item['email'] ?? null;
            $userObj->no_hp = $item['no_hp'] ?? '-';
            $userObj->komsel_id = $item['komsel_id'] ?? null;
            $userObj->roles = $item['roles'] ?? []; 

            // INJECT NAMA KOMSEL
            $kId = $item['komsel_id'] ?? null;
            $userObj->komsel_name = isset($komselMap[$kId]) ? $komselMap[$kId] : '-';

            return $userObj;
        };

        // Proses data
        $jemaat = collect($jemaatArray)->map($processUser);

        // 4. Filter Hak Akses (Jika bukan Super Admin)
        if (!$isSuperAdmin && !empty($leaderKomselIds)) {
            // Filter Jemaat: Hanya tampilkan yang komsel_id-nya milik leader
            $jemaat = $jemaat->whereIn('komsel_id', $leaderKomselIds);
            
            // Filter Dropdown Komsel
            $komselsToShow = $komselsToShow->whereIn('id', $leaderKomselIds);
        }

        // 5. Sorting
        $finalUsers = $jemaat->sortBy('name')->values(); 

        return view('KOMSEL.daftarkomsel', [
            'users' => $finalUsers,   
            'komsels' => $komselsToShow, 
        ]);
    }

    /**
     * Halaman Manajemen Jadwal
     */
    public function jadwal(Request $request)
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []); 

        // [FIX] Normalisasi Komsel untuk Jadwal
        $komselsApi = collect(Cache::remember(self::CACHE_KEY['komsel_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllKomsels() ?: [];
        }))->map(function($item) {
            $item = (array) $item;
            if (!isset($item['nama']) || empty($item['nama'])) {
                $item['nama'] = $item['nama_time'] ?? 'Komsel Tanpa Nama';
            }
            return $item;
        })->keyBy('id');

        $schedulesQuery = Schedule::orderBy('created_at', 'desc');

        if (!$isSuperAdmin && !empty($leaderKomselIds)) {
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
        }
        
        $schedules = $schedulesQuery->get();

        // Map nama komsel ke object schedule
        foreach ($schedules as $schedule) {
            $komselData = $komselsApi->get($schedule->komsel_id);
            $schedule->komsel_name = $komselData['nama'] ?? 'Komsel Tidak Ditemukan';
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
     * Simpan Jadwal Baru
     */
    public function storeJadwal(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        $validated = $request->validate([
            'komsel_id' => 'required|integer', 
            'day_of_week' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Menunggu,Berlangsung,Selesai,Gagal',
        ]);

        // Cek Wilayah
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($validated['komsel_id'], $leaderKomselIds)) {
            return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang membuat jadwal untuk komsel ini.');
        }

        // Cek Hierarki (Hanya Leader/Admin)
        if (!$isSuperAdmin && !$user->isLeaderKomsel()) {
            return redirect()->route('jadwal')->with('error', 'Akses Ditolak: Hanya Leader Komsel yang boleh membuat jadwal.');
        }

        $schedule = Schedule::create($validated);
        
        // [UPDATE] Broadcast Real-time dari API (Tidak pakai DB Lokal)
        $this->broadcastJadwalToKomsel($schedule);

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil ditambahkan dan notifikasi WA dikirim!');
    }

    /**
     * Update Jadwal
     */
    public function updateJadwal(Request $request, Schedule $schedule)
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
             return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang mengubah jadwal ini.');
        }

        if (!$isSuperAdmin && !$user->isLeaderKomsel()) {
            return redirect()->route('jadwal')->with('error', 'Akses Ditolak: Hanya Leader yang boleh edit.');
        }

        $validated = $request->validate([
            'komsel_id' => 'required|integer', 
            'day_of_week' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Menunggu,Berlangsung,Selesai,Gagal',
        ]);

        $schedule->update($validated);
        return redirect()->route('jadwal')->with('success', 'Jadwal diperbarui!');
    }

    /**
     * Hapus Jadwal
     */
    public function destroyJadwal(Request $request, Schedule $schedule)
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
             return redirect()->route('jadwal')->with('error', 'Anda tidak berwenang menghapus jadwal ini.');
        }

        if (!$isSuperAdmin && !$user->isLeaderKomsel()) {
            return redirect()->route('jadwal')->with('error', 'Akses Ditolak: Hanya Leader yang boleh hapus.');
        }

        $schedule->delete();
        return redirect()->route('jadwal')->with('success', 'Jadwal dihapus!');
    }

    // =========================================================================
    // API METHODS (BISA DIAKSES OLEH PARTNER & OTR)
    // =========================================================================

    public function getUsersForKomsel(Request $request, $komselId) 
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($komselId, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

        $admins = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllLeaders() ?: [];
        }));
        
        $jemaat = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat() ?: [];
        }));

        $allUsers = $admins->concat($jemaat);
        
        $usersInKomsel = $allUsers->filter(function ($user) use ($komselId) {
            $uKomselId = is_array($user) ? ($user['komsel_id'] ?? null) : ($user->komsel_id ?? null);
            return $uKomselId == $komselId;
        })
        ->map(function($user) {
            $id = is_array($user) ? $user['id'] : $user->id;
            $nama = is_array($user) ? ($user['nama'] ?? $user['name']) : ($user->nama ?? $user->name);
            return ['id' => $id, 'nama' => $nama];
        })
        ->sortBy('nama')
        ->values(); 

        return response()->json(['users' => $usersInKomsel]);
    }

    public function getAttendance(Request $request, Schedule $schedule)
    {
        $isSuperAdmin = $request->session()->get('is_super_admin', false);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        if (!$isSuperAdmin && !empty($leaderKomselIds) && !in_array($schedule->komsel_id, $leaderKomselIds)) {
            return response()->json(['error' => 'Tidak diizinkan'], 403);
        }

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
                $uId = is_array($user) ? $user['id'] : $user->id;
                $uName = is_array($user) ? ($user['nama'] ?? $user['name']) : ($user->nama ?? $user->name);
                $present_users[] = ['id' => $uId, 'nama' => $uName];
            } else {
                $localUser = User::find($id);
                if($localUser) {
                    $present_users[] = ['id' => $localUser->id, 'nama' => $localUser->name];
                }
            }
        }
        
        return response()->json(['present_users' => $present_users, 'guests' => $guestNames]);
    }

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

    public function assignKomsel(Request $request, $userId)
    {
        return back()->with('error', 'Fungsi "Assign Komsel" belum terhubung ke API lama.');
    }

    // --- BROADCAST HELPER (REAL-TIME FROM API) ---
    // Logika: Ambil data dari API Cache -> Filter HP -> Kirim
    private function broadcastJadwalToKomsel($schedule)
    {
        // 1. Ambil Data Jemaat dari Cache API (Cepat)
        $allJemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat() ?: [];
        });

        // 2. Filter Jemaat berdasarkan Komsel ID
        $targetPhoneNumbers = collect($allJemaat)
            ->filter(function ($jemaat) use ($schedule) {
                // Pastikan akses array aman
                $jKomselId = is_array($jemaat) ? ($jemaat['komsel_id'] ?? null) : ($jemaat->komsel_id ?? null);
                $jNoHp = is_array($jemaat) ? ($jemaat['no_hp'] ?? null) : ($jemaat->no_hp ?? null);

                // Syarat: ID Komsel cocok DAN punya No HP valid
                return $jKomselId == $schedule->komsel_id && !empty($jNoHp);
            })
            ->pluck('no_hp') // Ambil hanya nomor HP
            ->unique()       // Hapus duplikat
            ->toArray();

        // 3. Kirim jika ada nomor tujuan
        if (!empty($targetPhoneNumbers)) {
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

            // Kirim array nomor HP langsung ke FonnteService
            // FonnteService akan menangani implode array menjadi string koma
            FonnteService::send($targetPhoneNumbers, $pesan);
        }
    }
}