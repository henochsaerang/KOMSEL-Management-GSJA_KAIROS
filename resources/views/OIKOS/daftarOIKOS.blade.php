@extends('layouts.app')

@section('title', 'Daftar Kunjungan OIKOS')

@push('styles')
{{-- Flatpickr CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">

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

    /* === BULK ACTION FLOATING BAR === */
    #bulkActionBar {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(150%);
        background: var(--element-bg); border: 1px solid var(--border-color);
        padding: 10px 20px; border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 15px;
        z-index: 1060; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    #bulkActionBar.show { transform: translateX(-50%) translateY(0); }

    /* === INPUT GROUP STYLE (FLATPICKR) === */
    .input-group-text { background-color: var(--input-bg); border-color: var(--border-color); color: var(--text-secondary); }
    .input-date-custom { background-color: #fff !important; border-color: var(--border-color); color: var(--bs-body-color); }
    
    .flatpickr-calendar { background: var(--element-bg); border-color: var(--border-color); box-shadow: var(--shadow-md); }
    .flatpickr-day { color: var(--bs-body-color); }
    .flatpickr-day.flatpickr-disabled { color: var(--text-secondary); opacity: 0.3; }
    .flatpickr-current-month { color: var(--bs-body-color); }
    .flatpickr-weekday { color: var(--text-secondary); }

    /* === MOBILE CARD VIEW TRANSFORMATION === */
    @media (max-width: 768px) {
        .table-responsive, .card, .card-body, .table { overflow: visible !important; }
        .table thead { display: none; }
        .table, .table tbody, .table tr, .table td { display: block; width: 100%; }

        .table tbody tr {
            margin-bottom: 1.5rem; background-color: var(--element-bg);
            border: 1px solid var(--border-color); border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative;
            padding: 1.25rem; padding-top: 2.5rem; padding-left: 3.5rem; 
        }

        .table td { text-align: left; padding: 0.25rem 0; border: none; position: relative; }

        .table td:nth-child(1) { position: absolute; top: 1.2rem; left: 1.2rem; width: auto; padding: 0; z-index: 5; }
        .form-check-input.select-item { width: 1.3em; height: 1.3em; cursor: pointer; }

        .table td:nth-child(2) { display: none; } 
        
        .table td:nth-child(3) { font-size: 1.25rem; font-weight: 800; color: var(--bs-body-color); margin-bottom: 0.5rem; padding-right: 40px; }
        .table td:nth-child(4) { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--border-color); }
        .table td:nth-child(5) { position: absolute; top: 0; left: 0; width: auto; padding: 0; }
        .table td:nth-child(5) .badge {
            border-top-left-radius: 15px; border-bottom-right-radius: 15px; 
            border-top-right-radius: 0; border-bottom-left-radius: 0;
            padding: 0.5rem 1rem 0.5rem 3.5rem; font-size: 0.75rem;
        }
        
        .table td:nth-child(6) { font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; }
        .table td:nth-child(7) { position: absolute; top: 10px; right: 10px; width: auto; background: transparent !important; z-index: 100; }

        .btn-floating-action {
            width: 42px; height: 42px; border-radius: 50%; background: #fff; 
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            display: flex; align-items: center; justify-content: center;
            color: var(--text-primary); transition: transform 0.2s;
        }
        .btn-floating-action:active { transform: scale(0.95); }
        .dropdown-menu { position: absolute; transform: translate3d(-100%, 10px, 0px) !important; top: 0; left: 0; width: 220px; z-index: 9999 !important; }
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
            <button type="button" class="filter-nav-btn" data-filter="Revisi">Revisi</button>
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
            <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="card bg-transparent border-0 shadow-none" style="overflow: visible !important;">
        <div class="card-body p-0 p-md-4 bg-transparent" style="overflow: visible !important;">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-4 rounded-4 shadow-sm border">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-house-heart-fill me-2" style="color: var(--primary-color);"></i>Daftar Kunjungan OIKOS</h5>
                <a href="{{ route('formInput') }}" class="btn btn-primary fw-semibold rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Buat Jadwal</a>
            </div>
            
            <form id="bulkDeleteForm" action="{{ route('oikos.bulk_destroy') }}" method="POST">
                @csrf @method('DELETE')
                
                <div class="card border-0 shadow-sm rounded-4 bg-white" style="overflow: visible !important;">
                    <div class="table-responsive" style="overflow: visible !important;">
                        <table class="table table-hover align-middle mb-0" style="overflow: visible !important;">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="py-3 px-4 text-center text-muted" style="width: 5%">#</th>
                                    <th scope="col" class="py-3" style="width: 5%">No.</th>
                                    <th scope="col" class="py-3" style="width: 25%">Nama OIKOS</th>
                                    <th scope="col" class="py-3" style="width: 20%">Pelayan</th>
                                    <th scope="col" class="py-3" style="width: 15%">Status</th>
                                    <th scope="col" class="py-3" style="width: 20%">Jadwal</th>
                                    <th scope="col" class="text-center py-3 px-4" style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($oikosVisits as $visit)
                                <tr data-status="{{ $visit->status }}">
                                    <td class="px-4 text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $visit->id }}" class="form-check-input select-item border-secondary" style="cursor: pointer;">
                                    </td>
                                    <td class="fw-bold">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-md-none text-secondary small text-uppercase fw-bold mb-1">Nama OIKOS</div>
                                        {{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}
                                    </td>
                                    <td>
                                        @if($visit->pelayan_data)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle sm bg-primary text-white me-2" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:0.85em;font-weight:600;">{{ substr($visit->pelayan_data['name'] ?? $visit->pelayan_data['nama'] ?? '?', 0, 1) }}</div>
                                                <div>
                                                    <span class="d-block lh-sm fw-semibold">{{ $visit->pelayan_data['name'] ?? $visit->pelayan_data['nama'] }}</span>
                                                    @if($visit->original_pelayan_user_id) <span class="text-danger small fw-bold" style="font-size: 0.7rem;"><i class="bi bi-arrow-return-right"></i> Pengganti</span> @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted fst-italic">Belum ditentukan</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span @class([
                                            'badge', 
                                            'text-bg-warning' => $visit->status == 'Direncanakan', 
                                            'text-bg-primary' => $visit->status == 'Berlangsung', 
                                            'text-bg-info' => $visit->status == 'Diproses', 
                                            'text-bg-success' => $visit->status == 'Selesai', 
                                            'text-bg-danger' => $visit->status == 'Gagal', 
                                            'text-bg-secondary' => $visit->status == 'Revisi' || $visit->status == 'Menunggu Persetujuan'
                                        ])>
                                            @if($visit->status == 'Berlangsung') Draft
                                            @elseif($visit->status == 'Menunggu Persetujuan') Menunggu ACC
                                            @else {{ $visit->status }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center text-secondary">
                                            <i class="bi bi-calendar4-week me-2"></i>
                                            <span>{{ \Carbon\Carbon::parse($visit->start_date)->format('j M') }} - {{ \Carbon\Carbon::parse($visit->end_date)->format('j M Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center px-4">
                                        <div class="dropdown">
                                            <button class="btn btn-floating-action" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-4">
                                                <li><a class="dropdown-item rounded-2 py-2 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#viewLaporanModal" data-visit-id="{{ $visit->id }}"><i class="bi bi-eye me-2 text-primary"></i>Detail</a></li>
                                                
                                                @php $isUnlocked = $visit->report_unlock_until && \Carbon\Carbon::now()->lte($visit->report_unlock_until); @endphp
                                                
                                                {{-- LOGIKA TOMBOL INPUT LAPORAN (TERMASUK UNTUK DRAFT/REVISI) --}}
                                                {{-- Status "Menunggu Persetujuan" TIDAK BOLEH isi laporan --}}
                                                @if(in_array($visit->status, ['Direncanakan', 'Berlangsung', 'Revisi']))
                                                    @if($isReportDay || $visit->status == 'Revisi' || $isUnlocked || $visit->status == 'Berlangsung')
                                                        <li>
                                                            <a class="dropdown-item rounded-2 py-2 mb-1" href="#" 
                                                               data-bs-toggle="modal" 
                                                               data-bs-target="#laporanModal" 
                                                               data-visit-id="{{ $visit->id }}" 
                                                               data-oikos-nama="{{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}" 
                                                               data-revision-note="{{ $visit->status == 'Revisi' ? $visit->revision_comment : '' }}"
                                                               data-is-unlocked="{{ $isUnlocked ? '1' : '0' }}"
                                                               data-visit-status="{{ $visit->status }}">
                                                                
                                                                @if($visit->status == 'Revisi') 
                                                                    <span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-2"></i>Perbaiki</span> 
                                                                @elseif($visit->status == 'Berlangsung')
                                                                    <span class="text-primary fw-bold"><i class="bi bi-pencil-square me-2"></i>Lanjutkan</span>
                                                                @else 
                                                                    <i class="bi bi-pencil-square me-2 text-warning"></i>Input Laporan 
                                                                @endif
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><a class="dropdown-item rounded-2 py-2 mb-1 text-secondary" href="#" data-bs-toggle="modal" data-bs-target="#modalDelegate{{ $visit->id }}"><i class="bi bi-person-bounding-box me-2"></i>Ganti Pelayan</a></li>
                                                @endif

                                                {{-- KHUSUS ADMIN: APPROVE JADWAL / KONFIRMASI LAPORAN --}}
                                                @if(Auth::check() && is_array(Auth::user()->roles) && in_array('super_admin', Auth::user()->roles))
                                                    {{-- Approve Request Jadwal --}}
                                                    @if($visit->status == 'Menunggu Persetujuan')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a href="{{ route('oikos.approve_schedule', $visit->id) }}" class="dropdown-item rounded-2 py-2 text-success fw-bold bg-success bg-opacity-10 mb-1">
                                                                <i class="bi bi-check2-all me-2"></i>Setujui Jadwal
                                                            </a>
                                                        </li>
                                                    @endif

                                                    {{-- Konfirmasi Laporan Selesai --}}
                                                    @if($visit->status == 'Diproses')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><button type="submit" form="confirmForm{{$visit->id}}" class="dropdown-item rounded-2 py-2 text-success fw-bold bg-success bg-opacity-10 mb-1"><i class="bi bi-check-circle-fill me-2"></i>Konfirmasi</button></li>
                                                        <li><button type="button" class="dropdown-item rounded-2 py-2 text-danger fw-bold bg-danger bg-opacity-10" data-bs-toggle="modal" data-bs-target="#modalRevision{{ $visit->id }}"><i class="bi bi-arrow-counterclockwise me-2"></i>Revisi</button></li>
                                                    @endif
                                                @endif

                                                <li><hr class="dropdown-divider"></li>
                                                <li><form action="{{ route('oikos.destroy', $visit->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');">@csrf @method('DELETE')<button type="submit" class="dropdown-item rounded-2 py-2 text-danger"><i class="bi bi-trash me-2"></i>Hapus</button></form></li>
                                            </ul>
                                        </div>
                                        @if($visit->status == 'Diproses')<form id="confirmForm{{$visit->id}}" action="{{ route('oikos.confirm', $visit->id) }}" method="POST" onsubmit="return confirm('Konfirmasi selesai?')">@csrf @method('PATCH')</form>@endif
                                    </td>
                                </tr>

                                {{-- MODAL DELEGASI & REVISI (TETAP SAMA) --}}
                                <div class="modal fade" id="modalDelegate{{ $visit->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="{{ route('oikos.delegate', $visit->id) }}" method="POST">@csrf @method('PATCH')<div class="modal-content border-0 shadow"><div class="modal-header bg-warning bg-opacity-10 border-0"><h5 class="modal-title fw-bold text-dark">Delegasi / Ganti Pelayan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="alert alert-info small mb-3 border-0 bg-info bg-opacity-10 text-info"><i class="bi bi-info-circle-fill me-1"></i> Pelayan pengganti akan ditugaskan.</div><div class="mb-3"><label class="form-label fw-bold small text-uppercase text-secondary">Pelayan Pengganti</label><select name="new_pelayan_id" class="form-select" required><option value="" disabled selected>Pilih Pengganti...</option>@if(isset($pelayans))@foreach($pelayans as $p)<option value="{{ $p['id'] }}">{{ $p['nama'] }}</option>@endforeach @endif</select></div><div class="mb-3"><label class="form-label fw-bold small text-uppercase text-secondary">Alasan Penggantian</label><textarea name="replacement_reason" class="form-control" rows="3" required minlength="5"></textarea></div></div><div class="modal-footer border-0 pt-0 px-4 pb-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning px-4 fw-bold">Simpan</button></div></div></form></div></div>
                                @if(Auth::check() && is_array(Auth::user()->roles) && in_array('super_admin', Auth::user()->roles))
                                <div class="modal fade" id="modalRevision{{ $visit->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form action="{{ route('oikos.revision', $visit->id) }}" method="POST">@csrf @method('PATCH')<div class="modal-content border-0 shadow"><div class="modal-header bg-danger bg-opacity-10 border-0"><h5 class="modal-title fw-bold text-danger">Kembalikan untuk Revisi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="alert alert-warning small mb-3 border-0 bg-warning bg-opacity-10 text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i> Laporan akan dikembalikan ke status <b>Revisi</b>.</div><div class="mb-3"><label class="form-label fw-bold text-secondary text-uppercase small">Catatan Revisi <span class="text-danger">*</span></label><textarea name="revision_comment" class="form-control" rows="4" required minlength="5"></textarea></div></div><div class="modal-footer border-0 pt-0 px-4 pb-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger px-4 fw-bold">Kirim Revisi</button></div></div></form></div></div>
                                @endif

                                @empty
                                <tr id="empty-row"><td colspan="7" class="text-center text-secondary py-5"><div class="d-flex flex-column align-items-center"><i class="bi bi-calendar-x display-1 text-light mb-3"></i><p class="mb-0 fw-medium">Belum ada jadwal kunjungan OIKOS.</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- FLOATING ACTION BAR FOR BULK DELETE --}}
                <div id="bulkActionBar">
                    <span class="fw-bold text-secondary" id="selectedCount">0 item terpilih</span>
                    <button type="submit" class="btn btn-danger rounded-pill fw-bold px-4 shadow-sm" onclick="return confirm('Hapus semua data yang dipilih?')">
                        <i class="bi bi-trash-fill me-2"></i>Hapus Masal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- MODAL INPUT LAPORAN (DENGAN DUA TOMBOL SUBMIT) --}}
    <div class="modal fade" id="laporanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h1 class="modal-title fs-5 fw-bold" id="laporanModalLabel">Input Laporan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4">
                    
                    <div id="revisionAlertBox" class="alert alert-danger d-flex align-items-start mb-4 d-none border-0 bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-exclamation-circle-fill fs-5 me-3 mt-1"></i>
                        <div><div class="fw-bold text-uppercase small mb-1">Perlu Revisi</div><div id="revisionNoteText" class="small text-dark"></div></div>
                    </div>

                    <form id="laporanForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3">Realisasi</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tanggal Kunjungan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" id="realisasi_date" name="realisasi_date" class="form-control border-start-0 ps-0 input-date-custom" placeholder="Pilih tanggal...">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3 flex-column flex-sm-row">
                                <div class="flex-fill p-3 rounded-3 border bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_doa_5_jari" name="is_doa_5_jari" value="1">
                                        <label class="form-check-label fw-semibold" for="is_doa_5_jari">Doa 5 Jari</label>
                                    </div>
                                    <div id="dateInputContainerDLJ" class="d-none mt-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-calendar2-check"></i></span>
                                            <input type="text" id="realisasi_doa_5_jari_date" name="realisasi_doa_5_jari_date" class="form-control border-start-0 ps-0 input-date-custom" placeholder="Tgl. Doa...">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-fill p-3 rounded-3 border bg-light">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_doa_syafaat" name="is_doa_syafaat" value="1">
                                        <label class="form-check-label fw-semibold" for="is_doa_syafaat">Doa Syafaat</label>
                                    </div>
                                    <div id="dateInputContainerDS" class="d-none mt-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-calendar2-check"></i></span>
                                            <input type="text" id="realisasi_doa_syafaat_date" name="realisasi_doa_syafaat_date" class="form-control border-start-0 ps-0 input-date-custom" placeholder="Tgl. Doa...">
                                        </div>
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
                                            <textarea id="tindakan_cinta_desc" name="tindakan_cinta_desc" rows="2" class="form-control" placeholder="Deskripsi..."></textarea>
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
                                            <textarea id="tindakan_peduli_desc" name="tindakan_peduli_desc" rows="2" class="form-control" placeholder="Deskripsi..."></textarea>
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
                                <select name="respon_injil" id="respon_injil" class="form-select"><option value="" selected disabled>Pilih Respon...</option><option value="bermusuhan">a. Sikap Bermusuhan</option><option value="netral">b. Netral</option><option value="tertarik">c. Tertarik</option><option value="tertarik_murni">d. Tertarik Murni</option><option value="keputusan">e. Keputusan</option></select>
                            </div>
                            <div>
                                <label for="catatan" class="form-label small fw-bold">Catatan Tambahan</label>
                                <textarea id="catatan" name="catatan" rows="3" class="form-control bg-light" placeholder="Ceritakan hal menarik lainnya..."></textarea>
                            </div>
                        </div>
                        
                        {{-- TOMBOL AKSI MODAL --}}
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                            <div class="d-flex gap-2">
                                {{-- Tombol Simpan Draft (formnovalidate) --}}
                                <button type="submit" name="action" value="draft" class="btn btn-warning text-white fw-bold" formnovalidate>Simpan Sementara</button>
                                {{-- Tombol Kirim Final --}}
                                <button type="submit" name="action" value="submit" class="btn btn-primary px-4 fw-bold rounded-pill">Kirim Laporan</button>
                            </div>
                        </div>
                    </form>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="text-center text-secondary py-5">Memuat Laporan...</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-between">
                    <form id="formRequestUnlock" method="POST" class="d-none">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline-warning fw-bold text-dark border-2 rounded-pill px-3" onclick="return confirm('Minta Admin membuka akses laporan untuk data ini?')">
                            <i class="bi bi-lock-fill me-2"></i>Minta Buka Kunci
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Load Flatpickr Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIKA CHECKBOX BULK DELETE ---
    const items = document.querySelectorAll('.select-item');
    const bulkBar = document.getElementById('bulkActionBar');
    const countText = document.getElementById('selectedCount');

    function updateBulkBar() {
        const checkedCount = document.querySelectorAll('.select-item:checked').length;
        if (checkedCount > 0) {
            bulkBar.classList.add('show');
            countText.textContent = `${checkedCount} item terpilih`;
        } else {
            bulkBar.classList.remove('show');
        }
    }

    items.forEach(item => {
        item.addEventListener('change', updateBulkBar);
    });

    // --- LOGIKA FILTER ---
    const filterButtons = document.querySelectorAll('.filter-nav-btn');
    const slider = document.querySelector('.filter-slider');
    const tableRows = document.querySelectorAll('.table tbody tr');
    const emptyRow = document.getElementById('empty-row'); 

    function moveSlider(targetButton) { if (!targetButton) return; const targetRect = targetButton.getBoundingClientRect(); const containerRect = targetButton.parentElement.getBoundingClientRect(); slider.style.width = `${targetRect.width}px`; slider.style.transform = `translateX(${targetRect.left - containerRect.left}px)`; }
    const initialActiveButton = document.querySelector('.filter-nav-btn.active');
    if (initialActiveButton) { setTimeout(() => moveSlider(initialActiveButton), 100); }
    
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
                // Logika Filter: 'Berlangsung' (Draft) ditampilkan di tab 'Direncanakan'
                // 'Menunggu Persetujuan' ditampilkan di tab 'Direncanakan' juga
                let showRow = false;

                if (filterValue === 'all') showRow = true;
                else if (filterValue === 'Direncanakan') {
                    showRow = (rowStatus === 'Direncanakan' || rowStatus === 'Berlangsung' || rowStatus === 'Menunggu Persetujuan');
                } else {
                    showRow = (rowStatus === filterValue);
                }

                if (showRow) {
                    row.style.display = ''; visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (emptyRow) { emptyRow.style.display = (visibleRows === 0) ? '' : 'none'; }
        });
    });

    // --- LOGIKA MODAL INPUT LAPORAN (POPULATE DATA DRAFT) ---
    const laporanModalEl = document.getElementById('laporanModal');
    let fpInstances = [];

    if (laporanModalEl) {
        const laporanForm = document.getElementById('laporanForm');
        const revisionAlertBox = document.getElementById('revisionAlertBox');
        const revisionNoteText = document.getElementById('revisionNoteText');

        laporanModalEl.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const oikosNama = button.getAttribute('data-oikos-nama');
            const visitId = button.getAttribute('data-visit-id');
            const revisionNote = button.getAttribute('data-revision-note');
            
            const isUnlocked = button.getAttribute('data-is-unlocked') === '1';
            const visitStatus = button.getAttribute('data-visit-status'); 
            
            const modalTitle = laporanModalEl.querySelector('#laporanModalLabel');
            let url = "{{ route('oikos.report.store', ':id') }}";
            url = url.replace(':id', visitId);
            laporanForm.action = url;

            if (revisionNote) {
                modalTitle.textContent = `Perbaiki Laporan: ${oikosNama}`;
                modalTitle.classList.add('text-danger');
                revisionNoteText.textContent = revisionNote;
                revisionAlertBox.classList.remove('d-none');
            } else {
                modalTitle.textContent = `Laporan: ${oikosNama}`;
                modalTitle.classList.remove('text-danger');
                revisionAlertBox.classList.add('d-none');
            }

            // POPULATE DATA DRAFT DARI API (PENTING!)
            try {
                const response = await fetch(`/api/oikos-visits/${visitId}`);
                const data = await response.json();

                // Isi Form dengan data dari database (Draft)
                if (data.realisasi_date) {
                    fpInstances[0].setDate(data.realisasi_date); // realisasi_date index 0
                }
                document.getElementById('tindakan_cinta_desc').value = data.tindakan_cinta_desc || '';
                document.getElementById('tindakan_peduli_desc').value = data.tindakan_peduli_desc || '';
                document.getElementById('respon_injil').value = data.respon_injil || '';
                document.getElementById('catatan').value = data.catatan || '';

                // Handle Checkbox & Hidden Container
                const doa5 = document.getElementById('is_doa_5_jari');
                doa5.checked = !!data.is_doa_5_jari;
                doa5.dispatchEvent(new Event('change')); // Trigger toggle UI
                if(data.realisasi_doa_5_jari_date && fpInstances[1]) fpInstances[1].setDate(data.realisasi_doa_5_jari_date);

                const doaS = document.getElementById('is_doa_syafaat');
                doaS.checked = !!data.is_doa_syafaat;
                doaS.dispatchEvent(new Event('change'));
                if(data.realisasi_doa_syafaat_date && fpInstances[2]) fpInstances[2].setDate(data.realisasi_doa_syafaat_date);

                // Handle Tindakan Toggle (Jika ada desc/foto, nyalakan switch)
                const cintaSwitch = document.getElementById('tindakanCintaOikos');
                cintaSwitch.checked = !!(data.tindakan_cinta_desc || data.tindakan_cinta_photo_path);
                cintaSwitch.dispatchEvent(new Event('change'));

                const peduliSwitch = document.getElementById('tindakanPedulihOikos');
                peduliSwitch.checked = !!(data.tindakan_peduli_desc || data.tindakan_peduli_photo_path);
                peduliSwitch.dispatchEvent(new Event('change'));

            } catch (error) {
                console.error('Gagal mengambil draft:', error);
            }

            // --- LOGIKA KALENDER ---
            fpInstances.forEach(fp => fp.destroy());
            fpInstances = [];
            
            const today = new Date();
            const day = today.getDay(); 
            const diffToMonday = (day === 0 ? -6 : 1) - day;
            const monday = new Date(today);
            monday.setDate(today.getDate() + diffToMonday);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);

            const fpConfig = {
                locale: "id", dateFormat: "Y-m-d", minDate: "today", maxDate: sunday,
                disable: [
                    function(date) {
                        const d = date.getDay();
                        // Draft/Revisi Boleh Kapan Saja
                        if (isUnlocked || visitStatus === 'Revisi' || visitStatus === 'Berlangsung') return false; 
                        return !(d === 3 || d === 4 || d === 5 || d === 6);
                    }
                ]
            };

            fpInstances.push(flatpickr("#realisasi_date", fpConfig));
            fpInstances.push(flatpickr("#realisasi_doa_5_jari_date", fpConfig));
            fpInstances.push(flatpickr("#realisasi_doa_syafaat_date", fpConfig));
        });

        laporanModalEl.addEventListener('hidden.bs.modal', function () {
            laporanForm.reset();
            // Sembunyikan container
            document.querySelectorAll('#dateInputContainerDLJ, #dateInputContainerDS, #hiddenInputContainerTCO, #hiddenInputContainerTPO').forEach(el => el.classList.add('d-none'));
        });
    }

    const setupToggle = (checkboxId, containerId) => {
        const checkbox = document.getElementById(checkboxId);
        const container = document.getElementById(containerId);
        if (checkbox && container) { checkbox.addEventListener('change', function() { container.classList.toggle('d-none', !this.checked); }); }
    };
    setupToggle('is_doa_5_jari', 'dateInputContainerDLJ');
    setupToggle('is_doa_syafaat', 'dateInputContainerDS');
    setupToggle('tindakanCintaOikos', 'hiddenInputContainerTCO');
    setupToggle('tindakanPedulihOikos', 'hiddenInputContainerTPO');

    // --- LOGIKA VIEW DETAIL & UNLOCK BUTTON ---
    const viewLaporanModalEl = document.getElementById('viewLaporanModal');
    const isReportDay = @json(isset($isReportDay) ? $isReportDay : false); 

    if (viewLaporanModalEl) {
        const formUnlock = document.getElementById('formRequestUnlock');

        viewLaporanModalEl.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const visitId = button.getAttribute('data-visit-id');
            const modalBody = viewLaporanModalEl.querySelector('.modal-body');
            
            formUnlock.classList.add('d-none'); // Reset
            
            modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            
            try {
                let url = "{{ route('oikos.report.show', ':id') }}";
                url = url.replace(':id', visitId);
                const response = await fetch(url);
                const data = await response.json();

                // [LOGIKA TOMBOL BUKA KUNCI]
                const now = new Date();
                const unlockUntil = data.report_unlock_until ? new Date(data.report_unlock_until) : null;
                const isUnlocked = unlockUntil && unlockUntil > now;

                // Jika status Direncanakan/Berlangsung, Diluar Jadwal, dan Belum Unlock -> Munculkan Tombol
                if ((data.status === 'Direncanakan' || data.status === 'Berlangsung') && !isReportDay && !isUnlocked) {
                    let unlockUrl = "{{ route('oikos.request_unlock', ':id') }}";
                    unlockUrl = unlockUrl.replace(':id', visitId);
                    formUnlock.action = unlockUrl;
                    formUnlock.classList.remove('d-none');
                } else if (isUnlocked) {
                    modalBody.innerHTML = `<div class="alert alert-success border-0 bg-success bg-opacity-10 text-success fw-bold text-center"><i class="bi bi-unlock-fill me-2"></i>Laporan TERBUKA sampai ${unlockUntil.toLocaleString()}</div>`;
                } else {
                    modalBody.innerHTML = '';
                }

                // Render Content
                const formatDate = (dateString) => {
                    if (!dateString) return '-';
                    const date = new Date(dateString.split(' ')[0] + 'T00:00:00');
                    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                };

                // Jika status Berlangsung (Draft) tapi data kosong, tampilkan pesan
                const hasData = data.realisasi_date || data.tindakan_cinta_desc || data.tindakan_peduli_desc;

                if(!hasData) {
                     modalBody.innerHTML += `
                        <div class="text-center py-4 text-secondary">
                            <i class="bi bi-clipboard-x fs-1 opacity-50"></i>
                            <p class="mt-2 fw-medium">Belum ada laporan yang diisi.</p>
                            ${!isReportDay && !isUnlocked ? '<small class="text-danger">Diluar jadwal pengisian (Rabu-Sabtu).<br>Gunakan tombol "Minta Buka Kunci" di bawah jika perlu lapor sekarang.</small>' : ''}
                        </div>
                     `;
                } else {
                    const photoCintaHtml = data.tindakan_cinta_photo_path ? `<div class="mt-2"><a href="/storage/${data.tindakan_cinta_photo_path}" target="_blank"><img src="/storage/${data.tindakan_cinta_photo_path}" class="img-fluid rounded" style="max-height:200px"></a></div>` : '';
                    const photoPeduliHtml = data.tindakan_peduli_photo_path ? `<div class="mt-2"><a href="/storage/${data.tindakan_peduli_photo_path}" target="_blank"><img src="/storage/${data.tindakan_peduli_photo_path}" class="img-fluid rounded" style="max-height:200px"></a></div>` : '';
                    
                    let contentHtml = `<div class="row g-3"><div class="col-12"><div class="p-3 bg-light rounded-3"><b>Tanggal:</b> ${formatDate(data.realisasi_date)}</div></div><div class="col-12"><b>Doa 5 Jari:</b> ${data.is_doa_5_jari ? 'Ya' : 'Tidak'}<br><b>Doa Syafaat:</b> ${data.is_doa_syafaat ? 'Ya' : 'Tidak'}</div>`;
                    
                    if (data.tindakan_cinta_desc) contentHtml += `<div class="col-12"><b>Cinta OIKOS:</b> ${data.tindakan_cinta_desc} ${photoCintaHtml}</div>`;
                    if (data.tindakan_peduli_desc) contentHtml += `<div class="col-12"><b>Peduli OIKOS:</b> ${data.tindakan_peduli_desc} ${photoPeduliHtml}</div>`;
                    
                    contentHtml += `</div>`;
                    modalBody.innerHTML += contentHtml;
                }

            } catch (e) { 
                console.error(e);
                modalBody.innerHTML = 'Gagal memuat data.'; 
            }
        });
    }
});
</script>
@endpush