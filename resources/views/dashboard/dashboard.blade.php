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

    /* KPI Cards */
    .kpi-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s;
        height: 100%; display: flex; align-items: center; box-shadow: var(--shadow-sm);
        cursor: pointer; 
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

    /* Main Content Cards */
    .chart-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 1rem; box-shadow: var(--shadow-sm);
    }
    .chart-header { border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem; }
    .list-group-item {
        background-color: var(--element-bg); border-color: var(--border-color); color: var(--bs-body-color);
        padding: 1rem 1.25rem;
    }
    
    /* Quick Action Buttons */
    .btn-quick-action {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem;
        border-radius: 0.75rem; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;
    }

    /* Birthday Item Style */
    .birthday-item {
        background-color: #fff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
        text-decoration: none; /* Karena pakai tag A */
        color: inherit;
        height: 100%;
    }
    .birthday-item:hover {
        border-color: #dc3545;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.1);
        transform: translateY(-2px);
    }
    
    /* Style: Sudah Lewat */
    .birthday-item.passed {
        background-color: #f8f9fa;
        border-color: #e9ecef;
        opacity: 0.8;
    }
    .birthday-item.passed .avatar-circle {
        filter: grayscale(100%);
        opacity: 0.5;
    }
    
    /* Style: Hari Ini */
    .birthday-item.today {
        border: 2px solid #ff6b6b;
        background-color: #fff5f5;
    }

    /* Modal Specifics */
    .modal-content { background-color: var(--element-bg); color: var(--bs-body-color); border: 1px solid var(--border-color); }
    .modal-header { border-bottom: 1px solid var(--border-color); }
    .modal-footer { border-top: 1px solid var(--border-color); }

    /* Komsel Detail Card */
    .komsel-detail-card {
        background-color: var(--element-bg-subtle);
        border: 1px solid var(--border-color);
        border-radius: 12px; padding: 1rem; text-align: center; height: 100%;
    }
    .komsel-detail-number { font-size: 1.5rem; font-weight: 800; color: var(--primary-color); line-height: 1; }
    .komsel-detail-label { font-size: 0.85rem; font-weight: 600; color: var(--bs-body-color); margin-bottom: 4px; }

    .text-adaptive { color: var(--bs-body-color); }
    .text-muted-adaptive { color: var(--text-secondary); }
</style>
@endpush

@section('konten')

    {{-- 1. WELCOME CARD --}}
    <div class="card welcome-card mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between position-relative z-1">
            <div>
                <h2 class="fw-bold mb-1">
                    Selamat Datang, 
                    @if (in_array('super_admin', Auth::user()->roles ?? [])) Admin! 
                    @elseif (in_array('Leaders', Auth::user()->roles ?? [])) Leader! 
                    @else {{ explode(' ', Auth::user()->name)[0] }}! @endif
                </h2>
                <p class="mb-0 opacity-75">Berikut adalah ringkasan aktivitas pelayanan KOMSEL & OIKOS.</p>
            </div>
            <div class="d-none d-md-block"><i class="bi bi-stars fs-1 opacity-50"></i></div>
        </div>
    </div>

    {{-- 2. NOTIFIKASI REVISI --}}
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

    {{-- 3. KPI CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalAnggota">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Pengguna</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $totalAnggota ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalOikos">
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-house-heart-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">OIKOS (Bulan Ini)</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $oikosBulanIni ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card" data-bs-toggle="modal" data-bs-target="#modalKomsel">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-collection-fill"></i></div>
                <div>
                    <div class="text-muted-adaptive small fw-bold text-uppercase letter-spacing-1">Total KOMSEL</div>
                    <div class="fs-3 fw-bold text-adaptive">{{ $totalKomsel ?? 0 }}</div>
                </div>
            </div>
        </div>
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

    {{-- 4. MAIN CONTENT --}}
    <div class="row g-4 mb-4">
        
        {{-- LEFT COL: ULANG TAHUN (MENGGANTIKAN CHART) --}}
        <div class="col-lg-8">
            <div class="chart-card h-100 d-flex flex-column">
                {{-- Header Ulang Tahun --}}
                <div class="chart-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-gift-fill"></i>
                        </div>
                        <h6 class="fw-bold text-adaptive mb-0">Ulang Tahun Bulan Ini ({{ now()->format('F') }})</h6>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger">{{ isset($birthdayMembers) ? $birthdayMembers->count() : 0 }} Anggota</span>
                </div>

                {{-- Body Ulang Tahun --}}
                <div class="card-body p-4 flex-grow-1 d-flex flex-column">
                    
                    <div class="flex-grow-1 custom-scrollbar" style="min-height: 200px; max-height: 400px; overflow-y: auto; padding-right: 5px;">
                        @if(isset($birthdayMembers) && $birthdayMembers->isNotEmpty())
                            <div class="row g-3">
                                @foreach($birthdayMembers as $bday)
                                    @php
                                        // LOGIKA TANGGAL & STATUS
                                        $currentDay = (int) now()->day;
                                        $bdayDay = (int) $bday->hari_ultah;
                                        $isPassed = $bdayDay < $currentDay; // Sudah lewat tanggalnya
                                        $isToday = $bdayDay === $currentDay; // Ulang tahun hari ini!
                                    @endphp

                                    <div class="col-md-6">
                                        {{-- KLIK KARTU -> KE FORM KUNJUNGAN --}}
                                        {{-- Parameter: member_id & visit_type=HUT --}}
                                        <a href="{{ route('kunjungan.create', ['member_id' => $bday->id, 'visit_type' => 'HUT']) }}" 
                                           class="birthday-item {{ $isPassed ? 'passed' : '' }} {{ $isToday ? 'today' : '' }} text-decoration-none">
                                            
                                            {{-- Confetti: Hilang jika sudah lewat --}}
                                            @if(!$isPassed)
                                                <div style="position: absolute; top: -10px; right: -10px; font-size: 3rem; opacity: 0.05; transform: rotate(15deg);">🎉</div>
                                            @endif
                                            
                                            <div class="avatar-circle bg-gradient-danger text-white me-3 d-flex align-items-center justify-content-center rounded-circle fw-bold shadow-sm flex-shrink-0" 
                                                 style="width: 42px; height: 42px; font-size: 1rem; background: linear-gradient(45deg, #ff6b6b, #ff8787);">
                                                {{ $bday->avatar_initial }}
                                            </div>
                                            
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $bday->nama }}" style="max-width: 140px;">
                                                        {{ $bday->nama }}
                                                    </h6>
                                                    
                                                    {{-- BADGE STATUS --}}
                                                    @if($isPassed)
                                                        <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill ms-1" style="font-size: 0.6rem;">Selesai</span>
                                                    @elseif($isToday)
                                                        <span class="badge bg-danger text-white rounded-pill ms-1 border border-light shadow-sm" style="font-size: 0.6rem;">HARI INI!</span>
                                                    @else
                                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill ms-1" style="font-size: 0.6rem;">{{ $bday->umur }} Th</span>
                                                    @endif
                                                </div>
                                                
                                                <div class="small text-secondary d-flex align-items-center mt-1">
                                                    <i class="bi bi-cake2 me-1 {{ $isPassed ? 'text-secondary' : 'text-danger' }} opacity-75"></i> 
                                                    {{ $bday->tgl_lahir }}
                                                </div>
                                                
                                                <div class="small text-muted mt-0 fst-italic text-truncate" style="font-size: 0.75rem;">
                                                    {{ $bday->komsel_nama }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-muted opacity-75">
                                <i class="bi bi-emoji-smile display-4 mb-3 text-secondary opacity-25"></i>
                                <p>Tidak ada yang berulang tahun bulan ini di wilayah Anda.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer: Aksi Cepat --}}
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

        {{-- RIGHT COL: JADWAL MENDATANG --}}
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

    {{-- MODALS (Anggota, Oikos, Komsel) --}}
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
                                    <div class="komsel-detail-label text-truncate" title="{{ $komsel->nama }}">{{ $komsel->nama }}</div>
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