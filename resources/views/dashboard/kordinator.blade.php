@extends('layouts.app')

@section('title', 'Dashboard Koordinator')

@push('styles')
<style>
    /* === WELCOME & KPI CARDS (Sama dengan Gembala/Leader) === */
    .welcome-card {
        background: linear-gradient(135deg, #0ea5e9, #2563eb); /* Biru Khas Koordinator */
        color: white; border: none; border-radius: 1rem; overflow: hidden; position: relative;
        box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
    }
    .welcome-card::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    .kpi-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;
        height: 100%; display: flex; align-items: center; box-shadow: var(--shadow-sm);
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-color); }
    .kpi-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-right: 1rem; flex-shrink: 0; }

    /* === CHART & TABLE === */
    .chart-card { background-color: var(--element-bg); border: 1px solid var(--border-color); border-radius: 1rem; box-shadow: var(--shadow-sm); }
    .chart-header { border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; }
    
    .table-monitoring thead th { 
        background-color: var(--element-bg-subtle); color: var(--text-secondary);
        font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color);
        padding: 1rem;
    }
    .table-monitoring tbody td {
        padding: 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color);
    }
    
    .avatar-initials-sm {
        width: 28px; height: 28px; font-size: 0.7rem; font-weight: 700;
        background-color: var(--primary-color); color: white; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
    }
    
    .progress-thin { height: 6px; border-radius: 10px; background-color: var(--hover-bg); }
    
    .text-adaptive { color: var(--bs-body-color); }
    .text-muted-adaptive { color: var(--text-secondary); }
</style>
@endpush

@section('konten')

    {{-- 1. WELCOME CARD --}}
    <div class="card welcome-card mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between position-relative z-1">
            <div>
                <h2 class="fw-bold mb-1">Panel Koordinator</h2>
                <p class="mb-0 opacity-90">Halo <strong>{{ Auth::user()->name }}</strong>, berikut adalah laporan kinerja seluruh Komsel.</p>
            </div>
            <div class="d-none d-md-block"><i class="bi bi-bar-chart-steps fs-1 opacity-50"></i></div>
        </div>
    </div>

    {{-- 2. KPI CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-diagram-3-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Total Komsel</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $totalKomsel }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Total Jiwa</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ number_format($totalJemaat) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Leader Aktif</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $activeLeadersCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-clipboard-data-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Oikos Selesai</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $oikosStats->selesai ?? 0 }} <small class="fs-6 text-muted fw-normal">/ {{ $oikosStats->total ?? 0 }}</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- 3. CHART (GLOBAL TREND) --}}
        <div class="col-lg-8">
            <div class="chart-card h-100 d-flex flex-column">
                <div class="chart-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-adaptive mb-0">Tren Kehadiran Global</h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary">4 Minggu Terakhir</span>
                </div>
                <div class="card-body p-4 flex-grow-1">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="coordChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. QUICK STATUS --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="background-color: var(--element-bg);">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold text-adaptive mb-0">Status OIKOS</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3"><i class="bi bi-hourglass-split"></i></div>
                                <span class="fw-medium text-adaptive">Sedang Proses</span>
                            </div>
                            <span class="fw-bold">{{ $oikosStats->proses ?? 0 }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3"><i class="bi bi-check2-circle"></i></div>
                                <span class="fw-medium text-adaptive">Selesai</span>
                            </div>
                            <span class="fw-bold">{{ $oikosStats->selesai ?? 0 }}</span>
                        </div>
                    </div>
                    
                    <div class="p-4 mt-2 border-top" style="border-color: var(--border-color)!important;">
                        <h6 class="text-muted-adaptive small fw-bold text-uppercase mb-3">Menu Cepat</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.komselAktif') }}" class="btn btn-outline-primary fw-bold"><i class="bi bi-eye me-2"></i>Monitor Detail Komsel</a>
                            <a href="{{ route('jadwal') }}" class="btn btn-outline-secondary fw-bold"><i class="bi bi-calendar-week me-2"></i>Lihat Semua Jadwal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. TABEL MONITORING KOMSEL --}}
    <div class="row mt-4 mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background-color: var(--element-bg);">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-adaptive mb-0">Monitoring Kesehatan Komsel</h6>
                    <button class="btn btn-sm btn-light border"><i class="bi bi-download me-1"></i> Export</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-monitoring">
                        <thead>
                            <tr>
                                <th class="ps-4">Nama Komsel</th>
                                <th>Leader</th>
                                <th class="text-center">Anggota</th>
                                <th class="text-center">Rata2 Hadir</th>
                                <th style="width: 25%;">Kesehatan (Attendance Rate)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monitoringData as $data)
                            <tr>
                                <td class="ps-4 fw-bold text-adaptive">{{ $data->nama }}</td>
                                <td class="text-secondary small">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials-sm bg-secondary me-2">{{ $data->leader_initial }}</div>
                                        {{ $data->leader }}
                                    </div>
                                </td>
                                <td class="text-center text-adaptive">{{ $data->members }}</td>
                                <td class="text-center fw-bold text-adaptive">{{ $data->avg_attendance }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 progress-thin me-3">
                                            @php 
                                                $color = 'bg-success';
                                                if($data->rate < 50) $color = 'bg-danger';
                                                elseif($data->rate < 75) $color = 'bg-warning';
                                            @endphp
                                            <div class="progress-bar {{ $color }}" role="progressbar" style="width: {{ $data->rate }}%"></div>
                                        </div>
                                        <span class="small fw-bold {{ str_replace('bg-', 'text-', $color) }}">{{ $data->rate }}%</span>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="#" class="btn btn-sm btn-light rounded-circle"><i class="bi bi-chevron-right"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">Belum ada data komsel.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('coordChart');
        if (ctx) {
            const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#0ea5e9';
            const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();
            
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($attendanceChartLabels ?? []),
                    datasets: [{
                        label: 'Total Kehadiran Global',
                        data: @json($attendanceChartData ?? []),
                        backgroundColor: primaryColor,
                        borderRadius: 4,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { display: true, color: gridColor, drawBorder: false } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endpush