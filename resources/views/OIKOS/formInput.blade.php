@extends('layouts.app')

@section('title', 'Atur Jadwal OIKOS')

@push('styles')
{{-- Flatpickr CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">

<style>
    /* === MODERN CARD STYLE === */
    .card-form {
        border: none;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        background: var(--element-bg);
        overflow: visible; 
    }

    /* === SECTION DIVIDER === */
    .section-divider {
        display: flex; align-items: center; margin: 2rem 0 1.5rem;
        color: var(--text-secondary); font-weight: 700; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .section-divider::after {
        content: ""; flex: 1; height: 1px; background-color: var(--border-color); margin-left: 1rem;
    }

    /* === CUSTOM DROPDOWN (SEARCHABLE) === */
    .custom-dropdown { position: relative; width: 100%; }
    .dropdown-trigger {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.75rem 1rem; background: var(--input-bg);
        border: 1px solid var(--border-color); border-radius: 0.75rem;
        cursor: pointer; transition: all 0.2s; color: var(--bs-body-color);
    }
    .dropdown-trigger:hover, .dropdown-trigger.active { border-color: var(--primary-color); }
    .dropdown-menu-custom {
        position: absolute; top: 110%; left: 0; right: 0;
        background: var(--element-bg); border: 1px solid var(--border-color);
        border-radius: 0.75rem; box-shadow: var(--shadow-md);
        display: none; z-index: 1050; overflow: hidden; padding-bottom: 5px;
    }
    .dropdown-menu-custom.show { display: block; animation: fadeIn 0.15s ease-out; }
    .dropdown-search { padding: 10px; border-bottom: 1px solid var(--border-color); background: var(--element-bg-subtle); }
    .dropdown-search input {
        width: 100%; padding: 8px 12px; border: 1px solid var(--border-color);
        background: var(--input-bg); color: var(--bs-body-color); border-radius: 0.5rem; outline: none;
    }
    .dropdown-options { max-height: 220px; overflow-y: auto; }
    .dropdown-item-custom {
        padding: 10px 12px; cursor: pointer; color: var(--bs-body-color); transition: background 0.1s;
    }
    .dropdown-item-custom:hover { background: var(--hover-bg); color: var(--primary-color); }
    .dropdown-item-custom.selected { background: var(--primary-bg-subtle); color: var(--primary-color); font-weight: 600; }
    .dropdown-item-custom.hidden { display: none; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* === INPUTS & FORMS === */
    .form-control, .form-select {
        background-color: var(--input-bg); border-color: var(--border-color); color: var(--bs-body-color);
        border-radius: 0.75rem; padding: 0.75rem 1rem;
    }
    .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
    .form-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--bs-body-color); }
    .form-text { color: var(--text-secondary); }
    
    /* Toggle Switch Styling */
    .form-check-input {
        cursor: pointer; width: 3em; height: 1.5em;
    }
    .form-check-input:checked {
        background-color: var(--primary-color); border-color: var(--primary-color);
    }
    
    /* Flatpickr Dark Mode Overrides */
    .flatpickr-calendar { background: var(--element-bg); border-color: var(--border-color); box-shadow: var(--shadow-md); }
    .flatpickr-day { color: var(--bs-body-color); }
    .flatpickr-day.flatpickr-disabled { color: var(--text-secondary); opacity: 0.3; }
    .flatpickr-current-month { color: var(--bs-body-color); }
    .flatpickr-weekday { color: var(--text-secondary); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5" style="max-width: 900px;">
    
    {{-- Header dengan Tombol Kembali --}}
    <div class="d-flex align-items-center mb-4 mt-2">
        <a href="{{ route('oikos') }}" class="btn btn-light border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" 
           style="width: 45px; height: 45px; background: var(--element-bg); border-color: var(--border-color)!important; color: var(--text-secondary);">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <h4 class="fw-bold text-adaptive mb-0" style="color: var(--bs-body-color);">Jadwalkan OIKOS</h4>
            <p class="text-secondary mb-0 small">Atur jadwal penjangkauan atau kunjungan rutin</p>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- LOGIKA NOTIFIKASI STATUS AKSES --}}
    {{-- ======================================================= --}}
    
    @if(isset($isNormalScheduleDay) && !$isNormalScheduleDay)
        @if(isset($userCanBypass) && $userCanBypass)
            {{-- ALERT HIJAU: Admin/Unlock Mode --}}
            <div class="alert alert-success d-flex align-items-start border-0 shadow-sm rounded-4 mb-4" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754;">
                <i class="bi bi-unlock-fill me-3 fs-4 text-success"></i>
                <div class="text-success">
                    <strong class="d-block mb-1">Akses Khusus Aktif</strong>
                    Anda memiliki akses untuk membuat laporan/jadwal meskipun jangka waktu ini bukan untuk menginput laporan.
                </div>
            </div>
        @else
            {{-- ALERT KUNING: Mode Request Access --}}
            <div class="alert alert-warning d-flex align-items-start border-0 shadow-sm rounded-4 mb-4" role="alert" style="background-color: #fff3cd; color: #856404;">
                <i class="bi bi-exclamation-circle-fill me-3 fs-4 text-warning"></i>
                <div>
                    <strong class="d-block mb-1">Di Luar Jadwal Input</strong>
                    Saat ini di luar jadwal normal (Minggu-Selasa). Silakan isi form di bawah, lalu klik tombol <strong>"Ajukan Persetujuan Admin"</strong> agar jadwal ini disetujui.
                </div>
            </div>
        @endif
    @endif

    {{-- ======================================================= --}}

    <div class="card card-form">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('oikos.store') }}" method="POST" id="oikosForm">
                @csrf
                <input type="hidden" name="input_type" id="inputType" value="manual">

                {{-- SECTION 1: TARGET & PELAYAN --}}
                <div class="row g-4">
                    
                    {{-- KOLOM KIRI: TARGET OIKOS --}}
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Target OIKOS <span class="text-danger">*</span></label>
                            
                            {{-- Toggle Switch --}}
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggleInputType">
                                <label class="form-check-label small text-secondary fw-semibold ms-1" for="toggleInputType" style="margin-top: 2px;">
                                    Member Terdaftar
                                </label>
                            </div>
                        </div>

                        {{-- INPUT MANUAL --}}
                        <div id="inputManualContainer">
                            <input type="text" id="Anggota_tidakTerdaftar" name="Anggota_tidakTerdaftar" 
                                   class="form-control" placeholder="Nama Oikos / Keluarga (Cth: Kel. Bapak Budi)">
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i>Gunakan mode ini untuk orang baru.
                            </div>
                        </div>

                        {{-- INPUT TERDAFTAR (CUSTOM DROPDOWN) --}}
                        <div id="inputTerdaftarContainer" class="d-none">
                            <div class="custom-dropdown" id="memberDropdown">
                                <input type="hidden" name="Nama_Anggota" id="Nama_Anggota_Value">
                                
                                <div class="dropdown-trigger" id="triggerMember">
                                    <span class="selected-text text-secondary">-- Pilih Anggota --</span>
                                    <i class="bi bi-chevron-down text-secondary small"></i>
                                </div>

                                <div class="dropdown-menu-custom" id="menuMember">
                                    <div class="dropdown-search">
                                        <input type="text" placeholder="Cari nama anggota..." autocomplete="off">
                                    </div>
                                    <div class="dropdown-options">
                                        @foreach ($users as $user)
                                            <div class="dropdown-item-custom" 
                                                 {{-- Handle array syntax from API cache --}}
                                                 data-value="{{ is_array($user) ? $user['id'] : $user->id }}" 
                                                 data-text="{{ is_array($user) ? $user['nama'] : $user->nama }}">
                                                {{ is_array($user) ? $user['nama'] : $user->nama }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="form-text small mt-2">
                                <i class="bi bi-check-circle me-1"></i>Memilih dari database jemaat.
                            </div>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: PELAYAN --}}
                    <div class="col-lg-6">
                        <label class="form-label">Pelayan Bertugas</label>
                        
                        @if($isAdmin)
                            {{-- ADMIN: DROPDOWN PILIH PELAYAN --}}
                            <div class="custom-dropdown" id="pelayanDropdown">
                                <input type="hidden" name="pelayan" id="pelayanInput">
                                
                                <div class="dropdown-trigger">
                                    <span class="selected-text text-secondary">-- Pilih Pelayan --</span>
                                    <i class="bi bi-chevron-down text-secondary small"></i>
                                </div>

                                <div class="dropdown-menu-custom">
                                    <div class="dropdown-search">
                                        <input type="text" placeholder="Cari pelayan..." autocomplete="off">
                                    </div>
                                    <div class="dropdown-options">
                                        @foreach ($pelayans as $pelayan)
                                            <div class="dropdown-item-custom" 
                                                 data-value="{{ is_array($pelayan) ? $pelayan['id'] : $pelayan->id }}" 
                                                 data-text="{{ is_array($pelayan) ? $pelayan['nama'] : $pelayan->nama }}">
                                                {{ is_array($pelayan) ? $pelayan['nama'] : $pelayan->nama }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="form-text small mt-2">Admin dapat menugaskan pelayan lain.</div>
                        @else
                            {{-- LEADER: READONLY CARD --}}
                            <div class="d-flex align-items-center p-3 border rounded-3" style="background-color: var(--element-bg-subtle); border-color: var(--border-color)!important;">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle border border-success border-opacity-10 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="color: var(--bs-body-color);">{{ $currentUser->name ?? $currentUser->nama }}</div>
                                    <small class="text-secondary">Leader / Pelayan (Anda)</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="section-divider">Waktu Pelaksanaan</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Mulai (Rabu - Sabtu) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="background-color: var(--input-bg); border-color: var(--border-color); color: var(--text-secondary);">
                                <i class="bi bi-calendar-event"></i>
                            </span>
                            <input type="text" id="tanggalDari" name="tanggalDari" class="form-control border-start-0 ps-0" placeholder="Pilih tanggal mulai..." required style="border-left: 0;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Selesai (Rabu - Sabtu) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" style="background-color: var(--input-bg); border-color: var(--border-color); color: var(--text-secondary);">
                                <i class="bi bi-calendar-check"></i>
                            </span>
                            <input type="text" id="tanggalSampai" name="tanggalSampai" class="form-control border-start-0 ps-0" placeholder="Pilih tanggal selesai..." required style="border-left: 0;">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-5 mt-2 border-top" style="border-color: var(--border-color)!important;">
                    <a href="{{ route('oikos') }}" class="btn btn-link text-secondary text-decoration-none fw-medium">Batal</a>
                    
                    {{-- ================================================= --}}
                    {{-- LOGIKA TOMBOL SUBMIT (REQUEST vs SAVE) --}}
                    {{-- ================================================= --}}
                    
                    @if(isset($isNormalScheduleDay) && !$isNormalScheduleDay && (!isset($userCanBypass) || !$userCanBypass))
                        {{-- TOMBOL PENGAJUAN (Kuning) --}}
                        <button type="submit" name="action" value="request_access" class="btn btn-warning text-dark btn-lg px-4 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-send-exclamation me-2"></i> Ajukan Persetujuan Admin
                        </button>
                    @else
                        {{-- TOMBOL SIMPAN NORMAL (Biru) --}}
                        <button type="submit" name="action" value="save" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-calendar-check me-2"></i> Simpan Jadwal
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------
    // 1. TOGGLE & INPUT LOGIC
    // -------------------------------------------------------------------
    const toggleSwitch = document.getElementById('toggleInputType');
    const manualContainer = document.getElementById('inputManualContainer');
    const terdaftarContainer = document.getElementById('inputTerdaftarContainer');
    const inputTypeHidden = document.getElementById('inputType');
    const manualInput = document.getElementById('Anggota_tidakTerdaftar');

    function updateInputMode(isRegistered) {
        if (isRegistered) {
            manualContainer.classList.add('d-none');
            terdaftarContainer.classList.remove('d-none');
            inputTypeHidden.value = 'terdaftar';
            manualInput.value = ''; // Reset manual input
        } else {
            manualContainer.classList.remove('d-none');
            terdaftarContainer.classList.add('d-none');
            inputTypeHidden.value = 'manual';
            // Reset dropdown value (logic below)
            resetDropdown('memberDropdown');
        }
    }

    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            updateInputMode(this.checked);
        });
    }

    // -------------------------------------------------------------------
    // 2. CUSTOM DROPDOWN LOGIC (Reusable)
    // -------------------------------------------------------------------
    function initDropdown(wrapperId) {
        const wrapper = document.getElementById(wrapperId);
        if(!wrapper) return;

        const trigger = wrapper.querySelector('.dropdown-trigger');
        const menu = wrapper.querySelector('.dropdown-menu-custom');
        const searchInput = wrapper.querySelector('input[type="text"]');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        const displaySpan = wrapper.querySelector('.selected-text');
        const options = wrapper.querySelectorAll('.dropdown-item-custom');

        // Toggle
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close others
            document.querySelectorAll('.dropdown-menu-custom').forEach(m => {
                if(m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
            trigger.classList.toggle('active');
            if(menu.classList.contains('show')) setTimeout(() => searchInput.focus(), 100);
        });

        // Selection
        options.forEach(item => {
            item.addEventListener('click', () => {
                const val = item.dataset.value;
                const txt = item.dataset.text;
                
                hiddenInput.value = val;
                
                displaySpan.textContent = txt;
                displaySpan.classList.remove('text-secondary');
                displaySpan.classList.add('fw-bold', 'text-body');

                options.forEach(o => o.classList.remove('selected'));
                item.classList.add('selected');

                menu.classList.remove('show');
                trigger.classList.remove('active');
                searchInput.value = '';
                options.forEach(i => i.style.display = ''); 
            });
        });

        // Search
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            options.forEach(item => {
                const text = item.dataset.text.toLowerCase();
                item.style.display = text.includes(term) ? 'block' : 'none';
            });
        });

        // Outside Click
        document.addEventListener('click', (e) => {
            if(!wrapper.contains(e.target)) {
                menu.classList.remove('show');
                trigger.classList.remove('active');
            }
        });
    }

    // Helper to select programmatically
    function selectDropdownValue(wrapperId, value) {
        const wrapper = document.getElementById(wrapperId);
        if(!wrapper) return;
        const option = wrapper.querySelector(`.dropdown-item-custom[data-value="${value}"]`);
        if(option) option.click();
    }

    // Helper to reset dropdown
    function resetDropdown(wrapperId) {
        const wrapper = document.getElementById(wrapperId);
        if(!wrapper) return;
        
        wrapper.querySelector('input[type="hidden"]').value = '';
        const displaySpan = wrapper.querySelector('.selected-text');
        displaySpan.textContent = wrapperId === 'memberDropdown' ? '-- Pilih Anggota --' : '-- Pilih Pelayan --';
        displaySpan.classList.add('text-secondary');
        displaySpan.classList.remove('fw-bold', 'text-body');
        
        wrapper.querySelectorAll('.dropdown-item-custom').forEach(o => o.classList.remove('selected'));
    }

    // Initialize Dropdowns
    initDropdown('memberDropdown');
    initDropdown('pelayanDropdown');

    // -------------------------------------------------------------------
    // 3. AUTO-FILL LOGIC (FROM URL)
    // -------------------------------------------------------------------
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedId = urlParams.get('jemaat_id');

    if (preselectedId) {
        // 1. Switch to Registered Mode
        toggleSwitch.checked = true;
        updateInputMode(true);
        
        // 2. Select the member in dropdown
        setTimeout(() => {
            selectDropdownValue('memberDropdown', preselectedId);
        }, 100);
    }

    // -------------------------------------------------------------------
    // 4. DATE LOGIC (Wednesday - Saturday)
    // -------------------------------------------------------------------
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 (Sun) - 6 (Sat)
    
    // Calculate Wednesday of current week
    const diffToWednesday = 3 - dayOfWeek; 
    const wednesdayThisWeek = new Date(today);
    wednesdayThisWeek.setDate(today.getDate() + diffToWednesday);

    // Calculate Saturday of current week
    const saturdayThisWeek = new Date(wednesdayThisWeek);
    saturdayThisWeek.setDate(wednesdayThisWeek.getDate() + 3);

    const fpConfig = {
        locale: "id",
        dateFormat: "Y-m-d",
        minDate: wednesdayThisWeek,
        maxDate: saturdayThisWeek,
        disableMobile: "true" // Force Custom UI on mobile for consistency
    };

    const fpDari = flatpickr("#tanggalDari", {
        ...fpConfig,
        onChange: function(selectedDates, dateStr, instance) {
            fpSampai.set('minDate', dateStr);
            fpSampai.set('maxDate', saturdayThisWeek);
        }
    });

    const fpSampai = flatpickr("#tanggalSampai", {
        ...fpConfig,
        minDate: wednesdayThisWeek,
        maxDate: saturdayThisWeek
    });
});
</script>
@endpush