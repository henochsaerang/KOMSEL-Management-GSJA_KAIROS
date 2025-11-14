<?php

namespace App\Http\Controllers;

// [FIX 1] Tambahkan Service API dan Cache
use App\Services\OldApiService;
use Illuminate\Support\Facades\Cache;

use App\Exports\StatistikExport;
use App\Models\Schedule;
// use App\Models\UserKairos; // [DIHAPUS] Tidak dipakai lagi
use App\Models\Attendance;
use App\Models\GuestAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class StatistikController extends Controller
{
    protected $apiService;

    // [FIX 2] Definisikan konstanta cache
    const CACHE_KEY = [
        'jemaat_map'  => 'api_jemaat_map_by_id_v2',
        'leader_map'  => 'api_leader_map_by_id_v2',
        'komsel_map'  => 'api_komsel_map_std_by_id_v2',
    ];
    const CACHE_TTL = 3600; // 1 jam

    /**
     * [FIX 3] Inject OldApiService agar bisa baca cache
     */
    public function __construct(OldApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function statistik(Request $request)
    {
        // 1. Setup Filter dan Data Dasar
        $selectedMonth = $request->input('month', now()->month);
        $selectedYear = $request->input('year', now()->year);

        // 2. Query Dasar (Base Queries) untuk Efisiensi
        $schedulesQuery = Schedule::whereMonth('created_at', $selectedMonth)->whereYear('created_at', $selectedYear);
        $completedScheduleIds = (clone $schedulesQuery)->where('status', 'Selesai')->pluck('id');

        // 3. Hitung Realisasi Jadwal
        $totalSchedules = $schedulesQuery->count();
        $schedulesTerlaksana = $completedScheduleIds->count();
        $schedulesDibatalkan = (clone $schedulesQuery)->where('status', 'Gagal')->count();
        $schedulesDitunda = (clone $schedulesQuery)->where('status', 'Menunggu')->count();
        $schedulesBerlangsung = (clone $schedulesQuery)->where('status', 'Berlangsung')->count();

        // 4. Hitung Statistik Kehadiran
        $totalRegisteredAttendance = Attendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $totalGuestAttendance = GuestAttendance::whereIn('schedule_id', $completedScheduleIds)->count();
        $grandTotalAttendance = $totalRegisteredAttendance + $totalGuestAttendance;
        $averageAttendance = $schedulesTerlaksana > 0 ? round($grandTotalAttendance / $schedulesTerlaksana, 2) : 0;

        // ======================================================================================
        // [FIX 4] PERBAIKAN UTAMA: Menggunakan Cache API untuk TOP 5 ANGGOTA
        // ======================================================================================
        
        // A. Ambil Peta User (Jemaat + Leader) dari Cache API
        $jemaatMap = Cache::remember(self::CACHE_KEY['jemaat_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllJemaat(); 
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });
        $leaderMap = Cache::remember(self::CACHE_KEY['leader_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllLeaders(); 
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });
        $allUsersMap = $jemaatMap->union($leaderMap); // Gabungkan kedua peta

        // B. Query Top 5 ID dari tabel 'attendances' (LOKAL)
        $topAttendees = Attendance::whereIn('schedule_id', $completedScheduleIds)
            // ->with('userKairos:id,nama') // [DIHAPUS]
            ->select('user_id', DB::raw('count(*) as attendance_count')) // [DIUBAH] ke user_id
            ->groupBy('user_id') // [DIUBAH] ke user_id
            ->orderByDesc('attendance_count')
            ->limit(5)
            ->get();
        
        // C. "Hydrate" (gabungkan) data di PHP menggunakan Peta Cache
        $topAttendeesLabels = [];
        $topAttendeesData = [];
        foreach ($topAttendees as $attendee) {
            // Cari nama user di Peta Cache
            $userName = $allUsersMap->get($attendee->user_id)['nama'] ?? "User (ID: $attendee->user_id)";
            $topAttendeesLabels[] = $userName;
            $topAttendeesData[] = $attendee->attendance_count;
        }
        // ======================================================================================


        // 6. Hitung Kehadiran per Komsel (Logika ini sudah benar dari perbaikan sebelumnya)
        $komselMap = Cache::remember(self::CACHE_KEY['komsel_map'], self::CACHE_TTL, function () {
            $data = $this->apiService->getAllKomsels(); 
            return $data ? collect($data)->filter()->keyBy('id') : collect();
        });

        $schedulesWithAttendance = Schedule::whereIn('id', $completedScheduleIds)
            ->withCount(['attendances', 'guestAttendances'])
            ->get();

        $attendanceByKomselId = $schedulesWithAttendance->groupBy('komsel_id')
            ->map(function ($schedulesInGroup) {
                return $schedulesInGroup->sum(function ($schedule) {
                    return $schedule->attendances_count + $schedule->guest_attendances_count;
                });
            });

        $attendanceByKomsel = $attendanceByKomselId->mapWithKeys(function ($total, $komselId) use ($komselMap) {
            $komselName = $komselMap->get($komselId)['nama'] ?? "Komsel (ID: $komselId)";
            return [$komselName => $total];
        })->sortDesc();
        
        $komselTeraktif = $attendanceByKomsel->keys()->first() ?? '-';
        $komselChartLabels = $attendanceByKomsel->keys();
        $komselChartData = $attendanceByKomsel->values();

        // 7. Hitung Tren Kehadiran Harian (Logika ini sudah benar)
        $daysInMonth = Carbon::create($selectedYear, $selectedMonth)->daysInMonth;
        $dailyAttendanceQuery = Schedule::whereIn('id', $completedScheduleIds)
            ->withCount(['attendances', 'guestAttendances'])
            ->select(DB::raw('DAY(created_at) as day'), 'id')
            ->get();

        $dailyAttendance = $dailyAttendanceQuery->groupBy('day')
            ->map(fn ($dailySchedules) => $dailySchedules->sum(fn($s) => $s->attendances_count + $s->guest_attendances_count));

        $trendLabels = range(1, $daysInMonth);
        $trendData = collect($trendLabels)->map(fn($day) => $dailyAttendance->get($day, 0));
        
        $attendanceTrendLabels = $trendLabels;
        $attendanceTrendData = $trendData;

        // Data untuk dropdown filter
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m, 1)->translatedFormat('F');
        }
        $years = range(now()->year, now()->year - 5);
        
        // Mengirim semua data ke view
        return view('statistik', compact(
            'months', 'years', 'selectedMonth', 'selectedYear',
            'schedulesTerlaksana', 'grandTotalAttendance', 'averageAttendance', 'totalGuestAttendance', 'komselTeraktif',
            'totalSchedules', 'schedulesDibatalkan', 'schedulesDitunda', 'schedulesBerlangsung',
            'attendanceTrendLabels', 'attendanceTrendData',
            'komselChartLabels', 'komselChartData', 'attendanceByKomsel',
            'topAttendeesLabels', 'topAttendeesData', 'topAttendees'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = (int)$request->input('month', now()->month);
        $year = (int)$request->input('year', now()->year);
        $monthName = Carbon::create()->month($month)->translatedFormat('F');

        // [PENTING] Anda juga harus meng-inject $apiService ke StatistikExport
        // jika file itu juga melakukan query yang sama.
        return Excel::download(new StatistikExport($month, $year, $this->apiService), "statistik-komsel-{$monthName}-{$year}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        // [PENTING] Anda juga harus mengambil data $komselMap dan $allUsersMap
        // dan mengirimkannya ke view PDF Anda ('statistik_pdf')
        
        $month = (int)$request->input('month', now()->month);
        $year = (int)$request->input('year', now()->year);
        $monthName = Carbon::create()->month($month)->translatedFormat('F');

        // Anda harus mengumpulkan data di sini, sama seperti di metode statistik()
        $data = [
            'selectedMonth' => $monthName,
            'selectedYear' => $year,
            // ... (kirim data lain yang diperlukan oleh PDF, termasuk $allUsersMap dan $komselMap)
        ];
        
        $pdf = Pdf::loadView('statistik_pdf', $data);
        return $pdf->download("statistik-komsel-{$monthName}-{$year}.pdf");
    }
}