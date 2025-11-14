@extends('layouts.app')

@section('title', 'Daftar Anggota & Pemimpin')

@push('styles')
<style>
    /* Style umum */
    .table { border-color: var(--border-color); }
    .table th { color: var(--bs-body-color); font-weight: 600; }
    .table td { color: var(--text-secondary); }
    .table-hover > tbody > tr:hover > * { background-color: var(--hover-bg); color: var(--bs-body-color); }

    /* Badge untuk komsel */
    .komsel-badge {
        display: inline-block;
        padding: 0.3em 0.65em;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.5rem;
        background-color: var(--hover-bg);
        color: var(--text-secondary);
    }
    
    /* Style untuk popover agar terlihat lebih baik */
    .popover-body ul {
        margin-bottom: 0;
        padding-left: 1.2rem;
    }
</style>
@endpush

@section('konten')
<div class="card">
    <div class="card-body p-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="card-title fw-bold mb-0">Daftar Anggota & Pemimpin</h5>
            <div style="max-width: 250px;">
                <select class="form-select" id="komsel-filter">
                    <option value="all" selected>Filter: Semua KOMSEL</option>
                    @foreach ($komsels as $komsel)
                        <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="border-bottom">
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Peran (Role)</th>
                        <th scope="col">KOMSEL</th>
                        {{-- [DIHAPUS] Kolom status --}}
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr data-komsel-id="{{ $user['komsel_id'] ?? 'none' }}">
                            <td class="fw-bold">{{ $loop->iteration }}</td>
                            <td>{{ $user['nama'] }}</td>
                            
                            <td>
                                @if(!empty($user['roles']))
                                    <span class="badge text-bg-secondary">{{ $user['roles'][0] }}</span>
                                    
                                    @if(count($user['roles']) > 1)
                                        @php
                                            $popoverContent = '<ul>';
                                            $otherRoles = array_slice($user['roles'], 1);
                                            foreach($otherRoles as $role) {
                                                $popoverContent .= '<li>' . htmlspecialchars($role) . '</li>';
                                            }
                                            $popoverContent .= '</ul>';
                                        @endphp
                                        <a href="javascript:void(0);" class="text-primary ms-1" 
                                           tabindex="0"
                                           data-bs-toggle="popover" 
                                           data-bs-trigger="focus"
                                           data-bs-title="Semua Peran" 
                                           data-bs-html="true"
                                           data-bs-content="{{ $popoverContent }}">
                                            <i class="bi bi-info-circle-fill"></i>
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted small">Anggota</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $komselId = $user['komsel_id'] ?? null;
                                    $komselName = null;
                                    if ($komselId) {
                                        foreach ($komsels as $k) {
                                            if ($k['id'] == $komselId) {
                                                $komselName = $k['nama'];
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @if ($komselName)
                                    <span class="komsel-badge">{{ $komselName }}</span>
                                @else
                                    <span class="komsel-badge text-muted">Belum Ada</span>
                                @endif
                            </td>

                            {{-- [DIHAPUS] Kolom data status --}}

                            <td class="text-end">
                                {{-- [DIHAPUS] Tombol ubah status --}}
                                
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignKomselModal"
                                        data-user-id="{{ $user['id'] }}"
                                        data-user-nama="{{ $user['nama'] }}"
                                        data-komsel-id="{{ $user['komsel_id'] ?? '' }}"
                                        title="Tetapkan KOMSEL">
                                    <i class="bi bi-house-heart-fill"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- [FIX] Colspan diubah menjadi 5 --}}
                            <td colspan="5" class="text-center text-secondary py-4">
                                Tidak ada data pengguna. API mungkin tidak terjangkau.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- [DIHAPUS] Modal Status --}}

{{-- [MASIH DIPERLUKAN] Modal Assign Komsel --}}
<div class="modal fade" id="assignKomselModal" tabindex="-1" aria-labelledby="assignKomselModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5 fw-bold" id="assignKomselModalLabel">
                    Tetapkan KOMSEL untuk <span id="namaUserAssignModal"></span>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignKomselForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="komselSelect" class="form-label">Pilih KOMSEL</label>
                        <select class="form-select" id="komselSelect" name="komsel_id">
                            <option value="">-- Hapus dari KOMSEL --</option>
                            @foreach ($komsels as $komsel)
                                <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    if (popoverTriggerList.length > 0) {
        [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }

    const komselFilter = document.getElementById('komsel-filter');
    if (komselFilter) {
        komselFilter.addEventListener('change', function() {
            const filterValue = this.value;
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                if (row.querySelector('td[colspan]')) return; 
                const komselId = row.getAttribute('data-komsel-id');
                row.style.display = (filterValue === 'all' || komselId === filterValue) ? '' : 'none';
            });
        });
    }

    // [DIHAPUS] Blok JavaScript untuk statusModal
    
    // [MASIH DIPERLUKAN] JavaScript untuk assignKomselModal
    const assignKomselModal = document.getElementById('assignKomselModal');
    if (assignKomselModal) {
        assignKomselModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userNama = button.getAttribute('data-user-nama');
            const currentKomselId = button.getAttribute('data-komsel-id');

            const modalTitleSpan = assignKomselModal.querySelector('#namaUserAssignModal');
            const assignForm = assignKomselModal.querySelector('#assignKomselForm');
            const komselSelect = assignKomselModal.querySelector('#komselSelect');

            if(modalTitleSpan) modalTitleSpan.textContent = userNama;
            
            if(assignForm) {
                 // [FIX] Pastikan ini menggunakan "user" agar cocok dengan route
                let url = `{{ route("users.assignKomsel", ["user" => ":id"]) }}`.replace(':id', userId);
                assignForm.action = url;
            }

            if(komselSelect) komselSelect.value = currentKomselId;
        });
    }
});
</script>
@endpush