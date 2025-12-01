@extends('layouts.app')

@section('title', 'Daftar Kunjungan OIKOS')

@push('styles')
<style>
    /* === UMUM === */
    .table thead th { border-bottom: 2px solid var(--border-color); font-weight: 600; white-space: nowrap; }
    .schedule-badge { font-size: 0.8rem; padding: 0.4em 0.7em; background-color: var(--hover-bg); color: var(--text-secondary); border: 1px solid var(--border-color); white-space: nowrap; }
    .modal-content { background-color: var(--element-bg); }
    .report-detail-group dt { font-weight: 500; color: var(--bs-body-color); }
    .report-detail-group dd { color: var(--text-secondary); }

    /* === FILTER NAV === */
    .filter-nav-container { position: relative; display: inline-flex; background-color: var(--hover-bg); border-radius: 0.85rem; padding: 5px; box-shadow: var(--shadow); }
    .filter-nav-btn { border: none; background: transparent; color: var(--text-secondary); font-weight: 500; padding: 8px 20px; cursor: pointer; position: relative; z-index: 1; transition: color 0.3s ease; }
    .filter-nav-btn.active { color: #fff; }
    .filter-slider { position: absolute; top: 5px; left: 5px; height: calc(100% - 10px); background-color: var(--primary-color); border-radius: 0.75rem; z-index: 0; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }

    /* === MOBILE CARD VIEW TRANSFORMATION (KEY FIX) === */
    @media (max-width: 768px) {
        
        /* 1. MATIKAN SEMUA OVERFLOW YANG MEMOTONG DROPDOWN */
        .table-responsive, 
        .card, 
        .card-body,
        .table {
            overflow: visible !important; 
        }

        /* 2. HILANGKAN HEADER TABEL */
        .table thead { display: none; }
        
        /* 3. UBAH BARIS JADI KARTU */
        .table, .table tbody, .table tr, .table td { 
            display: block; 
            width: 100%; 
        }

        .table tbody tr {
            margin-bottom: 1.5rem; /* Jarak antar kartu lebih lebar */
            background-color: var(--element-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Shadow lebih halus */
            position: relative; /* Wajib untuk positioning tombol */
            padding: 1.25rem;
            padding-top: 2.5rem; /* Ruang ekstra di atas untuk status & tombol */
        }

        .table td {
            text-align: left;
            padding: 0.25rem 0; /* Padding lebih rapat */
            border: none;
            position: relative;
        }

        /* --- PENATAAN KONTEN KARTU --- */

        /* Sembunyikan No */
        .table td:nth-child(1) { display: none; }

        /* Nama OIKOS (Judul Besar) */
        .table td:nth-child(2) {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--bs-body-color);
            margin-bottom: 0.5rem;
            line-height: 1.2;
            padding-right: 40px; /* Jaga jarak dengan tombol floating */
        }

        /* Pelayan (Info User) */
        .table td:nth-child(3) {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed var(--border-color);
        }

        /* Status (Badge) - Pindah ke Pojok Kiri Atas */
        .table td:nth-child(4) {
            position: absolute;
            top: 0;
            left: 0;
            width: auto;
            padding: 0;
        }
        .table td:nth-child(4) .badge {
            border-top-left-radius: 15px;
            border-bottom-right-radius: 15px;
            border-top-right-radius: 0;
            border-bottom-left-radius: 0;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Jadwal (Footer Kecil) */
        .table td:nth-child(5) {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }

        /* === TOMBOL FLOATING (SOLUSI ANDA) === */
        .table td:last-child {
            position: absolute;
            top: 10px;
            right: 10px;
            width: auto;
            height: auto;
            padding: 0;
            margin: 0;
            background: transparent !important; /* Hapus background baris */
            border: none;
            overflow: visible; /* Penting! */
            z-index: 100;
        }

        /* Styling Tombol Bulat Putih */
        .btn-floating-action {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #fff; /* Latar Putih */
            border: 2px solid var(--border-color); /* Border tipis */
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Shadow agar mengambang */
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            transition: transform 0.2s;
        }
        .btn-floating-action:active {
            transform: scale(0.95);
        }

        /* Styling Dropdown Mobile */
        .dropdown-menu {
            position: absolute;
            transform: translate3d(-100%, 10px, 0px) !important; /* Paksa muncul di kiri bawah tombol */
            top: 0px;
            left: 0px;
            will-change: transform;
            width: 220px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
            z-index: 9999 !important;
        }
    }
</style>
@endpush

@section('konten')

    <div class="d-flex justify-content-center mb-4">
        <div class="filter-nav-container">
            <div class="filter-slider"></div>
            <button type="button" class="filter-nav-btn active" data-filter="all">Semua</button>
            <button type="button" class="filter-nav-btn" data-filter="Direncanakan">Direncanakan</button>
            <button type="button" class="filter-nav-btn" data-filter="Diproses">Diproses</button>
            <button type="button" class="filter-nav-btn" data-filter="Selesai">Selesai</button>
            <button type="button" class="filter-nav-btn" data-filter="Gagal">Gagal</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 
        CONTAINER UTAMA 
        Perhatikan: class "overflow-visible" ditambahkan manual untuk memastikan tidak ada clipping 
    --}}
    <div class="card bg-transparent border-0 shadow-none" style="overflow: visible !important;">
        <div class="card-body p-0 p-md-4 bg-transparent" style="overflow: visible !important;">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-4 rounded-4 shadow-sm border">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-house-heart-fill me-2" style="color: var(--primary-color);"></i>Daftar Kunjungan OIKOS</h5>
                <a href="{{ route('formInput') }}" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Buat Jadwal</a>
            </div>
            
            {{-- CONTAINER TABEL --}}
            <div class="card border-0 shadow-sm rounded-4 bg-white" style="overflow: visible !important;">
                <div class="table-responsive" style="overflow: visible !important;">
                    <table class="table table-hover align-middle mb-0" style="overflow: visible !important;">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="py-3 px-4" style="width: 5%">No.</th>
                                <th scope="col" class="py-3" style="width: 25%">Nama OIKOS</th>
                                <th scope="col" class="py-3" style="width: 25%">Pelayan</th>
                                <th scope="col" class="py-3" style="width: 15%">Status</th>
                                <th scope="col" class="py-3" style="width: 20%">Jadwal</th>
                                <th scope="col" class="text-center py-3 px-4" style="width: 10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($oikosVisits as $visit)
                            <tr data-status="{{ $visit->status }}">
                                <td class="fw-bold px-4">{{ $loop->iteration }}</td>
                                
                                {{-- Nama OIKOS --}}
                                <td>
                                    <div class="d-md-none text-secondary small text-uppercase fw-bold mb-1">Nama OIKOS</div>
                                    {{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}
                                </td>
                                
                                {{-- Pelayan --}}
                                <td>
                                    @if($visit->pelayan_data)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle sm bg-primary text-white me-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:0.85em;font-weight:600;">
                                                {{ substr($visit->pelayan_data['name'] ?? $visit->pelayan_data['nama'] ?? '?', 0, 1) }}
                                            </div>
                                            <div>
                                                <span class="d-block lh-sm fw-semibold">{{ $visit->pelayan_data['name'] ?? $visit->pelayan_data['nama'] }}</span>
                                                @if($visit->original_pelayan_user_id)
                                                    <span class="text-danger small fw-bold" style="font-size: 0.7rem;">
                                                        <i class="bi bi-arrow-return-right"></i> Pengganti
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">Belum ditentukan</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span @class([
                                        'badge',
                                        'text-bg-warning' => $visit->status == 'Direncanakan',
                                        'text-bg-primary' => $visit->status == 'Berlangsung',
                                        'text-bg-info' => $visit->status == 'Diproses',
                                        'text-bg-success' => $visit->status == 'Selesai',
                                        'text-bg-danger' => $visit->status == 'Gagal',
                                        'text-bg-secondary' => !in_array($visit->status, ['Direncanakan', 'Berlangsung', 'Diproses', 'Selesai', 'Gagal']),
                                    ])>
                                        {{ $visit->status }}
                                    </span>
                                </td>

                                {{-- Jadwal --}}
                                <td>
                                    <div class="d-flex align-items-center text-secondary">
                                        <i class="bi bi-calendar4-week me-2"></i>
                                        <span>{{ \Carbon\Carbon::parse($visit->start_date)->format('j M') }} - {{ \Carbon\Carbon::parse($visit->end_date)->format('j M Y') }}</span>
                                    </div>
                                </td>
                                
                                {{-- Aksi (Dropdown FLOATING) --}}
                                <td class="text-center px-4">
                                    <div class="dropdown">
                                        {{-- TOMBOL FLOATING YANG ANDA MINTA --}}
                                        <button class="btn btn-floating-action" type="button" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-4">
                                            {{-- Detail --}}
                                            <li>
                                                <a class="dropdown-item rounded-2 py-2 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#viewLaporanModal" data-visit-id="{{ $visit->id }}">
                                                    <i class="bi bi-eye me-2 text-primary"></i>Detail / Laporan
                                                </a>
                                            </li>

                                            @if(in_array($visit->status, ['Direncanakan', 'Diproses']))
                                                @if($isReportDay)
                                                    <li>
                                                        <a class="dropdown-item rounded-2 py-2 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#laporanModal" data-visit-id="{{ $visit->id }}" data-oikos-nama="{{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}">
                                                            <i class="bi bi-pencil-square me-2 text-warning"></i>Input Laporan
                                                        </a>
                                                    </li>
                                                @else
                                                    <li>
                                                        <span class="dropdown-item rounded-2 py-2 mb-1 text-muted" title="Laporan hanya hari Rabu-Sabtu" style="cursor: not-allowed;">
                                                            <i class="bi bi-clock-history me-2"></i>Lapor (Rabu-Sabtu)
                                                        </span>
                                                    </li>
                                                @endif

                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item rounded-2 py-2 mb-1 text-secondary" href="#" data-bs-toggle="modal" data-bs-target="#modalDelegate{{ $visit->id }}">
                                                        <i class="bi bi-person-bounding-box me-2"></i>Ganti Pelayan
                                                    </a>
                                                </li>
                                            @endif

                                            @if($visit->status == 'Diproses' && Auth::check() && is_array(Auth::user()->roles) && in_array('super_admin', Auth::user()->roles))
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('oikos.confirm', $visit->id) }}" method="POST" onsubmit="return confirm('Konfirmasi selesai?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="dropdown-item rounded-2 py-2 text-success fw-bold bg-success bg-opacity-10">
                                                        <i class="bi bi-check-circle-fill me-2"></i>Konfirmasi Selesai
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            {{-- MODAL DELEGASI --}}
                            <div class="modal fade" id="modalDelegate{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('oikos.delegate', $visit->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning bg-opacity-10 border-0">
                                                <h5 class="modal-title fw-bold text-dark">Delegasi / Ganti Pelayan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="alert alert-info small mb-3 border-0 bg-info bg-opacity-10 text-info">
                                                    <i class="bi bi-info-circle-fill me-1"></i> Pelayan pengganti akan ditugaskan.
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-secondary text-uppercase small">Pelayan Pengganti</label>
                                                    <select name="new_pelayan_id" class="form-select" required>
                                                        <option value="" disabled selected>Pilih Pengganti...</option>
                                                        @if(isset($pelayans))
                                                            @foreach($pelayans as $p)
                                                                <option value="{{ $p['id'] }}">{{ $p['nama'] }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-secondary text-uppercase small">Alasan Penggantian</label>
                                                    <textarea name="replacement_reason" class="form-control" rows="3" placeholder="Contoh: Saya sedang sakit..." required minlength="5"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning px-4 fw-bold">Simpan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <tr id="empty-row">
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-calendar-x display-1 text-light mb-3"></i>
                                        <p class="mb-0 fw-medium">Belum ada jadwal kunjungan OIKOS.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Modal Input Laporan --}}
    <div class="modal fade" id="laporanModal" tabindex="-1" aria-labelledby="laporanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h1 class="modal-title fs-5 fw-bold" id="laporanModalLabel">Input Laporan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-4">
                    <form id="laporanForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3">Realisasi</h6>
                            <div class="mb-3">
                                <label for="realisasi_date" class="form-label small fw-bold">Tanggal Kunjungan</label>
                                <input type="date" id="realisasi_date" name="realisasi_date" class="form-control bg-light" required>
                            </div>
                            
                            <div class="d-flex gap-3 flex-column flex-sm-row">
                                <div class="flex-fill p-3 rounded-3 border bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_doa_5_jari" name="is_doa_5_jari" value="1">
                                        <label class="form-check-label fw-semibold" for="is_doa_5_jari">Doa 5 Jari</label>
                                    </div>
                                    <div id="dateInputContainerDLJ" class="d-none mt-2">
                                        <input type="date" id="realisasi_doa_5_jari_date" name="realisasi_doa_5_jari_date" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="flex-fill p-3 rounded-3 border bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_doa_syafaat" name="is_doa_syafaat" value="1">
                                        <label class="form-check-label fw-semibold" for="is_doa_syafaat">Doa Syafaat</label>
                                    </div>
                                    <div id="dateInputContainerDS" class="d-none mt-2">
                                        <input type="date" id="realisasi_doa_syafaat_date" name="realisasi_doa_syafaat_date" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3">Tindakan Kasih</h6>
                            <div class="card mb-3 border-0 bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="tindakanCintaOikos" role="switch">
                                        <label class="form-check-label fw-bold" for="tindakanCintaOikos">1. Tindakan Cinta OIKOS</label>
                                    </div>
                                    <div id="hiddenInputContainerTCO" class="d-none mt-3 ps-4 border-start border-primary border-3">
                                        <div class="mb-2">
                                            <textarea id="tindakan_cinta_desc" name="tindakan_cinta_desc" rows="2" class="form-control" placeholder="Deskripsi tindakan..."></textarea>
                                        </div>
                                        <input type="file" id="tindakan_cinta_photo_path" name="tindakan_cinta_photo_path" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="tindakanPedulihOikos" role="switch">
                                        <label class="form-check-label fw-bold" for="tindakanPedulihOikos">2. Tindakan Peduli OIKOS</label>
                                    </div>
                                    <div id="hiddenInputContainerTPO" class="d-none mt-3 ps-4 border-start border-primary border-3">
                                        <div class="mb-2">
                                            <textarea id="tindakan_peduli_desc" name="tindakan_peduli_desc" rows="2" class="form-control" placeholder="Deskripsi tindakan..."></textarea>
                                        </div>
                                        <input type="file" id="tindakan_peduli_photo_path" name="tindakan_peduli_photo_path" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3">Hasil & Respon</h6>
                            <div class="p-3 bg-light rounded-3 mb-3">
                                <label class="form-label small fw-bold mb-2">Respon Terhadap Injil</label>
                                <select name="respon_injil" class="form-select">
                                    <option value="" selected disabled>Pilih Respon...</option>
                                    <option value="bermusuhan">a. Sikap Bermusuhan</option>
                                    <option value="netral">b. Netral</option>
                                    <option value="tertarik">c. Tertarik</option>
                                    <option value="tertarik_murni">d. Tertarik Murni</option>
                                    <option value="keputusan">e. Keputusan</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="catatan" class="form-label small fw-bold">Catatan Tambahan</label>
                                <textarea id="catatan" name="catatan" rows="3" class="form-control bg-light" placeholder="Ceritakan hal menarik lainnya..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="laporanForm" class="btn btn-primary px-4 fw-bold rounded-pill">Kirim Laporan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Lihat Laporan --}}
    <div class="modal fade" id="viewLaporanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h1 class="modal-title fs-5 fw-bold" id="viewLaporanModalLabel">Detail Laporan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-center text-secondary py-5">Memuat Laporan...</p>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIKA MODAL LIHAT LAPORAN ---
    const viewLaporanModalEl = document.getElementById('viewLaporanModal');
    if (viewLaporanModalEl) {
        viewLaporanModalEl.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const visitId = button.getAttribute('data-visit-id');
            const modalTitle = viewLaporanModalEl.querySelector('#viewLaporanModalLabel');
            const modalBody = viewLaporanModalEl.querySelector('.modal-body');
            
            modalTitle.textContent = `Laporan Kunjungan...`;
            modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-secondary">Memuat data...</p></div>';

            try {
                let url = "{{ route('oikos.report.show', ':id') }}";
                url = url.replace(':id', visitId);

                const response = await fetch(url);
                if (!response.ok) throw new Error('Gagal memuat data laporan.');
                
                const data = await response.json();
                const oikosNama = data.jemaat_data ? data.jemaat_data.nama : data.oikos_name;
                modalTitle.textContent = `Laporan: ${oikosNama}`;

                const formatDate = (dateString) => {
                    if (!dateString) return '-';
                    const date = new Date(dateString.split(' ')[0] + 'T00:00:00');
                    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                };

                const photoCintaHtml = data.tindakan_cinta_photo_path 
                    ? `<div class="mt-2"><span class="badge bg-light text-dark border mb-1">Foto Cinta OIKOS</span><br><a href="/storage/${data.tindakan_cinta_photo_path}" target="_blank"><img src="/storage/${data.tindakan_cinta_photo_path}" class="img-fluid rounded shadow-sm" style="max-height:200px; object-fit:cover;"></a></div>`
                    : '';
                const photoPeduliHtml = data.tindakan_peduli_photo_path 
                    ? `<div class="mt-2"><span class="badge bg-light text-dark border mb-1">Foto Peduli OIKOS</span><br><a href="/storage/${data.tindakan_peduli_photo_path}" target="_blank"><img src="/storage/${data.tindakan_peduli_photo_path}" class="img-fluid rounded shadow-sm" style="max-height:200px; object-fit:cover;"></a></div>`
                    : '';

                modalBody.innerHTML = `
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3">
                                <div class="row">
                                    <div class="col-6 mb-2"><small class="text-uppercase text-secondary fw-bold">Tanggal</small><div class="fw-semibold">${formatDate(data.realisasi_date)}</div></div>
                                    <div class="col-6 mb-2"><small class="text-uppercase text-secondary fw-bold">Pelayan</small><div class="fw-semibold">${data.pelayan_data ? data.pelayan_data.nama : 'N/A'}</div></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="h-100 p-3 border rounded-3">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">Aktivitas Doa</h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span>Doa 5 Jari:</span>
                                    <span class="fw-bold ${data.is_doa_5_jari ? 'text-success' : 'text-secondary'}">${data.is_doa_5_jari ? 'YA' : 'TIDAK'}</span>
                                </div>
                                ${data.is_doa_5_jari ? `<small class="text-muted d-block mb-3">Tgl: ${formatDate(data.realisasi_doa_5_jari_date)}</small>` : ''}
                                
                                <div class="mb-2 d-flex justify-content-between">
                                    <span>Doa Syafaat:</span>
                                    <span class="fw-bold ${data.is_doa_syafaat ? 'text-success' : 'text-secondary'}">${data.is_doa_syafaat ? 'YA' : 'TIDAK'}</span>
                                </div>
                                ${data.is_doa_syafaat ? `<small class="text-muted d-block">Tgl: ${formatDate(data.realisasi_doa_syafaat_date)}</small>` : ''}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="h-100 p-3 border rounded-3">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">Respon & Hasil</h6>
                                <div class="mb-1 text-secondary small text-uppercase fw-bold">Respon Injil</div>
                                <div class="fw-semibold mb-3 text-primary">${data.respon_injil ? data.respon_injil.toUpperCase() : '-'}</div>
                                
                                <div class="mb-1 text-secondary small text-uppercase fw-bold">Catatan</div>
                                <div class="fst-italic text-muted small">${data.catatan || 'Tidak ada catatan.'}</div>
                            </div>
                        </div>

                        <div class="col-12">
                             <h6 class="fw-bold border-bottom pb-2 mb-3 mt-2">Tindakan Kasih & Dokumentasi</h6>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="fw-bold text-dark mb-1">Cinta OIKOS</div>
                                    <p class="small text-muted mb-2">${data.tindakan_cinta_desc || '-'}</p>
                                    ${photoCintaHtml}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="fw-bold text-dark mb-1">Peduli OIKOS</div>
                                    <p class="small text-muted mb-2">${data.tindakan_peduli_desc || '-'}</p>
                                    ${photoPeduliHtml}
                                </div>
                             </div>
                        </div>
                    </div>
                `;

            } catch (error) {
                modalBody.innerHTML = `<div class="text-center py-4 text-danger"><i class="bi bi-exclamation-circle fs-1 mb-2"></i><p>${error.message}</p></div>`;
            }
        });
    }

    // --- LOGIKA MODAL INPUT LAPORAN ---
    const laporanModalEl = document.getElementById('laporanModal');
    if (laporanModalEl) {
        const laporanForm = document.getElementById('laporanForm');
        laporanModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const oikosNama = button.getAttribute('data-oikos-nama');
            const visitId = button.getAttribute('data-visit-id');
            
            const modalTitle = laporanModalEl.querySelector('#laporanModalLabel');
            modalTitle.textContent = `Laporan: ${oikosNama}`;

            let url = "{{ route('oikos.report.store', ':id') }}";
            url = url.replace(':id', visitId);
            laporanForm.action = url;
        });

        laporanModalEl.addEventListener('hidden.bs.modal', function () {
            laporanForm.reset();
            document.getElementById('dateInputContainerDLJ').classList.add('d-none');
            document.getElementById('dateInputContainerDS').classList.add('d-none');
            document.getElementById('hiddenInputContainerTCO').classList.add('d-none');
            document.getElementById('hiddenInputContainerTPO').classList.add('d-none');
        });
    }

    // --- SCRIPT INTERAKTIF FORM --- 
    const setupToggle = (checkboxId, containerId) => {
        const checkbox = document.getElementById(checkboxId);
        const container = document.getElementById(containerId);
        if (checkbox && container) {
            checkbox.addEventListener('change', function() {
                container.classList.toggle('d-none', !this.checked);
            });
        }
    };

    setupToggle('is_doa_5_jari', 'dateInputContainerDLJ');
    setupToggle('is_doa_syafaat', 'dateInputContainerDS');
    setupToggle('tindakanCintaOikos', 'hiddenInputContainerTCO');
    setupToggle('tindakanPedulihOikos', 'hiddenInputContainerTPO');
    
    // --- SCRIPT FILTER ---
    const filterButtons = document.querySelectorAll('.filter-nav-btn');
    const slider = document.querySelector('.filter-slider');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const emptyRow = document.getElementById('empty-row'); 

    function moveSlider(targetButton) { if (!targetButton) return; const targetRect = targetButton.getBoundingClientRect(); const containerRect = targetButton.parentElement.getBoundingClientRect(); slider.style.width = `${targetRect.width}px`; slider.style.transform = `translateX(${targetRect.left - containerRect.left}px)`; }
    
    const initialActiveButton = document.querySelector('.filter-nav-btn.active');
    if (initialActiveButton) { moveSlider(initialActiveButton); }
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            moveSlider(this);
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            let visibleRows = 0;

            tableRows.forEach(row => {
                if (row.id === 'empty-row') return; 
                
                const rowStatus = row.dataset.status; 
                
                if (filterValue === 'all' || rowStatus === filterValue) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyRow) {
                emptyRow.style.display = (visibleRows === 0) ? '' : 'none';
            }
        });
    });
    window.addEventListener('resize', () => { const currentActiveButton = document.querySelector('.filter-nav-btn.active'); if (currentActiveButton) { moveSlider(currentActiveButton); } });
});
</script>
@endpush