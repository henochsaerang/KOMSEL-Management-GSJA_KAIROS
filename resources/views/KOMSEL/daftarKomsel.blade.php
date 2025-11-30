@extends('layouts.app')

@section('title', 'Daftar Anggota')

@push('styles')
<style>
    /* === MODERN CARD & LAYOUT === */
    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
        background-color: var(--element-bg);
    }
    
    /* === TABLE STYLING (Desktop Default) === */
    .table thead th { 
        border-bottom: 2px solid var(--border-color); 
        font-weight: 600; 
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        padding: 1rem 1.5rem;
        background-color: var(--element-bg-subtle);
    }
    .table td { 
        padding: 1rem 1.5rem;
        vertical-align: middle;
        color: var(--bs-body-color);
        border-bottom: 1px solid var(--border-color);
    }
    .table-hover > tbody > tr:hover > * { 
        background-color: var(--hover-bg);
        transition: background-color 0.2s ease;
    }
    .table tbody tr:last-child td { border-bottom: none; }

    /* === AVATAR/INITIALS === */
    .avatar-sm {
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        flex-shrink: 0; /* Prevent shrinking */
    }

    /* === BADGES === */
    .komsel-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35em 0.8em;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        background-color: var(--primary-bg-subtle);
        color: var(--primary-color);
        border: 1px solid rgba(79, 70, 229, 0.1);
    }
    .role-badge {
        font-size: 0.75rem;
        padding: 0.35em 0.8em;
        border-radius: 6px;
        font-weight: 500;
        background-color: var(--element-bg-subtle);
        color: var(--bs-body-color);
        border: 1px solid var(--border-color);
    }

    /* === SEARCH BOX === */
    .search-wrapper {
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.2s;
        background-color: var(--element-bg);
        display: flex;
        align-items: center;
    }
    .search-wrapper:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    .search-wrapper input {
        color: var(--bs-body-color);
        background: transparent;
    }
    .search-wrapper input::placeholder {
        color: var(--text-secondary);
        opacity: 0.7;
    }

    /* === CUSTOM SEARCHABLE DROPDOWN === */
    .custom-dropdown { position: relative; width: 100%; }
    .dropdown-trigger {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.6rem 1rem; 
        background: var(--element-bg);
        border: 1px solid var(--border-color); 
        border-radius: 0.75rem;
        cursor: pointer; transition: all 0.2s; 
        color: var(--bs-body-color);
        user-select: none;
        min-height: 42px;
    }
    .dropdown-trigger:hover { border-color: #a5b4fc; }
    .dropdown-trigger.active {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    .dropdown-menu-custom {
        position: absolute; top: 110%; left: 0; right: 0;
        background: var(--element-bg);
        border: 1px solid var(--border-color); 
        border-radius: 0.75rem;
        box-shadow: var(--shadow-md);
        display: none; z-index: 1050; overflow: hidden; padding-bottom: 5px;
    }
    .dropdown-menu-custom.show { display: block; animation: fadeIn 0.2s ease-out; }
    .dropdown-search {
        padding: 10px; border-bottom: 1px solid var(--border-color); 
        background: var(--element-bg-subtle);
    }
    .dropdown-search input {
        width: 100%; padding: 6px 10px; 
        border: 1px solid var(--border-color);
        background-color: var(--input-bg);
        color: var(--bs-body-color);
        border-radius: 0.5rem; font-size: 0.85rem; outline: none;
    }
    .dropdown-options { max-height: 250px; overflow-y: auto; padding: 5px; }
    .dropdown-item-custom {
        padding: 8px 12px; cursor: pointer; border-radius: 0.5rem;
        transition: background 0.15s; 
        color: var(--bs-body-color);
        font-weight: 500; font-size: 0.9rem;
    }
    .dropdown-item-custom:hover { 
        background-color: var(--hover-bg);
        color: var(--primary-color); 
    }
    .dropdown-item-custom.selected { 
        background-color: var(--primary-color); 
        color: #fff; 
    }
    .dropdown-item-custom.hidden { display: none; }

    /* === MOBILE RESPONSIVE TABLE (CARD VIEW) === */
    @media (max-width: 768px) {
        /* Hide Table Header */
        .table thead { display: none; }
        
        /* Make Table Block */
        .table, .table tbody, .table tr, .table td { 
            display: block; 
            width: 100%; 
        }
        
        /* Card Style for Row */
        .table tbody tr {
            margin-bottom: 1rem;
            background-color: var(--element-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1rem;
            position: relative;
            box-shadow: var(--shadow-sm);
        }
        
        /* Remove border from cells */
        .table td {
            border: none;
            padding: 0.25rem 0;
            text-align: left;
        }

        /* 1. Hide No Column */
        .table td:nth-child(1) { display: none; }

        /* 2. Name & Avatar (Top) */
        .table td:nth-child(2) {
            margin-bottom: 0.5rem;
            padding-right: 40px; /* Space for action button */
        }

        /* 3. Role (Middle) */
        .table td:nth-child(3) {
            display: inline-block;
            width: auto;
            padding: 0;
            margin-right: 0.5rem;
            margin-left: 3.5rem; /* Align with name text (avatar width + margin) */
        }

        /* 4. Komsel (Middle) */
        .table td:nth-child(4) {
            display: inline-block;
            width: auto;
            padding: 0;
        }

        /* 5. Action Button (Absolute Top Right) */
        .table td:nth-child(5) {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: auto;
            padding: 0;
        }
        /* Hide 'Jadwal' text on mobile to save space */
        .table td:nth-child(5) .btn span { display: none; }
        .table td:nth-child(5) .btn i { margin-right: 0 !important; }
        .table td:nth-child(5) .btn { 
            padding: 0.4rem; 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            border-radius: 50% !important;
        }
    }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5">
    
    {{-- Header Section --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-body mb-1">Daftar Anggota</h4>
            <p class="text-secondary small mb-0">Manajemen data anggota dan pembagian KOMSEL</p>
        </div>
        
        <div class="d-flex gap-2 align-items-center">
            {{-- Custom Filter Dropdown --}}
            <div class="w-100" style="min-width: 250px;">
                <div class="custom-dropdown" id="komselFilterDropdown">
                    {{-- Hidden Input untuk Filter Logic --}}
                    <input type="hidden" id="komsel-filter" value="all">
                    
                    <div class="dropdown-trigger">
                        <span class="selected-text text-truncate">Semua KOMSEL</span>
                        <i class="bi bi-chevron-down text-secondary small ms-2"></i>
                    </div>

                    <div class="dropdown-menu-custom">
                        <div class="dropdown-search">
                            <input type="text" class="search-input" placeholder="Cari Komsel..." autocomplete="off">
                        </div>
                        <div class="dropdown-options">
                            <div class="dropdown-item-custom selected" data-value="all" data-text="Semua KOMSEL">Semua KOMSEL</div>
                            @foreach ($komsels as $komsel)
                                <div class="dropdown-item-custom" data-value="{{ $komsel['id'] }}" data-text="{{ $komsel['nama'] }}">
                                    {{ $komsel['nama'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Wrapper --}}
    <div class="card border-0 bg-transparent shadow-none"> {{-- Remove default card style on outer wrapper for mobile layout --}}
        <div class="card-body p-0">
            {{-- Toolbar / Search Bar --}}
            <div class="p-3 border-bottom d-flex align-items-center rounded-top-4 mb-3 mb-md-0" style="background-color: var(--element-bg); border: 1px solid var(--border-color); border-radius: 1rem;">
                <div class="search-wrapper d-flex align-items-center flex-grow-1" style="max-width: 400px; border: none; background: transparent;">
                    <span class="input-group-text border-0 bg-transparent ps-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-0 shadow-none bg-transparent p-0" id="nama-search" placeholder="Cari nama anggota..." autocomplete="off">
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="background-color: transparent;">
                    <thead class="d-none d-md-table-header-group">
                        <tr>
                            <th scope="col" class="ps-4" style="width: 60px;">No</th>
                            <th scope="col">Nama Anggota</th>
                            <th scope="col">Peran (Role)</th>
                            <th scope="col">Komunitas Sel</th>
                            <th scope="col" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent">
                        @forelse ($users as $user)
                            <tr data-komsel-id="{{ $user['komsel_id'] ?? 'none' }}">
                                <td class="ps-4 text-secondary fw-medium">{{ $loop->iteration }}</td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 shadow-sm">
                                            {{ substr($user['nama'], 0, 1) }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-body">{{ $user['nama'] }}</span>
                                            {{-- Mobile Only Role Hint --}}
                                            <span class="d-md-none text-secondary small" style="font-size: 0.7rem; margin-top: 2px;">
                                                ID: {{ $user['id'] }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    @if(!empty($user['roles']))
                                        <span class="badge role-badge">
                                            {{ $user['roles'][0] }}
                                        </span>
                                    @else
                                        <span class="badge role-badge text-secondary">Anggota</span>
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
                                        <span class="komsel-badge">
                                            <i class="bi bi-people-fill me-1 opacity-50"></i> {{ $komselName }}
                                        </span>
                                    @else
                                        <span class="text-secondary small fst-italic ms-1">Belum bergabung</span>
                                    @endif
                                </td>

                                <td class="text-end pe-4">
                                    <a href="{{ route('formInput', ['jemaat_id' => $user['id']]) }}" 
                                       class="btn btn-sm btn-light text-primary shadow-sm border rounded-pill px-3 fw-bold"
                                       style="background: var(--element-bg); border-color: var(--border-color)!important;"
                                       data-bs-toggle="tooltip" 
                                       title="Buat Jadwal OIKOS untuk {{ $user['nama'] }}">
                                        <i class="bi bi-calendar-plus me-md-1"></i> <span>Jadwal</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i class="bi bi-inbox fs-1 mb-2 text-secondary"></i>
                                        <p class="mb-0 fw-medium text-secondary">Tidak ada data anggota ditemukan.</p>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Tooltips
    [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(el => new bootstrap.Tooltip(el));

    // === FILTER LOGIC ===
    const komselFilterInput = document.getElementById('komsel-filter');
    const namaSearch = document.getElementById('nama-search');
    const tableRows = document.querySelectorAll('table tbody tr:not(#empty-row)');
    const emptyRow = document.getElementById('empty-row');

    function applyFilters() {
        const komselValue = komselFilterInput.value;
        const searchValue = namaSearch.value.toLowerCase().trim();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const komselId = row.getAttribute('data-komsel-id');
            // Target nama dengan lebih spesifik
            const namaCell = row.querySelector('td:nth-child(2) .fw-bold'); 
            const nama = namaCell ? namaCell.textContent.toLowerCase() : '';

            const komselMatch = (komselValue === 'all' || komselId === komselValue);
            const nameMatch = nama.includes(searchValue);

            if (komselMatch && nameMatch) {
                row.style.display = ''; 
                visibleCount++;
            } else {
                row.style.display = 'none'; 
            }
        });

        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
            const emptyText = emptyRow.querySelector('p');
            if (visibleCount === 0 && searchValue) {
                emptyText.textContent = `Tidak ditemukan anggota dengan nama "${searchValue}"`;
            } else if (visibleCount === 0) {
                emptyText.textContent = "Tidak ada data anggota ditemukan.";
            }
        }
    }

    // Listeners for standard inputs
    if (namaSearch) namaSearch.addEventListener('input', applyFilters);


    // === CUSTOM DROPDOWN LOGIC (Filter Komsel) ===
    const dropdownWrapper = document.getElementById('komselFilterDropdown');
    if (dropdownWrapper) {
        const trigger = dropdownWrapper.querySelector('.dropdown-trigger');
        const menu = dropdownWrapper.querySelector('.dropdown-menu-custom');
        const searchInput = dropdownWrapper.querySelector('.search-input');
        const optionsList = dropdownWrapper.querySelector('.dropdown-options');
        const options = optionsList.querySelectorAll('.dropdown-item-custom');
        const selectedText = dropdownWrapper.querySelector('.selected-text');

        // Toggle
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
            trigger.classList.toggle('active');
            if (menu.classList.contains('show')) {
                setTimeout(() => searchInput.focus(), 100);
            }
        });

        // Select Item
        options.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.getAttribute('data-text');

                // Update UI
                selectedText.textContent = text;
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');

                // Update Hidden Input & Trigger Filter
                komselFilterInput.value = value;
                applyFilters(); // Panggil filter langsung

                // Close Menu
                menu.classList.remove('show');
                trigger.classList.remove('active');
                searchInput.value = '';
                filterOptions('');
            });
        });

        // Search inside dropdown
        searchInput.addEventListener('input', function(e) {
            filterOptions(e.target.value.toLowerCase());
        });

        function filterOptions(query) {
            options.forEach(option => {
                const text = option.getAttribute('data-text').toLowerCase();
                option.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // Close on Click Outside
        document.addEventListener('click', function(e) {
            if (!dropdownWrapper.contains(e.target)) {
                menu.classList.remove('show');
                trigger.classList.remove('active');
            }
        });
    }
});
</script>
@endpush