@extends('layouts.app')

@section('title', 'Jadwal Ibadah KOMSEL')

@push('styles')
<style>
    /* === LAYOUT & BACKGROUND === */
    body { background-color: var(--bs-body-bg); }

    /* === MODERN SEARCH & FILTER === */
    .top-toolbar {
        background: transparent;
        padding-bottom: 1.5rem;
    }
    
    .search-wrapper {
        background: var(--element-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 0.5rem 1rem;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s ease;
        width: 100%;
        max-width: 320px;
        display: flex;
        align-items: center;
    }
    .search-wrapper input {
        color: var(--bs-body-color);
    }
    .search-wrapper i {
        color: var(--text-secondary);
    }
    .search-wrapper:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    
    /* === FILTER TABS === */
    .filter-nav-container {
        background-color: var(--hover-bg);
        padding: 4px;
        border-radius: 12px;
        display: inline-flex;
        position: relative;
        border: 1px solid var(--border-color);
    }
    .filter-nav-btn {
        border: none;
        background: transparent;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 16px;
        border-radius: 8px;
        cursor: pointer;
        z-index: 2;
        position: relative;
        transition: color 0.2s ease;
    }
    .filter-nav-btn.active { color: #fff; }
    .filter-slider {
        position: absolute;
        top: 4px; bottom: 4px; left: 4px;
        background: var(--primary-color);
        border-radius: 8px;
        z-index: 1;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), width 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* === SCHEDULE CARD LIST STYLE === */
    .schedule-card {
        background: var(--element-bg);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .schedule-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--border-color);
    }

    /* Time Box */
    .time-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: var(--element-bg-subtle);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        min-width: 90px;
        border: 1px solid var(--border-color);
    }
    .time-hour { font-size: 1.25rem; font-weight: 800; color: var(--bs-body-color); line-height: 1; }
    .time-day { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-top: 4px; letter-spacing: 0.5px; }

    /* Info Section */
    .info-section { flex: 1; min-width: 200px; }
    .komsel-title { font-size: 1.1rem; font-weight: 700; color: var(--bs-body-color); margin-bottom: 0.25rem; }
    .location-badge {
        display: inline-flex; align-items: center;
        font-size: 0.85rem; color: var(--text-secondary);
        background: var(--element-bg-subtle);
        padding: 4px 10px; border-radius: 6px;
        border: 1px solid var(--border-color);
    }

    /* Status Badge */
    .status-dot-badge {
        display: inline-flex; align-items: center;
        padding: 6px 12px; border-radius: 9999px;
        font-size: 0.75rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-dot-badge::before {
        content: ''; display: inline-block;
        width: 8px; height: 8px; border-radius: 50%;
        margin-right: 8px;
        background-color: currentColor;
    }
    /* Colors remain mostly same but use rgba for better blend */
    .status-Menunggu { background: rgba(255, 237, 213, 0.2); color: #ea580c; border: 1px solid rgba(255, 237, 213, 0.5); }
    .status-Berlangsung { background: rgba(219, 234, 254, 0.2); color: #2563eb; border: 1px solid rgba(219, 234, 254, 0.5); }
    .status-Selesai { background: rgba(220, 252, 231, 0.2); color: #15803d; border: 1px solid rgba(220, 252, 231, 0.5); }
    .status-Gagal { background: rgba(254, 226, 226, 0.2); color: #b91c1c; border: 1px solid rgba(254, 226, 226, 0.5); }

    /* Actions */
    .action-group { display: flex; gap: 0.5rem; }
    .btn-icon {
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 10px; 
        border: 1px solid var(--border-color);
        color: var(--text-secondary); 
        background: var(--element-bg);
        transition: all 0.2s;
    }
    .btn-icon:hover { background: var(--hover-bg); border-color: var(--text-secondary); color: var(--bs-body-color); }
    .btn-icon-danger:hover { background: rgba(254, 226, 226, 0.5); border-color: #fecaca; color: #ef4444; }
    
    .btn-action-primary {
        background: var(--primary-color); color: white; border: none;
        padding: 0.5rem 1rem; border-radius: 10px;
        font-weight: 600; font-size: 0.9rem;
        display: flex; align-items: center; gap: 0.5rem;
    }
    .btn-action-primary:hover { background: var(--primary-hover); color: white; transform: translateY(-1px); }

    /* === CREATE BUTTON === */
    .btn-create-jadwal {
        background: linear-gradient(135deg, var(--primary-color), #4f46e5);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 9999px;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex; align-items: center; gap: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
    }
    .btn-create-jadwal:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        color: white;
    }
    .btn-create-icon {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%; width: 24px; height: 24px;
        display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
    }

    /* === MODAL & FORM DARK MODE FIXES === */
    .modal-content {
        background-color: var(--element-bg);
        border: 1px solid var(--border-color);
        color: var(--bs-body-color);
    }
    .modal-header, .modal-footer {
        border-color: var(--border-color);
    }
    .modal-title { color: var(--bs-body-color); }
    
    /* Input Fields Override */
    .form-control, .form-select {
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        color: var(--bs-body-color);
        border-radius: 0.75rem;
        padding: 0.6rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: var(--input-bg);
        color: var(--bs-body-color);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    .form-control::placeholder { color: var(--text-secondary); opacity: 0.6; }
    
    .form-label { color: var(--text-secondary); font-size: 0.85rem; }

    /* List Group in Modal */
    .list-group-item {
        background-color: var(--element-bg);
        border-bottom: 1px solid var(--border-color);
        color: var(--bs-body-color);
    }

    @media (max-width: 768px) {
        .schedule-card { flex-direction: column; align-items: flex-start; }
        .time-box { width: 100%; flex-direction: row; justify-content: space-between; min-height: auto; padding: 0.75rem; }
        .time-hour { font-size: 1.1rem; }
        .action-group { width: 100%; justify-content: flex-end; margin-top: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 1rem; }
        .filter-wrapper { overflow-x: auto; white-space: nowrap; padding-bottom: 5px; width: 100%; }
    }
</style>
@endpush

@section('konten')

{{-- SETUP VARIABLE HAK AKSES VIEW --}}
@php
    $user = Auth::user();
    $isSuperAdmin = in_array('super_admin', $user->roles ?? []);
    $canManage = $isSuperAdmin || $user->isLeaderKomsel(); 
@endphp

<div class="container-fluid px-0 pb-5">
    
    {{-- HEADER & TOOLBAR --}}
    <div class="top-toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-2">
        <div>
            <h3 class="fw-bold text-body mb-1">Jadwal Ibadah</h3>
            <p class="text-secondary mb-0 small">Kelola agenda ibadah komunitas sel</p>
        </div>

        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            {{-- SEARCH BAR --}}
            <div class="search-wrapper d-flex align-items-center">
                <i class="bi bi-search me-2"></i>
                <input type="text" class="form-control border-0 shadow-none p-0 bg-transparent" 
                       id="scheduleSearchInput" placeholder="Cari komsel, lokasi..." autocomplete="off">
            </div>

            {{-- CREATE BUTTON (Hanya untuk Leader/Admin) --}}
            @if($canManage)
                <button type="button" class="btn-create-jadwal" data-bs-toggle="modal" data-bs-target="#createJadwalModal">
                    <div class="btn-create-icon">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <span>Buat Jadwal</span>
                </button>
            @endif
        </div>
    </div>

    {{-- FILTER NAVIGATION --}}
    <div class="mb-4 overflow-auto pb-2">
        <div class="filter-nav-container">
            <div class="filter-slider"></div>
            <button type="button" class="filter-nav-btn active" data-filter="all">Semua</button>
            <button type="button" class="filter-nav-btn" data-filter="Menunggu">Menunggu</button>
            <button type="button" class="filter-nav-btn" data-filter="Berlangsung">Berlangsung</button>
            <button type="button" class="filter-nav-btn" data-filter="Selesai">Selesai</button>
            <button type="button" class="filter-nav-btn" data-filter="Gagal">Gagal</button>
        </div>
    </div>

    {{-- FEEDBACK MESSAGES --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- SCHEDULE LIST --}}
    <div id="scheduleListContainer">
        @forelse ($schedules as $schedule)
        <div class="schedule-card" data-status="{{ $schedule->status }}">
            {{-- Time Box --}}
            <div class="time-box">
                <span class="time-hour">{{ \Carbon\Carbon::parse($schedule->time)->format('H:i') }}</span>
                <span class="time-day">{{ $schedule->day_of_week }}</span>
            </div>

            {{-- Info Section --}}
            <div class="info-section">
                <div class="d-flex align-items-start justify-content-between mb-1">
                    <h5 class="komsel-title">{{ $schedule->komsel_name ?? 'Nama Komsel Tidak Ada' }}</h5>
                </div>
                
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <div class="location-badge">
                        <i class="bi bi-geo-alt-fill text-danger me-1 opacity-75"></i> {{ $schedule->location }}
                    </div>
                    <div class="status-dot-badge status-{{ $schedule->status }}">
                        {{ $schedule->status }}
                    </div>
                </div>

                @if($schedule->description)
                    <p class="text-secondary small mb-0 text-truncate" style="max-width: 400px;">
                        <i class="bi bi-chat-square-text me-1"></i> {{ $schedule->description }}
                    </p>
                @endif
            </div>

            {{-- Action Group --}}
            <div class="action-group">
                {{-- Tombol Absensi (Partner Boleh Akses) --}}
                @if($schedule->status == 'Berlangsung')
                    <button type="button" class="btn-action-primary shadow-sm" 
                            title="Input Absensi" 
                            data-bs-toggle="modal" 
                            data-bs-target="#absensiModal" 
                            data-schedule-id="{{ $schedule->id }}" 
                            data-komsel-id="{{ $schedule->komsel_id }}" 
                            data-komsel-nama="{{ $schedule->komsel_name ?? '' }}">
                        <i class="bi bi-clipboard-check"></i> Absen
                    </button>
                @elseif($schedule->status == 'Selesai')
                    <button type="button" class="btn btn-icon text-primary border-primary bg-primary bg-opacity-10" 
                            title="Lihat Laporan" 
                            data-bs-toggle="modal" 
                            data-bs-target="#infoAbsensiModal" 
                            data-schedule-id="{{ $schedule->id }}" 
                            data-komsel-nama="{{ $schedule->komsel_name ?? '' }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    </button>
                @endif
                
                {{-- Tombol Edit & Hapus (Hanya Leader/Admin) --}}
                @if($canManage)
                    <button type="button" class="btn btn-icon" title="Edit" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editJadwalModal" 
                            data-id="{{ $schedule->id }}" 
                            data-komsel-id="{{ $schedule->komsel_id }}" 
                            data-komsel-nama="{{ $schedule->komsel_name ?? '' }}" 
                            data-day="{{ $schedule->day_of_week }}" 
                            data-time="{{ $schedule->time }}" 
                            data-location="{{ $schedule->location }}" 
                            data-description="{{ $schedule->description }}" 
                            data-status="{{ $schedule->status }}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    
                    <form action="{{ route('jadwal.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-icon btn-icon-danger" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div id="empty-row" class="text-center py-5">
            <div class="d-flex flex-column align-items-center opacity-50">
                <div class="rounded-circle shadow-sm mb-3 d-inline-flex align-items-center justify-content-center" 
                     style="background-color: var(--element-bg)!important; width: 80px; height: 80px;">
                    <i class="bi bi-calendar-x display-4 text-secondary"></i>
                </div>
                <h5 class="fw-bold text-secondary">Tidak ada jadwal</h5>
                <p class="text-secondary">Belum ada jadwal ibadah yang dibuat.</p>
            </div>
        </div>
        @endforelse
        
        {{-- Empty State for Filter --}}
        <div id="no-results" class="text-center py-5 d-none">
            <p class="text-muted fst-italic text-secondary">Tidak ditemukan jadwal yang sesuai.</p>
        </div>
    </div>
</div>

{{-- Modal Absensi --}}
<div class="modal fade" id="absensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Form Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="absensiForm">
                    <input type="hidden" id="absensiScheduleId">
                    <div class="row g-4">
                        <div class="col-md-6 border-end pe-md-4" style="border-color: var(--border-color)!important;">
                            <h6 class="fw-bold text-primary mb-3 text-uppercase small letter-spacing-1">Input Peserta</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Cari Anggota</label>
                                <input type="text" id="anggotaSearchInput" class="form-control mb-2" placeholder="Ketik nama...">
                                <div class="input-group">
                                    <select class="form-select" id="anggotaDropdown" size="4">
                                        <option disabled>Memuat data...</option>
                                    </select>
                                    <button class="btn btn-primary" type="button" id="addAnggotaBtn">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold text-secondary">Tamu / Simpatisan</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="guestNameInput" placeholder="Nama Tamu...">
                                    <button class="btn btn-outline-success border-0 bg-success bg-opacity-10 text-success fw-bold" type="button" id="addGuestBtn">
                                        Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0 text-uppercase small letter-spacing-1">Kehadiran</h6>
                                <span class="badge bg-primary bg-opacity-10 text-primary" id="countDisplay">0 Orang</span>
                            </div>
                            <div id="daftarHadirContainer" class="list-group list-group-flush border rounded-3 overflow-auto custom-scrollbar" style="max-height: 300px; background: var(--element-bg-subtle); border-color: var(--border-color)!important;">
                                {{-- Item will be injected here --}}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            {{-- [FIX] TOMBOL SIMPAN DENGAN EVENT LISTENER DIRECT --}}
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light fw-medium" style="background: var(--hover-bg); color: var(--bs-body-color); border-color: var(--border-color);" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveAbsensi" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">Simpan Data</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Info Absensi (Read Only) --}}
<div class="modal fade" id="infoAbsensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Laporan Kehadiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="bg-primary bg-opacity-10 p-4 text-center">
                    <h1 class="display-3 fw-bold text-primary mb-0" id="totalKehadiran">0</h1>
                    <small class="text-muted text-uppercase fw-bold letter-spacing-1">Total Hadir</small>
                </div>
                <div id="infoDaftarHadirContainer" class="list-group list-group-flush px-3 pb-3 pt-2 custom-scrollbar" style="max-height: 350px; overflow-y: auto;">
                    {{-- List injected here --}}
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Create Jadwal (Hanya Render jika Leader/Admin) --}}
@if($canManage)
<div class="modal fade" id="createJadwalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Buat Jadwal Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4">
                <form id="createForm" action="{{ route('jadwal.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Komunitas Sel</label>
                        <select class="form-select" name="komsel_id" required>
                            <option value="" disabled selected>Pilih KOMSEL...</option>
                            @foreach ($komsels as $komsel)
                                <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Hari</label>
                            <select class="form-select" name="day_of_week" required>
                                <option value="">Pilih...</option>
                                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Jam</label>
                            <input type="time" class="form-control" name="time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Lokasi</label>
                        <input type="text" class="form-control" name="location" placeholder="Contoh: Rumah Bpk. Budi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Catatan (Opsional)</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <input type="hidden" name="status" value="Menunggu">
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light fw-medium" style="background: var(--hover-bg); color: var(--bs-body-color); border-color: var(--border-color);" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="createForm" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Edit Jadwal --}}
<div class="modal fade" id="editJadwalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4">
                <form id="editForm" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Komunitas Sel</label>
                        <select class="form-select" id="editKomsel" name="komsel_id" required>
                            <option value="">Pilih KOMSEL...</option>
                            @foreach ($komsels as $komsel)
                                <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Hari</label>
                            <select class="form-select" id="editDayOfWeek" name="day_of_week" required>
                                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Jam</label>
                            <input type="time" class="form-control" id="editTime" name="time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Lokasi</label>
                        <input type="text" class="form-control" id="editLokasi" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Berlangsung">Berlangsung</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Gagal">Gagal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Catatan</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light fw-medium" style="background: var(--hover-bg); color: var(--bs-body-color); border-color: var(--border-color);" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="editForm" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- FILTER & SEARCH LOGIC ---
    const filterBtns = document.querySelectorAll('.filter-nav-btn');
    const slider = document.querySelector('.filter-slider');
    const searchInput = document.getElementById('scheduleSearchInput');
    const cards = document.querySelectorAll('.schedule-card');
    const noResults = document.getElementById('no-results');
    const emptyRow = document.getElementById('empty-row');

    const moveSlider = (btn) => {
        const rect = btn.getBoundingClientRect();
        const parentRect = btn.parentElement.getBoundingClientRect();
        slider.style.width = `${rect.width}px`;
        slider.style.transform = `translateX(${rect.left - parentRect.left}px)`;
    };

    const activeBtn = document.querySelector('.filter-nav-btn.active');
    if(activeBtn) setTimeout(() => moveSlider(activeBtn), 50);

    function applyFilters() {
        const activeBtn = document.querySelector('.filter-nav-btn.active');
        const statusFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
        const searchTerm = searchInput.value.toLowerCase().trim();
        let count = 0;

        cards.forEach(card => {
            const status = card.getAttribute('data-status');
            const text = card.textContent.toLowerCase(); 

            const matchesStatus = (statusFilter === 'all' || status === statusFilter);
            const matchesSearch = text.includes(searchTerm);

            if (matchesStatus && matchesSearch) {
                card.style.display = 'flex';
                count++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyRow) emptyRow.style.display = 'none'; 
        if (noResults) noResults.style.display = 'none';

        if (count === 0) {
            if (cards.length === 0) {
                if (emptyRow) emptyRow.style.display = 'block';
            } else {
                if (noResults) {
                    noResults.style.display = 'block';
                    noResults.querySelector('p').textContent = searchTerm 
                        ? `Tidak ditemukan jadwal untuk "${searchTerm}"`
                        : 'Tidak ada jadwal dengan status ini.';
                }
            }
        }
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            moveSlider(this);
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    searchInput.addEventListener('input', applyFilters);
    window.addEventListener('resize', () => {
        const current = document.querySelector('.filter-nav-btn.active');
        if(current) moveSlider(current);
    });

    // --- MODAL EDIT ---
    const editModal = document.getElementById('editJadwalModal');
    if(editModal) {
        editModal.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            const id = btn.getAttribute('data-id');
            editModal.querySelector('#editKomsel').value = btn.getAttribute('data-komsel-id');
            editModal.querySelector('#editDayOfWeek').value = btn.getAttribute('data-day');
            editModal.querySelector('#editTime').value = btn.getAttribute('data-time');
            editModal.querySelector('#editLokasi').value = btn.getAttribute('data-location');
            
            const statusVal = btn.getAttribute('data-status');
            if(statusVal) editModal.querySelector('#editStatus').value = statusVal.trim();
            
            editModal.querySelector('#editDescription').value = btn.getAttribute('data-description');
            let url = "{{ route('jadwal.update', ':id') }}".replace(':id', id);
            editModal.querySelector('#editForm').action = url;
        });
    }

    // --- MODAL ABSENSI (FIXED LOGIC) ---
    const absensiModal = document.getElementById('absensiModal');
    if(absensiModal) {
        const listContainer = document.getElementById('daftarHadirContainer');
        const dropdown = document.getElementById('anggotaDropdown');
        const countDisplay = document.getElementById('countDisplay');
        
        const updateCount = () => {
            const count = listContainer.querySelectorAll('.list-group-item').length;
            countDisplay.textContent = `${count} Orang`;
        };

        const addToList = (id, name, isGuest) => {
            const uid = isGuest ? `g-${name}` : `u-${id}`;
            if(listContainer.querySelector(`[data-uid="${uid}"]`)) return;
            
            const div = document.createElement('div');
            div.className = 'list-group-item border-0 border-bottom d-flex justify-content-between align-items-center bg-transparent px-2';
            div.setAttribute('data-uid', uid);
            div.setAttribute('data-id', id);
            div.setAttribute('data-is-guest', isGuest);
            div.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="bg-${isGuest ? 'secondary' : 'primary'} bg-opacity-10 text-${isGuest ? 'secondary' : 'primary'} rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi ${isGuest ? 'bi-person' : 'bi-person-fill'} small"></i>
                    </div>
                    <span class="fw-medium ${isGuest ? 'text-muted' : 'text-dark'}">${name}</span>
                    ${isGuest ? '<span class="badge bg-light text-secondary border ms-2" style="font-size: 0.6rem;">TAMU</span>' : ''}
                </div>
                <button type="button" class="btn btn-sm text-danger remove-btn"><i class="bi bi-trash"></i></button>
            `;
            div.querySelector('.remove-btn').onclick = () => { div.remove(); updateCount(); };
            listContainer.appendChild(div);
            updateCount();
        };

        absensiModal.addEventListener('show.bs.modal', async function(e) {
            const btn = e.relatedTarget;
            const scheduleId = btn.getAttribute('data-schedule-id');
            const komselId = btn.getAttribute('data-komsel-id');
            document.getElementById('absensiScheduleId').value = scheduleId;
            
            listContainer.innerHTML = '<div class="p-4 text-center text-muted"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>';
            dropdown.innerHTML = '<option disabled>Memuat anggota...</option>';
            countDisplay.textContent = '...';

            try {
                const [usersRes, attendRes] = await Promise.all([
                    fetch(`{{ url('/api/komsel') }}/${komselId}/users`), 
                    fetch(`{{ route('api.schedule.attendances.get', ':id') }}`.replace(':id', scheduleId))
                ]);

                if(!usersRes.ok || !attendRes.ok) throw new Error("Gagal memuat data.");

                const usersData = await usersRes.json();
                const attendData = await attendRes.json();

                dropdown.innerHTML = '<option selected disabled value="">Pilih anggota...</option>';
                if(usersData.users) {
                    usersData.users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.nama;
                        dropdown.appendChild(opt);
                    });
                }

                listContainer.innerHTML = '';
                // Populate Existing Data
                if(usersData.users) {
                    usersData.users.forEach(u => {
                        // Compare string/int safely
                        if(attendData.present_users.some(p => parseInt(p.id) === parseInt(u.id))) {
                            addToList(u.id, u.nama, false);
                        }
                    });
                }
                if(attendData.guests) {
                    attendData.guests.forEach(g => addToList(g, g, true));
                }
                updateCount();

            } catch(err) {
                console.error(err);
                listContainer.innerHTML = `<div class="p-3 text-center text-danger small">Gagal memuat data.</div>`;
            }
        });

        document.getElementById('addAnggotaBtn').onclick = () => {
            const opt = dropdown.options[dropdown.selectedIndex];
            if(opt && opt.value) {
                addToList(opt.value, opt.text, false);
                dropdown.value = "";
            }
        };

        document.getElementById('addGuestBtn').onclick = () => {
            const inp = document.getElementById('guestNameInput');
            const name = inp.value.trim();
            if(name) {
                addToList(name, name, true);
                inp.value = "";
            }
        };

        document.getElementById('anggotaSearchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            Array.from(dropdown.options).forEach(opt => {
                if(opt.value) opt.style.display = opt.text.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        // [FIX] SAVE ABSENSI LOGIC (DIRECT FETCH)
        document.getElementById('btnSaveAbsensi').addEventListener('click', async function() {
            const submitBtn = this;
            
            const scheduleId = document.getElementById('absensiScheduleId').value;
            const present_users = [];
            const guests = [];

            listContainer.querySelectorAll('.list-group-item').forEach(el => {
                const isGuest = el.getAttribute('data-is-guest') === 'true'; 
                const idOrName = el.getAttribute('data-id');

                if (isGuest) {
                    guests.push(idOrName);
                } else {
                    present_users.push(parseInt(idOrName));
                }
            });

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

            try {
                const res = await fetch(`{{ route('api.schedule.attendances.store', ':id') }}`.replace(':id', scheduleId), {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify({ 
                        present_users: present_users, 
                        guest_names: guests 
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    if (res.status === 422) {
                        let errorMsg = "Validasi Gagal:\n";
                        for (const [key, value] of Object.entries(data.errors)) {
                            errorMsg += `- ${value}\n`;
                        }
                        throw new Error(errorMsg);
                    } else {
                        throw new Error(data.message || "Gagal menyimpan data.");
                    }
                }

                // Success
                bootstrap.Modal.getInstance(absensiModal).hide();
                window.location.reload();

            } catch (err) {
                alert(err.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Simpan Data';
            }
        });
    }

    // --- INFO MODAL ---
    const infoModal = document.getElementById('infoAbsensiModal');
    if(infoModal) {
        infoModal.addEventListener('show.bs.modal', async function(e) {
            const btn = e.relatedTarget;
            const list = document.getElementById('infoDaftarHadirContainer');
            const totalEl = document.getElementById('totalKehadiran');
            
            list.innerHTML = '<div class="p-4 text-center text-muted"><span class="spinner-border spinner-border-sm"></span></div>';
            totalEl.textContent = '-';

            try {
                const res = await fetch(`{{ route('api.schedule.attendances.get', ':id') }}`.replace(':id', btn.dataset.scheduleId));
                if(!res.ok) throw new Error();
                const data = await res.json();

                list.innerHTML = '';
                let count = 0;
                
                if(data.present_users) {
                    data.present_users.forEach(u => {
                        list.innerHTML += `<div class="list-group-item border-0 border-bottom bg-transparent px-0 py-2"><i class="bi bi-check-circle-fill text-success me-2"></i>${u.nama}</div>`;
                        count++;
                    });
                }
                if(data.guests) {
                    data.guests.forEach(g => {
                        list.innerHTML += `<div class="list-group-item border-0 border-bottom bg-transparent px-0 py-2"><i class="bi bi-person-fill text-secondary me-2"></i>${g} <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.65em;">TAMU</span></div>`;
                        count++;
                    });
                }
                
                totalEl.textContent = count;
                if(count === 0) list.innerHTML = '<div class="p-4 text-center text-muted">Tidak ada kehadiran tercatat.</div>';

            } catch(e) {
                list.innerHTML = '<div class="p-4 text-center text-danger">Gagal memuat data.</div>';
            }
        });
    }
});
</script>
@endpush