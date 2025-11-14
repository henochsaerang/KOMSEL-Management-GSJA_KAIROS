@extends('layouts.app')

@section('title', 'Dasbor Statistik')

@section('konten')
    {{-- Filter Bulan dan Tahun --}}
    <div class="row mb-4">
        <div class="col-12">
            <form action="{{ route('statistik') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <h5 class="fw-bold mb-0 me-2">Filter Data:</h5>
                <select name="month" class="form-select" style="max-width: 150px;">
                    @foreach ($months as $num => $name)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="form-select" style="max-width: 120px;">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    {{-- KONTEN UTAMA: KARTU STATISTIK --}}

    <!-- === ROW 1: KPI METRICS === -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title text-uppercase">Total Pertemuan Selesai</h6>
                    <div class="fs-1 fw-bold">{{ $schedulesTerlaksana }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-bg-info h-100">
                <div class="card-body">
                    <h6 class="card-title text-uppercase">Total Kehadiran</h6>
                    <div class="fs-1 fw-bold">{{ $grandTotalAttendance }}</div>
                    <small>Rata-rata: {{ $averageAttendance }} / pertemuan</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-bg-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title text-uppercase">Total Kehadiran Tamu</h6>
                    <div class="fs-1 fw-bold">{{ $totalGuestAttendance }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <h6 class="card-title text-uppercase">KOMSEL Teraktif</h6>
                    <div class="fs-2 fw-bold">{{ $komselTeraktif ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- === ROW 2: MAIN CHARTS === -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-graph-up me-2"></i>Tren Kehadiran Harian</h5>
                    <div style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2"></i>Realisasi Jadwal</h5>
                    @if ($totalSchedules > 0)
                        <div style="height: 300px;" class="my-auto">
                            <canvas id="realisasiChart"></canvas>
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                            <i class="bi bi-calendar-x-fill fs-1 mb-3"></i>
                            <p class="fw-bold">Belum ada data jadwal.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- === ROW 3: DETAIL CHARTS === -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-bar-chart-steps me-2"></i>Total Kehadiran per KOMSEL</h5>
                    @if ($attendanceByKomsel->isNotEmpty())
                        <div style="height: 250px;">
                            <canvas id="komselChart"></canvas>
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                            <i class="bi bi-bar-chart-line-fill fs-1 mb-3"></i>
                            <p class="fw-bold text-center">Belum ada data kehadiran.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3"><i class="bi bi-person-check-fill me-2"></i>Peringkat Kehadiran Anggota</h5>
                    @if ($topAttendees->isNotEmpty())
                        <div style="height: 250px;">
                            <canvas id="topAttendeesChart"></canvas>
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                            <i class="bi bi-graph-up-arrow fs-1 mb-3"></i>
                            <p class="fw-bold text-center">Belum ada data kehadiran anggota.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const getCssVariable = (variable) => getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        const primaryColor = getCssVariable('--primary-color');

        // 1. Tren Kehadiran Harian (Line/Area Chart)
        const ctxTrend = document.getElementById('trendChart');
        if (ctxTrend) {
            new Chart(ctxTrend.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($attendanceTrendLabels),
                    datasets: [{
                        label: 'Total Hadir',
                        data: @json($attendanceTrendData),
                        backgroundColor: primaryColor + '33',
                        borderColor: primaryColor,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: getCssVariable('--border-color') }, ticks: { color: getCssVariable('--text-secondary'), precision: 0 } }, x: { grid: { display: false }, ticks: { color: getCssVariable('--text-secondary') } } }, plugins: { legend: { display: false } } }
            });
        }

        // 2. Realisasi Jadwal (Donut Chart)
        const ctxRealisasi = document.getElementById('realisasiChart');
        if (ctxRealisasi && {{ $totalSchedules }} > 0) {
            new Chart(ctxRealisasi.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Terlaksana', 'Dibatalkan', 'Menunggu', 'Berlangsung'],
                    datasets: [{
                        data: [{{ $schedulesTerlaksana }}, {{ $schedulesDibatalkan }}, {{ $schedulesDitunda }}, {{ $schedulesBerlangsung }}],
                        backgroundColor: ['#198754', '#dc3545', '#ffc107', '#0dcaf0'],
                        borderColor: getCssVariable('--element-bg'),
                        borderWidth: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: getCssVariable('--text-secondary'), usePointStyle: true } } } }
            });
        }

        // 3. Kehadiran per KOMSEL (Horizontal Bar Chart)
        const ctxKomsel = document.getElementById('komselChart');
        if (ctxKomsel && @json($komselChartData).length > 0) {
            new Chart(ctxKomsel.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($komselChartLabels),
                    datasets: [{
                        label: 'Total Kehadiran',
                        data: @json($komselChartData),
                        backgroundColor: primaryColor + '80',
                        borderColor: primaryColor,
                        borderWidth: 2
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, grid: { color: getCssVariable('--border-color') }, ticks: { color: getCssVariable('--text-secondary'), precision: 0 } }, y: { grid: { display: false }, ticks: { color: getCssVariable('--text-secondary') } } }, plugins: { legend: { display: false } } }
            });
        }

        // 4. Peringkat Kehadiran Anggota (Vertical Bar Chart)
        const ctxTopAttendees = document.getElementById('topAttendeesChart');
        if (ctxTopAttendees && @json($topAttendeesData).length > 0) {
            new Chart(ctxTopAttendees.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($topAttendeesLabels),
                    datasets: [{
                        label: 'Jumlah Kehadiran',
                        data: @json($topAttendeesData),
                        backgroundColor: primaryColor + '80',
                        borderColor: primaryColor,
                        borderWidth: 2
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: getCssVariable('--border-color') }, ticks: { color: getCssVariable('--text-secondary'), precision: 0 } }, x: { grid: { display: false }, ticks: { color: getCssVariable('--text-secondary') } } }, plugins: { legend: { display: false } } }
            });
        }
    });
</script>
@endpush