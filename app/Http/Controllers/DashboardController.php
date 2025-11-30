<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\OikosVisit;
use App\Models\User; 
use App\Models\Attendance;
use App\Models\GuestAttendance;
use App\Services\OldApiService; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $apiService;

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

    public function index(Request $request) 
    {
        $this->runAutoFailCheck();

        $currentUserId = Auth::id();
        if (!$currentUserId) return redirect()->route('login');

        $user = User::find($currentUserId);
        if (!$user) {
            Auth::logout();
            return redirect()->route('login');
        }

        $roles = $user->roles ?? [];

        // --- LOGIKA PENENTUAN ROLE ---

        // A. GEMBALA
        $isGembala = in_array('super_admin', $roles) && !in_array('Leaders', $roles) && !$user->is_coordinator;

        // B. KOORDINATOR
        $isCoordinator = ($user->is_coordinator == 1) || (in_array('super_admin', $roles) && in_array('Leaders', $roles));

        // C. LEADER
        $isLeader = in_array('Leaders', $roles) && !$isGembala && !$isCoordinator;

        // D. JEMAAT
        $isJemaat = !$isGembala && !$isCoordinator && !$isLeader;


        // === ROUTING VIEW BERDASARKAN ROLE ===
        
        if ($isGembala) {
            return $this->dashboardGembala();
        }

        if ($isJemaat) {
            return $this->dashboardJemaat($currentUserId);
        }

        // === VIEW UMUM (KOORDINATOR & LEADER) ===
        $leaderKomselIds = $request->session()->get('user_komsel_ids', []);
        
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat();
        }))->map(function($item) { return (object)$item; });
        
        $komselMap = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            return $this->apiService->getAllKomsels();
        }))->keyBy('id');

        $oikosQuery = OikosVisit::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        $schedulesQuery = Schedule::query(); 
        $upcomingSchedulesQuery = Schedule::whereIn('status', ['Menunggu', 'Berlangsung'])->where('created_at', '>=', now()->startOfWeek());

        // Data Preview Variables for Modal
        $myMembersPreview = collect();
        $myOikosPreview = collect();
        $myKomselsDetails = collect();

        // [LOGIKA SCOPING DATA - DIPERBARUI UNTUK FASE 4]
        if (!$isCoordinator) {
            // LEADER BIASA
            
            // 1. Filter OIKOS: Hanya Tugas Saya (Sebagai Pelayan)
            // HAPUS LOGIKA LAMA: $oikosQuery->whereIn('jemaat_id', $jemaatIdsInMyKomsels);
            $oikosQuery->where('pelayan_user_id', $currentUserId); 
            
            // 2. Filter Jadwal Komsel: Tetap berdasarkan ID Komsel
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
            $upcomingSchedulesQuery->whereIn('komsel_id', $leaderKomselIds);
            $totalKomsel = count($leaderKomselIds);

            // Data untuk Modal Preview
            $myMembersPreview = $jemaatList->whereIn('komsel_id', $leaderKomselIds)->take(10); 
            $myOikosPreview = (clone $oikosQuery)->latest()->take(5)->get(); 
            
            // Data Detail per Komsel
            $myKomselsDetails = collect($leaderKomselIds)->map(function($kid) use ($komselMap, $jemaatList) {
                return (object) [
                    'id' => $kid,
                    'nama' => $komselMap[$kid]['nama'] ?? 'Unknown Komsel',
                    'member_count' => $jemaatList->where('komsel_id', $kid)->count()
                ];
            });
        } else {
            // KOORDINATOR: Lihat Semua Data
            $totalKomsel = $komselMap->count();
            
            // Koordinator preview data
            $myMembersPreview = $jemaatList->take(10);
            $myOikosPreview = (clone $oikosQuery)->latest()->take(5)->get();
            
            // Koordinator Detail Komsel
            $myKomselsDetails = $komselMap->take(6)->map(function($k) use ($jemaatList) {
                return (object) [
                    'id' => $k['id'],
                    'nama' => $k['nama'],
                    'member_count' => $jemaatList->where('komsel_id', $k['id'])->count()
                ];
            });
        }

        // Hitung Total Anggota (Jiwa)
        if ($isCoordinator) {
            $totalAnggota = $this->calculateTotalJiwa();
        } else {
            $totalAnggota = $jemaatList->whereIn('komsel_id', $leaderKomselIds)->count();
        }

        $oikosBulanIni = $oikosQuery->count();

        // Statistik Kehadiran
        $completedScheduleIds = (clone $schedulesQuery)->where('status', 'Selesai')->pluck('id');
        $totalCompleted = $completedScheduleIds->count();
        $totalAttendance = Attendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $totalGuests = GuestAttendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $averageAttendance = $totalCompleted > 0 ? round(($totalAttendance + $totalGuests) / $totalCompleted, 2) : 0;

        $upcomingSchedules = $upcomingSchedulesQuery->orderBy('created_at', 'asc')->limit(5)->get();
        foreach ($upcomingSchedules as $schedule) {
            $komselData = $komselMap->get($schedule->komsel_id);
            $schedule->komsel_name = $komselData['nama'] ?? 'Komsel API';
        }

        $chartData = $this->getAttendanceChartData($schedulesQuery);

        // Notifikasi Revisi (Hanya untuk Leader)
        $oikosRevisiUntukUser = collect();
        if (!$isCoordinator) {
            $currentUserId = $user->id;
            // Revisi juga dicek berdasarkan pelayan_user_id
            $oikosRevisiUntukUser = OikosVisit::where('status', 'Revisi')
                ->where('pelayan_user_id', $currentUserId)
                ->get(['id', 'oikos_name', 'revision_comment']);
        }

        // Tambahkan variabel 'isAdmin' untuk view logic
        return view('dashboard.dashboard', compact(
            'totalAnggota', 'totalKomsel', 'oikosBulanIni', 'averageAttendance',
            'upcomingSchedules', 'oikosRevisiUntukUser', 'isCoordinator', 'isLeader',
            'myMembersPreview', 'myOikosPreview', 'myKomselsDetails'
        ) + $chartData + ['isAdmin' => $isCoordinator]);
    }

    /**
     * LOGIKA KHUSUS JEMAAT (ANGGOTA)
     */
    private function dashboardJemaat($userId)
    {
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat();
        }));
        
        $myData = $jemaatList->firstWhere('id', $userId);
        
        $myKomsel = null;
        $myLeader = null;
        $nextSchedule = null;

        if ($myData && !empty($myData['komsel_id'])) {
            $komselId = $myData['komsel_id'];

            $komselsMap = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
                return $this->apiService->getAllKomsels();
            }))->keyBy('id');
            
            $myKomsel = $komselsMap->get($komselId);

            $leadersList = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
                return $this->apiService->getAllLeaders();
            }));

            $myLeader = $leadersList->first(function($leader) use ($komselId) {
                $leaderKomsels = is_array($leader['komsels']) ? $leader['komsels'] : [];
                return in_array($komselId, $leaderKomsels);
            });

            $nextSchedule = Schedule::where('komsel_id', $komselId)
                ->whereIn('status', ['Menunggu', 'Berlangsung'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('dashboard.jemaat', compact('myData', 'myKomsel', 'myLeader', 'nextSchedule'));
    }

    /**
     * LOGIKA KHUSUS GEMBALA (SUPER ADMIN)
     */
    private function dashboardGembala()
    {
        Cache::forget(self::CACHE_KEY['pelayan_list']); 
        $allPelayan = collect(Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllOikosPelayan();
        }));

        $localCoordinatorsIds = DB::table('users')->where('is_coordinator', 1)->pluck('id')->toArray();

        $potentialCoordinators = $allPelayan->map(function($item) use ($localCoordinatorsIds) {
            $id = is_array($item) ? $item['id'] : $item->id;
            $name = is_array($item) ? ($item['nama'] ?? $item['name'] ?? 'Tanpa Nama') : ($item->nama ?? $item->name ?? 'Tanpa Nama');
            $email = is_array($item) ? ($item['email'] ?? null) : ($item->email ?? null);
            $rolesRaw = is_array($item) ? ($item['roles'] ?? []) : ($item->roles ?? []);

            if (is_string($rolesRaw)) $roles = json_decode($rolesRaw, true) ?? [];
            else $roles = $rolesRaw;

            return (object) [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'roles' => $roles,
                'is_coordinator' => in_array($id, $localCoordinatorsIds)
            ];
        })->filter(function($p) {
            if (empty($p->roles) || !is_array($p->roles)) return false;
            $rolesLower = array_map('strtolower', $p->roles);
            return in_array('leaders', $rolesLower) || in_array('leader', $rolesLower);
        })->sortBy('name')->values();

        $totalJemaat = $this->calculateTotalJiwa();
        $totalKomsel = collect(Cache::get(self::CACHE_KEY['komsel_map']))->count();
        $totalLeaders = $potentialCoordinators->count();

        $oikosStats = OikosVisit::selectRaw("
            count(*) as total,
            sum(case when status = 'Selesai' then 1 else 0 end) as berhasil,
            sum(case when status = 'Gagal' then 1 else 0 end) as gagal,
            sum(case when status IN ('Direncanakan', 'Berlangsung', 'Diproses', 'Revisi') then 1 else 0 end) as proses
        ")->first();

        return view('dashboard.gembala', compact('totalJemaat', 'totalKomsel', 'potentialCoordinators', 'totalLeaders', 'oikosStats'));
    }

    public function komselAktif(Request $request)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) return redirect()->route('login');

        $user = User::find($currentUserId);
        if (!$user) return redirect()->route('login');

        $roles = $user->roles ?? [];
        $isGembala = in_array('super_admin', $roles) && !in_array('Leaders', $roles);
        $isCoordinator = ($user->is_coordinator == 1) || (in_array('super_admin', $roles) && in_array('Leaders', $roles));

        if (!$isGembala && !$isCoordinator) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $rawKomsels = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            return $this->apiService->getAllKomsels();
        })); 
        $rawJemaat = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllJemaat();
        }));
        $rawLeaders = collect(Cache::remember(self::CACHE_KEY['pelayan_list'], self::CACHE_TTL, function () {
            return $this->apiService->getAllOikosPelayan();
        }));

        $komselData = $rawKomsels->map(function ($komsel) use ($rawJemaat, $rawLeaders) {
            $kId = is_array($komsel) ? $komsel['id'] : $komsel->id;
            $kNama = is_array($komsel) ? $komsel['nama'] : $komsel->nama;
            
            $assignedUsers = $rawLeaders->filter(function ($l) use ($kId) {
                $myKomsels = is_array($l) ? ($l['komsels'] ?? []) : ($l->komsels ?? []);
                if (is_string($myKomsels)) $myKomsels = json_decode($myKomsels, true) ?? [];
                return is_array($myKomsels) && in_array($kId, $myKomsels);
            });

            $realLeader = $assignedUsers->first(function ($u) {
                $roles = is_array($u) ? ($u['roles'] ?? []) : ($u->roles ?? []);
                if (is_string($roles)) $roles = json_decode($roles, true) ?? [];
                return !in_array('super_admin', $roles);
            });

            $finalLeader = $realLeader ?? $assignedUsers->first();
            $leaderName = 'Belum Ada Leader';
            if ($finalLeader) {
                $leaderName = is_array($finalLeader) 
                    ? ($finalLeader['nama'] ?? $finalLeader['name'] ?? 'Leader') 
                    : ($finalLeader->nama ?? $finalLeader->name ?? 'Leader');
            }

            $members = $rawJemaat->filter(function ($j) use ($kId) {
                $jKomselId = is_array($j) ? ($j['komsel_id'] ?? null) : ($j->komsel_id ?? null);
                return $jKomselId == $kId;
            })->values();

            return (object) [
                'id' => $kId,
                'nama' => $kNama,
                'leader_name' => $leaderName,
                'members' => $members,
                'total_members' => $members->count()
            ];
        });

        $komselData = $komselData->sortByDesc('total_members')->values();

        return view('dashboard.komsel_aktif', compact('komselData'));
    }

    public function appointCoordinator(Request $request)
    {
        $currentUserId = Auth::id();
        $user = User::find($currentUserId);
        $roles = $user->roles ?? [];
        
        if (!in_array('super_admin', $roles)) abort(403);

        $targetUserId = $request->input('user_id');
        $allPelayan = collect(Cache::get(self::CACHE_KEY['pelayan_list']));
        
        $targetData = $allPelayan->first(function($item) use ($targetUserId) {
            $id = is_array($item) ? $item['id'] : $item->id;
            return $id == $targetUserId;
        });

        if (!$targetData) return back()->with('error', 'Data pelayan tidak ditemukan di API.');

        DB::table('users')->update(['is_coordinator' => 0]);

        $apiId = is_array($targetData) ? $targetData['id'] : $targetData->id;
        $apiNama = is_array($targetData) ? ($targetData['nama'] ?? $targetData['name']) : ($targetData->nama ?? $targetData->name);
        $apiEmail = is_array($targetData) ? $targetData['email'] : $targetData->email;
        $apiRoles = is_array($targetData) ? $targetData['roles'] : $targetData->roles;
        $apiPassword = is_array($targetData) ? ($targetData['password'] ?? bcrypt('password')) : ($targetData->password ?? bcrypt('password'));

        $localUser = User::updateOrCreate(
            ['id' => $apiId], 
            [
                'name' => $apiNama, 
                'email' => $apiEmail,
                'password' => $apiPassword, 
                'roles' => $apiRoles,
                'is_coordinator' => true
            ]
        );

        return back()->with('success', $localUser->name . ' telah ditetapkan sebagai Koordinator Tunggal.');
    }

    public function resetCoordinator(Request $request)
    {
        $user = Auth::user();
        if (!in_array('super_admin', $user->roles ?? [])) abort(403);

        DB::table('users')->update(['is_coordinator' => 0]);

        return back()->with('success', 'Semua Koordinator telah di-reset.');
    }

    // HELPER METHODS
    private function calculateTotalJiwa() {
        $jemaatList = collect(Cache::remember(self::CACHE_KEY['jemaat_list'], self::CACHE_TTL, function () {
            $res = $this->apiService->getAllJemaat();
            return $res ?: [];
        }));
        $leaderList = collect(Cache::remember(self::CACHE_KEY['leader_list'], self::CACHE_TTL, function () {
            $res = $this->apiService->getAllLeaders();
            return $res ?: [];
        }));
        $allIds = $jemaatList->pluck('id')->merge($leaderList->pluck('id'))->unique();
        return $allIds->count();
    }

    private function getAttendanceChartData($baseScheduleQuery) {
        $startDate = now()->subWeeks(3)->startOfWeek();
        $endDate = now()->endOfWeek();
        
        $scheduleIds = (clone $baseScheduleQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'Selesai')
            ->pluck('id');
            
        $attendance = Attendance::whereIn('schedule_id', $scheduleIds)
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('count(*) as total'))
            ->groupBy('week')->pluck('total', 'week');
            
        $guests = GuestAttendance::whereIn('schedule_id', $scheduleIds)
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('count(*) as total'))
            ->groupBy('week')->pluck('total', 'week');
            
        $labels = []; $data = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = now()->subWeeks($i);
            $weekNumber = $date->format('W'); 
            $labels[] = 'Minggu ' . $weekNumber;
            $val = ($attendance[$weekNumber] ?? 0) + ($guests[$weekNumber] ?? 0);
            $data[] = $val;
        }
        return ['attendanceChartLabels' => $labels, 'attendanceChartData' => $data];
    }

    private function runAutoFailCheck() {
        if (!Cache::has('oikos_auto_fail_check')) {
            $today = Carbon::today();
            OikosVisit::where('end_date', '<', $today)
                ->whereIn('status', ['Direncanakan', 'Berlangsung'])
                ->update(['status' => 'Gagal']);
            Cache::put('oikos_auto_fail_check', true, 1440); 
        }
    }
}