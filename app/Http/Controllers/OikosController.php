<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\User;        // Model "jejak" user lokal
use App\Models\OikosVisit;  // Model OikosVisit lokal

// Service & Helper
use App\Services\OldApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class OikosController extends Controller
{
    protected $apiService;

    // Definisikan cache key yang unik dan terstruktur
    const CACHE_KEY = [
        'jemaat_list'   => 'api_jemaat_list_v2',
        'jemaat_map'    => 'api_jemaat_map_by_id_v2',
        'pelayan_list'  => 'api_oikos_pelayan_list_v2', // Data Pelayan Oikos dari API
        'pelayan_map'   => 'api_oikos_pelayan_map_v2',  // Map Pelayan Oikos dari API
    ];
    // Tentukan durasi cache
    const CACHE_TTL = 3600; // 1 jam dalam detik

    /**
     * Inject OldApiService
     */
    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * [DIUBAH TOTAL]
     * Menampilkan form input Oikos.
     * Mengambil Jemaat DARI API dan Pelayan DARI API.
     */
    public function formInputOikos(Request $request) 
    {
        // 1. Ambil data JEMAAT dari API (Cache)
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            // Filter data null/kosong dan urutkan
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });
        
        // 2. Ambil data PELAYAN dari API (Cache)
        $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            // Panggil metode service baru yang memanggil endpoint API baru
            $data = $this->apiService->getAllOikosPelayan(); 
            return $data ? collect($data)->filter()->sortBy('nama')->values() : collect();
        });

        $data = [
            'aktifInput'  => 'active',
            'title'       => 'Jadwal OIKOS',
            'users'       => $jemaatList,   // <-- Kirim daftar Jemaat (Array dari API)
            'pelayans'    => $pelayanList,  // <-- Kirim daftar Pelayan (Array dari API)
        ];

        return view('OIKOS.formInput', $data);
    }

    /**
     * [VALIDASI DIUBAH]
     * Menyimpan data kunjungan Oikos baru.
     * Validasi 'pelayan' sekarang adalah 'integer' (API ID), bukan 'exists:users,id' (Lokal).
     */
    public function storeOikosVisit(Request $request)
    {
        $validated = $request->validate([
            'input_type' => 'required|string',
            'Anggota_tidakTerdaftar' => 'required_if:input_type,manual|nullable|string|max:255',
            'Nama_Anggota' => 'required_if:input_type,terdaftar|nullable|integer', // ID Jemaat dari API
            'pelayan' => 'required|integer', // [DIUBAH] ID Pelayan dari API
            'tanggalDari' => 'required|date',
            'tanggalSampai' => 'required|date|after_or_equal:tanggalDari',
        ]);

        $oikosName = '';
        $jemaatId = null;
        $pelayanId = (int)$validated['pelayan']; // Ambil ID Pelayan (dari API)

        if ($validated['input_type'] === 'manual') {
            $oikosName = $validated['Anggota_tidakTerdaftar'];
        } else {
            // Ambil nama Jemaat dari cache API
            $jemaatId = (int)$validated['Nama_Anggota'];
            $jemaatList = Cache::get(self::CACHE_KEY['jemaat_list'], collect());
            $jemaat = collect($jemaatList)->firstWhere('id', $jemaatId);
            $oikosName = $jemaat ? $jemaat['nama'] : 'Jemaat (ID: ' . $jemaatId . ')';
        }

        OikosVisit::create([
            'oikos_name' => $oikosName,
            'jemaat_id' => $jemaatId, // ID Jemaat dari API
            'pelayan_user_id' => $pelayanId, // ID Pelayan dari API
            'start_date' => $validated['tanggalDari'],
            'end_date' => $validated['tanggalSampai'],
            'status' => 'Direncanakan',
        ]);

        return redirect()->route('oikos')->with('success', 'Jadwal kunjungan OIKOS berhasil disimpan!');
    }

    /**
     * [HYDRATION DIUBAH]
     * Menampilkan daftar Oikos (Data Lokal + Data API)
     * Sekarang mengambil data Pelayan dari API, bukan relasi lokal.
     */
    public function daftarOikos() 
    {
        // 1. Ambil data Jemaat dari API (cache, di-indeks per ID)
        $jemaatList = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        // 2. Ambil data Pelayan dari API (cache, di-indeks per ID)
        $pelayanList = Cache::remember(self::CACHE_KEY['pelayan_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllOikosPelayan();
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        // 3. Ambil data OikosVisit LOKAL (TANPA ->with('pelayan') lokal)
        $oikosVisits = OikosVisit::orderBy('start_date', 'desc')->get();

        // 4. Gabungkan data Jemaat & Pelayan (dari API) ke data Oikos
        foreach ($oikosVisits as $visit) {
            // Gabungkan Jemaat
            if ($visit->jemaat_id && $jemaatList->has($visit->jemaat_id)) {
                $visit->jemaat_data = $jemaatList->get($visit->jemaat_id); 
            } else {
                $visit->jemaat_data = null;
            }
            
            // Gabungkan Pelayan (dari API)
            if ($visit->pelayan_user_id && $pelayanList->has($visit->pelayan_user_id)) {
                $visit->pelayan_data = $pelayanList->get($visit->pelayan_user_id); 
            } else {
                // Fallback: Coba load relasi lokal jika API-ID tidak ditemukan
                $visit->load('pelayan:id,name');
                $visit->pelayan_data = $visit->pelayan; 
            }
        }

        $data = [
            'aktifOikos'  => 'active',
            'title'       => 'Daftar Oikos',
            'oikosVisits' => $oikosVisits,
        ];

        return view('OIKOS.daftarOIKOS', $data);
    }

    /**
     * Menyimpan laporan Oikos (LOKAL)
     * (Tidak ada perubahan, ini murni operasi lokal)
     */
    public function storeReport(Request $request, OikosVisit $oikosVisit)
    {
        $validated = $request->validate([
            'realisasi_date' => 'required|date',
            'is_doa_5_jari' => 'nullable', 'realisasi_doa_5_jari_date' => 'nullable|date',
            'is_doa_syafaat' => 'nullable', 'realisasi_doa_syafaat_date' => 'nullable|date',
            'tindakan_cinta_desc' => 'nullable|string',
            'tindakan_cinta_photo_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tindakan_peduli_desc' => 'nullable|string',
            'respon_injil' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        
        $path = $oikosVisit->tindakan_cinta_photo_path;
        if ($request->hasFile('tindakan_cinta_photo_path')) {
            if ($path) Storage::disk('public')->delete($path);
            $path = $request->file('tindakan_cinta_photo_path')->store('oikos_reports', 'public');
        }

        $oikosVisit->update(array_merge($validated, [
            'tindakan_cinta_photo_path' => $path,
            'is_doa_5_jari' => $request->has('is_doa_5_jari'),
            'is_doa_syafaat' => $request->has('is_doa_syafaat'),
            'status' => 'Diproses'
        ]));

        return redirect()->route('oikos')->with('success', 'Laporan OIKOS berhasil dikirim.');
    }

    /**
     * Konfirmasi laporan (LOKAL)
     * (Tidak ada perubahan, ini murni operasi lokal berdasarkan 'jejak' Auth)
     */
    public function confirmVisit(OikosVisit $oikosVisit)
    {
        $user = Auth::user(); // Mengambil user 'jejak' lokal

        if (!$user || !is_array($user->roles) || !in_array('super_admin', $user->roles)) {
            return redirect()->route('oikos')->with('error', 'Anda tidak memiliki wewenang.');
        }

        if ($oikosVisit->status !== 'Diproses') {
            return redirect()->route('oikos')->with('warning', 'Laporan ini tidak dalam status "Diproses".');
        }

        $oikosVisit->update(['status' => 'Selesai']);

        return redirect()->route('oikos')->with('success', 'Laporan Oikos berhasil dikonfirmasi.');
    }


    /**
     * [HYDRATION DIUBAH]
     * Mengambil detail laporan (Lokal + API)
     * Menggunakan data Pelayan dari API.
     */
    public function getReportDetails(OikosVisit $oikosVisit)
    {
        // 1. Ambil data Jemaat dari API (cache)
        $jemaatList = Cache::get(self::CACHE_KEY['jemaat_map'], collect());
        if ($oikosVisit->jemaat_id && $jemaatList->has($oikosVisit->jemaat_id)) {
            $oikosVisit->jemaat_data = $jemaatList->get($oikosVisit->jemaat_id);
        }

        // 2. Ambil data Pelayan dari API (cache)
        $pelayanList = Cache::get(self::CACHE_KEY['pelayan_map'], collect());
        if ($oikosVisit->pelayan_user_id && $pelayanList->has($oikosVisit->pelayan_user_id)) {
            $oikosVisit->pelayan_data = $pelayanList->get($oikosVisit->pelayan_user_id);
        } else {
            // Fallback: Coba relasi lokal
            $oikosVisit->load('pelayan:id,name');
            $oikosVisit->pelayan_data = $oikosVisit->pelayan;
        }

        return response()->json($oikosVisit);
    }
}