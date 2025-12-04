@extends('layouts.app')

@section('title', 'Daftar Anggota Komsel')

@push('styles')
<style>
    /* === VARIABLES & GENERAL === */
    :root {
        --bg-soft: #f8f9fa;
        --card-bg: #ffffff;
        --border-color: #eff2f5;
        --text-main: #344767;
        --text-sub: #7b809a;
        --primary-soft: rgba(94, 114, 228, 0.1);
        --primary-color: #5e72e4;
    }

    /* === CARD & STRUCTURE === */
    .card-modern {
        background: var(--card-bg);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    }
    
    /* === TABLE STYLING === */
    .table > :not(caption) > * > * {
        padding: 1rem 1.5rem;
        border-bottom-color: var(--border-color);
    }
    .table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-sub);
        font-weight: 700;
        background-color: #fcfcfc;
        border-bottom: 2px solid var(--border-color);
    }
    .table tbody tr:hover {
        background-color: #fafbff;
    }
    
    /* === AVATAR === */
    .avatar-initial {
        width: 42px;
        height: 42px;
        background: linear-gradient(310deg, #5e72e4 0%, #825ee4 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 6px -1px rgba(94, 114, 228, 0.3);
    }

    /* === BADGES === */
    .badge-komsel {
        background-color: var(--primary-soft);
        color: var(--primary-color);
        padding: 0.5em 0.9em;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-role {
        background-color: #e9ecef;
        color: var(--text-main);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* === CUSTOM DROPDOWN FILTER === */
    .filter-dropdown {
        position: relative;
        min-width: 260px;
    }
    .filter-trigger {
        background: white;
        border: 1px solid #d2d6da;
        padding: 0.6rem 1rem;
        border-radius: 0.75rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }
    .filter-trigger:hover, .filter-trigger.active {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px var(--primary-soft);
    }
    .filter-menu {
        position: absolute;
        top: 110%;
        left: 0;
        width: 100%;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        z-index: 1000;
        display: none;
        overflow: hidden;
    }
    .filter-menu.show { display: block; animation: fadeIn 0.2s ease; }
    .filter-search { padding: 10px; border-bottom: 1px solid var(--border-color); background: #f8f9fa; }
    .filter-search input { width: 100%; padding: 6px 10px; border: 1px solid #d2d6da; border-radius: 6px; font-size: 0.85rem; }
    .filter-options { max-height: 250px; overflow-y: auto; }
    .filter-item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 0.9rem;
        color: var(--text-main);
        transition: background 0.15s;
    }
    .filter-item:hover { background-color: var(--primary-soft); color: var(--primary-color); }
    .filter-item.selected { background-color: var(--primary-color); color: white; }

    /* === MOBILE RESPONSIVE === */
    @media (max-width: 768px) {
        .table thead { display: none; }
        .table, .table tbody, .table tr, .table td { display: block; width: 100%; }
        
        .table tr {
            margin-bottom: 1rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1rem;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .table td { border: none; padding: 0.25rem 0; text-align: left; }
        
        /* Layout Mobile */
        .table td:nth-child(1) { display: none; } /* No */
        .table td:nth-child(2) { margin-bottom: 0.5rem; padding-right: 50px; } /* Nama */
        .table td:nth-child(3) { display: inline-block; margin-right: 10px; } /* Role */
        .table td:nth-child(4) { display: inline-block; } /* Komsel */
        
        /* Action Button Absolute Top Right */
        .table td:nth-child(5) {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: auto;
            padding: 0;
        }
        .table td:nth-child(5) .btn span { display: none; } /* Hide Text "Jadwal" */
        .table td:nth-child(5) .btn {
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('konten')
<div class="container-fluid py-4">
    
    {{-- HEADER SECTION --}}
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h4 class="fw-bold mb-1 text-dark">Data Anggota Komsel</h4>
            <p class="text-secondary small mb-0">Kelola data jemaat, penugasan komsel, dan jadwal kunjungan.</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end gap-2">
            {{-- Filter Custom --}}
            <div class="filter-dropdown" id="komselFilter">
                <input type="hidden" id="selectedKomselId" value="all">
                
                <div class="filter-trigger" id="filterTrigger">
                    <span class="text-truncate fw-medium" id="filterLabel">Semua Komsel</span>
                    <i class="bi bi-chevron-down ms-2 small text-secondary"></i>
                </div>

                <div class="filter-menu">
                    <div class="filter-search">
                        <input type="text" placeholder="Cari nama komsel..." id="filterSearchInput">
                    </div>
                    <div class="filter-options">
                        <div class="filter-item selected" data-value="all" data-label="Semua Komsel">Semua Komsel</div>
                        @foreach($komsels as $komsel)
                            {{-- Gunakan array syntax karena komsels adalah collection of arrays --}}
                            <div class="filter-item" data-value="{{ $komsel['id'] }}" data-label="{{ $komsel['nama'] }}">
                                {{ $komsel['nama'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="card card-modern">
        {{-- Search Bar --}}
        <div class="p-3 border-bottom bg-light bg-opacity-25">
            <div class="input-group border rounded-3 bg-white overflow-hidden" style="max-width: 400px;">
                <span class="input-group-text bg-white border-0 ps-3 text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-0 shadow-none ps-2" id="searchName" placeholder="Cari nama anggota..." autocomplete="off">
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Profil Anggota</th>
                        <th>Peran (Role)</th>
                        <th>Komunitas Sel</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="memberTableBody">
                    @forelse($users as $user)
                        {{-- Data attribute komsel-id penting untuk filter --}}
                        <tr data-komsel-id="{{ $user->komsel_id ?? 'none' }}" class="member-row">
                            <td class="ps-4 text-secondary fw-bold small">{{ $loop->iteration }}</td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initial me-3">
                                        {{-- Fallback jika nama kosong --}}
                                        {{ substr($user->name ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-dark fw-bold name-text">{{ $user->name }}</span>
                                        <span class="text-secondary small" style="font-size: 0.75rem;">
                                            <i class="bi bi-telephone me-1"></i> {{ $user->no_hp ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{-- Jika ada roles, tampilkan yang pertama. Jika tidak, Anggota --}}
                                @if(isset($user->roles) && !empty($user->roles) && isset($user->roles[0]))
                                    <span class="badge-role">{{ $user->roles[0] }}</span>
                                @else
                                    <span class="badge-role">Anggota</span>
                                @endif
                            </td>

                            <td>
                                @if(!empty($user->komsel_name) && $user->komsel_name !== '-')
                                    <span class="badge-komsel">
                                        <i class="bi bi-people-fill"></i> {{ $user->komsel_name }}
                                    </span>
                                @else
                                    <span class="text-secondary small fst-italic ms-1">Belum ada komsel</span>
                                @endif
                            </td>

                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-white border shadow-sm text-primary fw-bold" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-calendar-plus me-1"></i> <span>Jadwal</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2">
                                        <li>
                                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center" href="{{ route('formInput', ['jemaat_id' => $user->id]) }}">
                                                <i class="bi bi-house-heart text-warning me-2 fs-5"></i>
                                                <div>
                                                    <div class="fw-bold small">OIKOS</div>
                                                    <div class="text-muted" style="font-size: 10px;">Penjangkauan Jiwa</div>
                                                </div>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center" href="{{ route('kunjungan.create', ['member_id' => $user->id]) }}">
                                                <i class="bi bi-person-walking text-success me-2 fs-5"></i>
                                                <div>
                                                    <div class="fw-bold small">Kunjungan</div>
                                                    <div class="text-muted" style="font-size: 10px;">Gembala / Besuk</div>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyState">
                            <td colspan="5" class="text-center py-5">
                                <div class="opacity-50">
                                    <i class="bi bi-folder-x display-4 text-secondary"></i>
                                    <p class="mt-2 fw-medium text-secondary">Belum ada data anggota.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    
                    {{-- Row untuk "Tidak ditemukan" saat filter --}}
                    <tr id="noResultRow" style="display: none;">
                        <td colspan="5" class="text-center py-5">
                            <i class="bi bi-search text-secondary fs-1 opacity-50"></i>
                            <p class="mt-2 text-secondary">Tidak ditemukan anggota dengan filter tersebut.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. SEARCH & FILTER LOGIC ---
    const searchInput = document.getElementById('searchName');
    const komselInput = document.getElementById('selectedKomselId');
    const rows = document.querySelectorAll('.member-row');
    const noResultRow = document.getElementById('noResultRow');
    const emptyState = document.getElementById('emptyState'); 

    function applyFilter() {
        const searchText = searchInput.value.toLowerCase();
        const filterKomsel = komselInput.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const nameEl = row.querySelector('.name-text');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            const komselId = row.getAttribute('data-komsel-id');

            // Cek kondisi
            const matchName = name.includes(searchText);
            const matchKomsel = (filterKomsel === 'all' || komselId == filterKomsel);

            if (matchName && matchKomsel) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle No Result State
        if (rows.length > 0) {
            noResultRow.style.display = (visibleCount === 0) ? 'table-row' : 'none';
        }
    }

    // Event Listeners
    searchInput.addEventListener('input', applyFilter);


    // --- 2. CUSTOM DROPDOWN LOGIC ---
    const dropdown = document.getElementById('komselFilter');
    // Cek jika elemen filter ada (untuk mencegah error di halaman kosong)
    if(dropdown) {
        const trigger = document.getElementById('filterTrigger');
        const menu = dropdown.querySelector('.filter-menu');
        const label = document.getElementById('filterLabel');
        const filterSearch = document.getElementById('filterSearchInput');
        const options = dropdown.querySelectorAll('.filter-item');

        // Toggle Menu
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
            trigger.classList.toggle('active');
            if(menu.classList.contains('show')) filterSearch.focus();
        });

        // Pilih Opsi
        options.forEach(opt => {
            opt.addEventListener('click', function() {
                // Update UI
                label.textContent = this.getAttribute('data-label');
                options.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');

                // Update Logic
                komselInput.value = this.getAttribute('data-value');
                applyFilter(); // Trigger filter tabel

                // Tutup
                menu.classList.remove('show');
                trigger.classList.remove('active');
                filterSearch.value = ''; // Reset search dropdown
                filterDropdownOptions(''); // Reset list options
            });
        });

        // Search di dalam Dropdown Options
        filterSearch.addEventListener('input', (e) => {
            filterDropdownOptions(e.target.value.toLowerCase());
        });

        function filterDropdownOptions(query) {
            options.forEach(opt => {
                const text = opt.getAttribute('data-label').toLowerCase();
                opt.style.display = text.includes(query) ? 'block' : 'none';
            });
        }

        // Klik di luar menutup dropdown
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) {
                menu.classList.remove('show');
                trigger.classList.remove('active');
            }
        });
    }
});
</script>
@endpush