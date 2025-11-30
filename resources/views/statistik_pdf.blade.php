<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Statistik</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #4f46e5; font-size: 24px; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; background-color: #eef2ff; padding: 8px; border-left: 4px solid #4f46e5; margin-bottom: 15px; color: #333; }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f9fafb; font-weight: bold; color: #555; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .summary-box { width: 100%; margin-bottom: 20px; }
        .summary-item { float: left; width: 33%; text-align: center; margin-bottom: 10px; }
        .summary-val { font-size: 20px; font-weight: bold; color: #4f46e5; display: block; }
        .summary-label { font-size: 10px; text-transform: uppercase; color: #888; }
        .clearfix::after { content: ""; clear: both; display: table; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 10px; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN STATISTIK PELAYANAN</h1>
        <p>Periode: {{ $monthName }} {{ $year }}</p>
        <p>Dicetak Oleh: {{ $user->name }} pada {{ date('d M Y H:i') }}</p>
    </div>

    <!-- RINGKASAN -->
    <div class="section">
        <div class="summary-box clearfix">
            <div class="summary-item">
                <span class="summary-val">{{ $schedulesTerlaksana }}</span>
                <span class="summary-label">Pertemuan Selesai</span>
            </div>
            <div class="summary-item">
                <span class="summary-val">{{ $grandTotalAttendance }}</span>
                <span class="summary-label">Total Kehadiran</span>
            </div>
            <div class="summary-item">
                <span class="summary-val">{{ $averageAttendance }}</span>
                <span class="summary-label">Rata-rata Hadir</span>
            </div>
        </div>
    </div>

    <!-- STATISTIK JADWAL -->
    <div class="section">
        <div class="section-title">1. Statistik Jadwal & Ibadah</div>
        <table>
            <tr>
                <td width="60%">Total Jadwal Terencana</td>
                <td class="text-right font-bold">{{ $totalSchedules }}</td>
            </tr>
            <tr>
                <td>Jadwal Terlaksana</td>
                <td class="text-right">{{ $schedulesTerlaksana }}</td>
            </tr>
            <tr>
                <td>Jadwal Dibatalkan/Gagal</td>
                <td class="text-right">{{ $schedulesDibatalkan }}</td>
            </tr>
            <tr>
                <td>Jadwal Menunggu/Berlangsung</td>
                <td class="text-right">{{ $schedulesDitunda + $schedulesBerlangsung }}</td>
            </tr>
            <tr>
                <td colspan="2" style="background: #fafafa; height: 5px;"></td>
            </tr>
            <tr>
                <td>Total Kehadiran Anggota</td>
                <td class="text-right">{{ $totalRegisteredAttendance }}</td>
            </tr>
            <tr>
                <td>Total Kehadiran Tamu</td>
                <td class="text-right">{{ $totalGuestAttendance }}</td>
            </tr>
        </table>
    </div>

    <!-- STATISTIK OIKOS -->
    <div class="section">
        <div class="section-title">2. Statistik OIKOS</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Target OIKOS Total</td>
                    <td class="text-right font-bold">{{ $totalOikos }}</td>
                    <td class="text-right">100%</td>
                </tr>
                <tr>
                    <td>Berhasil / Selesai</td>
                    <td class="text-right">{{ $oikosSelesai }}</td>
                    <td class="text-right">{{ $totalOikos > 0 ? round(($oikosSelesai / $totalOikos) * 100) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Dalam Proses (Terjadwal/Revisi)</td>
                    <td class="text-right">{{ $oikosProses }}</td>
                    <td class="text-right">{{ $totalOikos > 0 ? round(($oikosProses / $totalOikos) * 100) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Gagal / Batal</td>
                    <td class="text-right">{{ $oikosGagal }}</td>
                    <td class="text-right">{{ $totalOikos > 0 ? round(($oikosGagal / $totalOikos) * 100) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TOP KOMSEL -->
    <div class="section">
        <div class="section-title">3. Komunitas Sel Teraktif (Top 10)</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th>Nama KOMSEL</th>
                    <th class="text-right">Total Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 1; @endphp
                @foreach($attendanceByKomsel->take(10) as $nama => $total)
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ $nama }}</td>
                    <td class="text-right font-bold">{{ $total }}</td>
                </tr>
                @endforeach
                @if($attendanceByKomsel->isEmpty())
                <tr>
                    <td colspan="3" style="text-align: center; color: #999;">Tidak ada data kehadiran.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem KOMSEL KAIROS.
    </div>

</body>
</html>