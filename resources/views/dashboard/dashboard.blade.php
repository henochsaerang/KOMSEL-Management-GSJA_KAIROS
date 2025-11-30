@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    /* === DASHBOARD STYLES === */
    /* Welcome Card */
    .welcome-card {
        background: linear-gradient(135deg, var(--primary-color), #4338ca);
        color: white; border: none; border-radius: 1rem; overflow: hidden; position: relative;
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
    }
    .welcome-card::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    /* KPI Cards (Interactive) */
    .kpi-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;
        height: 100%; display: flex; align-items: center; box-shadow: var(--shadow-sm);
        cursor: pointer; /* Menandakan bisa diklik */
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }
    .kpi-icon {
        width: 56px; height: 56px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.75rem; margin-right: 1rem; flex-shrink: 0;
    }

    /* Chart Card */
    .chart-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; box-shadow: var(--shadow-sm);
    }
    .chart-header { border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; }
    .list-group-item {
        background-color: var(--element-bg); border-color: var(--border-color); color: var(--bs-body-color);
        padding: 1rem 1.25rem;
    }
    .btn-quick-action {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem;
        border-radius: 0.75rem; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;
    }
    
    /* Modal Specifics */
    .modal-content { background-color: var(--element-bg); color: var(--bs-body-color); border: 1px solid var(--border-color); }
    .modal-header { border-bottom: 1px solid var(--border-color); }
    .modal-footer { border-top: 1px solid var(--border-color); }
    
    /* Komsel Detail Card inside Modal */
    .komsel-detail-card {
        background-color: var(--element-bg-subtle);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        height: 100%;
    }
    .komsel-detail-number { font-size: 1.5rem; font-weight: 800; color: var(--primary-color); line-height: 1; }
    .komsel-detail-label { font-size: 0.85rem; font-weight: 600; color: var(--bs-body-color); margin-bottom: 4px; }

    .text-adaptive { color: var(--bs-body-color); }
    .text-muted-adaptive { color: var(--text-secondary); }
</style>
@endpush

@section('konten')

    {{-- WELCOME --}}
    <div class="card welcome-card mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between position-relative z-1">
            <div>
                <h2 class="fw-bold mb-1">
                    Selamat Datang, 
                    @if (in_array('super_admin', Auth::user()->roles)) Admin! @elseif (in_array('Leaders', Auth::user()->roles)) Leader! @else {{ explode(' ', Auth::user()->name)[0] }}! @endif
                </h2>
                <p class="mb-0 opacity-75">Berikut adalah ringkasan aktivitas pelayanan KOMSEL & OIKOS.</p>
            </div>
            <div class="d-none d-md-block"><i class="bi bi-stars fs-1 opacity-50"></i></div>
        </div>
    </div>

    {{-- NOTIFIKASI REVISI --}}
    @if (isset($oikosRevisiUntukUser) && $oikosRevisiUntukUser->isNotEmpty())
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-start" role="alert" style="background-color: rgba(255, 193, 7, 0.15); color: #856404;">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 mt-1 text-warning"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading fw-bold mb-1">Perhatian: Revisi Diperlukan</h5>
                <p class="mb-2 small">Terdapat <strong>{{ $oikosRevisiUntukUser->count() }} laporan OIKOS</strong> yang perlu diperbaiki.</p>
                <a href="{{ route('oikos') }}" class="btn btn-sm btn-warning fw-bold rounded-pill px-3">Periksa Sekarang</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI CARDS (CLICKABLE) --}}
    <div class="row g-4 mb-4">
        <!-- 1. Total Anggota (Trigger Modal Anggota) -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalAnggota">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Pengguna</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $totalAnggota ?? 0 }}</div>
                </div>
            </div>
        </div>
        
        <!-- 2. OIKOS Bulan Ini (Trigger Modal OIKOS) -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalOikos">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-house-heart-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">OIKOS (Bulan Ini)</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $oikosBulanIni ?? 0 }}</div>
                </div>
            </div>
        </div>

        <!-- 3. Total Komsel (Trigger Modal Komsel) -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalKomsel">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-collection-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Total KOMSEL</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $totalKomsel ?? 0 }}</div>
                </div>
            </div>
        </div>

        <!-- 4. Rata-rata Kehadiran (No Modal, Stat Only) -->
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" style="cursor: default;">
                <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Rata-rata Hadir</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $averageAttendance ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    @if (Auth::check() && !empty(Auth::user()->roles))
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="chart-card h-100 d-flex flex-column">
                    <div class="chart-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-adaptive mb-0">Grafik Kehadiran (4 Minggu Terakhir)</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Real-time</span>
                    </div>
                    <div class="card-body p-4 flex-grow-1">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="dashboardKehadiranChart"></canvas>
                        </div>
                        <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color)!important;">
                            <h6 class="text-muted-adaptive small fw-bold text-uppercase mb-3">Aksi Cepat</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('formInput') }}" class="btn btn-outline-primary btn-quick-action"><i class="bi bi-plus-lg"></i> Tambah OIKOS</a>
                                <a href="{{ route('jadwal') }}" class="btn btn-outline-success btn-quick-action"><i class="bi bi-calendar-plus"></i> Buat Jadwal</a>
                                <a href="{{ route('kunjungan.create') }}" class="btn btn-outline-info btn-quick-action"><i class="bi bi-person-walking"></i> Catat Kunjungan</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <div class="chart-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-adaptive mb-0">Jadwal Mendatang</h6>
                        <a href="{{ route('jadwal') }}" class="text-primary text-decoration-none small fw-bold">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($upcomingSchedules ?? [] as $schedule)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3 d-flex flex-column align-items-center justify-content-center text-center" style="width: 50px; height: 50px;">
                                            <small class="d-block fw-bold lh-1" style="font-size: 0.65rem;">{{ \Carbon\Carbon::parse($schedule->time)->format('M') }}</small>
                                            <span class="d-block fw-bold fs-5 lh-1">{{ \Carbon\Carbon::parse($schedule->created_at)->format('d') }}</span>
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-bold text-adaptive text-truncate">{{ $schedule->komsel_name ?? 'Komsel' }}</div>
                                            <div class="small text-muted-adaptive text-truncate">
                                                <i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($schedule->time)->format('H:i') }}
                                                <span class="mx-1">•</span> {{ $schedule->location }}
                                            </div>
                                        </div>
                                    </div>
                                    @php $statusClass = match($schedule->status) { 'Menunggu' => 'bg-warning text-dark', 'Berlangsung' => 'bg-primary', default => 'bg-secondary' }; @endphp
                                    <span class="badge {{ $statusClass }} rounded-pill ms-2">{{ $schedule->status }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-center py-5">
                                    <div class="opacity-50 mb-2"><i class="bi bi-calendar-x fs-1 text-secondary"></i></div>
                                    <p class="text-muted-adaptive mb-0 small">Belum ada jadwal ibadah mendatang.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- JEMAAT VIEW --}}
        <div class="card border-0 shadow-sm text-center p-5" style="background: var(--element-bg);">
            <div class="mb-4"><div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-4"><i class="bi bi-emoji-smile fs-1"></i></div></div>
            <h3 class="fw-bold text-adaptive mb-2">Halo, {{ Auth::user()->name }}!</h3>
            <p class="text-muted-adaptive mb-4" style="max-width: 500px; margin: 0 auto;">Terima kasih telah menjadi bagian dari keluarga KOMSEL KAIROS.</p>
            <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger rounded-pill px-4"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form>
        </div>
    @endif

    {{-- MODAL 1: ANGGOTA --}}
    <div class="modal fade" id="modalAnggota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-adaptive">Anggota Komsel Anda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <ul class="list-group list-group-flush rounded-3 border" style="border-color: var(--border-color)!important;">
                        @forelse($myMembersPreview as $member)
                            <li class="list-group-item border-bottom" style="border-color: var(--border-color)!important;">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-person-fill small"></i></div>
                                    <span class="fw-medium text-adaptive">{{ $member->nama }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted-adaptive py-4">Tidak ada data anggota.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <a href="{{ route('daftarKomsel') }}" class="btn btn-primary w-100 rounded-pill fw-bold">Lihat Semua Anggota</a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: OIKOS --}}
    <div class="modal fade" id="modalOikos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-adaptive">OIKOS Terbaru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <ul class="list-group list-group-flush rounded-3 border" style="border-color: var(--border-color)!important;">
                        @forelse($myOikosPreview as $oikos)
                            <li class="list-group-item border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border-color)!important;">
                                <div>
                                    <div class="fw-medium text-adaptive">{{ $oikos->oikos_name }}</div>
                                    <small class="text-muted-adaptive">{{ \Carbon\Carbon::parse($oikos->start_date)->format('d M Y') }}</small>
                                </div>
                                <span class="badge bg-light text-secondary border">{{ $oikos->status }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted-adaptive py-4">Tidak ada data OIKOS terbaru.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <a href="{{ route('oikos') }}" class="btn btn-primary w-100 rounded-pill fw-bold">Kelola OIKOS</a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 3: KOMSEL --}}
    <div class="modal fade" id="modalKomsel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-adaptive">Detail Komunitas Sel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        @forelse($myKomselsDetails as $komsel)
                            <div class="col-6">
                                <div class="komsel-detail-card">
                                    <div class="komsel-detail-label text-truncate">{{ $komsel->nama }}</div>
                                    <div class="komsel-detail-number">{{ $komsel->member_count }}</div>
                                    <small class="text-muted-adaptive" style="font-size: 0.7rem;">Anggota</small>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted-adaptive py-4">Tidak ada data komsel.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light w-100 rounded-pill" data-bs-dismiss="modal" style="background-color: var(--hover-bg); color: var(--bs-body-color); border-color: var(--border-color);">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @if (Auth::check() && !empty(Auth::user()->roles))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Chart JS logic (sama seperti sebelumnya)
            const getCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            const ctx = document.getElementById('dashboardKehadiranChart');
            if (ctx) {
                const gridColor = getCssVar('--border-color'); 
                const textColor = getCssVar('--text-secondary');
                const primaryColor = getCssVar('--primary-color');

                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($attendanceChartLabels ?? []),
                        datasets: [{
                            label: 'Jumlah Hadir',
                            data: @json($attendanceChartData ?? []),
                            backgroundColor: primaryColor + '20', 
                            borderColor: primaryColor,
                            borderWidth: 2,
                            pointBackgroundColor: getCssVar('--element-bg'),
                            pointBorderColor: primaryColor,
                            pointHoverBackgroundColor: primaryColor,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, precision: 0 } },
                            x: { grid: { display: false }, ticks: { color: textColor } }
                        }
                    }
                });
            }
        });
    </script>
    @endif
@endpush