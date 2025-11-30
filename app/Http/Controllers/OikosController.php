<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\User;        
use App\Models\OikosVisit;  
use App\Models\Notification; // [BARU] Jangan lupa import ini untuk notifikasi

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

    const CACHE_KEY = [
        'jemaat_list'   => 'api_jemaat_list_v2',
        'jemaat_map'    => 'api_jemaat_map_by_id_v2',
        'pelayan_list'  => 'api_oikos_pelayan_list_v2', 
        'pelayan_map'   => 'api_oikos_pelayan_map_v2',  
    ];
    const CACHE_TTL = 3600; 

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan form input Oikos.
     */
    public function formInputOikos(Request $request) 
    {
        // Cek visual waktu & izin (Minggu-Selasa)
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        
        // Pastikan User memiliki method canScheduleNow (ada di Model User Fase 1)
        // Gunakan null coalescing operator untuk keamanan jika method belum ada
        $canBypass = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;

        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });
        
        $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllOikosPelayan(); 
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });

        $data = [
            'aktifInput'  => 'active',
            'title'       => 'Jadwal OIKOS',
            'users'       => $jemaatList,   
            'pelayans'    => $pelayanList,
            'isAllowedDay' => $isScheduleDay || $canBypass, // Kirim status izin ke view
        ];

        return view('OIKOS.formInput', $data);
    }

    /**
     * Menyimpan data kunjungan Oikos baru (SCHEDULE).
     * RULE: Hanya Minggu - Selasa, Kecuali Unlocked.
     */
    public function storeOikosVisit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $today = now();
        
        // Validasi Waktu (Minggu-Selasa) kecuali Admin atau punya izin
        $allowedDays = [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY];
        $isScheduleDay = in_array($today->dayOfWeek, $allowedDays);
        $hasSpecialAccess = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;
        
        // Cek Admin (Sederhana)
        $isAdmin = $user && is_array($user->roles) && (in_array('super_admin', $user->roles) || in_array('coordinator', $user->roles));

        if (!$isScheduleDay && !$hasSpecialAccess && !$isAdmin) {
            return redirect()->back()->with('error', 'Pembuatan jadwal hanya dibuka hari Minggu - Selasa. Hubungi Admin untuk Jadwal Mendadak.');
        }

        $validated = $request->validate([
            'input_type' => 'required|string',
            'Anggota_tidakTerdaftar' => 'required_if:input_type,manual|nullable|string|max:255',
            'Nama_Anggota' => 'required_if:input_type,terdaftar|nullable|integer', 
            'pelayan' => 'required|integer', 
            'tanggalDari' => 'required|date',
            'tanggalSampai' => 'required|date|after_or_equal:tanggalDari',
        ]);

        $oikosName = '';
        $jemaatId = null;
        $pelayanId = (int)$validated['pelayan']; 

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

        // [FASE 4] Notifikasi Otomatis: Jika yang input BUKAN pelayan yang bertugas (artinya Admin menunjuk Pengurus)
        if ($user->id != $pelayanId) {
            Notification::create([
                'user_id' => $pelayanId, // Kirim ke pelayan yang ditunjuk
                'title'   => 'Tugas Pelayanan Baru',
                'message' => 'Admin telah menjadwalkan Anda untuk mengunjungi: ' . $oikosName,
                'type'    => 'assignment',
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->route('oikos')->with('success', 'Jadwal kunjungan OIKOS berhasil disimpan!');
    }

    /**
     * Menampilkan daftar Oikos (Data Lokal + Data API)
     * Filter berdasarkan Role (Admin vs Pengurus).
     */
    public function daftarOikos() 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Data Jemaat (API)
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        // 2. Data Pelayan Map (API - keyBy ID untuk tabel)
        $pelayanMap = Cache::remember(self::CACHE_KEY['pelayan_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllOikosPelayan();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        // 3. [FIX] Data Pelayan List (API - list murni untuk DROPDOWN modal)
        $pelayanListForDropdown = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllOikosPelayan(); 
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });

        // 4. Ambil data OikosVisit LOKAL
        // [Logika Filter Role - Fase 4]
        $isAdmin = false;
        if ($user && is_array($user->roles) && (in_array('super_admin', $user->roles) || in_array('coordinator', $user->roles))) {
            $isAdmin = true;
        }

        $query = OikosVisit::orderBy('start_date', 'desc');
        
        // Jika BUKAN Admin, hanya lihat tugas sendiri atau tugas yang pernah didelegasikan
        if (!$isAdmin) {
            $query->where(function($q) use ($user) {
                $q->where('pelayan_user_id', $user->id)
                  ->orWhere('original_pelayan_user_id', $user->id);
            });
        }

        $oikosVisits = $query->get();

        // 5. Hydrate (Gabungkan data API ke model Lokal)
        foreach ($oikosVisits as $visit) {
            if ($visit->jemaat_id && $jemaatList->has($visit->jemaat_id)) {
                $visit->jemaat_data = $jemaatList->get($visit->jemaat_id); 
            } else {
                $visit->jemaat_data = null;
            }
            
            if ($visit->pelayan_user_id && $pelayanMap->has($visit->pelayan_user_id)) {
                $visit->pelayan_data = $pelayanMap->get($visit->pelayan_user_id); 
            } else {
                $visit->load('pelayan:id,name');
                $visit->pelayan_data = $visit->pelayan; 
            }
        }

        // Cek hari laporan (Rabu-Sabtu)
        $reportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        $isReportDay = in_array(now()->dayOfWeek, $reportDays);

        $data = [
            'aktifOikos'  => 'active',
            'title'       => 'Daftar Oikos',
            'oikosVisits' => $oikosVisits,
            'isReportDay' => $isReportDay,
            'pelayans'    => $pelayanListForDropdown, // [FIX] Kirim ini ke view agar dropdown tidak kosong
        ];

        return view('OIKOS.daftarOIKOS', $data);
    }

    /**
     * Menyimpan laporan Oikos (REPORT).
     * RULE: Hanya Rabu - Sabtu.
     * RULE: Wajib 2 Foto & Deskripsi Detail.
     */
    public function storeReport(Request $request, OikosVisit $oikosVisit)
    {
        // Validasi Hari Laporan (Rabu-Sabtu)
        $allowedReportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        if (!in_array(now()->dayOfWeek, $allowedReportDays)) {
             return redirect()->back()->with('error', 'Pengisian laporan hanya dibuka hari Rabu - Sabtu.');
        }

        $validated = $request->validate([
            'realisasi_date' => 'required|date',
            'is_doa_5_jari' => 'nullable', 
            'realisasi_doa_5_jari_date' => 'nullable|date',
            'is_doa_syafaat' => 'nullable', 
            'realisasi_doa_syafaat_date' => 'nullable|date',
            
            // [Fase 2] Wajib Detail
            'tindakan_cinta_desc' => 'required|string|min:20', 
            'tindakan_cinta_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'tindakan_peduli_desc' => 'required|string|min:20',
            'tindakan_peduli_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            
            'respon_injil' => 'nullable|string',
            'catatan' => 'nullable|string',
        ], [
            'tindakan_cinta_desc.min' => 'Deskripsi Tindakan Cinta harus detail (min. 20 karakter).',
            'tindakan_peduli_desc.min' => 'Deskripsi Tindakan Peduli harus detail (min. 20 karakter).',
            'tindakan_cinta_photo_path.required' => 'Foto Tindakan Cinta wajib diupload.',
            'tindakan_peduli_photo_path.required' => 'Foto Tindakan Peduli wajib diupload.',
        ]);
        
        // Validasi Wajib Foto (Harus ada: baru diupload ATAU sudah ada di DB)
        if (!$request->hasFile('tindakan_cinta_photo_path') && !$oikosVisit->tindakan_cinta_photo_path) {
            return back()->withErrors(['tindakan_cinta_photo_path' => 'Foto Tindakan Cinta wajib diunggah!']);
        }
        if (!$request->hasFile('tindakan_peduli_photo_path') && !$oikosVisit->tindakan_peduli_photo_path) {
            return back()->withErrors(['tindakan_peduli_photo_path' => 'Foto Tindakan Peduli wajib diunggah!']);
        }

        // Upload Foto Cinta
        $pathCinta = $oikosVisit->tindakan_cinta_photo_path;
        if ($request->hasFile('tindakan_cinta_photo_path')) {
            if ($pathCinta) Storage::disk('public')->delete($pathCinta);
            $pathCinta = $request->file('tindakan_cinta_photo_path')->store('oikos_reports/cinta', 'public');
        }

        // Upload Foto Peduli
        $pathPeduli = $oikosVisit->tindakan_peduli_photo_path;
        if ($request->hasFile('tindakan_peduli_photo_path')) {
            if ($pathPeduli) Storage::disk('public')->delete($pathPeduli);
            $pathPeduli = $request->file('tindakan_peduli_photo_path')->store('oikos_reports/peduli', 'public');
        }

        $oikosVisit->update(array_merge($validated, [
            'tindakan_cinta_photo_path' => $pathCinta,
            'tindakan_peduli_photo_path' => $pathPeduli,
            'is_doa_5_jari' => $request->has('is_doa_5_jari'),
            'is_doa_syafaat' => $request->has('is_doa_syafaat'),
            'status' => 'Diproses'
        ]));

        return redirect()->route('oikos')->with('success', 'Laporan OIKOS berhasil dikirim dan menunggu konfirmasi.');
    }

    /**
     * Konfirmasi laporan (ADMIN).
     */
    public function confirmVisit(OikosVisit $oikosVisit)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cek Role Admin
        if (!$user || !is_array($user->roles) || !in_array('super_admin', $user->roles)) {
            // return redirect()->route('oikos')->with('error', 'Anda tidak memiliki wewenang.');
        }

        // [Fase 2] Validasi Status
        if ($oikosVisit->status !== 'Diproses') {
            return redirect()->route('oikos')->with('warning', 'Laporan ini tidak dalam status "Diproses".');
        }

        // Cek Kelengkapan (Double Check)
        if (empty($oikosVisit->tindakan_cinta_desc) || empty($oikosVisit->tindakan_peduli_desc)) {
             return redirect()->route('oikos')->with('error', 'Laporan belum lengkap, tidak bisa dikonfirmasi.');
        }

        $oikosVisit->update(['status' => 'Selesai']);

        return redirect()->route('oikos')->with('success', 'Laporan Oikos berhasil dikonfirmasi.');
    }

    /**
     * Mengambil detail laporan (AJAX).
     */
    public function getReportDetails(OikosVisit $oikosVisit)
    {
        $jemaatList = Cache::get(self::CACHE_KEY['jemaat_map'], collect());
        if ($oikosVisit->jemaat_id && $jemaatList->has($oikosVisit->jemaat_id)) {
            $oikosVisit->jemaat_data = $jemaatList->get($oikosVisit->jemaat_id);
        }

        $pelayanList = Cache::get(self::CACHE_KEY['pelayan_map'], collect());
        if ($oikosVisit->pelayan_user_id && $pelayanList->has($oikosVisit->pelayan_user_id)) {
            $oikosVisit->pelayan_data = $pelayanList->get($oikosVisit->pelayan_user_id);
        } else {
            $oikosVisit->load('pelayan:id,name');
            $oikosVisit->pelayan_data = $oikosVisit->pelayan;
        }

        return response()->json($oikosVisit);
    }

    // [FASE 3] FITUR DELEGASI / GANTI PELAYAN
    public function delegateVisit(Request $request, OikosVisit $oikosVisit)
    {
        $request->validate([
            'new_pelayan_id' => 'required|integer',
            'replacement_reason' => 'required|string|min:5',
        ]);

        $oldPelayanId = $oikosVisit->pelayan_user_id;
        $newPelayanId = (int)$request->new_pelayan_id;
        
        if ($oldPelayanId == $newPelayanId) {
            return back()->with('error', 'Pelayan pengganti tidak boleh sama dengan pelayan saat ini.');
        }

        // Update Data Kunjungan
        $oikosVisit->update([
            'original_pelayan_user_id' => $oldPelayanId,
            'pelayan_user_id' => $newPelayanId,
            'replacement_reason' => $request->replacement_reason,
        ]);

        // Buat Notifikasi untuk Pelayan Baru
        Notification::create([
            'user_id' => $newPelayanId,
            'title'   => 'Tugas Kunjungan Baru (Delegasi)',
            'message' => 'Anda telah ditunjuk menggantikan tugas kunjungan ke: ' . $oikosVisit->oikos_name . '. Alasan: ' . $request->replacement_reason,
            'type'    => 'assignment',
            'action_url' => route('oikos'),
        ]);

        return back()->with('success', 'Pelayan berhasil diganti dan notifikasi telah dikirim.');
    }
}