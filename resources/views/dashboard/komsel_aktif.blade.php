@extends('layouts.app')

@section('title', 'Daftar Komsel Aktif')

@push('styles')
<style>
    .komsel-card {
        border: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: #fff;
        /* Pastikan kartu tidak transparan agar tidak tumpang tindih aneh */
        position: relative;
        z-index: 1;
    }
    .komsel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        z-index: 5; /* Naikkan z-index saat hover */
    }
    .komsel-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px 12px 0 0;
        padding: 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .member-list-container {
        max-height: 300px;
        overflow-y: auto;
        scrollbar-width: thin;
    }
    .accordion-button:not(.collapsed) {
        color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.05);
        box-shadow: none; /* Hilangkan shadow focus default */
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }
    .badge-leader {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
    }
    .avatar-tiny {
        width: 24px;
        height: 24px;
        background-color: #dee2e6;
        color: #6c757d;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        margin-right: 8px;
    }
</style>
@endpush

@section('konten')
<div class="container-fluid py-4 px-4">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <h2 class="fw-bold text-dark mb-0">Direktori Komsel Aktif</h2>
            <p class="text-secondary mb-0">Menampilkan seluruh data Komsel beserta Leader dan Anggotanya.</p>
        </div>
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border">
            <span class="fw-bold text-primary">{{ $komselData->count() }}</span> <span class="text-secondary small text-uppercase">Komsel Terdaftar</span>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchKomsel" class="form-control border-start-0 ps-0" placeholder="Cari nama komsel atau leader...">
            </div>
        </div>
    </div>

    {{-- Grid Komsel --}}
    {{-- [FIX] Tambahkan 'align-items-start' agar kartu tidak saling menarik tinggi satu sama lain --}}
    <div class="row g-4 align-items-start" id="komselGrid">
        @forelse($komselData as $komsel)
            <div class="col-md-6 col-xl-4 komsel-item">
                <div class="card komsel-card shadow-sm h-100">
                    {{-- Card Header: Nama & Leader --}}
                    <div class="komsel-header d-flex justify-content-between align-items-start">
                        <div class="w-100">
                            <h5 class="fw-bold text-dark mb-1 text-truncate" title="{{ $komsel->nama }}">
                                {{ $komsel->nama }}
                            </h5>
                            <div class="d-flex align-items-center mt-2">
                                <span class="badge badge-leader rounded-pill px-3 py-2 fw-normal">
                                    <i class="bi bi-person-workspace me-1"></i> {{ $komsel->leader_name }}
                                </span>
                            </div>
                        </div>
                        <div class="text-center ms-3">
                            <h3 class="fw-bold text-primary mb-0">{{ $komsel->total_members }}</h3>
                            <small class="text-muted" style="font-size: 0.65rem;">ANGGOTA</small>
                        </div>
                    </div>

                    {{-- Card Body: Accordion Anggota --}}
                    <div class="card-body p-0">
                        <div class="accordion accordion-flush" id="accordionKomsel{{ $komsel->id }}">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="heading{{ $komsel->id }}">
                                    <button class="accordion-button collapsed fw-semibold text-secondary small py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $komsel->id }}" aria-expanded="false">
                                        <i class="bi bi-people-fill me-2"></i> Lihat Daftar Anggota
                                    </button>
                                </h2>
                                <div id="collapse{{ $komsel->id }}" class="accordion-collapse collapse" data-bs-parent="#accordionKomsel{{ $komsel->id }}">
                                    <div class="accordion-body p-0 member-list-container bg-light bg-opacity-25">
                                        @if($komsel->members->isNotEmpty())
                                            <ul class="list-group list-group-flush">
                                                @foreach($komsel->members as $member)
                                                    @php
                                                        $mNama = is_array($member) ? ($member['nama'] ?? $member['name']) : ($member->nama ?? $member->name);
                                                    @endphp
                                                    <li class="list-group-item bg-transparent border-bottom-0 py-2 d-flex align-items-center">
                                                        <div class="avatar-tiny">
                                                            {{ substr($mNama, 0, 1) }}
                                                        </div>
                                                        <span class="small text-dark">{{ $mNama }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-center py-4 text-muted small">
                                                <i class="bi bi-person-x fs-4 d-block mb-1"></i>
                                                Belum ada anggota terdaftar.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Footer info --}}
                    <div class="card-footer bg-white border-top-0 py-2">
                        <small class="text-muted" style="font-size: 0.7rem;">ID Komsel: {{ $komsel->id }}</small>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-hdd-stack display-4 text-secondary opacity-25"></i>
                <p class="mt-3 text-secondary">Tidak ada data Komsel aktif ditemukan.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Script Search --}}
<script>
    document.getElementById('searchKomsel').addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.komsel-item');

        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            item.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>
@endsection