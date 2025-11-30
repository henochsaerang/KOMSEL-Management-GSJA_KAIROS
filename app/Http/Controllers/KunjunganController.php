<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\User;
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class KunjunganController extends Controller
{
    protected $apiService;

    const CACHE_KEY_JEMAAT = 'api_jemaat_map_by_id_v2';
    const CACHE_KEY_LEADERS = 'api_leader_map_by_id_v2';
    const CACHE_TTL = 3600;

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    // --- HELPER PERMISSIONS ---
    private function getPermissions($user) {
        $roles = $user->roles ?? [];
        $isSuperAdmin = in_array('super_admin', $roles);
        $isCoordinator = ($user->is_coordinator == 1) || ($isSuperAdmin && count($roles) > 1);
        $canManagePelayan = $isSuperAdmin || $isCoordinator;
        
        return compact('isSuperAdmin', 'isCoordinator', 'canManagePelayan');
    }

    public function index(Request $request)
    {
        // ... (Kode index SAMA SEPERTI SEBELUMNYA - Tidak berubah) ...
        $user = Auth::user();
        $perms = $this->getPermissions($user);

        $query = Kunjungan::with('pic')->orderBy('tanggal', 'desc');

        if (!$perms['canManagePelayan']) {
            $query->where('pic_id', $user->id);
        }
        
        $kunjungans = $query->get();

        $jemaatMap = collect(Cache::remember(self::CACHE_KEY_JEMAAT, self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        }));

        $transformedKunjungans = $kunjungans->map(function ($item) use ($jemaatMap) {
            $memberData = $jemaatMap->get($item->member_id);
            $realName = is_array($memberData) ? ($memberData['nama'] ?? null) : ($memberData->nama ?? null);
            
            return [
                'id' => $item->id,
                'tanggal' => $item->tanggal,
                'nama_anggota' => $realName ?? $item->nama_anggota_snapshot ?? "Jemaat ID: {$item->member_id}",
                'jenis_kunjungan' => $item->jenis_kunjungan,
                'status' => $item->status,
                'catatan' => $item->catatan,
                'photo_path' => $item->photo_path,
                'pic' => $item->pic ? $item->pic->name : 'Unknown',
                'pic_id' => $item->pic_id
            ];
        });

        return view('kunjungan.index', [
            'title' => 'Data Kunjungan',
            'kunjungans' => $transformedKunjungans,
            'canApprove' => $perms['canManagePelayan']
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $perms = $this->getPermissions($user);
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);

        // 1. Ambil Data Anggota
        $jemaatMap = collect(Cache::remember(self::CACHE_KEY_JEMAAT, self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        }));

        // Filter Anggota
        $myMembers = $jemaatMap->filter(function ($jemaat) use ($leaderKomselIds, $perms) {
            // Jika Admin, tampilkan SEMUA anggota
            if ($perms['canManagePelayan']) return true; 

            // Jika Leader, hanya tampilkan anggota komselnya
            $jKomselId = is_array($jemaat) ? ($jemaat['komsel_id'] ?? null) : ($jemaat->komsel_id ?? null);
            $jKomsels = is_array($jemaat) ? ($jemaat['komsels'] ?? []) : ($jemaat->komsels ?? []);

            if (empty($jKomselId) && empty($jKomsels)) return false;
            if ($jKomselId && in_array($jKomselId, $leaderKomselIds)) return true;
            if (!empty($jKomsels) && !empty(array_intersect($jKomsels, $leaderKomselIds))) return true;
            
            return false;
        })->sortBy('nama');

        // 2. Ambil Data Pelayan (Khusus Admin)
        $pelayans = collect();
        if ($perms['canManagePelayan']) {
            // Ambil list leader lengkap dengan data 'komsels' untuk logika sinkronisasi
            $pelayans = collect(Cache::remember(self::CACHE_KEY_LEADERS, self::CACHE_TTL, function () {
                // Pastikan service mengembalikan data 'komsels' (array ID komsel yang dipimpin)
                $data = $this->apiService->getAllLeaders(); 
                return $data ? collect($data)->sortBy('nama')->values() : collect();
            }));
        }

        return view('kunjungan.create', [
            'title' => 'Catat Kunjungan Baru',
            'members' => $myMembers,
            'pelayans' => $pelayans,
            'canManagePelayan' => $perms['canManagePelayan'],
            'currentUser' => $user
        ]);
    }

    // ... (Method store, updateReport, confirm, destroy SAMA SEPERTI SEBELUMNYA) ...
    public function store(Request $request)
    {
        $user = Auth::user();
        $perms = $this->getPermissions($user);

        $request->validate([
            'member_id' => 'required',
            'tanggal' => 'required|date',
            'jenis_kunjungan' => 'required|string',
            'catatan' => 'nullable|string',
            'pic_id' => $perms['canManagePelayan'] ? 'required' : 'nullable'
        ]);

        $picId = $perms['canManagePelayan'] ? $request->pic_id : $user->id;

        // Validasi Security untuk Non-Admin
        if (!$perms['canManagePelayan']) {
            $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
            $jemaatList = collect(Cache::get(self::CACHE_KEY_JEMAAT));
            $targetJemaat = $jemaatList->get($request->member_id);
            
            if (!$targetJemaat) return back()->with('error', 'Data jemaat tidak ditemukan.');

            $jKomselId = is_array($targetJemaat) ? ($targetJemaat['komsel_id'] ?? null) : ($targetJemaat->komsel_id ?? null);
            if (!in_array($jKomselId, $leaderKomselIds)) {
                return back()->with('error', 'Validasi Gagal: Anggota ini bukan bagian dari Komsel Anda.');
            }
        }

        Kunjungan::create([
            'pic_id' => $picId,
            'member_id' => $request->member_id,
            'nama_anggota_snapshot' => $request->nama_anggota_hidden ?? 'Jemaat ID '.$request->member_id,
            'tanggal' => $request->tanggal,
            'jenis_kunjungan' => $request->jenis_kunjungan,
            'catatan' => $request->catatan,
            'status' => 'Terjadwal'
        ]);

        return redirect()->route('kunjungan')->with('success', 'Jadwal kunjungan berhasil dibuat.');
    }

    public function updateReport(Request $request, Kunjungan $kunjungan)
    {
        $user = Auth::user();
        $perms = $this->getPermissions($user);

        if (!$perms['canManagePelayan'] && $kunjungan->pic_id !== $user->id) {
            return redirect()->route('kunjungan')->with('error', 'Anda tidak berwenang mengubah data ini.');
        }

        $request->validate([
            'catatan_hasil' => 'required|string',
            'status_akhir' => 'required|in:Selesai,Batal',
            'bukti_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:3072' 
        ]);

        $data = [
            'catatan' => $request->catatan_hasil,
        ];

        if ($request->status_akhir == 'Batal') {
            $data['status'] = 'Batal';
        } else {
            $data['status'] = $perms['canManagePelayan'] ? 'Selesai' : 'Diproses';
        }

        if ($request->hasFile('bukti_foto')) {
            if ($kunjungan->photo_path) {
                Storage::disk('public')->delete($kunjungan->photo_path);
            }
            $path = $request->file('bukti_foto')->store('kunjungan_photos', 'public');
            $data['photo_path'] = $path;
        }

        $kunjungan->update($data);

        $msg = ($data['status'] == 'Diproses') ? 'Laporan dikirim dan menunggu konfirmasi Admin.' : 'Laporan kunjungan berhasil disimpan.';
        return redirect()->route('kunjungan')->with('success', $msg);
    }

    public function confirm(Kunjungan $kunjungan)
    {
        $perms = $this->getPermissions(Auth::user());

        if (!$perms['canManagePelayan']) {
            return redirect()->route('kunjungan')->with('error', 'Anda tidak memiliki wewenang konfirmasi.');
        }

        if ($kunjungan->status !== 'Diproses') {
            return back()->with('warning', 'Hanya kunjungan status "Diproses" yang bisa dikonfirmasi.');
        }

        $kunjungan->update(['status' => 'Selesai']);

        return redirect()->route('kunjungan')->with('success', 'Kunjungan berhasil di-ACC (Selesai).');
    }

    public function destroy(Request $request, Kunjungan $kunjungan)
    {
        $perms = $this->getPermissions(Auth::user());
        
        if (!$perms['canManagePelayan'] && $kunjungan->pic_id !== Auth::id()) {
            return redirect()->route('kunjungan')->with('error', 'Anda tidak berwenang menghapus data ini.');
        }

        if ($kunjungan->photo_path) {
            Storage::disk('public')->delete($kunjungan->photo_path);
        }

        $kunjungan->delete();
        return redirect()->route('kunjungan')->with('success', 'Data kunjungan berhasil dihapus.');
    }
}