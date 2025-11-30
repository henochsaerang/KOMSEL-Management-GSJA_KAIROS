@extends('layouts.app')

@section('title', 'Daftar Kunjungan')

@push('styles')
<style>
    /* DARK MODE COMPATIBLE STYLES */
    body { background-color: var(--bs-body-bg); }
    
    /* === FILTER & SLIDER === */
    .filter-wrapper {
        display: flex; justify-content: center; width: 100%; margin-bottom: 1.5rem;
    }
    .filter-nav-container {
        background-color: var(--hover-bg); padding: 4px; border-radius: 99px;
        display: inline-flex; position: relative; border: 1px solid var(--border-color); flex-shrink: 0;
    }
    .filter-nav-btn {
        border: none; background: transparent; color: var(--text-secondary);
        font-weight: 600; font-size: 0.85rem; padding: 8px 24px;
        border-radius: 99px; cursor: pointer; z-index: 2; position: relative;
        transition: color 0.2s ease; white-space: nowrap;
    }
    .filter-nav-btn.active { color: #fff; }
    .filter-slider {
        position: absolute; top: 4px; bottom: 4px; left: 4px;
        background: var(--primary-color); border-radius: 99px; z-index: 1;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Mobile Filter Scroll */
    @media (max-width: 576px) {
        .filter-wrapper { 
            display: block; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; 
            text-align: left; margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; width: calc(100% + 2rem);
            -webkit-overflow-scrolling: touch;
        }
        .filter-nav-container { display: inline-flex; }
        .filter-wrapper::-webkit-scrollbar { display: none; }
    }

    /* === DESKTOP TABLE STYLING === */
    .card-table {
        background: var(--element-bg); 
        border: 1px solid var(--border-color);
        border-radius: 1rem; 
        box-shadow: var(--shadow-sm); 
        overflow: hidden;
    }
    .table { margin-bottom: 0; width: 100%; border-collapse: collapse; }
    .table thead th {
        background-color: var(--element-bg-subtle); 
        color: var(--text-secondary);
        font-weight: 700; text-transform: uppercase; font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border-color); padding: 1rem 1.5rem;
        vertical-align: middle;
    }
    .table tbody td {
        padding: 1rem 1.5rem; vertical-align: middle; color: var(--bs-body-color);
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table-hover tbody tr:hover td { background-color: var(--hover-bg); }
    
    /* Badges */
    .badge-jenis {
        background: var(--primary-bg-subtle); color: var(--primary-color);
        border: 1px solid rgba(79, 70, 229, 0.2); padding: 0.4em 0.8em; font-weight: 600;
    }

    /* === MODAL STYLING === */
    .modal-content { background-color: var(--element-bg); border: 1px solid var(--border-color); color: var(--bs-body-color); }
    .modal-header { border-bottom: 1px solid var(--border-color); }
    .modal-footer { border-top: 1px solid var(--border-color); }
    .form-control, .form-select { background-color: var(--input-bg); color: var(--bs-body-color); border-color: var(--border-color); }
    .form-control:focus { border-color: var(--primary-color); }
    .btn-close { filter: var(--bs-btn-close-filter); } 
    
    .modal-body code, .table code, .table span {
        background-color: transparent !important;
    }

    /* === MOBILE CARD VIEW TRANSFORMATION === */
    @media (max-width: 768px) {
        .table thead { display: none; }
        .table, .table tbody, .table tr, .table td { display: block; width: 100%; }
        
        /* Card Style */
        .table tbody tr {
            margin-bottom: 1rem; background-color: var(--element-bg) !important;
            border: 1px solid var(--border-color); border-radius: 16px; padding: 1.25rem;
            position: relative; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 0.5rem;
        }
        
        .table td { border: none; padding: 0; background-color: transparent !important; text-align: left; }

        /* 1. Header: Waktu (Top Left) */
        .table td:nth-child(1) { 
            order: 1; padding-bottom: 0.75rem; margin-bottom: 0.5rem;
            border-bottom: 1px dashed var(--border-color) !important; display: flex; align-items: center;
            background-color: var(--element-bg-subtle) !important; padding-top: 0; /* Reset */
        }

        /* 2. Status (Top Right Absolute) */
        .table td:nth-child(4) { 
            position: absolute; top: 1.25rem; right: 1.25rem; width: auto; order: 0; z-index: 2; padding: 0;
        }

        /* 3. Body: Nama & PIC */
        .table td:nth-child(2) { order: 2; margin-bottom: 0.25rem; }
        .table td:nth-child(2) .fw-bold { font-size: 1.1rem; color: var(--bs-body-color) !important; display: block; margin-bottom: 0.25rem; }
        .table td:nth-child(2) small { font-size: 0.85rem; color: var(--text-secondary); }

        /* 4. Jenis */
        .table td:nth-child(3) { order: 3; margin-bottom: 1rem; }

        /* 5. Footer: Aksi */
        .table td:nth-child(5) { 
            order: 4; padding-top: 1rem; 
            border-top: 1px solid var(--border-color) !important; 
            display: flex; justify-content: flex-end;
        }
        .table td:nth-child(5) .d-flex { width: 100%; gap: 0.5rem; }
        .table td:nth-child(5) .btn { flex: 1; justify-content: center; }
        .table td:nth-child(5) .btn-light { flex: 0 0 auto; width: 40px; }
        
        #empty-row td { text-align: center; width: 100%; display: block; padding: 3rem 1rem; }
    }
</style>
@endpush

@section('konten')

    {{-- ALERT NOTIFIKASI --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTER NAVIGATION --}}
    <div class="filter-wrapper">
        <div class="filter-nav-container">
            <div class="filter-slider"></div>
            <button type="button" class="filter-nav-btn active" data-filter="all">Semua</button>
            <button type="button" class="filter-nav-btn" data-filter="Terjadwal">Terjadwal</button>
            <button type="button" class="filter-nav-btn" data-filter="Diproses">Menunggu ACC</button>
            <button type="button" class="filter-nav-btn" data-filter="Selesai">Selesai</button>
        </div>
    </div>
    
    <div class="card card-table bg-transparent shadow-none border-0"> 
        <div class="card-body p-0">
            {{-- HEADER TOOLBAR --}}
            <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3 rounded-top-4 mb-3 mb-md-0" 
                 style="background: var(--element-bg); border-color: var(--border-color)!important; border: 1px solid var(--border-color); border-bottom: none !important;">
                <div>
                    <h5 class="fw-bold mb-1 text-adaptive" style="color: var(--bs-body-color);">Daftar Kunjungan</h5>
                    <p class="text-secondary small mb-0">Riwayat dan jadwal kunjungan komsel</p>
                </div>
                <a href="{{ route('kunjungan.create') }}" class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Catat
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-transparent">
                    <thead class="d-none d-md-table-header-group">
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Anggota & PIC</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent">
                        {{-- [FIX] Variabel sekarang menggunakan $kunjungans (sesuai Controller) --}}
                        @forelse($kunjungans as $visit)
                        <tr data-status="{{ $visit['status'] }}">
                            {{-- 1. WAKTU --}}
                            <td class="ps-md-4">
                                <span class="fw-bold text-body">{{ $visit['tanggal']->format('d M Y') }}</span>
                                <span class="text-secondary d-inline-block ms-1">{{ $visit['tanggal']->format('H:i') }}</span>
                            </td>

                            {{-- 2. ANGGOTA --}}
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-primary fs-6">{{ $visit['nama_anggota'] }}</span>
                                    <small class="text-secondary mt-1" style="font-size: 0.8rem;">
                                        <i class="bi bi-person-badge me-1"></i>{{ $visit['pic'] }}
                                    </small>
                                </div>
                            </td>

                            {{-- 3. JENIS --}}
                            <td>
                                <span class="badge badge-jenis rounded-pill fw-normal">
                                    {{ $visit['jenis_kunjungan'] }}
                                </span>
                            </td>

                            {{-- 4. STATUS --}}
                            <td>
                                @php
                                    $badgeClass = match($visit['status']) {
                                        'Terjadwal' => 'bg-warning text-dark bg-opacity-25',
                                        'Diproses' => 'bg-info text-dark bg-opacity-25',
                                        'Selesai' => 'bg-success text-success bg-opacity-10 border border-success border-opacity-25',
                                        'Batal' => 'bg-danger text-danger bg-opacity-10',
                                        default => 'bg-secondary'
                                    };
                                    $icon = match($visit['status']) {
                                        'Terjadwal' => 'bi-hourglass-split',
                                        'Diproses' => 'bi-clock-history',
                                        'Selesai' => 'bi-check-circle-fill',
                                        'Batal' => 'bi-x-circle',
                                        default => ''
                                    };
                                @endphp
                                <span class="badge rounded-pill px-3 py-2 {{ $badgeClass }}">
                                    <i class="bi {{ $icon }} me-1"></i> {{ $visit['status'] }}
                                </span>
                            </td>

                            {{-- 5. AKSI --}}
                            <td class="text-end pe-md-4">
                                <div class="d-flex justify-content-end gap-2 w-100">
                                    
                                    {{-- TOMBOL LAPOR --}}
                                    @if(($visit['status'] == 'Terjadwal' || $visit['status'] == 'Diproses'))
                                    <button class="btn btn-sm btn-outline-success fw-medium d-flex align-items-center flex-grow-1 flex-md-grow-0 justify-content-center" 
                                            onclick="openReportModal(this)"
                                            data-id="{{ $visit['id'] }}"
                                            data-nama="{{ $visit['nama_anggota'] }}"
                                            data-catatan="{{ $visit['catatan'] }}"
                                            data-photo="{{ $visit['photo_path'] ? asset('storage/'.$visit['photo_path']) : '' }}"
                                            title="Input Laporan">
                                        <i class="bi bi-pencil-square me-1"></i> Lapor
                                    </button>
                                    @endif

                                    {{-- TOMBOL KONFIRMASI --}}
                                    @if($visit['status'] == 'Diproses' && $canApprove)
                                        <form action="{{ route('kunjungan.confirm', $visit['id']) }}" method="POST" class="d-inline flex-grow-1 flex-md-grow-0" onsubmit="return confirm('Konfirmasi kunjungan ini sebagai Selesai?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center w-100 justify-content-center" title="Setujui Laporan">
                                                <i class="bi bi-check-lg me-1"></i> ACC
                                            </button>
                                        </form>
                                    @endif

                                    {{-- LIHAT DETAIL --}}
                                    @if($visit['status'] == 'Selesai')
                                        <button class="btn btn-sm btn-light border text-secondary flex-grow-1 flex-md-grow-0" 
                                                onclick="openReportModal(this)" 
                                                data-id="{{ $visit['id'] }}" 
                                                data-nama="{{ $visit['nama_anggota'] }}"
                                                data-catatan="{{ $visit['catatan'] }}"
                                                data-photo="{{ $visit['photo_path'] ? asset('storage/'.$visit['photo_path']) : '' }}"
                                                data-readonly="true"
                                                title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i> Detail
                                        </button>
                                    @endif

                                    {{-- HAPUS --}}
                                    <form action="{{ route('kunjungan.destroy', $visit['id']) }}" method="POST" onsubmit="return confirm('Hapus data kunjungan ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="empty-row">
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center opacity-50">
                                    <i class="bi bi-calendar-x display-4 text-secondary mb-2"></i>
                                    <p class="text-secondary mb-0">Belum ada data kunjungan.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL LAPORAN / DETAIL --}}
    <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-body">Laporan Realisasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reportForm" method="POST" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <div class="modal-body pt-4">
                        <div class="p-3 rounded-3 mb-4 d-flex align-items-center" style="background: var(--primary-bg-subtle); color: var(--primary-color);">
                            <i class="bi bi-person-fill fs-4 me-3"></i>
                            <div>
                                <div class="small text-uppercase fw-bold opacity-75">Target Kunjungan</div>
                                <div class="fw-bold fs-5" id="modalNamaAnggota">Nama Anggota</div>
                            </div>
                        </div>

                        {{-- Status Akhir --}}
                        <div class="mb-4" id="statusGroup">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Status Akhir</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="status_akhir" id="statusSelesai" value="Selesai" checked>
                                    <label class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center gap-2 py-2" for="statusSelesai">
                                        <i class="bi bi-check-circle-fill"></i> Berhasil
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="status_akhir" id="statusBatal" value="Batal">
                                    <label class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2 py-2" for="statusBatal">
                                        <i class="bi bi-x-circle-fill"></i> Batal
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Upload Foto --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Dokumentasi</label>
                            
                            {{-- Input File --}}
                            <input type="file" name="bukti_foto" id="buktiFotoInput" class="form-control mb-2" accept="image/*">
                            
                            {{-- Image Preview --}}
                            <div id="photoPreviewBox" class="position-relative d-none mt-2 border rounded overflow-hidden text-center bg-black">
                                <img id="fotoDisplay" src="" class="img-fluid" style="max-height: 250px; object-fit: contain;">
                                <button type="button" id="removePhotoBtn" class="btn btn-sm btn-dark position-absolute top-0 end-0 m-2 rounded-circle opacity-75">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div id="noPhotoText" class="text-secondary small fst-italic d-none">Tidak ada foto dilampirkan.</div>
                        </div>

                        {{-- CATATAN --}}
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Catatan Hasil</label>
                            <textarea name="catatan_hasil" id="catatanInput" class="form-control" rows="4" placeholder="Ceritakan hasil kunjungan..." required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light" style="background: var(--hover-bg); color: var(--bs-body-color); border-color: var(--border-color);" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" id="btnSimpan" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm">Simpan Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openReportModal(btn) {
        const modal = new bootstrap.Modal(document.getElementById('reportModal'));
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        const catatan = btn.getAttribute('data-catatan');
        const photoUrl = btn.getAttribute('data-photo');
        const isReadonly = btn.getAttribute('data-readonly') === 'true';

        document.getElementById('modalNamaAnggota').textContent = nama;
        const catatanInput = document.getElementById('catatanInput');
        catatanInput.value = catatan || '';
        
        let url = "{{ route('kunjungan.report', ':id') }}".replace(':id', id);
        document.getElementById('reportForm').action = url;

        const photoBox = document.getElementById('photoPreviewBox');
        const photoDisplay = document.getElementById('fotoDisplay');
        const fileInput = document.getElementById('buktiFotoInput');
        const noPhotoText = document.getElementById('noPhotoText');
        
        fileInput.value = '';

        if (photoUrl) {
            photoDisplay.src = photoUrl;
            photoBox.classList.remove('d-none');
            noPhotoText.classList.add('d-none');
        } else {
            photoBox.classList.add('d-none');
            noPhotoText.classList.toggle('d-none', !isReadonly);
        }

        const statusGroup = document.getElementById('statusGroup');
        const submitBtn = document.getElementById('btnSimpan');
        const removePhotoBtn = document.getElementById('removePhotoBtn');

        if (isReadonly) {
            statusGroup.classList.add('d-none');
            fileInput.classList.add('d-none');
            catatanInput.disabled = true;
            submitBtn.classList.add('d-none');
            if(removePhotoBtn) removePhotoBtn.classList.add('d-none');
            document.querySelector('.modal-title').textContent = "Detail Kunjungan";
        } else {
            statusGroup.classList.remove('d-none');
            fileInput.classList.remove('d-none');
            catatanInput.disabled = false;
            submitBtn.classList.remove('d-none');
            if(removePhotoBtn) removePhotoBtn.classList.remove('d-none');
            document.querySelector('.modal-title').textContent = "Laporan Realisasi";
        }

        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btns = document.querySelectorAll('.filter-nav-btn');
        const slider = document.querySelector('.filter-slider');
        const rows = document.querySelectorAll('tbody tr:not(#empty-row)');
        const emptyRow = document.getElementById('empty-row');

        const moveSlider = (el) => {
            const rect = el.getBoundingClientRect();
            const parent = el.parentElement.getBoundingClientRect();
            slider.style.width = `${rect.width}px`;
            slider.style.transform = `translateX(${rect.left - parent.left}px)`;
        };
        
        const active = document.querySelector('.filter-nav-btn.active');
        if(active) setTimeout(() => moveSlider(active), 50);

        btns.forEach(btn => {
            btn.addEventListener('click', function() {
                moveSlider(this);
                btns.forEach(b => b.classList.remove('active')); 
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                let count = 0;
                rows.forEach(r => {
                    if(filter === 'all' || r.dataset.status === filter) {
                        r.style.display = ''; count++;
                    } else {
                        r.style.display = 'none';
                    }
                });
                if(emptyRow) emptyRow.style.display = count === 0 ? '' : 'none';
            });
        });

        const fileInput = document.getElementById('buktiFotoInput');
        const previewImg = document.getElementById('fotoDisplay');
        const previewBox = document.getElementById('photoPreviewBox');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewBox.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
        
        const removeBtn = document.getElementById('removePhotoBtn');
        if(removeBtn) {
            removeBtn.addEventListener('click', () => {
                fileInput.value = '';
                previewBox.classList.add('d-none');
                previewImg.src = '';
            });
        }
        
        window.addEventListener('resize', () => {
            const active = document.querySelector('.filter-nav-btn.active');
            if(active) moveSlider(active);
        });
    });
</script>
@endpush