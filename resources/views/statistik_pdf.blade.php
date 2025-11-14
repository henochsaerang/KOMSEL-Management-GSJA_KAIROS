<!DOCTYPE html>
<html>
<head>
    <title>Statistik KOMSEL</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Statistik KOMSEL - {{ $monthName }} {{ $year }}</h1>
    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Jadwal</td>
                <td>{{ $totalSchedules }}</td>
            </tr>
            <tr>
                <td>Jadwal Terlaksana</td>
                <td>{{ $schedulesTerlaksana }}</td>
            </tr>
            <tr>
                <td>Jadwal Dibatalkan</td>
                <td>{{ $schedulesDibatalkan }}</td>
            </tr>
            <tr>
                <td>Jadwal Menunggu</td>
                <td>{{ $schedulesDitunda }}</td>
            </tr>
            <tr>
                <td>Jadwal Berlangsung</td>
                <td>{{ $schedulesBerlangsung }}</td>
            </tr>
            <tr>
                <td>Total Anggota Aktif</td>
                <td>{{ $totalAnggotaAktif }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
