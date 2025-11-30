@extends('layouts.app')

@section('title', 'Detail Laporan OIKOS')

@push('styles')
<style>
    /* DARK MODE COMPATIBLE */
    body { background-color: var(--bs-body-bg); }

    .card-detail {
        background-color: var(--element-bg);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
    }
    
    .section-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: var(--bs-body-color);
    }

    .doa-card {
        background-color: var(--element-bg-subtle);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s;
    }
    .doa-card.active {
        background-color: rgba(16, 185, 129, 0.1); /* Green-100 opacity */
        border-color: #10b981;
        color: #065f46;
    }
    .doa-icon {
        font-size: 1.25rem;
    }
    
    .response-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background-color: var(--hover-bg);
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        font-weight: 600;
        color: var(--bs-body-color);
        text-transform: capitalize;
    }

    /* Modal Styling Override */
    .modal-content { background-color: var(--element-bg); color: var(--bs-body-color); border-color: var(--border-color); }
    .modal-header, .modal-footer { border-color: var(--border-color); }
    .form-control { background-color: var(--input-bg); color: var(--bs-body-color); border-color: var(--border-color); }
    .btn-close { filter: var(--bs-btn-close-filter); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5">
    
    {{-- HEADER --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div class="d-flex align-items-center">
            <a href="{{ route('oikos') }}" class="btn btn-light border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" 
               style="width: 40px; height: 40px; background: var(--element-bg); border-color: var(--border-color)!important; color: var(--text-secondary);">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold text-body mb-0">Detail Laporan</h4>
                <p class="text-secondary small mb-0">ID: #{{ $visit->id }} • {{ $visit->created_at->format('d M Y') }}</p>
            </div>
        </div>

        @php
            $statusClass = match($visit->status) {
                'Diproses' => 'bg-info text-dark bg-opacity-25 border-info',
                'Selesai' => 'bg-success text-success bg-opacity-10 border-success',
                'Revisi' => 'bg-warning text-warning bg-opacity-10 border-warning',
                'Gagal' => 'bg-danger text-danger bg-opacity-10 border-danger',
                default => 'bg-secondary text-secondary bg-opacity-10 border-secondary'
            };
        @endphp
        <span class="badge rounded-pill px-4 py-2 fs-6 border {{ $statusClass }}">
            {{ $visit->status }}
        </span>
    </div>

    <div class="row g-4">
        {{-- KOLOM KIRI: INFORMASI UTAMA --}}
        <div class="col-lg-8">
            <div class="card card-detail mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary mb-4 d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-2"></i> Identitas & Waktu
                    </h5>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="section-label">Nama Target OIKOS</div>
                            <div class="info-value fs-5">{{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="section-label">Pelayan (PIC)</div>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-fill text-secondary"></i>
                                </div>
                                <span class="info-value">{{ $visit->pelayan_data['nama'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="section-label">Tanggal Realisasi</div>
                            <div class="info-value">
                                <i class="bi bi-calendar-check me-1 text-secondary"></i>
                                {{ $visit->realisasi_date ? \Carbon\Carbon::parse($visit->realisasi_date)->format('l, d F Y') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-detail mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-success mb-4 d-flex align-items-center">
                        <i class="bi bi-activity me-2"></i> Aktivitas Rohani
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="doa-card {{ $visit->is_doa_5_jari ? 'active' : '' }}">
                                <i class="bi {{ $visit->is_doa_5_jari ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }} doa-icon"></i>
                                <div>
                                    <div class="fw-bold">Doa 5 Jari</div>
                                    <small class="text-secondary">{{ $visit->is_doa_5_jari ? 'Dilakukan' : 'Tidak dilakukan' }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="doa-card {{ $visit->is_doa_syafaat ? 'active' : '' }}">
                                <i class="bi {{ $visit->is_doa_syafaat ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }} doa-icon"></i>
                                <div>
                                    <div class="fw-bold">Doa Syafaat</div>
                                    <small class="text-secondary">{{ $visit->is_doa_syafaat ? 'Dilakukan' : 'Tidak dilakukan' }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="section-label">Tindakan Cinta OIKOS</div>
                        <div class="p-3 rounded-3 border" style="background-color: var(--element-bg-subtle);">
                            {{ $visit->tindakan_cinta_desc ?? '-' }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="section-label">Tindakan Peduli OIKOS</div>
                        <div class="p-3 rounded-3 border" style="background-color: var(--element-bg-subtle);">
                            {{ $visit->tindakan_peduli_desc ?? '-' }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="section-label">Respon Terhadap Injil</div>
                            <div class="response-badge">
                                @php
                                    $iconRespon = match(strtolower($visit->respon_injil)) {
                                        'keputusan' => 'bi-star-fill text-warning',
                                        'tertarik murni' => 'bi-heart-fill text-danger',
                                        'tertarik' => 'bi-hand-thumbs-up-fill text-primary',
                                        'netral' => 'bi-emoji-neutral text-secondary',
                                        'bermusuhan' => 'bi-emoji-frown-fill text-dark',
                                        default => 'bi-question-circle'
                                    };
                                @endphp
                                <i class="bi {{ $iconRespon }} me-2"></i> {{ $visit->respon_injil ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($visit->catatan)
            <div class="card card-detail mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-secondary mb-3 d-flex align-items-center">
                        <i class="bi bi-sticky-fill me-2"></i> Catatan Tambahan
                    </h5>
                    <p class="mb-0 fst-italic text-body" style="line-height: 1.6;">
                        "{{ $visit->catatan }}"
                    </p>
                </div>
            </div>
            @endif
        </div>

        {{-- KOLOM KANAN: DOKUMENTASI & AKSI --}}
        <div class="col-lg-4">
            {{-- Card Dokumentasi --}}
            <div class="card card-detail mb-4">
                <div class="card-header bg-transparent fw-bold py-3 border-bottom border-color">
                    <i class="bi bi-camera-fill me-2 text-secondary"></i> Dokumentasi
                </div>
                <div class="card-body p-3 text-center">
                    @if($visit->tindakan_cinta_photo_path)
                        <div class="position-relative rounded-3 overflow-hidden border shadow-sm group-hover-zoom">
                            <a href="{{ asset('storage/' . $visit->tindakan_cinta_photo_path) }}" target="_blank">
                                <img src="{{ asset('storage/' . $visit->tindakan_cinta_photo_path) }}" class="img-fluid w-100" style="object-fit: cover; max-height: 300px;">
                                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-25 opacity-0 hover-opacity-100 transition-all">
                                    <span class="badge bg-dark bg-opacity-75"><i class="bi bi-zoom-in me-1"></i> Perbesar</span>
                                </div>
                            </a>
                        </div>
                    @else
                        <div class="py-5 rounded-3 border border-dashed d-flex flex-column align-items-center justify-content-center" style="background-color: var(--hover-bg);">
                            <i class="bi bi-image text-secondary opacity-25 display-1"></i>
                            <span class="text-secondary small mt-2">Tidak ada foto dilampirkan</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card Aksi (Hanya muncul untuk Admin/Koor) --}}
            @if($canApprove)
                <div class="card card-detail border-top border-4 border-primary">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-body">Tinjauan Admin</h5>
                        
                        @if($visit->status == 'Diproses')
                            <div class="d-grid gap-2">
                                <form action="{{ route('oikos.confirm', $visit->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" onclick="return confirm('Setujui laporan ini?')">
                                        <i class="bi bi-check-circle-fill me-2"></i> Setujui Laporan
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-outline-warning w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#revisionModal">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i> Minta Revisi
                                </button>
                            </div>
                            <div class="mt-3 pt-3 border-top text-center">
                                <form action="{{ route('oikos.destroy', $visit->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link text-danger text-decoration-none small" onclick="return confirm('Hapus permanen?')">
                                        <i class="bi bi-trash me-1"></i> Hapus Laporan Ini
                                    </button>
                                </form>
                            </div>

                        @elseif($visit->status == 'Selesai')
                            <div class="alert alert-success border-0 d-flex align-items-center mb-0" role="alert">
                                <i class="bi bi-check-circle-fill fs-1 me-3"></i>
                                <div>
                                    <div class="fw-bold">Laporan Disetujui</div>
                                    <div class="small">Pada: {{ $visit->updated_at->format('d M Y H:i') }}</div>
                                </div>
                            </div>
                        @elseif($visit->status == 'Revisi')
                            <div class="alert alert-warning border-0 mb-0">
                                <h6 class="fw-bold"><i class="bi bi-exclamation-circle-fill me-1"></i> Dalam Revisi</h6>
                                <hr>
                                <small class="d-block text-truncate">"{{ json_decode($visit->revision_comment)->comment ?? $visit->revision_comment }}"</small>
                            </div>
                        @else
                             <div class="text-center text-secondary py-2">Tidak ada aksi tersedia.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal Revisi (Hanya jika status Diproses dan User adalah Admin) --}}
@if($canApprove && $visit->status == 'Diproses')
<div class="modal fade" id="revisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Minta Revisi Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('oikos.revision', $visit->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-secondary">Komentar / Alasan Revisi</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Jelaskan bagian mana yang perlu diperbaiki..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-secondary">Bagian yang perlu dicek</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['realisasi_date' => 'Tanggal', 'doa_5_jari' => 'Doa 5 Jari', 'tindakan_cinta' => 'Tindakan Cinta', 'tindakan_peduli' => 'Tindakan Peduli', 'respon_injil' => 'Respon', 'catatan' => 'Catatan'] as $key => $label)
                                <input type="checkbox" class="btn-check" name="fields[]" value="{{ $key }}" id="chk_{{$key}}" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm rounded-pill" for="chk_{{$key}}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 rounded-pill shadow-sm">Kirim Permintaan Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection