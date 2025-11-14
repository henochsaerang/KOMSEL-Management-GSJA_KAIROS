@extends('layouts.app')

@section('title', 'Dashboard')

@section('konten')

    <div class="card bg-body-tertiary border-0 mb-4">
        <div class="card-body p-4">
            
            {{-- [FIX] Logika Selamat Datang Sesuai Role --}}
            <h4 class="fw-bold">
                Selamat Datang, 
                @if (in_array('super_admin', Auth::user()->roles))
                    Admin!
                @elseif (in_array('Leaders', Auth::user()->roles))
                    Leader!
                @else
                    {{ Auth::user()->name }}!
                @endif
            </h4>
            
            <p class="text-secondary mb-0">Berikut adalah ringkasan aktivitas KOMSEL & OIKOS KAIROS.</p>
        </div>
    </div>

    {{-- Kartu KPI --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <div>
                        <span class="fs-4 fw-bold">{{ $totalAnggota }}</span>
                        <div class="text-secondary small">Total Pengguna Login</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                {{-- [FIX HTML] Menambahkan div yang hilang --}}
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success-subtle text-success p-3 rounded-3 me-3">
                        <i class="bi bi-house-heart-fill fs-2"></i>
                    </div>
                    <div>
                        <span class="fs-4 fw-bold">{{ $oikosBulanIni }}</span>
                        <div class="text-secondary small">OIKOS Bulan Ini</div>
                    </div>
                </div> 
            </div> 
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning-subtle text-warning p-3 rounded-3 me-3">
                        <i class="bi bi-collection-fill fs-2"></i>
                    </div>
                    <div>
                        <span class="fs-4 fw-bold">{{ $totalKomsel }}</span>
                        <div class="text-secondary small">Total KOMSEL (API)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                {{-- [FIX HTML] Menambahkan div yang hilang --}}
                <div class="card-body d-flex align-items-center">
                    <div class="bg-info-subtle text-info p-3 rounded-3 me-3">
                        <i class="bi bi-bar-chart-line-fill fs-2"></i>
                    </div>
                    <div>
                        <span class="fs-4 fw-bold">{{ $averageAttendance }}</span>
                        <div class="text-secondary small">Kehadiran Rata-rata</div>
                    </div>
                </div> 
            </div> 
        </div>
    </div>


    {{-- Tampilkan konten ini HANYA jika user adalah Admin (punya roles) --}}
    @if (Auth::check() && !empty(Auth::user()->roles))
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold mb-3">Grafik Kehadiran (4 Minggu Terakhir)</h5>
                        <div class="flex-grow-1" style="min-height: 250px;">
                            <canvas id="dashboardKehadiranChart"></canvas>
                        </div>
                        <hr>
                        <h6 class="fw-semibold">Aksi Cepat</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('formInput') }}" class="btn btn-outline-primary"><i class="bi bi-plus-circle me-1"></i> Tambah OIKOS</a>
                            <a href="{{ route('jadwal') }}" class="btn btn-outline-secondary"><i class="bi bi-calendar-plus me-1"></i> Buat Jadwal Baru</a>
                        </div>
                    </div>
                </div>
            </div>

        
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title fw-bold mb-0">Jadwal KOMSEL</h5>
                            <a href="{{ route('jadwal') }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua</a>
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse ($upcomingSchedules as $schedule)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-medium">{{ $schedule->komsel_name }}</div>
                                        <small class="text-secondary">{{ \Carbon\Carbon::parse($schedule->created_at)->format('D, j M') }}, {{ $schedule->time }}</small><br>
                                        <small class="text-secondary">Lokasi: {{ $schedule->location }}</small>
                                    </div>
                                    @php
                                        $statusBadgeClass = $schedule->status == 'Menunggu' ? 'text-bg-warning' : 'text-bg-info';
                                    @endphp
                                    <span class="badge {{ $statusBadgeClass }}">{{ $schedule->status }}</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-secondary text-center">
                                    Belum ada jadwal KOMSEL mendatang.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Tampilkan pesan ini jika yang login adalah Jemaat (roles kosong) --}}
        <div class="card">
            <div class="card-body text-center p-5">
                <h4 class="fw-bold">Selamat Datang, Jemaat!</h4>
                <p class="text-secondary">Fitur dashboard Admin tidak tersedia untuk akun Anda.</p>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Logout</button>
                </form>
            </div>
        </div>
    @endif 

@endsection

@push('scripts')
    {{-- Script untuk ChartJS (Hanya jika Admin) --}}
    @if (Auth::check() && !empty(Auth::user()->roles))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getCssVariable = (variable) => getComputedStyle(document.documentElement).getPropertyValue(variable).trim();

            const ctxKehadiran = document.getElementById('dashboardKehadiranChart');
            if (ctxKehadiran) {
                new Chart(ctxKehadiran.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: @json($attendanceChartLabels),
                        datasets: [{
                            label: 'Jumlah Hadir',
                            data: @json($attendanceChartData),
                            backgroundColor: getCssVariable('--primary-color') + '33',
                            borderColor: getCssVariable('--primary-color'),
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: getCssVariable('--border-color') },
                                ticks: { color: getCssVariable('--text-secondary'), precision: 0 }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: getCssVariable('--text-secondary') }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        });
    </script>
    @endif
@endpush