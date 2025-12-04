<?php

namespace App\Http\Controllers;

// Model Lokal
use App\Models\Schedule;
use App\Models\OikosVisit;
use App\Models\User; 
use App\Models\Attendance;
use App\Models\GuestAttendance;

// Service
use App\Services\OldApiService; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $apiService;

    // Konfigurasi Cache Key & TTL
    const CACHE_KEY = [
        'jemaat_list'   => 'api_jemaat_list_v2',
        'leader_list'   => 'api_leader_list_v2', 
        'komsel_map'    => 'api_komsel_map_std_by_id_v2',
        'pelayan_list'  => 'api_oikos_pelayan_list_v2',
    ];
    const CACHE_TTL = 3600; 

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Index Method: Router Utama Dashboard
     */
    public function index(Request $request) 
    {
        // 1. Maintenance Otomatis (Update status Oikos yang kadaluarsa)
        $this->runAutoFailCheck();

        $currentUserId = Auth::id();
        if (!$currentUserId) return redirect()->route('login');

        /** @var User $user */
        $user = User::find($currentUserId);
        if (!$user) {
            Auth::logout();
            return redirect()->route('login');
        }

        $roles = $user->roles ?? [];

        // --- IDENTIFIKASI ROLE ---
        
        // A. GEMBALA (Super Admin murni, bukan Leader Operasional)
        $isGembala = in_array('super_admin', $roles) && !$user->is_coordinator && !in_array('Leaders', $roles); 

        // B. KOORDINATOR (Super Admin + Leader ATAU Flag is_coordinator)
        $isCoordinator = ($user->is_coordinator == 1) || (in_array('super_admin', $roles) && in_array('Leaders', $roles));

        // C. PENGURUS KOMSEL (Leader, Partner, OTR)
        $isPengurusKomsel = $user->isLeaderKomsel() || $user->isPartner() || $user->isOTR();

        // D. JEMAAT BIASA
        $isJemaat = !$isGembala && !$isCoordinator && !$isPengurusKomsel;

        // --- ROUTING VIEW ---
        
        if ($isGembala) {
            return $this->dashboardGembala();
        }

        if ($isCoordinator) {
            return $this->dashboardKordinator();
        }

        if ($isJemaat) {
            return $this->dashboardJemaat($currentUserId);
        }

        // Default: Dashboard untuk Leader/Partner/OTR
        return $this->dashboardLeader($request, $currentUserId);
    }

    // =========================================================================
    // 1. DASHBOARD KOORDINATOR (MONITORING & EVALUASI)
    // =========================================================================
    private function dashboardKordinator()
    {
        // Ambil Data Global dari Cache API
        $allJemaat = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, fn() => $this->apiService->getAllJemaat() ?: []));
        
        // Normalisasi Leader (Pastikan roles array dan lowercase)
        $allLeaders = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, fn() => $this->apiService->getAllLeaders() ?: []))
            ->map(function($item) {
                $item = (array)$item;
                $roles = $item['roles'] ?? [];
                if(is_string($roles)) $roles = json_decode($roles, true) ?? [];
                $item['roles_lower'] = array_map('strtolower', $roles);
                return (object)$item;
            });
        
        // Normalisasi Komsel Map
        $allKomsels = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, fn() => $this->apiService->getAllKomsels() ?: []))
            ->map(function($item) {
                $item = (array)$item;
                if(empty($item['nama'])) $item['nama'] = $item['nama_time'] ?? 'Komsel';
                return (object)$item;
            });

        // Statistik Utama
        $totalKomsel = $allKomsels->count();
        $totalJemaat = $allJemaat->count();
        
        // Hitung Leader Aktif (Exclude Super Admin dari hitungan Leader Murni)
        $activeLeadersCount = $allLeaders->filter(function($l) {
            $isSuperAdmin = in_array('super_admin', $l->roles_lower);
            $k = is_array($l) ? ($l['komsels'] ?? []) : ($l->komsels ?? []);
            if (is_string($k)) $k = json_decode($k, true);
            return !empty($k) && !$isSuperAdmin; 
        })->count();

        // Data Monitoring Komsel (Tabel Detail)
        $monitoringData = $allKomsels->map(function($komsel) use ($allJemaat, $allLeaders) {
            $kId = is_array($komsel) ? $komsel['id'] : $komsel->id;
            $kName = is_array($komsel) ? $komsel['nama'] : $komsel->nama;

            // --- LOGIKA PENCARIAN LEADER ---
            // 1. Cari semua user yang punya akses ke komsel ini
            $assignedUsers = $allLeaders->filter(function($l) use ($kId) {
                $k = is_array($l) ? ($l['komsels'] ?? []) : ($l->komsels ?? []);
                if (is_string($k)) $k = json_decode($k, true);
                return is_array($k) && in_array($kId, $k);
            });

            // 2. Filter: Cari yang BUKAN Super Admin (Real Leader)
            $realLeader = $assignedUsers->first(function($u) {
                return !in_array('super_admin', $u->roles_lower);
            });

            $finalLeader = $realLeader ?? $assignedUsers->first();
            $leaderName = $finalLeader ? ($finalLeader->nama ?? $finalLeader->name ?? 'Belum Ada Leader') : 'Kosong';
            
            // Hitung Anggota
            $memberCount = $allJemaat->where('komsel_id', $kId)->count();

            // Hitung Rata-rata Kehadiran (4 Minggu Terakhir)
            $schedules = Schedule::where('komsel_id', $kId)
                ->where('status', 'Selesai')
                ->where('created_at', '>=', now()->subWeeks(4))
                ->withCount(['attendances', 'guestAttendances'])
                ->get();
            
            $totalHadir = $schedules->sum(fn($s) => $s->attendances_count + $s->guest_attendances_count);
            $avgHadir = $schedules->count() > 0 ? round($totalHadir / $schedules->count()) : 0;

            // Persentase Kehadiran (Target: 80% anggota aktif)
            $target = $memberCount * 0.8;
            $rate = ($target > 0) ? min(100, round(($avgHadir / $memberCount) * 100)) : 0;

            return (object) [
                'id' => $kId,
                'nama' => $kName,
                'leader' => $leaderName,
                'leader_initial' => substr($leaderName, 0, 1),
                'members' => $memberCount,
                'avg_attendance' => $avgHadir,
                'rate' => $rate
            ];
        })->sortByDesc('rate')->values();

        // Oikos Stats (Global)
        $oikosStats = OikosVisit::selectRaw("
            count(*) as total,
            sum(case when status = 'Selesai' then 1 else 0 end) as selesai,
            sum(case when status IN ('Direncanakan', 'Berlangsung', 'Diproses') then 1 else 0 end) as proses
        ")->first();

        // Chart Data (Global Trend)
        $chartData = $this->getAttendanceChartData(Schedule::query());

        return view('dashboard.kordinator', compact(
            'totalKomsel', 'totalJemaat', 'activeLeadersCount', 
            'monitoringData', 'oikosStats'
        ) + $chartData);
    }

    // =========================================================================
    // 2. DASHBOARD GEMBALA (STRUKTUR & GLOBAL OVERVIEW)
    // =========================================================================
    private function dashboardGembala()
    {
        // Ambil SEMUA Data Pengurus
        $allStaff = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllLeaders() ?: [];
        }))->map(function($item) {
            $item = (array) $item;
            $roles = $item['roles'] ?? [];
            if (is_string($roles)) $roles = json_decode($roles, true) ?? [];
            $item['roles_lower'] = array_map('strtolower', $roles);
            return (object) $item;
        });

        // Identifikasi Koordinator
        $coordinator = $allStaff->first(function($u) {
            $r = $u->roles_lower;
            return in_array('super_admin', $r) && in_array('panel_user', $r) && in_array('leaders', $r);
        });
        $coordinatorId = $coordinator ? $coordinator->id : null;

        // Identifikasi Gembala
        $gembala = $allStaff->first(function($u) use ($coordinatorId) {
            $r = $u->roles_lower;
            return in_array('super_admin', $r) && $u->id !== $coordinatorId;
        });

        // Filter List (Exclude Koordinator dari Leader list)
        $leaders = $allStaff->filter(fn($u) => in_array('leaders', $u->roles_lower) && $u->id !== $coordinatorId)->sortBy('nama')->values();
        $partners = $allStaff->filter(fn($u) => in_array('partners', $u->roles_lower))->sortBy('nama')->values();
        $otr = $allStaff->filter(fn($u) => in_array('orang tua rohani', $u->roles_lower))->sortBy('nama')->values();

        // Statistik
        $totalJemaat = $this->calculateTotalJiwa();
        $totalKomsel = collect(Cache::get(self::CACHE_KEY['komsel_map']))->count();
        $totalLeaders = $leaders->count();

        $oikosStats = OikosVisit::selectRaw("
            count(*) as total,
            sum(case when status = 'Selesai' then 1 else 0 end) as berhasil,
            sum(case when status = 'Gagal' then 1 else 0 end) as gagal,
            sum(case when status IN ('Direncanakan', 'Berlangsung', 'Diproses', 'Revisi') then 1 else 0 end) as proses
        ")->first();

        return view('dashboard.gembala', compact(
            'coordinator', 'gembala', 'leaders', 'partners', 'otr',
            'totalJemaat', 'totalKomsel', 'totalLeaders', 'oikosStats'
        ));
    }

    // =========================================================================
    // 3. DASHBOARD LEADER/PARTNER (OPERASIONAL HARIAN)
    // =========================================================================
    private function dashboardLeader(Request $request, $currentUserId)
    {
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        // 1. Data Jemaat
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, fn() => $this->apiService->getAllJemaat() ?: []))
            ->map(fn($item) => (object)$item);
        
        // 2. Data Komsel (Normalisasi Nama)
        $komselMap = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, fn() => $this->apiService->getAllKomsels() ?: []))
            ->map(function($item) {
                $item = (array)$item;
                if(empty($item['nama'])) $item['nama'] = $item['nama_time'] ?? 'Komsel';
                return (array)$item;
            })->keyBy('id');

        // 3. Query Data Operasional
        $oikosQuery = OikosVisit::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->where(function($q) use ($currentUserId) {
                $q->where('pelayan_user_id', $currentUserId)
                  ->orWhere('original_pelayan_user_id', $currentUserId);
            });
        
        $schedulesQuery = Schedule::whereIn('komsel_id', $leaderKomselIds);
        
        // 4. KPI Stats
        $totalKomsel = count($leaderKomselIds);
        $totalAnggota = $jemaatList->whereIn('komsel_id', $leaderKomselIds)->count();
        $oikosBulanIni = $oikosQuery->count();

        // Rata-rata Kehadiran
        $completed = (clone $schedulesQuery)->where('status', 'Selesai')->withCount(['attendances', 'guestAttendances'])->get();
        $totalAtt = $completed->sum(fn($s) => $s->attendances_count + $s->guest_attendances_count);
        $averageAttendance = $completed->count() > 0 ? round($totalAtt / $completed->count(), 1) : 0;

        // 5. Jadwal Mendatang (5 Terakhir, yang Aktif)
        $upcomingSchedules = Schedule::whereIn('komsel_id', $leaderKomselIds)
            ->whereIn('status', ['Menunggu', 'Berlangsung'])
            ->orderBy('created_at', 'desc')->limit(5)->get();
        
        foreach ($upcomingSchedules as $s) {
            $s->komsel_name = $komselMap[$s->komsel_id]['nama'] ?? 'Komsel';
        }

        // 6. [FIXED] Fitur Ulang Tahun Bulan Ini (Dengan ID untuk Link)
        $currentMonth = now()->month;
        $birthdayMembers = $jemaatList->filter(function($j) use ($currentMonth, $leaderKomselIds) {
            if(empty($j->tgl_lahir) || !in_array($j->komsel_id, $leaderKomselIds)) return false;
            try {
                return Carbon::parse($j->tgl_lahir)->month === $currentMonth;
            } catch (\Exception $e) { return false; }
        })->map(function($j) use ($komselMap) {
             $dob = Carbon::parse($j->tgl_lahir);
             return (object)[
                 'id' => $j->id, // PENTING: Untuk link ke create kunjungan
                 'nama' => $j->nama, 
                 'tgl_lahir' => $dob->format('d M Y'),
                 'hari_ultah' => $dob->format('d'), 
                 'umur' => $dob->age,
                 'komsel_nama' => $komselMap[$j->komsel_id]['nama'] ?? '-',
                 'avatar_initial' => substr($j->nama, 0, 1)
             ];
        })->sortBy('hari_ultah')->values();

        // 7. Data Lainnya (Preview & Notif)
        $chartData = $this->getAttendanceChartData($schedulesQuery);
        $oikosRevisiUntukUser = OikosVisit::where('status', 'Revisi')->where('pelayan_user_id', $currentUserId)->get();
        
        $myMembersPreview = $jemaatList->whereIn('komsel_id', $leaderKomselIds)->take(10);
        $myOikosPreview = (clone $oikosQuery)->latest()->take(5)->get();
        
        $myKomselsDetails = collect($leaderKomselIds)->map(function($kid) use ($komselMap, $jemaatList) {
            return (object) [
                'id' => $kid, 
                'nama' => $komselMap[$kid]['nama'] ?? 'Unknown', 
                'member_count' => $jemaatList->where('komsel_id', $kid)->count()
            ];
        });

        // Kirim Variabel ke View
        return view('dashboard.dashboard', compact(
            'totalAnggota', 'totalKomsel', 'oikosBulanIni', 'averageAttendance',
            'upcomingSchedules', 'oikosRevisiUntukUser', 'myMembersPreview', 
            'myOikosPreview', 'myKomselsDetails', 'birthdayMembers'
        ) + $chartData + ['isAdmin' => false, 'isCoordinator' => false, 'isLeader' => true]);
    }

    // =========================================================================
    // 4. DASHBOARD JEMAAT (SIMPLE VIEW)
    // =========================================================================
    private function dashboardJemaat($userId)
    {
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, fn() => $this->apiService->getAllJemaat() ?: []));
        
        $myData = $jemaatList->firstWhere('id', $userId);
        $myKomsel = null; $myLeader = null; $nextSchedule = null;

        if ($myData && !empty($myData['komsel_id'])) {
            $komselId = $myData['komsel_id'];
            
            $komselsMap = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, fn() => $this->apiService->getAllKomsels() ?: []))
                ->map(fn($item) => (object)$item)->keyBy('id');
            
            $myKomsel = $komselsMap->get($komselId);

            $leadersList = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, fn() => $this->apiService->getAllLeaders() ?: []));
            
            $myLeader = $leadersList->first(function($leader) use ($komselId) {
                $leader = (object)$leader;
                $k = $leader->komsels ?? [];
                if(is_string($k)) $k = json_decode($k, true);
                return is_array($k) && in_array($komselId, $k);
            });

            $nextSchedule = Schedule::where('komsel_id', $komselId)
                ->whereIn('status', ['Menunggu', 'Berlangsung'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('dashboard.jemaat', compact('myData', 'myKomsel', 'myLeader', 'nextSchedule'));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    private function calculateTotalJiwa() {
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, fn() => $this->apiService->getAllJemaat() ?: []));
        return $jemaatList->count();
    }

    private function getAttendanceChartData($baseScheduleQuery) {
        $labels = []; $data = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek(); $end = now()->subWeeks($i)->endOfWeek();
            $labels[] = "Minggu " . $start->weekOfYear;
            
            $scheduleIds = (clone $baseScheduleQuery)->whereBetween('created_at', [$start, $end])->where('status', 'Selesai')->pluck('id');
            $count = Attendance::whereIn('schedule_id', $scheduleIds)->count() + GuestAttendance::whereIn('schedule_id', $scheduleIds)->count();
            $data[] = $count;
        }
        return ['attendanceChartLabels' => $labels, 'attendanceChartData' => $data];
    }

    private function runAutoFailCheck() {
        if (!Cache::has('oikos_auto_fail_check')) {
            OikosVisit::where('end_date', '<', Carbon::today())
                ->whereIn('status', ['Direncanakan', 'Berlangsung', 'Menunggu Persetujuan'])
                ->update(['status' => 'Gagal']);
            Cache::put('oikos_auto_fail_check', true, 1440); 
        }
    }

    // --- ADMIN ACTIONS ---
    public function komselAktif(Request $request) { 
        // Method ini sebenarnya redundant karena logicnya sama dengan Dashboard Koordinator
        // Tapi biarkan saja jika route masih memakainya
        return $this->dashboardKordinator(); 
    }
    public function appointCoordinator(Request $request) { return back(); }
    public function resetCoordinator(Request $request) { return back(); }
}