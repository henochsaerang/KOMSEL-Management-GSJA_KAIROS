<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\User;
use App\Models\OikosVisit;
use App\Models\Notification;

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
        
        // 1. Logika Waktu & Izin
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $canBypass = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;

        // 2. Cek Role Admin
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        // 3. Ambil ID Komsel Leader (Untuk Filter)
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        if (empty($leaderKomselIds) && $user->komsel_id) {
            $leaderKomselIds = [$user->komsel_id];
        }

        // 4. Ambil Data Jemaat (Cache API) & FILTER
        $allJemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter() : collect();
        });
        
        // Pastikan Collection
        $allJemaat = collect($allJemaat); 

        // [LOGIKA FILTER]
        $jemaatList = $allJemaat->filter(function ($jemaat) use ($isAdmin, $leaderKomselIds) {
            if ($isAdmin) return true; // Admin lihat semua

            $jKomselId = is_array($jemaat) ? ($jemaat['komsel_id'] ?? null) : ($jemaat->komsel_id ?? null);
            
            // Cek apakah anggota ini satu komsel dengan leader
            if ($jKomselId && in_array($jKomselId, $leaderKomselIds)) {
                return true;
            }
            return false;
        })->sortBy('nama')->values();
        
        // 5. Jika Admin, ambil list semua pelayan
        $pelayanList = collect();
        if ($isAdmin) {
            $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
                $data = $this->apiService->getAllOikosPelayan(); 
                return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
            });
            $pelayanList = collect($pelayanList);
        }

        $data = [
            'aktifInput'   => 'active',
            'title'        => 'Jadwal OIKOS',
            'users'        => $jemaatList, 
            'pelayans'     => $pelayanList, 
            'isAdmin'      => $isAdmin,     
            'currentUser'  => $user,        
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
        
        // 1. Validasi Waktu
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $hasSpecialAccess = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        if (!$isScheduleDay && !$hasSpecialAccess && !$isAdmin) {
            return redirect()->back()->with('error', 'Pembuatan jadwal hanya dibuka hari Minggu - Selasa.');
        }

        $validated = $request->validate([
            'input_type' => 'required|string',
            'Anggota_tidakTerdaftar' => 'required_if:input_type,manual|nullable|string|max:255',
            'Nama_Anggota' => 'required_if:input_type,terdaftar|nullable|integer',
            'pelayan' => 'nullable|integer',
            'tanggalDari' => 'required|date',
            'tanggalSampai' => 'required|date|after_or_equal:tanggalDari',
        ]);

        // 2. Validasi Keamanan: Pastikan Leader hanya memilih anggota komselnya
        if (!$isAdmin && $validated['input_type'] === 'terdaftar') {
            $targetId = (int)$validated['Nama_Anggota'];
            $allJemaat = collect(Cache::get(self::CACHE_KEY['jemaat_list'], []));
            $targetJemaat = $allJemaat->firstWhere('id', $targetId);
            
            $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
            if (empty($leaderKomselIds) && $user->komsel_id) $leaderKomselIds = [$user->komsel_id];

            $jKomselId = is_array($targetJemaat) ? ($targetJemaat['komsel_id'] ?? null) : ($targetJemaat->komsel_id ?? null);

            if (!$jKomselId || !in_array($jKomselId, $leaderKomselIds)) {
                return redirect()->back()->with('error', 'Validasi Gagal: Anda hanya boleh menjadwalkan anggota Komsel Anda sendiri.');
            }
        }

        $oikosName = '';
        $jemaatId = null;
        $pelayanId = $request->input('pelayan') ? (int)$request->input('pelayan') : $user->id;

        if ($validated['input_type'] === 'manual') {
            $oikosName = $validated['Anggota_tidakTerdaftar'];
        } else {
            $jemaatId = (int)$validated['Nama_Anggota'];
            $jemaatList = collect(Cache::get(self::CACHE_KEY['jemaat_list'], []));
            $jemaat = $jemaatList->firstWhere('id', $jemaatId);
            $realName = is_array($jemaat) ? ($jemaat['nama'] ?? null) : ($jemaat->nama ?? null);
            $oikosName = $realName ?? 'Jemaat (ID: ' . $jemaatId . ')';
        }

        OikosVisit::create([
            'oikos_name' => $oikosName,
            'jemaat_id' => $jemaatId,
            'pelayan_user_id' => $pelayanId, 
            'start_date' => $validated['tanggalDari'],
            'end_date' => $validated['tanggalSampai'],
            'status' => 'Direncanakan',
        ]);

        if ($user->id != $pelayanId && class_exists(Notification::class)) {
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
        
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            return collect($this->apiService->getAllJemaat())->filter()->keyBy('id');
        });
        $pelayanListForDropdown = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            return collect($this->apiService->getAllOikosPelayan())->filter()->sortBy('nama')->values();
        });
        
        $jemaatList = collect($jemaatList);
        $pelayanListForDropdown = collect($pelayanListForDropdown);
        $pelayanMap = $pelayanListForDropdown->keyBy('id');

        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        $query = OikosVisit::orderBy('start_date', 'desc');
        
        if (!$isAdmin) {
            $query->where(function($q) use ($user) {
                $q->where('pelayan_user_id', $user->id)
                  ->orWhere('original_pelayan_user_id', $user->id);
            });
        }

        $oikosVisits = $query->get();

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

        $reportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        $isReportDay = in_array(now()->dayOfWeek, $reportDays);

        return view('OIKOS.daftarOIKOS', [
            'aktifOikos'  => 'active',
            'title'       => 'Daftar Oikos',
            'oikosVisits' => $oikosVisits,
            'isReportDay' => $isReportDay,
            'pelayans'    => $pelayanListForDropdown,
        ]);
    }

    /**
     * [UPDATE] Menyimpan laporan realisasi kunjungan (Dengan Cek Unlock & VALIDASI BEBAS KARAKTER).
     */
    public function storeReport(Request $request, OikosVisit $oikosVisit)
    {
        $allowedReportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        
        // Cek Hari ATAU Cek Izin Unlock
        $isUnlockActive = $oikosVisit->report_unlock_until && Carbon::now()->lte($oikosVisit->report_unlock_until);
        
        if (!in_array(now()->dayOfWeek, $allowedReportDays) && !$isUnlockActive) {
            return redirect()->back()->with('error', 'Pengisian laporan hanya dibuka hari Rabu - Sabtu, kecuali Anda meminta akses buka kunci.');
        }

        $validated = $request->validate([
            'realisasi_date' => 'required|date',
            'is_doa_5_jari' => 'nullable', 
            'realisasi_doa_5_jari_date' => 'nullable|date',
            'is_doa_syafaat' => 'nullable', 
            'realisasi_doa_syafaat_date' => 'nullable|date',
            // [FIX] Hapus 'min:20', ganti jadi 'required|string' saja (bebas karakter)
            'tindakan_cinta_desc' => 'required|string', 
            'tindakan_cinta_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            // [FIX] Hapus 'min:20', ganti jadi 'required|string' saja (bebas karakter)
            'tindakan_peduli_desc' => 'required|string',
            'tindakan_peduli_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'respon_injil' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        
        if (!$request->hasFile('tindakan_cinta_photo_path') && !$oikosVisit->tindakan_cinta_photo_path) {
            return back()->withErrors(['tindakan_cinta_photo_path' => 'Foto Tindakan Cinta wajib diunggah!']);
        }
        if (!$request->hasFile('tindakan_peduli_photo_path') && !$oikosVisit->tindakan_peduli_photo_path) {
            return back()->withErrors(['tindakan_peduli_photo_path' => 'Foto Tindakan Peduli wajib diunggah!']);
        }

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

        $oikosVisit->update(array_merge($validated, [
            'tindakan_cinta_photo_path' => $pathCinta,
            'tindakan_peduli_photo_path' => $pathPeduli,
            'is_doa_5_jari' => $request->has('is_doa_5_jari'),
            'is_doa_syafaat' => $request->has('is_doa_syafaat'),
            'status' => 'Diproses',
            'report_unlock_until' => null // Reset unlock setelah berhasil lapor
        ]));

        return redirect()->route('oikos')->with('success', 'Laporan OIKOS berhasil dikirim.');
    }

    /**
     * Konfirmasi laporan oleh Admin.
     */
    public function confirmVisit(OikosVisit $oikosVisit)
    {
        if ($oikosVisit->status !== 'Diproses') return redirect()->route('oikos')->with('warning', 'Laporan ini tidak dalam status "Diproses".');
        if (empty($oikosVisit->tindakan_cinta_desc) || empty($oikosVisit->tindakan_peduli_desc)) {
            return redirect()->route('oikos')->with('error', 'Laporan belum lengkap.');
        }
        $oikosVisit->update(['status' => 'Selesai']);
        return redirect()->route('oikos')->with('success', 'Laporan Oikos berhasil dikonfirmasi.');
    }

    /**
     * API Internal untuk mengambil detail laporan (Ajax).
     */
    public function getReportDetails(OikosVisit $oikosVisit)
    {
        $jemaatList = collect(Cache::get(self::CACHE_KEY['jemaat_map'], []));
        if ($oikosVisit->jemaat_id && $jemaatList->has($oikosVisit->jemaat_id)) {
            $oikosVisit->jemaat_data = $jemaatList->get($oikosVisit->jemaat_id);
        }
        
        $pelayanList = collect(Cache::get(self::CACHE_KEY['pelayan_map'], []));
        if ($oikosVisit->pelayan_user_id && $pelayanList->has($oikosVisit->pelayan_user_id)) {
            $oikosVisit->pelayan_data = $pelayanList->get($oikosVisit->pelayan_user_id);
        } else { 
            $oikosVisit->load('pelayan:id,name'); 
            $oikosVisit->pelayan_data = $oikosVisit->pelayan; 
        }

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

        if(class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $newPelayanId,
                'title'   => 'Tugas Kunjungan Baru (Delegasi)',
                'message' => 'Anda telah ditunjuk menggantikan tugas kunjungan ke: ' . $oikosVisit->oikos_name . '. Alasan: ' . $request->replacement_reason,
                'type'    => 'assignment',
                'action_url' => route('oikos'),
            ]);
        }

        return back()->with('success', 'Pelayan berhasil diganti.');
    }

    /**
     * Meminta revisi laporan (Khusus Admin/Coordinator).
     */
    public function requestRevision(Request $request, OikosVisit $oikosVisit)
    {
        $user = Auth::user();
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        if (!$isAdmin) return redirect()->back()->with('error', 'Anda tidak memiliki wewenang untuk meminta revisi.');

        $request->validate([ 'revision_comment' => 'required|string|min:5' ]);

        $oikosVisit->update([
            'status' => 'Revisi', 
            'revision_comment' => $request->revision_comment
        ]);

        if (class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $oikosVisit->pelayan_user_id,
                'title'   => 'Laporan Perlu Revisi',
                'message' => 'Laporan kunjungan ke ' . $oikosVisit->oikos_name . ' dikembalikan. Catatan: ' . $request->revision_comment,
                'type'    => 'revision', 
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->back()->with('success', 'Laporan berhasil dikembalikan untuk revisi.');
    }

    /**
     * [BARU] Leader meminta akses buka kunci laporan di luar jadwal.
     */
    public function requestReportUnlock(OikosVisit $oikosVisit)
    {
        $admins = User::all()->filter(function($u) {
            $roles = $u->roles ?? [];
            return in_array('super_admin', $roles) || in_array('coordinator', $roles);
        });

        foreach ($admins as $admin) {
            if (class_exists(Notification::class)) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'Permintaan Buka Kunci',
                    'message' => Auth::user()->name . ' meminta akses input laporan untuk: ' . $oikosVisit->oikos_name,
                    'type'    => 'request_unlock',
                    'action_url' => route('oikos.approve_unlock', $oikosVisit->id), 
                ]);
            }
        }

        return back()->with('success', 'Permintaan buka kunci terkirim ke Admin. Harap tunggu persetujuan.');
    }

    /**
     * [BARU] Admin menyetujui buka kunci (Akses 24 Jam).
     */
    public function approveReportUnlock(OikosVisit $oikosVisit)
    {
        $user = Auth::user();
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);
        
        if (!$isAdmin) abort(403, 'Unauthorized');

        $oikosVisit->update([
            'report_unlock_until' => now()->addDay()
        ]);

        if (class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $oikosVisit->pelayan_user_id,
                'title'   => 'Laporan Dibuka',
                'message' => 'Akses laporan untuk ' . $oikosVisit->oikos_name . ' telah dibuka selama 24 jam.',
                'type'    => 'info',
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->route('oikos')->with('success', 'Akses laporan berhasil dibuka selama 24 jam.');
    }

    /**
     * Hapus satu item (Single Delete)
     */
    public function destroy(OikosVisit $oikosVisit)
    {
        if ($oikosVisit->tindakan_cinta_photo_path) Storage::disk('public')->delete($oikosVisit->tindakan_cinta_photo_path);
        if ($oikosVisit->tindakan_peduli_photo_path) Storage::disk('public')->delete($oikosVisit->tindakan_peduli_photo_path);
        
        $oikosVisit->delete();
        return redirect()->back()->with('success', 'Data kunjungan berhasil dihapus.');
    }

    /**
     * Hapus Masal (Bulk Delete)
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $visits = OikosVisit::whereIn('id', $ids)->get();

        foreach($visits as $visit) {
             if ($visit->tindakan_cinta_photo_path) Storage::disk('public')->delete($visit->tindakan_cinta_photo_path);
             if ($visit->tindakan_peduli_photo_path) Storage::disk('public')->delete($visit->tindakan_peduli_photo_path);
             $visit->delete();
        }
        
        return redirect()->back()->with('success', count($ids) . ' data berhasil dihapus masal.');
    }
}