@extends('layouts.app')

@section('title', 'Panel Eksekutif Gembala')

@push('styles')
<style>
    /* DARK MODE COMPATIBLE */
    body { background-color: var(--bs-body-bg); }

    /* === CARD STATS === */
    .stat-card {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        background-color: var(--element-bg);
        overflow: hidden;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }
    
    .stat-link { display: block; text-decoration: none; color: inherit; height: 100%; }
    .stat-link:hover { color: inherit; }
    
    .icon-shape {
        width: 60px; height: 60px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 14px;
        font-size: 1.75rem;
        transition: all 0.3s ease;
    }
    .stat-card:hover .icon-shape { transform: scale(1.1); }

    /* === AVATAR & TABLE === */
    .avatar-circle {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1rem;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }
    .table-hover tbody tr:hover td { background-color: var(--hover-bg); }
    
    .role-badge { 
        font-size: 0.7rem; padding: 0.35em 0.8em; font-weight: 600; 
        border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    /* === OIKOS DETAIL === */
    .oikos-detail-item { font-size: 0.65rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
    .oikos-detail-value { font-weight: 800; font-size: 1.2rem; display: block; line-height: 1.2; color: var(--bs-body-color); }
    
    /* === CARD & TABLE COLORS === */
    .card { background-color: var(--element-bg); color: var(--bs-body-color); border-color: var(--border-color); }
    .card-header, .card-footer { border-color: var(--border-color); background-color: var(--element-bg); }
    
    .table { color: var(--bs-body-color); border-color: var(--border-color); }
    .table thead th { 
        background-color: var(--element-bg-subtle); color: var(--text-secondary); 
        border-bottom: 1px solid var(--border-color); 
        font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .table td { border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    
    .input-group-text { background-color: var(--element-bg-subtle); border-color: var(--border-color); color: var(--text-secondary); }
    .form-control { background-color: var(--input-bg); border-color: var(--border-color); color: var(--bs-body-color); }
    .form-control:focus { background-color: var(--input-bg); color: var(--bs-body-color); border-color: var(--primary-color); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5">
    
    {{-- HEADER SECTION --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-5 gap-3">
        <div>
            <h2 class="fw-bold text-body mb-1">Dashboard Eksekutif</h2>
            <p class="text-secondary mb-0">
                Panel Otoritas Tertinggi &bullet; 
                <span class="text-primary fw-bold">Gembala {{ Auth::user()->name }}</span>
            </p>
        </div>
        <div class="mt-2 mt-md-0">
            <span class="badge bg-dark px-3 py-2 rounded-pill shadow-sm border border-secondary">
                <i class="bi bi-shield-lock-fill me-2"></i>Super Admin Access
            </span>
        </div>
    </div>

    {{-- STATISTIK UTAMA --}}
    <div class="row g-4 mb-5">
        {{-- 1. Total Jiwa --}}
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="icon-shape bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary text-uppercase fw-bold mb-1 small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Jiwa</h6>
                        <h3 class="fw-bold mb-0 text-body">{{ number_format($totalJemaat) }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 2. Total Komsel --}}
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('daftarKomsel') }}" class="stat-link">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-shape bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-secondary text-uppercase fw-bold mb-1 small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Komsel Aktif</h6>
                            <h3 class="fw-bold mb-0 text-body">{{ number_format($totalKomsel) }}</h3>
                            <div class="d-flex align-items-center text-primary mt-1" style="font-size: 0.75rem; font-weight: 600;">
                                <span>Lihat Detail</span> <i class="bi bi-arrow-right ms-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- 3. Leaders Aktif --}}
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="icon-shape bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary text-uppercase fw-bold mb-1 small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Leaders Aktif</h6>
                        <h3 class="fw-bold mb-0 text-body">{{ number_format($totalLeaders) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Statistik OIKOS --}}
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card shadow-sm h-100 border-0 overflow-hidden">
                <div class="card-body p-0 d-flex flex-column justify-content-between h-100">
                    <div class="p-4 d-flex align-items-center">
                        <div class="icon-shape bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-secondary text-uppercase fw-bold mb-1 small" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total OIKOS</h6>
                            <h3 class="fw-bold mb-0 text-body">{{ number_format($oikosStats->total ?? 0) }}</h3>
                        </div>
                    </div>
                    <div class="row g-0 border-top" style="background-color: var(--hover-bg); border-color: var(--border-color)!important;">
                        <div class="col-4 text-center py-2 border-end" style="border-color: var(--border-color)!important;">
                            <span class="oikos-detail-value text-success">{{ number_format($oikosStats->berhasil ?? 0) }}</span>
                            <span class="oikos-detail-item">Berhasil</span>
                        </div>
                        <div class="col-4 text-center py-2 border-end" style="border-color: var(--border-color)!important;">
                            <span class="oikos-detail-value text-warning">{{ number_format($oikosStats->proses ?? 0) }}</span>
                            <span class="oikos-detail-item">Proses</span>
                        </div>
                        <div class="col-4 text-center py-2">
                            <span class="oikos-detail-value text-danger">{{ number_format($oikosStats->gagal ?? 0) }}</span>
                            <span class="oikos-detail-item">Gagal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FEEDBACK ALERT --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                <div><h6 class="fw-bold mb-0">Berhasil!</h6><p class="mb-0 small">{{ session('success') }}</p></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div><h6 class="fw-bold mb-0">Gagal!</h6><p class="mb-0 small">{{ session('error') }}</p></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- MANAJEMEN KOORDINATOR --}}
    <div class="card border-0 shadow-sm" style="background-color: var(--element-bg);">
        <div class="card-header bg-transparent py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center" style="border-color: var(--border-color)!important;">
            <div>
                <h5 class="fw-bold mb-1 text-body"><i class="bi bi-sliders me-2 text-primary"></i>Manajemen Koordinator</h5>
                <p class="text-secondary small mb-0">Tunjuk Leader untuk mengelola operasional harian (Jadwal, Revisi, Absensi).</p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="input-group">
                    <span class="input-group-text border-end-0 text-secondary" style="background-color: var(--input-bg); border-color: var(--border-color);"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchLeaderInput" class="form-control border-start-0" placeholder="Cari nama leader..." style="background-color: var(--input-bg); border-color: var(--border-color); color: var(--bs-body-color);">
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="leaderTable">
                    <thead style="background-color: var(--element-bg-subtle);">
                        <tr>
                            <th class="px-4 py-3">Identitas Leader</th>
                            <th class="py-3">Peran & Jabatan</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($potentialCoordinators as $candidate)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">
                                            {{ substr(data_get($candidate, 'name', data_get($candidate, 'nama', '?')), 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body text-nowrap">{{ data_get($candidate, 'name', data_get($candidate, 'nama', 'Tanpa Nama')) }}</div>
                                            <div class="small text-secondary"><i class="bi bi-envelope me-1"></i>{{ data_get($candidate, 'email', '-') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    @php 
                                        $roles = data_get($candidate, 'roles', []);
                                        if (is_string($roles)) {
                                            $decoded = json_decode($roles, true);
                                            $roles = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [$roles];
                                        }
                                        if (!is_array($roles)) $roles = [];
                                    @endphp

                                    @if(!empty($roles))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($roles as $role)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold role-badge">{{ ucfirst($role) }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-secondary small fst-italic">Tidak ada role</span>
                                    @endif
                                </td>
                                <td class="text-center py-3">
                                    @if(data_get($candidate, 'is_coordinator'))
                                        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">
                                            <i class="bi bi-patch-check-fill me-1"></i> Koordinator Utama
                                        </span>
                                    @else
                                        <span class="badge text-secondary border px-3 py-2 rounded-pill" style="background-color: var(--element-bg-subtle);">Leader</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @php
                                        $candidateName = data_get($candidate, 'name', data_get($candidate, 'nama', 'Leader'));
                                        $candidateId = data_get($candidate, 'id');
                                    @endphp

                                    @if(data_get($candidate, 'is_coordinator'))
                                        <button class="btn btn-sm btn-outline-secondary disabled opacity-50" disabled>
                                            <i class="bi bi-check2-all me-1"></i> Terpilih
                                        </button>
                                    @else
                                        <form action="{{ route('admin.appointCoordinator') }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN: Mengangkat {{ $candidateName }} akan MENGGANTIKAN Koordinator saat ini. Apakah Anda yakin?');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="user_id" value="{{ $candidateId }}">
                                            <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold shadow-sm rounded-pill">
                                                <i class="bi bi-arrow-up-circle me-1"></i> Angkat
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-5"><div class="d-flex flex-column align-items-center justify-content-center opacity-50"><i class="bi bi-inbox-fill display-4 text-secondary mb-2"></i><h6 class="text-secondary fw-bold">Tidak ada data Leader</h6></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center text-secondary small">
                <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                <span><strong>Catatan:</strong> Hanya boleh ada 1 Koordinator. Pengangkatan baru akan menonaktifkan yang lama.</span>
            </div>
            
            <form action="{{ route('admin.resetCoordinator') }}" method="POST" onsubmit="return confirm('KONFIRMASI RESET: Apakah Anda yakin ingin menghapus semua Koordinator? Tidak akan ada yang bertugas sementara.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Koordinator
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchLeaderInput');
        const tableRows = document.querySelectorAll('#leaderTable tbody tr');

        if(searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                const term = e.target.value.toLowerCase();
                tableRows.forEach(row => {
                    const text = row.querySelector('td:first-child').textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    });
</script>
@endsection