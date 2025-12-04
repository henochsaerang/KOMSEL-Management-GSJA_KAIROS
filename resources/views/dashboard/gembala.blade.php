@extends('layouts.app')

@section('title', 'Panel Eksekutif Gembala')

@push('styles')
<style>
    /* DARK MODE COMPATIBLE */
    body { background-color: var(--bs-body-bg); }

    /* === STAT CARD === */
    .stat-card {
        border: 1px solid var(--border-color); border-radius: 16px;
        background-color: var(--element-bg); height: 100%;
        transition: transform 0.2s; box-shadow: var(--shadow-sm);
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .stat-link { text-decoration: none; color: inherit; display: block; height: 100%; }

    /* === PROFILE LISTS === */
    .personnel-card {
        background-color: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 16px; overflow: hidden; height: 100%;
        display: flex; flex-direction: column;
    }
    .personnel-header {
        padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color);
        font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;
    }
    .personnel-list {
        flex-grow: 1; overflow-y: auto; max-height: 400px; /* Scrollable list */
    }
    .personnel-item {
        padding: 0.8rem 1.25rem; border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 12px;
    }
    .personnel-item:last-child { border-bottom: none; }
    
    /* Avatar */
    .avatar-xs {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.8rem; color: white;
    }

    /* Coordinator Highlight */
    .coord-card {
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: white; border: none; border-radius: 16px;
        padding: 2rem; position: relative; overflow: hidden;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
    }
    .coord-card::after {
        content: ''; position: absolute; right: -20px; bottom: -40px;
        width: 150px; height: 150px; background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    /* Text Colors */
    .text-adaptive { color: var(--bs-body-color); }
    .text-muted-adaptive { color: var(--text-secondary); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5">
    
    {{-- HEADER SECTION --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-5 gap-3">
        <div>
            <h2 class="fw-bold text-body mb-1">Dashboard Eksekutif</h2>
            <p class="text-secondary mb-0">
                Selamat datang, 
                <span class="text-primary fw-bold">Gembala {{ Auth::user()->name }}</span>
            </p>
        </div>
        <span class="badge bg-dark px-3 py-2 rounded-pill shadow-sm border border-secondary">
            <i class="bi bi-shield-lock-fill me-2"></i>Super Admin Access
        </span>
    </div>

    {{-- STATISTIK UTAMA (SAMA SEPERTI SEBELUMNYA) --}}
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card p-4 d-flex flex-row align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-4"><i class="bi bi-people-fill"></i></div>
                <div>
                    <h6 class="text-uppercase text-secondary small fw-bold mb-1">Total Jiwa</h6>
                    <h3 class="fw-bold mb-0 text-body">{{ number_format($totalJemaat) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('daftarKomsel') }}" class="stat-link">
                <div class="card stat-card p-4 d-flex flex-row align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 fs-4"><i class="bi bi-diagram-3-fill"></i></div>
                    <div>
                        <h6 class="text-uppercase text-secondary small fw-bold mb-1">Komsel Aktif</h6>
                        <h3 class="fw-bold mb-0 text-body">{{ number_format($totalKomsel) }}</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card p-4 d-flex flex-row align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 fs-4"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <h6 class="text-uppercase text-secondary small fw-bold mb-1">Leaders</h6>
                    <h3 class="fw-bold mb-0 text-body">{{ number_format($totalLeaders) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card p-0 overflow-hidden border-0 h-100">
                <div class="p-3 d-flex align-items-center gap-3 border-bottom">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2 fs-5"><i class="bi bi-heart-fill"></i></div>
                    <div>
                        <div class="small fw-bold text-secondary text-uppercase">Total OIKOS</div>
                        <div class="fw-bold fs-5 text-body">{{ number_format($oikosStats->total ?? 0) }}</div>
                    </div>
                </div>
                <div class="d-flex text-center bg-light small">
                    <div class="flex-fill py-2 border-end"><span class="text-success fw-bold">{{ $oikosStats->berhasil ?? 0 }}</span> <span class="text-muted d-block" style="font-size:0.6rem">Berhasil</span></div>
                    <div class="flex-fill py-2 border-end"><span class="text-warning fw-bold">{{ $oikosStats->proses ?? 0 }}</span> <span class="text-muted d-block" style="font-size:0.6rem">Proses</span></div>
                    <div class="flex-fill py-2"><span class="text-danger fw-bold">{{ $oikosStats->gagal ?? 0 }}</span> <span class="text-muted d-block" style="font-size:0.6rem">Gagal</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: STRUKTUR ORGANISASI --}}
    <h5 class="fw-bold text-body mb-4"><i class="bi bi-diagram-2-fill me-2 text-primary"></i>Struktur Pelayanan</h5>

    <div class="row g-4">
        {{-- 1. KOORDINATOR KOMSEL (FULL WIDTH / HIGHLIGHTED) --}}
        <div class="col-12 mb-2">
            @if($coordinator)
                <div class="coord-card d-flex flex-column flex-md-row align-items-center gap-4">
                    <div class="bg-white bg-opacity-25 p-1 rounded-circle">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 80px; height: 80px;">
                            {{ substr($coordinator->nama, 0, 1) }}
                        </div>
                    </div>
                    <div class="text-center text-md-start z-1">
                        <div class="badge bg-warning text-dark fw-bold mb-2"><i class="bi bi-star-fill me-1"></i> Koordinator Utama</div>
                        <h3 class="fw-bold mb-1">{{ $coordinator->nama }}</h3>
                        <p class="mb-0 opacity-75">{{ $coordinator->email ?? '-' }}</p>
                        <p class="mb-0 small opacity-50 mt-1">Mengawasi seluruh aktivitas Komsel & Oikos.</p>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary border-0 shadow-sm rounded-4 text-center py-4">
                    <i class="bi bi-exclamation-circle display-4 mb-2 d-block opacity-50"></i>
                    <h6 class="fw-bold">Belum Ada Koordinator</h6>
                    <p class="small mb-0">Sistem belum mendeteksi user dengan akses lengkap (Super Admin + Panel User + Leader).</p>
                </div>
            @endif
        </div>

        {{-- 2. LEADERS LIST --}}
        <div class="col-lg-4">
            <div class="personnel-card">
                <div class="personnel-header bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-person-badge-fill me-2"></i> Daftar Leaders ({{ $leaders->count() }})
                </div>
                <div class="personnel-list custom-scrollbar">
                    @forelse($leaders as $leader)
                        <div class="personnel-item">
                            <div class="avatar-xs bg-primary">
                                {{ substr($leader->nama, 0, 1) }}
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-adaptive text-truncate" title="{{ $leader->nama }}">{{ $leader->nama }}</div>
                                <div class="small text-muted-adaptive">Komsel Leader</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small">Tidak ada data leader.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 3. PARTNERS LIST --}}
        <div class="col-lg-4">
            <div class="personnel-card">
                <div class="personnel-header bg-success bg-opacity-10 text-success">
                    <i class="bi bi-person-heart me-2"></i> Daftar Partners ({{ $partners->count() }})
                </div>
                <div class="personnel-list custom-scrollbar">
                    @forelse($partners as $partner)
                        <div class="personnel-item">
                            <div class="avatar-xs bg-success">
                                {{ substr($partner->nama, 0, 1) }}
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-adaptive text-truncate" title="{{ $partner->nama }}">{{ $partner->nama }}</div>
                                <div class="small text-muted-adaptive">Partner Pelayanan</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small">Tidak ada data partner.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 4. ORANG TUA ROHANI LIST --}}
        <div class="col-lg-4">
            <div class="personnel-card">
                <div class="personnel-header bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people-fill me-2"></i> Orang Tua Rohani ({{ $otr->count() }})
                </div>
                <div class="personnel-list custom-scrollbar">
                    @forelse($otr as $ot)
                        <div class="personnel-item">
                            <div class="avatar-xs bg-info">
                                {{ substr($ot->nama, 0, 1) }}
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-adaptive text-truncate" title="{{ $ot->nama }}">{{ $ot->nama }}</div>
                                <div class="small text-muted-adaptive">Pembimbing Rohani</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small">Tidak ada data OTR.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection