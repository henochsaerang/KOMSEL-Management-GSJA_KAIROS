<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\User;
use App\Models\OikosVisit;
use App\Models\Notification; // [BARU] Import notifikasi

// Service & Helper
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OikosController extends Controller
{
    protected $apiService;

    // Cache key untuk data dari API Lama
    const CACHE_KEY = [
        'jemaat_list'   => 'api_jemaat_list_v2',
        'jemaat_map'    => 'api_jemaat_map_by_id_v2',
        'pelayan_list'  => 'api_oikos_pelayan_list_v2',
        'pelayan_map'   => 'api_oikos_pelayan_map_v2',
    ];
    const CACHE_TTL = 3600; // Cache 1 Jam

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman formulir input jadwal OIKOS.
     */
    public function formInputOikos(Request $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // 1. Logika Waktu & Izin (Hanya Minggu-Selasa atau Akses Khusus)
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $canBypass = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;

        // 2. Ambil Data Jemaat (Cache API)
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });
        
        // 3. Cek Role (Admin vs Leader)
        $isAdmin = is_array($user->roles) && (in_array('super_admin', $user->roles) || in_array('coordinator', $user->roles));
        
        // Jika Admin, ambil list semua pelayan untuk dropdown
        $pelayanList = collect();
        if ($isAdmin) {
            $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
                $data = $this->apiService->getAllOikosPelayan(); 
                return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
            });
        }

        $data = [
            'aktifInput'   => 'active',
            'title'        => 'Jadwal OIKOS',
            'users'        => $jemaatList,
            'pelayans'     => $pelayanList, // Hanya terisi jika Admin
            'isAdmin'      => $isAdmin,     // Flag untuk UI
            'currentUser'  => $user,        // Data user login
            'isAllowedDay' => $isScheduleDay || $canBypass,
        ];

        return view('OIKOS.formInput', $data);
    }

    /**
     * Menyimpan jadwal kunjungan baru ke database.
     */
    public function storeOikosVisit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $today = now();
        
        // 1. Validasi Waktu (Double Check di Server)
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $hasSpecialAccess = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;
        $isAdmin = $user && is_array($user->roles) && (in_array('super_admin', $user->roles) || in_array('coordinator', $user->roles));

        if (!$isScheduleDay && !$hasSpecialAccess && !$isAdmin) {
            return redirect()->back()->with('error', 'Pembuatan jadwal hanya dibuka hari Minggu - Selasa.');
        }

        $validated = $request->validate([
            'input_type' => 'required|string',
            'Anggota_tidakTerdaftar' => 'required_if:input_type,manual|nullable|string|max:255',
            'Nama_Anggota' => 'required_if:input_type,terdaftar|nullable|integer',
            'pelayan' => 'nullable|integer', // Bisa null jika leader (auto-assign)
            'tanggalDari' => 'required|date',
            'tanggalSampai' => 'required|date|after_or_equal:tanggalDari',
        ]);

        $oikosName = '';
        $jemaatId = null;
        
        // 2. Tentukan Pelayan [FIXED LOGIC]
        // Cek apakah 'pelayan' ada di request (untuk Admin)
        // Jika tidak ada atau null, gunakan ID user yang sedang login (Leader)
        $pelayanId = $request->input('pelayan') ? (int)$request->input('pelayan') : $user->id;

        // 3. Tentukan Target Oikos (Manual / Terdaftar)
        if ($validated['input_type'] === 'manual') {
            $oikosName = $validated['Anggota_tidakTerdaftar'];
        } else {
            $jemaatId = (int)$validated['Nama_Anggota'];
            $jemaatList = Cache::get(self::CACHE_KEY['jemaat_list'], collect());
            $jemaat = collect($jemaatList)->firstWhere('id', $jemaatId);
            $oikosName = $jemaat ? $jemaat['nama'] : 'Jemaat (ID: ' . $jemaatId . ')';
        }

        OikosVisit::create([
            'oikos_name' => $oikosName,
            'jemaat_id' => $jemaatId,
            'pelayan_user_id' => $pelayanId, 
            'start_date' => $validated['tanggalDari'],
            'end_date' => $validated['tanggalSampai'],
            'status' => 'Direncanakan',
        ]);

        // 4. Kirim Notifikasi (Jika Admin menugaskan orang lain)
        if ($user->id != $pelayanId) {
            Notification::create([
                'user_id' => $pelayanId,
                'title'   => 'Tugas Pelayanan Baru',
                'message' => 'Admin telah menjadwalkan Anda untuk mengunjungi: ' . $oikosName,
                'type'    => 'assignment',
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->route('oikos')->with('success', 'Jadwal kunjungan OIKOS berhasil disimpan!');
    }

    /**
     * Menampilkan daftar kunjungan OIKOS.
     */
    public function daftarOikos() 
    {
        $user = Auth::user();
        
        // Ambil Data Cache untuk Hydration
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            return collect($this->apiService->getAllJemaat())->filter()->keyBy('id');
        });
        $pelayanListForDropdown = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            return collect($this->apiService->getAllOikosPelayan())->filter()->sortBy('nama')->values();
        });
        $pelayanMap = collect($pelayanListForDropdown)->keyBy('id');

        // Cek Role Admin
        $isAdmin = false;
        if ($user && is_array($user->roles) && (in_array('super_admin', $user->roles) || in_array('coordinator', $user->roles))) {
            $isAdmin = true;
        }

        // Query Data
        $query = OikosVisit::orderBy('start_date', 'desc');
        
        // Filter: Leader hanya melihat tugas sendiri / tugas yang didelegasikan
        if (!$isAdmin) {
            $query->where(function($q) use ($user) {
                $q->where('pelayan_user_id', $user->id)
                  ->orWhere('original_pelayan_user_id', $user->id);
            });
        }

        $oikosVisits = $query->get();

        // Gabungkan data lokal dengan data API (Nama Jemaat & Pelayan)
        foreach ($oikosVisits as $visit) {
            $visit->jemaat_data = ($visit->jemaat_id && $jemaatList->has($visit->jemaat_id)) ? $jemaatList->get($visit->jemaat_id) : null;
            
            if ($visit->pelayan_user_id && $pelayanMap->has($visit->pelayan_user_id)) {
                $visit->pelayan_data = $pelayanMap->get($visit->pelayan_user_id); 
            } else {
                $visit->load('pelayan:id,name');
                $visit->pelayan_data = $visit->pelayan; 
            }
        }

        // Logika Hari Laporan (Rabu-Sabtu)
        $reportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        $isReportDay = in_array(now()->dayOfWeek, $reportDays);

        return view('OIKOS.daftarOIKOS', [
            'aktifOikos'  => 'active',
            'title'       => 'Daftar Oikos',
            'oikosVisits' => $oikosVisits,
            'isReportDay' => $isReportDay,
            'pelayans'    => $pelayanListForDropdown, // Untuk dropdown delegasi
        ]);
    }

    /**
     * Menyimpan laporan realisasi kunjungan.
     */
    public function storeReport(Request $request, OikosVisit $oikosVisit)
    {
        // Validasi Hari
        $allowedReportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        if (!in_array(now()->dayOfWeek, $allowedReportDays)) return redirect()->back()->with('error', 'Pengisian laporan hanya dibuka hari Rabu - Sabtu.');

        // Validasi Input
        $validated = $request->validate([
            'realisasi_date' => 'required|date',
            'is_doa_5_jari' => 'nullable', 
            'realisasi_doa_5_jari_date' => 'nullable|date',
            'is_doa_syafaat' => 'nullable', 
            'realisasi_doa_syafaat_date' => 'nullable|date',
            'tindakan_cinta_desc' => 'required|string|min:20', 
            'tindakan_cinta_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'tindakan_peduli_desc' => 'required|string|min:20',
            'tindakan_peduli_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'respon_injil' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        
        // Validasi Foto (Wajib ada)
        if (!$request->hasFile('tindakan_cinta_photo_path') && !$oikosVisit->tindakan_cinta_photo_path) return back()->withErrors(['tindakan_cinta_photo_path' => 'Foto Tindakan Cinta wajib diunggah!']);
        if (!$request->hasFile('tindakan_peduli_photo_path') && !$oikosVisit->tindakan_peduli_photo_path) return back()->withErrors(['tindakan_peduli_photo_path' => 'Foto Tindakan Peduli wajib diunggah!']);

        // Upload Foto
        $pathCinta = $oikosVisit->tindakan_cinta_photo_path;
        if ($request->hasFile('tindakan_cinta_photo_path')) {
            if ($pathCinta) Storage::disk('public')->delete($pathCinta);
            $pathCinta = $request->file('tindakan_cinta_photo_path')->store('oikos_reports/cinta', 'public');
        }

        $pathPeduli = $oikosVisit->tindakan_peduli_photo_path;
        if ($request->hasFile('tindakan_peduli_photo_path')) {
            if ($pathPeduli) Storage::disk('public')->delete($pathPeduli);
            $pathPeduli = $request->file('tindakan_peduli_photo_path')->store('oikos_reports/peduli', 'public');
        }

        // Update Data
        $oikosVisit->update(array_merge($validated, [
            'tindakan_cinta_photo_path' => $pathCinta,
            'tindakan_peduli_photo_path' => $pathPeduli,
            'is_doa_5_jari' => $request->has('is_doa_5_jari'),
            'is_doa_syafaat' => $request->has('is_doa_syafaat'),
            'status' => 'Diproses'
        ]));

        return redirect()->route('oikos')->with('success', 'Laporan OIKOS berhasil dikirim.');
    }

    /**
     * Konfirmasi laporan oleh Admin.
     */
    public function confirmVisit(OikosVisit $oikosVisit)
    {
        if ($oikosVisit->status !== 'Diproses') return redirect()->route('oikos')->with('warning', 'Laporan ini tidak dalam status "Diproses".');
        
        // Double check kelengkapan
        if (empty($oikosVisit->tindakan_cinta_desc) || empty($oikosVisit->tindakan_peduli_desc)) return redirect()->route('oikos')->with('error', 'Laporan belum lengkap.');
        
        $oikosVisit->update(['status' => 'Selesai']);
        return redirect()->route('oikos')->with('success', 'Laporan Oikos berhasil dikonfirmasi.');
    }

    /**
     * API Internal untuk mengambil detail laporan (Ajax).
     */
    public function getReportDetails(OikosVisit $oikosVisit)
    {
        $jemaatList = Cache::get(self::CACHE_KEY['jemaat_map'], collect());
        if ($oikosVisit->jemaat_id && $jemaatList->has($oikosVisit->jemaat_id)) $oikosVisit->jemaat_data = $jemaatList->get($oikosVisit->jemaat_id);
        
        $pelayanList = Cache::get(self::CACHE_KEY['pelayan_map'], collect());
        if ($oikosVisit->pelayan_user_id && $pelayanList->has($oikosVisit->pelayan_user_id)) $oikosVisit->pelayan_data = $pelayanList->get($oikosVisit->pelayan_user_id);
        else { $oikosVisit->load('pelayan:id,name'); $oikosVisit->pelayan_data = $oikosVisit->pelayan; }

        return response()->json($oikosVisit);
    }

    /**
     * Fitur Delegasi / Ganti Pelayan.
     */
    public function delegateVisit(Request $request, OikosVisit $oikosVisit)
    {
        $request->validate(['new_pelayan_id' => 'required|integer', 'replacement_reason' => 'required|string|min:5']);
        
        $oldPelayanId = $oikosVisit->pelayan_user_id;
        $newPelayanId = (int)$request->new_pelayan_id;
        
        if ($oldPelayanId == $newPelayanId) return back()->with('error', 'Pelayan pengganti tidak boleh sama.');

        $oikosVisit->update([
            'original_pelayan_user_id' => $oldPelayanId,
            'pelayan_user_id' => $newPelayanId,
            'replacement_reason' => $request->replacement_reason,
        ]);

        // Kirim Notifikasi ke Pelayan Baru
        Notification::create([
            'user_id' => $newPelayanId,
            'title'   => 'Tugas Kunjungan Baru (Delegasi)',
            'message' => 'Anda telah ditunjuk menggantikan tugas kunjungan ke: ' . $oikosVisit->oikos_name . '. Alasan: ' . $request->replacement_reason,
            'type'    => 'assignment',
            'action_url' => route('oikos'),
        ]);

        return back()->with('success', 'Pelayan berhasil diganti.');
    }
}