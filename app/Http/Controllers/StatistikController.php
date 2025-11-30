<?php

namespace App\Http\Controllers;

use App\Services\OldApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Exports\StatistikExport;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\GuestAttendance;
use App\Models\OikosVisit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class StatistikController extends Controller
{
    protected $apiService;

    const CACHE_KEY = [
        'jemaat_map'  => 'api_jemaat_map_by_id_v2',
        'leader_map'  => 'api_leader_map_by_id_v2',
        'komsel_map'  => 'api_komsel_map_std_by_id_v2',
    ];
    const CACHE_TTL = 3600; // 1 jam

    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Helper untuk mengambil dan menghitung data statistik secara terpusat.
     * Digunakan oleh Web View, Export Excel, dan Export PDF.
     * * @param int $month
     * @param int $year
     * @return array
     */
    private function getStatistikData($month, $year)
    {
        $user = Auth::user();
        $isSuperAdmin = session()->get('is_super_admin', false);
        $leaderKomselIds = session()->get('user_komsel_ids', []);

        // 1. Ambil Data Referensi dari Cache (API)
        // Menggunakan collect() dan keyBy('id') untuk lookup cepat O(1)
        $jemaatMap = collect(Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, fn() => $this->apiService->getAllJemaat()))->keyBy('id');
        $leaderMap = collect(Cache::remember(self::CACHE_KEY['leader_map'], self::CACHE_TTL, fn() => $this->apiService->getAllLeaders()))->keyBy('id');
        $komselMap = collect(Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, fn() => $this->apiService->getAllKomsels()))->keyBy('id');
        $allUsersMap = $jemaatMap->union($leaderMap);

        // 2. Query Dasar Jadwal (Schedules)
        $schedulesQuery = Schedule::whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        // Filter Scoping: Jika bukan Admin, hanya lihat jadwal komsel milik Leader
        if (!$isSuperAdmin) {
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
        }

        // Ambil ID jadwal yang sudah selesai untuk hitung kehadiran
        $completedScheduleIds = (clone $schedulesQuery)->where('status', 'Selesai')->pluck('id');

        // 3. Hitung Metrics Jadwal
        $stats['totalSchedules'] = $schedulesQuery->count();
        $stats['schedulesTerlaksana'] = $completedScheduleIds->count();
        $stats['schedulesDibatalkan'] = (clone $schedulesQuery)->where('status', 'Gagal')->count();
        $stats['schedulesDitunda'] = (clone $schedulesQuery)->where('status', 'Menunggu')->count();
        $stats['schedulesBerlangsung'] = (clone $schedulesQuery)->where('status', 'Berlangsung')->count();

        // 4. Hitung Metrics Kehadiran (Attendance)
        $stats['totalRegisteredAttendance'] = Attendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $stats['totalGuestAttendance'] = GuestAttendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $stats['grandTotalAttendance'] = $stats['totalRegisteredAttendance'] + $stats['totalGuestAttendance'];
        
        // Rata-rata kehadiran per pertemuan
        $stats['averageAttendance'] = $stats['schedulesTerlaksana'] > 0 
            ? round($stats['grandTotalAttendance'] / $stats['schedulesTerlaksana'], 2) 
            : 0;

        // 5. Query Dasar OIKOS
        $oikosQuery = OikosVisit::whereMonth('start_date', $month)
            ->whereYear('start_date', $year);

        // Filter Scoping OIKOS:
        // Admin lihat semua. Leader lihat OIKOS terhadap anggota komselnya ATAU inputannya sendiri.
        if (!$isSuperAdmin) {
            // Cari ID Jemaat yang ada di dalam Komsel Leader
            $myJemaatIds = $jemaatMap->filter(function ($jemaat) use ($leaderKomselIds) {
                $jKomselId = is_array($jemaat) ? ($jemaat['komsel_id'] ?? null) : ($jemaat->komsel_id ?? null);
                return in_array($jKomselId, $leaderKomselIds);
            })->keys();

            $oikosQuery->where(function($q) use ($myJemaatIds, $user) {
                $q->whereIn('jemaat_id', $myJemaatIds)
                  ->orWhere('pelayan_user_id', $user->id);
            });
        }

        // 6. Hitung Metrics OIKOS
        $stats['totalOikos'] = $oikosQuery->count();
        $stats['oikosSelesai'] = (clone $oikosQuery)->where('status', 'Selesai')->count();
        $stats['oikosGagal'] = (clone $oikosQuery)->whereIn('status', ['Gagal', 'Batal'])->count();
        // Status on-going digabung
        $stats['oikosProses'] = (clone $oikosQuery)->whereIn('status', ['Direncanakan', 'Diproses', 'Revisi'])->count();

        // 7. Data Chart: Kehadiran per Komsel
        // Ambil jadwal selesai beserta jumlah kehadirannya
        $schedulesWithCount = Schedule::whereIn('id', $completedScheduleIds)
            ->withCount(['attendances', 'guestAttendances'])
            ->get();

        $attendanceByKomselId = $schedulesWithCount->groupBy('komsel_id')
            ->map(function ($group) {
                return $group->sum(fn($s) => $s->attendances_count + $s->guest_attendances_count);
            });

        // Mapping ID Komsel ke Nama Komsel
        $attendanceByKomsel = $attendanceByKomselId->mapWithKeys(function ($total, $komselId) use ($komselMap) {
            $komselData = $komselMap->get($komselId);
            $komselName = is_array($komselData) ? ($komselData['nama'] ?? '-') : ($komselData->nama ?? '-');
            if ($komselName === '-') $komselName = "Komsel #$komselId";
            
            return [$komselName => $total];
        })->sortDesc();
        
        $stats['attendanceByKomsel'] = $attendanceByKomsel;
        $stats['komselTeraktif'] = $attendanceByKomsel->keys()->first() ?? '-';
        $stats['komselChartLabels'] = $attendanceByKomsel->keys();
        $stats['komselChartData'] = $attendanceByKomsel->values();

        // 8. Data Chart: Top 5 Anggota (Rajin Hadir)
        $topAttendees = Attendance::whereIn('schedule_id', $completedScheduleIds)
            ->select('user_id', DB::raw('count(*) as attendance_count')) 
            ->groupBy('user_id') 
            ->orderByDesc('attendance_count')
            ->limit(5)
            ->get();
        
        $stats['topAttendeesLabels'] = [];
        $stats['topAttendeesData'] = [];
        $stats['topAttendees'] = $topAttendees; // Untuk iterasi di view jika perlu

        foreach ($topAttendees as $attendee) {
            $userData = $allUsersMap->get($attendee->user_id);
            $userName = is_array($userData) ? ($userData['nama'] ?? '-') : ($userData->nama ?? '-');
            if ($userName === '-') $userName = "User #{$attendee->user_id}";
            
            $stats['topAttendeesLabels'][] = $userName;
            $stats['topAttendeesData'][] = $attendee->attendance_count;
        }

        // 9. Data Chart: Tren Harian (Line Chart)
        $dailyAttendanceQuery = Schedule::whereIn('id', $completedScheduleIds)
            ->withCount(['attendances', 'guestAttendances'])
            ->select(DB::raw('DAY(created_at) as day'), 'id')
            ->get();

        $dailyAttendanceMap = $dailyAttendanceQuery->groupBy('day')
            ->map(fn ($dailySchedules) => $dailySchedules->sum(fn($s) => $s->attendances_count + $s->guest_attendances_count));

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $stats['attendanceTrendLabels'] = range(1, $daysInMonth);
        // Map data harian, isi 0 jika tidak ada data pada tanggal tersebut
        $stats['attendanceTrendData'] = collect($stats['attendanceTrendLabels'])
            ->map(fn($day) => $dailyAttendanceMap->get($day, 0));

        return $stats;
    }

    /**
     * Halaman Utama Statistik (Web)
     */
    public function statistik(Request $request)
    {
        $userRoles = $request->session()->get('user_roles', []);
        if (empty($userRoles)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki wewenang untuk melihat statistik.');
        }

        $selectedMonth = $request->input('month', now()->month);
        $selectedYear = $request->input('year', now()->year);

        // Ambil semua data statistik dari helper
        $data = $this->getStatistikData($selectedMonth, $selectedYear);

        // Data Dropdown Filter
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->translatedFormat('F');
        }
        $years = range(now()->year, now()->year - 5);
        
        // Gabungkan data statistik dengan data dropdown
        return view('statistik', array_merge($data, compact('months', 'years', 'selectedMonth', 'selectedYear')));
    }

    /**
     * Export Data ke Excel
     */
    public function exportExcel(Request $request)
    {
        $month = (int)$request->input('month', now()->month);
        $year = (int)$request->input('year', now()->year);
        $monthName = Carbon::create()->month($month)->translatedFormat('F');

        // StatistikExport akan menggunakan logic query yang mirip/sama
        return Excel::download(new StatistikExport($month, $year, $this->apiService, $request->session()), "statistik-komsel-{$monthName}-{$year}.xlsx");
    }

    /**
     * Export Laporan ke PDF
     */
    public function exportPdf(Request $request)
    {
        $month = (int)$request->input('month', now()->month);
        $year = (int)$request->input('year', now()->year);
        $monthName = Carbon::create()->month($month)->translatedFormat('F');

        // 1. Ambil data real menggunakan helper yang sama dengan view statistik
        $data = $this->getStatistikData($month, $year);
        
        // 2. Tambahkan variabel meta untuk header PDF
        $data['monthName'] = $monthName;
        $data['year'] = $year;
        $data['user'] = Auth::user();
        
        // 3. Render PDF
        $pdf = Pdf::loadView('statistik_pdf', $data);
        return $pdf->download("Laporan_Statistik_{$monthName}_{$year}.pdf");
    }
}