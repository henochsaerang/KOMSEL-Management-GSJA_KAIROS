<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\GuestAttendance;
use App\Models\OikosVisit;
use App\Services\OldApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class StatistikExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $month;
    protected $year;
    protected $apiService;
    protected $session;

    public function __construct($month, $year, OldApiService $apiService, $session)
    {
        $this->month = $month;
        $this->year = $year;
        $this->apiService = $apiService;
        $this->session = $session;
    }

    public function collection()
    {
        // 1. Ambil Konteks User (Admin/Leader)
        $isSuperAdmin = $this->session->get('is_super_admin', false);
        $leaderKomselIds = $this->session->get('user_komsel_ids', []);
        $userId = Auth::id();

        // 2. Query Jadwal
        $schedulesQuery = Schedule::whereMonth('created_at', $this->month)
            ->whereYear('created_at', $this->year);

        if (!$isSuperAdmin) {
            $schedulesQuery->whereIn('komsel_id', $leaderKomselIds);
        }

        // Hitung Metrik Jadwal
        $totalSchedules = (clone $schedulesQuery)->count();
        $completedSchedules = (clone $schedulesQuery)->where('status', 'Selesai')->get();
        $completedIds = $completedSchedules->pluck('id');
        
        $schedulesTerlaksana = $completedSchedules->count();
        $schedulesDibatalkan = (clone $schedulesQuery)->where('status', 'Gagal')->count();
        $schedulesMenunggu = (clone $schedulesQuery)->where('status', 'Menunggu')->count();
        $schedulesBerlangsung = (clone $schedulesQuery)->where('status', 'Berlangsung')->count();

        // 3. Hitung Metrik Kehadiran
        $totalRegistered = Attendance::whereIn('schedule_id', $completedIds)->count();
        $totalGuest = GuestAttendance::whereIn('schedule_id', $completedIds)->count();
        $grandTotalAttendance = $totalRegistered + $totalGuest;
        $averageAttendance = $schedulesTerlaksana > 0 ? round($grandTotalAttendance / $schedulesTerlaksana, 2) : 0;

        // 4. Hitung Metrik OIKOS
        $oikosQuery = OikosVisit::whereMonth('start_date', $this->month)
            ->whereYear('start_date', $this->year);

        // Filter Oikos sesuai role (Sama seperti Controller)
        if (!$isSuperAdmin) {
            // Ambil data jemaat dari API untuk filter ID
            $jemaatAll = $this->apiService->getAllJemaat();
            $jemaatCollection = collect($jemaatAll);
            
            $myJemaatIds = $jemaatCollection->filter(function ($j) use ($leaderKomselIds) {
                $kId = is_array($j) ? ($j['komsel_id'] ?? null) : ($j->komsel_id ?? null);
                return in_array($kId, $leaderKomselIds);
            })->pluck('id');

            $oikosQuery->where(function($q) use ($myJemaatIds, $userId) {
                $q->whereIn('jemaat_id', $myJemaatIds)
                  ->orWhere('pelayan_user_id', $userId);
            });
        }

        $totalOikos = $oikosQuery->count();
        $oikosSelesai = (clone $oikosQuery)->where('status', 'Selesai')->count();
        $oikosProses = (clone $oikosQuery)->whereIn('status', ['Direncanakan', 'Diproses', 'Revisi'])->count();

        // 5. Hitung Total Anggota Aktif (Pengganti userKairos)
        // Kita hitung dari data API Jemaat
        $totalJemaat = 0;
        if ($isSuperAdmin) {
            // Jika admin, hitung semua jemaat di API
            $jemaatAll = $this->apiService->getAllJemaat();
            $totalJemaat = count($jemaatAll);
        } else {
            // Jika leader, hitung jemaat di komselnya saja
            if (!isset($jemaatCollection)) {
                $jemaatAll = $this->apiService->getAllJemaat();
                $jemaatCollection = collect($jemaatAll);
            }
            $totalJemaat = $jemaatCollection->filter(function ($j) use ($leaderKomselIds) {
                $kId = is_array($j) ? ($j['komsel_id'] ?? null) : ($j->komsel_id ?? null);
                return in_array($kId, $leaderKomselIds);
            })->count();
        }

        // Return sebagai Collection untuk Excel
        return collect([[
            'Periode' => Carbon::create(null, $this->month)->locale('id')->monthName . ' ' . $this->year,
            'Total Jadwal' => $totalSchedules,
            'Terlaksana' => $schedulesTerlaksana,
            'Gagal' => $schedulesDibatalkan,
            'Menunggu/Berlangsung' => $schedulesMenunggu + $schedulesBerlangsung,
            'Total Kehadiran' => $grandTotalAttendance,
            'Rata-rata Hadir' => $averageAttendance,
            'Total Target OIKOS' => $totalOikos,
            'OIKOS Berhasil' => $oikosSelesai,
            'OIKOS Proses' => $oikosProses,
            'Total Jemaat (Scope)' => $totalJemaat,
        ]]);
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Total Jadwal',
            'Jadwal Terlaksana',
            'Jadwal Gagal',
            'Jadwal Pending',
            'Total Kehadiran',
            'Rata-rata Kehadiran',
            'Target OIKOS',
            'OIKOS Berhasil',
            'OIKOS Proses',
            'Total Jemaat Aktif',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}