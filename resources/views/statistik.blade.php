@extends('layouts.app')

@section('title', 'Dasbor Statistik')

@push('styles')
<style>
    /* DARK MODE & BASE STYLES */
    body { background-color: var(--bs-body-bg); }

    /* KPI CARDS (Interactive) */
    .kpi-card {
        background-color: var(--element-bg);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.5rem;
        height: 100%;
        display: flex;
        align-items: center;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer; /* Indikator bisa diklik */
        position: relative;
        overflow: hidden;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }
    /* Active State Styles */
    .kpi-card.active {
        border-color: var(--primary-color);
        background-color: var(--primary-bg-subtle);
        box-shadow: 0 0 0 2px var(--primary-color);
    }
    .kpi-card.active .kpi-label { color: var(--primary-color); }
    
    .kpi-icon {
        width: 56px; height: 56px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.75rem; margin-right: 1.25rem; flex-shrink: 0;
    }
    .kpi-label {
        font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 0.25rem;
        transition: color 0.2s;
    }
    .kpi-value {
        font-size: 1.75rem; font-weight: 800; color: var(--bs-body-color); line-height: 1.2;
    }
    .kpi-subtext { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem; }

    /* SECTION HEADERS */
    .section-title {
        font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        color: var(--text-secondary); margin: 2rem 0 1rem 0; display: flex; align-items: center;
    }
    .section-title::after {
        content: ''; flex: 1; height: 1px; background: var(--border-color); margin-left: 1rem; opacity: 0.5;
    }

    /* CHART AREA */
    .chart-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; box-shadow: var(--shadow-sm); height: 100%; overflow: hidden;
    }
    .chart-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center;
    }
    .chart-title { font-size: 1rem; font-weight: 700; color: var(--bs-body-color); margin: 0; }

    /* FILTER BAR */
    .filter-bar {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem;
        box-shadow: var(--shadow-sm); display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
    }
    .form-select {
        background-color: var(--input-bg); border-color: var(--border-color); color: var(--bs-body-color);
        border-radius: 0.75rem; height: 42px; font-size: 0.9rem;
    }
    .form-select:focus { border-color: var(--primary-color); }
    .btn-filter { border-radius: 0.75rem; height: 42px; }
    
    .empty-state {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        height: 100%; min-height: 200px; color: var(--text-secondary); opacity: 0.7;
    }
</style>
@endpush

@section('konten')

    {{-- HEADER --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-body mb-1">Analisis Data</h3>
            <p class="text-secondary mb-0 small">Laporan statistik kehadiran, jadwal komsel, dan OIKOS</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="filter-bar">
        <div class="d-flex align-items-center text-body fw-bold me-auto">
            <i class="bi bi-funnel-fill me-2 text-primary"></i> Filter Periode:
        </div>
        <form action="{{ route('statistik') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1 justify-content-end">
            <select name="month" class="form-select shadow-none" style="width: 160px;">
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" class="form-select shadow-none" style="width: 120px;">
                @foreach ($years as $year)
                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm btn-filter">Terapkan</button>
        </form>
        
        <div class="border-start ps-3 ms-2 d-none d-lg-flex gap-2" style="border-color: var(--border-color)!important;">
            <a href="{{ route('statistik.export.excel', request()->all()) }}" class="btn btn-outline-success btn-filter d-flex align-items-center" title="Export Excel">
                <i class="bi bi-file-earmark-spreadsheet"></i>
            </a>
            <a href="{{ route('statistik.export.pdf', request()->all()) }}" class="btn btn-outline-danger btn-filter d-flex align-items-center" title="Export PDF">
                <i class="bi bi-file-earmark-pdf"></i>
            </a>
        </div>
    </div>

    {{-- 1. KPI CARDS: JADWAL (INTERACTIVE) --}}
    <div class="section-title">Statistik Ibadah (Klik untuk Grafik)</div>
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card active" onclick="updateChart('jadwal', this)">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Pertemuan Selesai</div>
                    <div class="kpi-value">{{ $schedulesTerlaksana }}</div>
                    <div class="kpi-subtext">Dari {{ $totalSchedules }} total jadwal</div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('kehadiran', this)">
                <div class="kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Total Kehadiran</div>
                    <div class="kpi-value">{{ $grandTotalAttendance }}</div>
                    <div class="kpi-subtext">Anggota + Tamu</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('tamu', this)">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <div class="kpi-label">Total Tamu</div>
                    <div class="kpi-value">{{ $totalGuestAttendance }}</div>
                    <div class="kpi-subtext">Partisipasi eksternal</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('komsel', this)">
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div style="min-width: 0; width: 100%;">
                    <div class="kpi-label">Komsel Teraktif</div>
                    <div class="kpi-value text-truncate" style="font-size: 1.4rem;" title="{{ $komselTeraktif }}">{{ $komselTeraktif ?? '-' }}</div>
                    <div class="kpi-subtext">Lihat Peringkat</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. KPI CARDS: OIKOS (INTERACTIVE - MOVED UP) --}}
    <div class="section-title">Laporan OIKOS (Klik untuk Grafik)</div>
    <div class="row g-4 mb-4">
        <!-- 1. Total Target -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('oikos_status', this)">
                <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-bullseye"></i>
                </div>
                <div>
                    <div class="kpi-label">Total Target</div>
                    <div class="kpi-value">{{ $totalOikos }}</div>
                    <div class="kpi-subtext">Jiwa OIKOS</div>
                </div>
            </div>
        </div>
        <!-- 2. Berhasil -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('oikos_status', this)">
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="kpi-label text-success">Berhasil/Selesai</div>
                    <div class="kpi-value text-success">{{ $oikosSelesai }}</div>
                    <div class="kpi-subtext">Jiwa dimenangkan</div>
                </div>
            </div>
        </div>
        <!-- 3. Dalam Proses -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('oikos_status', this)">
                <div class="kpi-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="kpi-label text-info">Dalam Proses</div>
                    <div class="kpi-value text-info">{{ $oikosProses }}</div>
                    <div class="kpi-subtext">Sedang berjalan</div>
                </div>
            </div>
        </div>
        <!-- 4. Gagal -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" onclick="updateChart('oikos_status', this)">
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="kpi-label text-danger">Batal/Gagal</div>
                    <div class="kpi-value text-danger">{{ $oikosGagal }}</div>
                    <div class="kpi-subtext">Perlu evaluasi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. DYNAMIC CHART SECTION (MAIN CHART) --}}
    <div class="row g-4 mb-4">
        {{-- Main Dynamic Chart --}}
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title" id="dynamicChartTitle">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i> Analisis Jadwal
                    </h5>
                </div>
                <div class="chart-body">
                    <div style="height: 350px; width: 100%;">
                        <canvas id="dynamicChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Side Chart (Always Trend) --}}
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="bi bi-graph-up me-2 text-info"></i> Tren Harian</h5>
                </div>
                <div class="chart-body">
                    <div style="height: 350px; width: 100%;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. DETAIL CHARTS (BOTTOM) --}}
    <div class="row g-4 mb-4">
        <!-- Bar Chart Komsel -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="bi bi-bar-chart-steps me-2 text-warning"></i> Total Kehadiran per KOMSEL</h5>
                </div>
                <div class="chart-body">
                    @if ($attendanceByKomsel->isNotEmpty())
                        <div style="height: 300px;">
                            <canvas id="komselChart"></canvas>
                        </div>
                    @else
                        <div class="empty-state" style="height: 300px;">
                            <i class="bi bi-bar-chart mb-3 fs-1"></i>
                            <p>Belum ada data kehadiran</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Attendees -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="bi bi-award-fill me-2 text-success"></i> Top 5 Kehadiran Anggota</h5>
                </div>
                <div class="chart-body">
                    @if ($topAttendees->isNotEmpty())
                        <div style="height: 300px;">
                            <canvas id="topAttendeesChart"></canvas>
                        </div>
                    @else
                        <div class="empty-state" style="height: 300px;">
                            <i class="bi bi-people fs-1 mb-3"></i>
                            <p>Belum ada data anggota</p>
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
        // --- 1. SETUP COLORS & VARS ---
        const getCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        let dynamicChartInstance = null;

        // --- 2. PREPARE DATA ---
        const chartDataSources = {
            'jadwal': {
                type: 'doughnut',
                title: 'Analisis Status Jadwal',
                icon: 'bi-calendar-check-fill text-primary',
                labels: ['Terlaksana', 'Dibatalkan', 'Menunggu', 'Berlangsung'],
                data: [{{ $schedulesTerlaksana }}, {{ $schedulesDibatalkan }}, {{ $schedulesDitunda }}, {{ $schedulesBerlangsung }}],
                colors: ['#10b981', '#ef4444', '#f59e0b', '#3b82f6'],
                hoverColors: ['#059669', '#dc2626', '#d97706', '#2563eb']
            },
            'kehadiran': {
                type: 'pie',
                title: 'Perbandingan Kehadiran',
                icon: 'bi-people-fill text-info',
                labels: ['Anggota Terdaftar', 'Tamu'],
                data: [{{ $totalRegisteredAttendance }}, {{ $totalGuestAttendance }}],
                colors: ['#6366f1', '#f59e0b'],
                hoverColors: ['#4f46e5', '#d97706']
            },
            'tamu': {
                type: 'bar',
                title: 'Rasio Tamu vs Anggota',
                icon: 'bi-person-plus-fill text-warning',
                labels: ['Anggota', 'Tamu'],
                data: [{{ $totalRegisteredAttendance }}, {{ $totalGuestAttendance }}],
                colors: ['#6366f180', '#f59e0b80'], 
                borders: ['#6366f1', '#f59e0b'],
                indexAxis: 'y'
            },
            'komsel': {
                type: 'bar',
                title: 'Peringkat Keaktifan Komsel',
                icon: 'bi-trophy-fill text-success',
                labels: @json($komselChartLabels),
                data: @json($komselChartData),
                colors: '#10b98180',
                borders: '#10b981',
                indexAxis: 'y'
            },
            // Data Chart OIKOS (Status)
            'oikos_status': {
                type: 'doughnut',
                title: 'Status Laporan OIKOS',
                icon: 'bi-house-heart-fill text-danger',
                labels: ['Berhasil/Selesai', 'Dalam Proses', 'Gagal/Batal'],
                data: [{{ $oikosSelesai }}, {{ $oikosProses }}, {{ $oikosGagal }}],
                colors: ['#198754', '#0dcaf0', '#dc3545'],
                hoverColors: ['#157347', '#31d2f2', '#bb2d3b']
            }
        };

        // --- 3. RENDER FUNCTION ---
        window.updateChart = function(key, element) {
            // Update Active State UI
            document.querySelectorAll('.kpi-card').forEach(card => card.classList.remove('active'));
            if(element) element.classList.add('active');

            // Get Data Config
            const config = chartDataSources[key];
            if(!config) return;

            // Update Title
            const titleEl = document.getElementById('dynamicChartTitle');
            titleEl.innerHTML = `<i class="bi ${config.icon} me-2"></i> ${config.title}`;

            // Destroy Old Chart
            if (dynamicChartInstance) dynamicChartInstance.destroy();

            // Scroll smoothly to chart ONLY on mobile
            if (window.innerWidth < 768) {
                document.getElementById('dynamicChartTitle').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Common Chart Options
            const ctx = document.getElementById('dynamicChart').getContext('2d');
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { color: getCssVar('--text-secondary'), padding: 20, usePointStyle: true } 
                    },
                    tooltip: {
                        backgroundColor: getCssVar('--element-bg'),
                        titleColor: getCssVar('--bs-body-color'),
                        bodyColor: getCssVar('--text-secondary'),
                        borderColor: getCssVar('--border-color'),
                        borderWidth: 1,
                    }
                },
                layout: { padding: { top: 20, bottom: 10 } }
            };

            // Specific Options for Bar vs Pie
            if (config.type === 'bar') {
                commonOptions.scales = {
                    x: { beginAtZero: true, grid: { color: getCssVar('--border-color') }, ticks: { color: getCssVar('--text-secondary') } },
                    y: { grid: { display: false }, ticks: { color: getCssVar('--text-secondary') } }
                };
                if(config.indexAxis) commonOptions.indexAxis = config.indexAxis;
            }

            // Construct Dataset
            const dataset = {
                data: config.data,
                backgroundColor: config.colors,
                borderColor: config.borders || getCssVar('--element-bg'),
                borderWidth: config.type === 'bar' ? 1 : 2,
                hoverOffset: 10
            };
            if(config.hoverColors) dataset.hoverBackgroundColor = config.hoverColors;

            // Render New Chart
            dynamicChartInstance = new Chart(ctx, {
                type: config.type,
                data: {
                    labels: config.labels,
                    datasets: [dataset]
                },
                options: commonOptions
            });
        };

        // --- 4. INIT DEFAULT CHART ---
        updateChart('jadwal', document.querySelector('.kpi-card')); 

        // --- 5. INIT STATIC TREND CHART ---
        const ctxTrend = document.getElementById('trendChart');
        if (ctxTrend) {
            new Chart(ctxTrend.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($attendanceTrendLabels),
                    datasets: [{
                        label: 'Total Hadir',
                        data: @json($attendanceTrendData),
                        backgroundColor: getCssVar('--primary-color') + '20',
                        borderColor: getCssVar('--primary-color'),
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: getCssVar('--element-bg'),
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: getCssVar('--border-color') }, ticks: { color: getCssVar('--text-secondary') } },
                        x: { grid: { display: false }, ticks: { color: getCssVar('--text-secondary') } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
@endpush