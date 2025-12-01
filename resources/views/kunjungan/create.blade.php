@extends('layouts.app')

@section('title', 'Catat Kunjungan Baru')

@push('styles')
<style>
    /* === MODERN CARD STYLE === */
    .card-form {
        border: none;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        background: var(--element-bg);
        overflow: visible; 
    }

    /* === RADIO CARDS (VISIT TYPE) === */
    .visit-type-option { display: none; }
    .visit-type-label {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 1rem;
        background-color: var(--element-bg-subtle);
        border: 2px solid transparent;
        border-radius: 12px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%; color: var(--text-secondary);
    }
    .visit-type-label:hover {
        background-color: var(--hover-bg);
        border-color: var(--border-color);
        transform: translateY(-2px);
    }
    .visit-type-option:checked + .visit-type-label {
        background-color: var(--primary-bg-subtle);
        border-color: var(--primary-color);
        color: var(--primary-color);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
    .visit-type-option:checked + .visit-type-label .visit-type-text {
        color: var(--bs-body-color);
    }
    .visit-type-icon { font-size: 1.75rem; margin-bottom: 0.5rem; }
    .visit-type-text { font-weight: 600; font-size: 0.8rem; }

    /* === SECTION DIVIDER === */
    .section-divider {
        display: flex; align-items: center; margin: 2rem 0 1.5rem;
        color: var(--text-secondary); font-weight: 700; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .section-divider::after {
        content: ""; flex: 1; height: 1px; background-color: var(--border-color); margin-left: 1rem;
    }

    /* === CUSTOM DROPDOWN === */
    .custom-dropdown { position: relative; width: 100%; }
    .dropdown-trigger {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.75rem 1rem; background: var(--element-bg);
        border: 1px solid var(--border-color); border-radius: 0.75rem;
        cursor: pointer; transition: all 0.2s; color: var(--bs-body-color);
    }
    .dropdown-trigger:hover { border-color: var(--primary-color); }
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

    /* === INPUTS === */
    .form-control, .form-select {
        background-color: var(--input-bg); border-color: var(--border-color); color: var(--bs-body-color);
        border-radius: 0.75rem; padding: 0.75rem 1rem;
    }
    .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
    .form-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--bs-body-color); }
    .form-text { color: var(--text-secondary); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5" style="max-width: 900px;">
    
    <div class="d-flex align-items-center mb-4 mt-2">
        <a href="{{ route('kunjungan') }}" class="btn btn-light border shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" 
           style="width: 45px; height: 45px; background: var(--element-bg); border-color: var(--border-color)!important; color: var(--text-secondary);">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <h4 class="fw-bold text-adaptive mb-0" style="color: var(--bs-body-color);">Jadwalkan Kunjungan</h4>
            <p class="text-secondary mb-0 small">Buat agenda kunjungan pastoral atau jemaat baru</p>
        </div>
    </div>

    <div class="card card-form">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('kunjungan.store') }}" method="POST" id="createForm">
                @csrf
                
                <div class="row g-4">
                    {{-- KIRI: PILIH PELAYAN DULU (Jika Admin) --}}
                    <div class="col-lg-6">
                        <label class="form-label">PIC / Pelayan</label>
                        
                        @if($canManagePelayan)
                            {{-- ADMIN: PILIH PELAYAN --}}
                            <div class="custom-dropdown" id="pelayanDropdown">
                                <input type="hidden" name="pic_id" id="picIdInput" required>
                                
                                <div class="dropdown-trigger" id="triggerPelayan">
                                    <span class="selected-text text-secondary">-- Pilih Pelayan --</span>
                                    <i class="bi bi-chevron-down text-secondary small"></i>
                                </div>

                                <div class="dropdown-menu-custom" id="menuPelayan">
                                    <div class="dropdown-search">
                                        <input type="text" placeholder="Cari pelayan..." autocomplete="off">
                                    </div>
                                    <div class="dropdown-options">
                                        @foreach($pelayans as $p)
                                            @php
                                                // Pastikan 'komsels' adalah array valid JSON
                                                $komselsArray = is_array($p['komsels']) ? $p['komsels'] : [];
                                            @endphp
                                            <div class="dropdown-item-custom" 
                                                 data-value="{{ $p['id'] }}" 
                                                 data-text="{{ $p['nama'] }}"
                                                 data-komsels='{{ json_encode($komselsArray) }}'>
                                                {{ $p['nama'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-1 small"><i class="bi bi-info-circle me-1"></i>Pilih Pelayan untuk menyaring daftar anggota.</div>
                        @else
                            {{-- LEADER: READONLY --}}
                            <div class="d-flex align-items-center p-3 border rounded-3" style="background-color: var(--element-bg-subtle); border-color: var(--border-color)!important;">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle border border-primary border-opacity-10 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="color: var(--bs-body-color);">{{ $currentUser->nama }}</div>
                                    <small class="text-secondary">Leader / Pelayan (Anda)</small>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="form-label">Anggota Komsel <span class="text-danger">*</span></label>
                            
                            {{-- DROPDOWN ANGGOTA --}}
                            <div class="custom-dropdown" id="memberDropdown">
                                <input type="hidden" name="member_id" id="realMemberId" required>
                                <input type="hidden" name="nama_anggota_hidden" id="nama_anggota_hidden">
                                
                                <div class="dropdown-trigger" id="triggerMember">
                                    <span class="selected-text text-secondary">-- Cari nama anggota --</span>
                                    <i class="bi bi-chevron-down text-secondary small"></i>
                                </div>

                                <div class="dropdown-menu-custom" id="menuMember">
                                    <div class="dropdown-search">
                                        <input type="text" placeholder="Ketik nama..." autocomplete="off">
                                    </div>
                                    <div class="dropdown-options">
                                        @forelse($members as $member)
                                            <div class="dropdown-item-custom" 
                                                 data-value="{{ $member['id'] }}" 
                                                 data-text="{{ $member['nama'] }}"
                                                 data-komsel-id="{{ $member['komsel_id'] ?? '' }}">
                                                {{ $member['nama'] }}
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-secondary small">Tidak ada data anggota</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- KANAN: WAKTU --}}
                    <div class="col-lg-6">
                        <label class="form-label">Waktu Kunjungan <span class="text-danger">*</span></label>
                        <div class="p-1 border rounded-3 h-100 d-flex flex-column justify-content-center" style="background-color: var(--element-bg-subtle); border-color: var(--border-color)!important;">
                            <input type="datetime-local" name="tanggal" class="form-control border-0 bg-transparent fw-bold text-center" 
                                   style="font-size: 1.2rem; height: 100%; color: var(--bs-body-color);" 
                                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                </div>

                <div class="section-divider">Detail Kegiatan</div>

                {{-- JENIS KUNJUNGAN --}}
                <div class="mb-4">
                    <label class="form-label mb-3">Jenis Kunjungan <span class="text-danger">*</span></label>
                    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3">
                        @php
                            $types = [
                                ['id'=>'Pastoral', 'icon'=>'bi-book-half', 'label'=>'Pastoral', 'color'=>'text-primary'],
                                ['id'=>'HUT', 'icon'=>'bi-gift', 'label'=>'Ulang Tahun', 'color'=>'text-danger'],
                                ['id'=>'Sakit', 'icon'=>'bi-bandaid', 'label'=>'Jenguk Sakit', 'color'=>'text-warning'],
                                ['id'=>'Kedukaan', 'icon'=>'bi-cloud-rain', 'label'=>'Kedukaan', 'color'=>'text-secondary'],
                                ['id'=>'Konseling', 'icon'=>'bi-chat-heart', 'label'=>'Konseling', 'color'=>'text-success'],
                                ['id'=>'Lainnya', 'icon'=>'bi-three-dots', 'label'=>'Lainnya', 'color'=>'text-body'],
                            ];
                        @endphp
                        @foreach($types as $type)
                        <div class="col">
                            <input type="radio" name="jenis_kunjungan" id="type{{ $type['id'] }}" value="{{ $type['id'] }}" class="visit-type-option" {{ $loop->first ? 'checked' : '' }}>
                            <label for="type{{ $type['id'] }}" class="visit-type-label">
                                <i class="bi {{ $type['icon'] }} visit-type-icon {{ $type['color'] }}"></i>
                                <span class="visit-type-text text-center">{{ $type['label'] }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label">Rencana / Catatan Awal</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Tuliskan topik pembahasan atau pokok doa..."></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top" style="border-color: var(--border-color)!important;">
                    <a href="{{ route('kunjungan') }}" class="btn btn-link text-secondary text-decoration-none fw-medium">Batal</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill fw-bold shadow-sm">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------------
    // 1. INITIALIZATION & DROPDOWN LOGIC
    // -------------------------------------------------------------------
    const memberDropdown = document.getElementById('memberDropdown');
    const pelayanDropdown = document.getElementById('pelayanDropdown');
    
    // Helper: Trigger Click on specific option programmatically
    function selectDropdownOption(wrapperId, value) {
        const wrapper = document.getElementById(wrapperId);
        if (!wrapper) return;
        const option = wrapper.querySelector(`.dropdown-item-custom[data-value="${value}"]`);
        if (option) option.click();
    }

    // Helper: Filter Member Options based on Komsel Array
    function filterMembersByKomsel(komselIds) {
        const options = memberDropdown.querySelectorAll('.dropdown-item-custom');
        let visibleCount = 0;
        
        options.forEach(opt => {
            const memberKomselId = opt.dataset.komselId;
            if (!komselIds || komselIds.length === 0 || komselIds.includes(String(memberKomselId))) {
                opt.classList.remove('hidden');
                visibleCount++;
            } else {
                opt.classList.add('hidden');
            }
        });
        
        const currentVal = document.getElementById('realMemberId').value;
        const currentOpt = memberDropdown.querySelector(`.dropdown-item-custom[data-value="${currentVal}"]`);
        if (currentOpt && currentOpt.classList.contains('hidden')) {
             const triggerText = memberDropdown.querySelector('.selected-text');
             triggerText.textContent = '-- Cari nama anggota --';
             triggerText.classList.add('text-secondary');
             triggerText.classList.remove('fw-bold', 'text-body');
             document.getElementById('realMemberId').value = '';
        }
    }

    // -------------------------------------------------------------------
    // 2. EVENT LISTENERS: BI-DIRECTIONAL SYNC
    // -------------------------------------------------------------------

    if (pelayanDropdown) {
        pelayanDropdown.addEventListener('dropdown-selected', function(e) {
            const pelayanId = e.detail.value;
            const pelayanName = e.detail.text;
            const selectedOption = pelayanDropdown.querySelector(`.dropdown-item-custom[data-value="${pelayanId}"]`);
            let komsels = [];
            
            if (selectedOption && selectedOption.dataset.komsels) {
                try {
                    komsels = JSON.parse(selectedOption.dataset.komsels);
                    komsels = komsels.map(String);
                } catch (e) { console.error("Error parsing komsels JSON", e); }
            }

            console.log("Pelayan Selected:", pelayanName, "Komsels:", komsels);
            filterMembersByKomsel(komsels);
        });
    }

    if (memberDropdown && pelayanDropdown) {
        memberDropdown.addEventListener('dropdown-selected', function(e) {
            const memberId = e.detail.value;
            const selectedOption = memberDropdown.querySelector(`.dropdown-item-custom[data-value="${memberId}"]`);
            const memberKomselId = String(selectedOption.dataset.komselId);

            if (memberKomselId) {
                const pelayanOptions = pelayanDropdown.querySelectorAll('.dropdown-item-custom');
                let foundPelayanId = null;

                for (let opt of pelayanOptions) {
                    let pKomsels = [];
                    try {
                        pKomsels = JSON.parse(opt.dataset.komsels).map(String);
                    } catch (err) {}

                    if (pKomsels.includes(memberKomselId)) {
                        foundPelayanId = opt.dataset.value;
                        break; 
                    }
                }

                if (foundPelayanId) {
                    selectDropdownOption('pelayanDropdown', foundPelayanId);
                }
            }
        });
    }

    // -------------------------------------------------------------------
    // 3. CUSTOM DROPDOWN CORE
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

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu-custom').forEach(m => {
                if(m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
            if(menu.classList.contains('show')) setTimeout(() => searchInput.focus(), 100);
        });

        options.forEach(item => {
            item.addEventListener('click', () => {
                const val = item.dataset.value;
                const txt = item.dataset.text;
                
                hiddenInput.value = val;
                
                if(hiddenInput.id === 'realMemberId') {
                    const nameHidden = document.getElementById('nama_anggota_hidden');
                    if(nameHidden) nameHidden.value = txt;
                }

                displaySpan.textContent = txt;
                displaySpan.classList.remove('text-secondary');
                displaySpan.classList.add('fw-bold');
                displaySpan.style.color = 'var(--bs-body-color)';

                options.forEach(o => o.classList.remove('selected'));
                item.classList.add('selected');

                menu.classList.remove('show');
                searchInput.value = '';
                options.forEach(i => i.style.display = ''); 

                const event = new CustomEvent('dropdown-selected', { detail: { value: val, text: txt } });
                wrapper.dispatchEvent(event);
            });
        });

        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            options.forEach(item => {
                if (item.classList.contains('hidden')) return; 
                const text = item.dataset.text.toLowerCase();
                item.style.display = text.includes(term) ? 'block' : 'none';
            });
        });

        document.addEventListener('click', (e) => {
            if(!wrapper.contains(e.target)) menu.classList.remove('show');
        });
    }

    initDropdown('memberDropdown');
    initDropdown('pelayanDropdown');

    // =========================================================
    // [AUTO-FILL] LOGIKA UNTUK URL PARAMETER (member_id)
    // =========================================================
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedMemberId = urlParams.get('member_id');

    if (preselectedMemberId) {
        // Panggil fungsi helper untuk memilih member
        selectDropdownOption('memberDropdown', preselectedMemberId);
        
        // (Opsional) Bersihkan URL agar terlihat bersih
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: newUrl}, '', newUrl);
    }
});
</script>
@endpush