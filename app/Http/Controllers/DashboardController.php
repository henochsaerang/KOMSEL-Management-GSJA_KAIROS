<?php

namespace App\Http\Controllers; // <-- [PERBAIKAN] Namespace yang benar

// [PERBAIKAN] Menambahkan model OikosVisit dan Schedule
use App\Models\Schedule;
use App\Models\OikosVisit;
use App\Models\User; // Model 'users' LOKAL (untuk 'jejak' login)
use App\Models\Attendance;
use App\Models\GuestAttendance;

// Service dan helper
use App\Services\OldApiService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;


class DashboardController extends Controller
{
    protected $apiService;

    /**
     * [BARU] Inject OldApiService
     */
    public function __construct(OldApiService $apiService)
    {
        // [PERBAIKAN] Menggunakan '->' (panah)
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman dashboard dengan data ringkasan.
     */
    public function index()
    {
        // --- KPI & Data Utama ---
        
        // Ambil data Jemaat dan Komsel dari API (dengan cache 1 jam)
        $jemaatList = Cache::remember('api_jemaat_list', now()->addHour(), function () {
            $data = $this->apiService->getAllJemaat();
            return $data ? collect($data) : collect();
        });
        
        $komselList = Cache::remember('api_komsel_list', now()->addHour(), function () {
            $data = $this->apiService->getAllKomsels();
            // Asumsi data komsel dari API (model Time) di-key berdasarkan ID
            return $data ? collect($data)->keyBy('id') : collect();
        });

        // Hitung total dari data API
        $totalJemaat = $jemaatList->count();
        $totalKomsel = $komselList->count();
        
        // Total 'User' adalah "jejak" user (Admin + Jemaat) yang pernah login ke APLIKASI BARU
        $totalAnggota = User::count(); 
        
        // Ambil data Oikos dari DB LOKAL
        $oikosBulanIni = OikosVisit::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count();

        // --- Statistik Kehadiran Global (dari DB LOKAL) ---
        $completedScheduleIds = Schedule::where('status', 'Selesai')->pluck('id');
        $totalCompleted = $completedScheduleIds->count();
        $totalAttendance = Attendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $totalGuests = GuestAttendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $averageAttendance = $totalCompleted > 0 ? round(($totalAttendance + $totalGuests) / $totalCompleted, 2) : 0;

        // --- Jadwal KOMSEL Mendatang (dari DB LOKAL) ---
        $upcomingSchedules = Schedule::whereIn('status', ['Menunggu', 'Berlangsung'])
            ->where('created_at', '>=', now()->startOfWeek())
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();

        // Gabungkan data Komsel dari API secara manual
        foreach ($upcomingSchedules as $schedule) {
            if ($komselList->has($schedule->komsel_id)) {
                // Asumsi data komsel dari API (model Time) memiliki key 'nama'
                $schedule->komsel_name = $komselList->get($schedule->komsel_id)['nama'] ?? 'Komsel API';
            } else {
                $schedule->komsel_name = 'Komsel Tidak Ditemukan';
            }
        }

        // --- Data Grafik Kehadiran (dari DB LOKAL) ---
        $startDate = now()->subWeeks(3)->startOfWeek();
        $endDate = now()->endOfWeek();
        
        $scheduleIdsForChart = Schedule::whereBetween('created_at', [$startDate, $endDate])
                                        ->where('status', 'Selesai')
                                        ->pluck('id');

        $weeklyAttendanceData = Attendance::whereIn('schedule_id', $scheduleIdsForChart)
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('count(*) as total'))
            ->groupBy('week')
            ->pluck('total', 'week');
            
        $weeklyGuestData = GuestAttendance::whereIn('schedule_id', $scheduleIdsForChart)
            ->select(DB::raw('WEEK(created_at) as week'), DB::raw('count(*) as total'))
            ->groupBy('week')
            ->pluck('total', 'week');

        $attendanceChartLabels = [];
        $attendanceChartData = [];
        for ($i = 3; $i >= 0; $i--) {
            $week = now()->copy()->subWeeks($i)->startOfWeek();
            $weekNumber = $week->format('W');
            $attendanceChartLabels[] = 'Minggu ' . $weekNumber;
            $totalWeeklyAttendance = ($weeklyAttendanceData[$weekNumber] ?? 0) + ($weeklyGuestData[$weekNumber] ?? 0);
            $attendanceChartData[] = $totalWeeklyAttendance;
        }

        // --- Mengirim Semua Data ke View ---
        return view('dashboard', compact(
            'totalAnggota',
            'totalKomsel',
            'oikosBulanIni',
            'averageAttendance',
            'upcomingSchedules',
            'attendanceChartLabels',
            'attendanceChartData'
        ));
    }
}