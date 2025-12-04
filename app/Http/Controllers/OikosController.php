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
     * Menampilkan halaman formulir input jadwal OIKOS.
     */
    public function formInputOikos(Request $request) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // 1. Logika Waktu
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $hasUnlockAccess = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;
        
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);
        
        $canBypass = $isAdmin || $hasUnlockAccess;

        // 2. Ambil ID Komsel Leader
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        if (empty($leaderKomselIds) && $user->komsel_id) {
            $leaderKomselIds = [$user->komsel_id];
        }

        // 3. Ambil Data Jemaat
        $allJemaat = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data) : collect();
        });
        
        $jemaatList = collect($allJemaat)->filter(function ($jemaat) use ($isAdmin, $leaderKomselIds) {
            if ($isAdmin) return true;
            $jKomselId = is_array($jemaat) ? ($jemaat['komsel_id'] ?? null) : ($jemaat->komsel_id ?? null);
            return $jKomselId && in_array($jKomselId, $leaderKomselIds);
        })->sortBy('nama')->values();
        
        // 4. Data Pelayan (Untuk Admin)
        $pelayanList = collect();
        if ($isAdmin) {
            $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
                $data = $this->apiService->getAllOikosPelayan(); 
                return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
            });
            $pelayanList = collect($pelayanList);
        }

        return view('OIKOS.formInput', [
            'aktifInput'   => 'active',
            'title'        => 'Jadwal OIKOS',
            'users'        => $jemaatList, 
            'pelayans'     => $pelayanList, 
            'isAdmin'      => $isAdmin,     
            'currentUser'  => $user,        
            'isAllowedDay' => $isScheduleDay || $canBypass, 
            'isNormalScheduleDay' => $isScheduleDay,        
            'userCanBypass' => $canBypass                   
        ]);
    }

    /**
     * Menyimpan jadwal kunjungan baru.
     * LOGIC BARU: Jika di luar jadwal, simpan sebagai 'Menunggu Persetujuan'.
     */
    public function storeOikosVisit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Cek Waktu & Izin
        $isScheduleDay = in_array(now()->dayOfWeek, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::TUESDAY]);
        $hasSpecialAccess = method_exists($user, 'canScheduleNow') ? $user->canScheduleNow() : false;
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        // Cek Action dari Form (request_access atau save normal)
        $isRequestingAccess = $request->input('action') === 'request_access';

        // Validasi Akses: Jika diblokir DAN tidak sedang meminta akses -> Tolak
        if (!$isScheduleDay && !$hasSpecialAccess && !$isAdmin && !$isRequestingAccess) {
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

        // Logic Nama Oikos
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

        // TENTUKAN STATUS AWAL
        $initialStatus = $isRequestingAccess ? 'Menunggu Persetujuan' : 'Direncanakan';

        $visit = OikosVisit::create([
            'oikos_name' => $oikosName,
            'jemaat_id' => $jemaatId,
            'pelayan_user_id' => $pelayanId, 
            'start_date' => $validated['tanggalDari'],
            'end_date' => $validated['tanggalSampai'],
            'status' => $initialStatus,
        ]);

        // JIKA REQUEST ACCESS -> Notifikasi ke Admin
        if ($isRequestingAccess) {
            $this->notifyAdmins('Permintaan Jadwal Khusus', 
                $user->name . ' mengajukan jadwal oikos di luar waktu normal untuk: ' . $oikosName, 
                'request_schedule', 
                route('oikos.approve_schedule', $visit->id)
            );
            return redirect()->route('oikos')->with('success', 'Permintaan jadwal berhasil dikirim ke Admin. Mohon tunggu persetujuan.');
        }

        // JIKA PENUGASAN OLEH ADMIN -> Notifikasi ke Pelayan
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
     * [BARU] Admin Menyetujui Permintaan Jadwal Khusus
     * Mengubah status jadi 'Direncanakan' dan membuka kunci laporan.
     */
    public function approveScheduleRequest(OikosVisit $oikosVisit)
    {
        $user = Auth::user();
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);
        
        if (!$isAdmin) abort(403, 'Unauthorized');

        // Approve & Unlock 24 Jam
        $oikosVisit->update([
            'status' => 'Direncanakan',
            'report_unlock_until' => now()->addDay() 
        ]);

        // Notifikasi ke Pembuat Jadwal
        if (class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $oikosVisit->pelayan_user_id,
                'title'   => 'Jadwal Disetujui',
                'message' => 'Jadwal OIKOS untuk ' . $oikosVisit->oikos_name . ' disetujui. Anda dapat mengisi laporan sekarang.',
                'type'    => 'info',
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Jadwal berhasil disetujui dan diaktifkan.');
    }

    /**
     * Menampilkan daftar kunjungan OIKOS.
     */
    public function daftarOikos() 
    {
        $user = Auth::user();
        
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, fn() => 
            collect($this->apiService->getAllJemaat())->filter()->keyBy('id')
        );
        $pelayanListForDropdown = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, fn() => 
            collect($this->apiService->getAllOikosPelayan())->filter()->sortBy('nama')->values()
        );
        $pelayanMap = collect($pelayanListForDropdown)->keyBy('id');

        $isAdmin = in_array('super_admin', $user->roles ?? []) || in_array('coordinator', $user->roles ?? []);

        $query = OikosVisit::orderBy('start_date', 'desc');
        if (!$isAdmin) {
            $query->where(fn($q) => $q->where('pelayan_user_id', $user->id)->orWhere('original_pelayan_user_id', $user->id));
        }
        $oikosVisits = $query->get();

        foreach ($oikosVisits as $visit) {
            $visit->jemaat_data = ($visit->jemaat_id && $jemaatList->has($visit->jemaat_id)) ? $jemaatList->get($visit->jemaat_id) : null;
            
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
     * Menyimpan laporan (Support Draft & Final Submit).
     */
    public function storeReport(Request $request, OikosVisit $oikosVisit)
    {
        $allowedReportDays = [Carbon::WEDNESDAY, Carbon::THURSDAY, Carbon::FRIDAY, Carbon::SATURDAY];
        
        $isUnlockActive = ($oikosVisit->report_unlock_until && Carbon::now()->lte($oikosVisit->report_unlock_until)) 
                          || $oikosVisit->status === 'Revisi' 
                          || $oikosVisit->status === 'Berlangsung'; // Draft juga boleh diedit kapan saja
        
        if (!in_array(now()->dayOfWeek, $allowedReportDays) && !$isUnlockActive) {
            return redirect()->back()->with('error', 'Pengisian laporan hanya dibuka hari Rabu - Sabtu.');
        }

        $action = $request->input('action', 'submit');

        // Validasi Dinamis (Draft vs Submit)
        $rules = [
            'realisasi_date' => $action === 'submit' ? 'required|date' : 'nullable|date',
            'tindakan_cinta_desc' => $action === 'submit' ? 'required|string' : 'nullable|string',
            'tindakan_peduli_desc' => $action === 'submit' ? 'required|string' : 'nullable|string',
            // ... field lain nullable ...
            'tindakan_cinta_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'tindakan_peduli_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'respon_injil' => 'nullable|string',
            'catatan' => 'nullable|string',
            'is_doa_5_jari' => 'nullable',
            'realisasi_doa_5_jari_date' => 'nullable|date',
            'is_doa_syafaat' => 'nullable',
            'realisasi_doa_syafaat_date' => 'nullable|date',
        ];
        $validated = $request->validate($rules);

        // Cek Wajib Foto saat Submit Final
        if ($action === 'submit') {
            if (!$request->hasFile('tindakan_cinta_photo_path') && !$oikosVisit->tindakan_cinta_photo_path) {
                return back()->withErrors(['tindakan_cinta_photo_path' => 'Foto Tindakan Cinta wajib diunggah untuk pengiriman final!'])->withInput();
            }
            if (!$request->hasFile('tindakan_peduli_photo_path') && !$oikosVisit->tindakan_peduli_photo_path) {
                return back()->withErrors(['tindakan_peduli_photo_path' => 'Foto Tindakan Peduli wajib diunggah untuk pengiriman final!'])->withInput();
            }
        }

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

        $updateData = array_merge($validated, [
            'tindakan_cinta_photo_path' => $pathCinta,
            'tindakan_peduli_photo_path' => $pathPeduli,
            'is_doa_5_jari' => $request->has('is_doa_5_jari'),
            'is_doa_syafaat' => $request->has('is_doa_syafaat'),
        ]);

        if ($action === 'draft') {
            $updateData['status'] = 'Berlangsung'; 
        } else {
            $updateData['status'] = 'Diproses';
            $updateData['report_unlock_until'] = null;
        }

        $oikosVisit->update($updateData);

        return redirect()->route('oikos')->with('success', $action === 'draft' ? 'Laporan disimpan sementara.' : 'Laporan dikirim final.');
    }

    /**
     * Konfirmasi laporan oleh Admin.
     */
    public function confirmVisit(OikosVisit $oikosVisit)
    {
        if ($oikosVisit->status !== 'Diproses') return redirect()->route('oikos')->with('warning', 'Laporan ini tidak dalam status "Diproses".');
        
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
                'message' => 'Anda ditunjuk menggantikan tugas ke: ' . $oikosVisit->oikos_name,
                'type'    => 'assignment',
                'action_url' => route('oikos'),
            ]);
        }

        return back()->with('success', 'Pelayan berhasil diganti.');
    }

    public function requestRevision(Request $request, OikosVisit $oikosVisit)
    {
        $user = Auth::user();
        $roles = $user->roles ?? [];
        $isAdmin = in_array('super_admin', $roles) || in_array('coordinator', $roles);

        if (!$isAdmin) return redirect()->back()->with('error', 'Unauthorized.');

        $request->validate([ 'revision_comment' => 'required|string|min:5' ]);

        $oikosVisit->update([
            'status' => 'Revisi', 
            'revision_comment' => $request->revision_comment
        ]);

        if (class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $oikosVisit->pelayan_user_id,
                'title'   => 'Laporan Perlu Revisi',
                'message' => 'Laporan kunjungan ' . $oikosVisit->oikos_name . ' dikembalikan. Catatan: ' . $request->revision_comment,
                'type'    => 'revision', 
                'action_url' => route('oikos'),
            ]);
        }

        return redirect()->back()->with('success', 'Laporan dikembalikan untuk revisi.');
    }

    // Request Unlock (Untuk laporan yang sudah ada tapi terlambat)
    public function requestReportUnlock(OikosVisit $oikosVisit)
    {
        $this->notifyAdmins('Permintaan Buka Kunci', 
            Auth::user()->name . ' meminta akses input laporan untuk: ' . $oikosVisit->oikos_name, 
            'request_unlock', 
            route('oikos.approve_unlock', $oikosVisit->id)
        );
        return back()->with('success', 'Permintaan terkirim ke Admin.');
    }

    // Approve Unlock (Untuk laporan terlambat)
    public function approveReportUnlock(OikosVisit $oikosVisit)
    {
        $user = Auth::user();
        $isAdmin = in_array('super_admin', $user->roles ?? []) || in_array('coordinator', $user->roles ?? []);
        if (!$isAdmin) abort(403);

        $oikosVisit->update(['report_unlock_until' => now()->addDay()]);

        if (class_exists(Notification::class)) {
            Notification::create([
                'user_id' => $oikosVisit->pelayan_user_id,
                'title'   => 'Laporan Dibuka',
                'message' => 'Akses laporan ' . $oikosVisit->oikos_name . ' dibuka 24 jam.',
                'type'    => 'info',
                'action_url' => route('oikos'),
            ]);
        }
        return redirect()->route('oikos')->with('success', 'Akses laporan dibuka.');
    }

    public function destroy(OikosVisit $oikosVisit)
    {
        if ($oikosVisit->tindakan_cinta_photo_path) Storage::disk('public')->delete($oikosVisit->tindakan_cinta_photo_path);
        if ($oikosVisit->tindakan_peduli_photo_path) Storage::disk('public')->delete($oikosVisit->tindakan_peduli_photo_path);
        $oikosVisit->delete();
        return redirect()->back()->with('success', 'Data dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) return redirect()->back()->with('error', 'Tidak ada data dipilih.');

        $visits = OikosVisit::whereIn('id', $ids)->get();
        foreach($visits as $visit) {
             if ($visit->tindakan_cinta_photo_path) Storage::disk('public')->delete($visit->tindakan_cinta_photo_path);
             if ($visit->tindakan_peduli_photo_path) Storage::disk('public')->delete($visit->tindakan_peduli_photo_path);
             $visit->delete();
        }
        return redirect()->back()->with('success', count($ids) . ' data dihapus.');
    }

    // Helper Private Notifikasi
    private function notifyAdmins($title, $message, $type, $url) {
        $admins = User::all()->filter(function($u) {
            $roles = $u->roles ?? [];
            return in_array('super_admin', $roles) || in_array('coordinator', $roles);
        });

        if (class_exists(Notification::class)) {
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title'   => $title,
                    'message' => $message,
                    'type'    => $type,
                    'action_url' => $url, 
                ]);
            }
        }
    }
}