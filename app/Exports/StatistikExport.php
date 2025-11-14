<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\userKairos;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class StatistikExport implements FromCollection, WithHeadings, WithMapping
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        $schedulesQuery = Schedule::query()
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month);

        $stats = new \stdClass();
        $stats->totalSchedules = $schedulesQuery->count();
        $stats->schedulesTerlaksana = (clone $schedulesQuery)->where('status', 'Selesai')->count();
        $stats->schedulesDibatalkan = (clone $schedulesQuery)->where('status', 'Gagal')->count();
        $stats->schedulesDitunda = (clone $schedulesQuery)->where('status', 'Menunggu')->count();
        $stats->schedulesBerlangsung = (clone $schedulesQuery)->where('status', 'Berlangsung')->count();
        $stats->totalAnggotaAktif = userKairos::where('status', 'aktif')->count();

        return collect([$stats]);
    }

    public function headings(): array
    {
        return [
            'Total Jadwal',
            'Jadwal Terlaksana',
            'Jadwal Dibatalkan',
            'Jadwal Menunggu',
            'Jadwal Berlangsung',
            'Total Anggota Aktif',
        ];
    }

    public function map($stats): array
    {
        return [
            $stats->totalSchedules,
            $stats->schedulesTerlaksana,
            $stats->schedulesDibatalkan,
            $stats->schedulesDitunda,
            $stats->schedulesBerlangsung,
            $stats->totalAnggotaAktif,
        ];
    }
}
