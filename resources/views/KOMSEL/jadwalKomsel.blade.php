@extends('layouts.app')

@section('title', 'Jadwal Ibadah KOMSEL')

@push('styles')
{{-- CSS KHUSUS HANYA UNTUK HALAMAN INI --}}
<style>
    /* Style umum */
    .table { border-color: var(--border-color); }
    .table th { color: var(--bs-body-color); font-weight: 600; }
    .table td { color: var(--text-secondary); }
    .table-hover > tbody > tr:hover > * { background-color: var(--hover-bg); color: var(--bs-body-color); }
    .modal-content { background-color: var(--element-bg); border-color: var(--border-color); }

    /* Style untuk filter animasi */
    .filter-nav-container { position: relative; display: inline-flex; background-color: var(--hover-bg); border-radius: 0.85rem; padding: 5px; box-shadow: var(--shadow); }
    .filter-nav-btn { border: none; background: transparent; color: var(--text-secondary); font-weight: 500; padding: 8px 20px; cursor: pointer; position: relative; z-index: 1; transition: color 0.3s ease, padding 0.3s ease, font-size 0.3s ease; }
    .filter-nav-btn.active { color: #fff; }
    .filter-slider { position: absolute; top: 5px; left: 5px; height: calc(100% - 10px); background-color: var(--primary-color); border-radius: 0.75rem; z-index: 0; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
    
    @media (max-width: 576px) {
        .filter-nav-container { padding: 4px; }
        .filter-nav-btn { padding: 6px 12px; font-size: 0.875rem; }
        .filter-slider { top: 4px; left: 4px; height: calc(100% - 8px); }
    }
    
    .remove-anggota-btn {
        padding: 0;
        line-height: 1;
    }
    .guest-badge { font-size: 0.7em; vertical-align: middle; margin-left: 5px; }
</style>
@endpush


@section('konten')

<div class="d-flex justify-content-center mb-4">
    <div class="filter-nav-container">
        <div class="filter-slider"></div>
        <button type="button" class="filter-nav-btn active" data-filter="all">Semua</button>
        <button type="button" class="filter-nav-btn" data-filter="Menunggu">Menunggu</button>
        <button type="button" class="filter-nav-btn" data-filter="Berlangsung">Berlangsung</button>
        <button type="button" class="filter-nav-btn" data-filter="Selesai">Selesai</button>
        <button type="button" class="filter-nav-btn" data-filter="Gagal">Gagal</button>
    </div>
</div>

{{-- KONTEN UTAMA: KARTU, TABEL, DAN MODAL --}}
<div class="card">
    <div class="card-body p-4">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
           <h5 class="card-title fw-bold mb-0">Jadwal Ibadah KOMSEL</h5>
           <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createJadwalModal">
               <i class="bi bi-plus-circle-fill me-2"></i>Buat Jadwal Baru
           </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="border-bottom">
                        <th scope="col">No</th>
                        <th scope="col">Nama KOMSEL</th>
                        <th scope="col">Status</th>
                        <th scope="col">Jadwal</th>
                        <th scope="col">Lokasi</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                    <tr data-status="{{ $schedule->status }}">
                        <td class="fw-bold">{{ $loop->iteration }}</td>
                        
                        {{-- [FIX 1] Gunakan properti 'komsel_name' yang disiapkan di controller --}}
                        <td>{{ $schedule->komsel_name ?? 'N/A' }}</td>
                        
                        <td>
                            <span @class([
                                'badge',
                                'text-bg-warning' => $schedule->status == 'Menunggu',
                                'text-bg-info' => $schedule->status == 'Berlangsung',
                                'text-bg-success' => $schedule->status == 'Selesai',
                                'text-bg-danger' => $schedule->status == 'Gagal',
                                'text-bg-secondary' => !in_array($schedule->status, ['Menunggu', 'Berlangsung', 'Selesai', 'Gagal']),
                            ])>
                                {{ $schedule->status }}
                            </span>
                        </td>
                        <td>{{ $schedule->day_of_week }}, {{ \Carbon\Carbon::parse($schedule->time)->format('H:i') }}</td>
                        <td>{{ $schedule->location }}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                @if($schedule->status == 'Berlangsung')
                                    {{-- [FIX 2] Gunakan 'komsel_name' di data-atribute --}}
                                    <button type="button" class="btn btn-sm btn-success" title="Input Absensi" data-bs-toggle="modal" data-bs-target="#absensiModal" data-schedule-id="{{ $schedule->id }}" data-komsel-id="{{ $schedule->komsel_id }}" data-komsel-nama="{{ $schedule->komsel_name ?? '' }}"><i class="bi bi-clipboard2-check-fill"></i></button>
                                @elseif($schedule->status == 'Selesai')
                                    {{-- [FIX 3] Gunakan 'komsel_name' di data-atribute --}}
                                    <button type="button" class="btn btn-sm btn-info" title="Info Absensi" data-bs-toggle="modal" data-bs-target="#infoAbsensiModal" data-schedule-id="{{ $schedule->id }}" data-komsel-nama="{{ $schedule->komsel_name ?? '' }}"><i class="bi bi-info-circle-fill"></i></button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary disabled" title="Absensi hanya untuk jadwal yang berlangsung"><i class="bi bi-clipboard2-check-fill"></i></button>
                                @endif
                                
                                {{-- [FIX 4] Gunakan 'komsel_name' di data-atribute --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Ubah Jadwal" data-bs-toggle="modal" data-bs-target="#editJadwalModal" data-id="{{ $schedule->id }}" data-komsel-id="{{ $schedule->komsel_id }}" data-komsel-nama="{{ $schedule->komsel_name ?? '' }}" data-day="{{ $schedule->day_of_week }}" data-time="{{ $schedule->time }}" data-location="{{ $schedule->location }}" data-description="{{ $schedule->description }}" data-status="{{ $schedule->status }}"><i class="bi bi-pencil-fill"></i></button>
                                
                                <form action="{{ route('jadwal.destroy', $schedule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Jadwal"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="empty-row">
                        <td colspan="6" class="text-center text-secondary py-4">Belum ada jadwal yang dibuat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Absensi --}}
<div class="modal fade" id="absensiModal" tabindex="-1" aria-labelledby="absensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h1 class="modal-title fs-5 fw-bold" id="absensiModalLabel">Form Absensi</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="absensiForm">
                    <input type="hidden" id="absensiScheduleId">
                    <div class="mb-3"><label for="anggotaSearchInput" class="form-label">Cari Anggota</label><input type="text" id="anggotaSearchInput" class="form-control mb-2" placeholder="Ketik untuk mencari...">
                        <label for="anggotaDropdown" class="form-label fw-semibold">Tambahkan Anggota Terdaftar</label><div class="input-group"><select class="form-select" id="anggotaDropdown" size="5"><option>Memuat...</option></select><button class="btn btn-outline-primary" type="button" id="addAnggotaBtn"><i class="bi bi-plus-lg"></i> Tambah</button></div>
                    </div>
                    <div class="mb-3"><label for="guestNameInput" class="form-label fw-semibold">Tambahkan Tamu (Partisipan)</label><div class="input-group"><input type="text" class="form-control" id="guestNameInput" placeholder="Masukkan nama tamu..."><button class="btn btn-outline-success" type="button" id="addGuestBtn"><i class="bi bi-person-plus-fill"></i> Tambah Tamu</button></div></div><hr>
                    <div class="mb-3"><label class="form-label text-secondary small">Daftar Hadir</label><div id="daftarHadirContainer" class="list-group"></div></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" form="absensiForm" class="btn btn-success">Simpan Absensi</button></div>
        </div>
    </div>
</div>

{{-- Modal Info Absensi --}}
<div class="modal fade" id="infoAbsensiModal" tabindex="-1" aria-labelledby="infoAbsensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h1 class="modal-title fs-5 fw-bold" id="infoAbsensiModalLabel">Laporan Absensi</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><p class="text-secondary">Total Kehadiran: <strong id="totalKehadiran">0</strong></p><div id="infoDaftarHadirContainer" class="list-group"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

{{-- Modal Create Jadwal --}}
<div class="modal fade" id="createJadwalModal" tabindex="-1" aria-labelledby="createJadwalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h1 class="modal-title fs-5" id="createJadwalModalLabel">Buat Jadwal Baru</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="createForm" action="{{ route('jadwal.store') }}" method="POST">
                    @csrf
                    <div class="mb-3"><label for="createKomsel" class="form-label">Nama KOMSEL</label>
                        <select class="form-select" id="createKomsel" name="komsel_id" required>
                            <option value="" disabled selected>Pilih KOMSEL</option>
                            {{-- [FIX 5] Gunakan sintaks ARRAY dan key 'nama' --}}
                            @foreach ($komsels as $komsel)
                                <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label for="createDayOfWeek" class="form-label">Hari Ibadah</label><select class="form-select" id="createDayOfWeek" name="day_of_week" required><option value="">Pilih Hari</option><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option><option value="Minggu">Minggu</option></select></div>
                    <div class="mb-3"><label for="createTime" class="form-label">Waktu Ibadah</label><input type="time" class="form-control" id="createTime" name="time" required></div>
                    <div class="mb-3"><label for="createLokasi" class="form-label">Lokasi</label><input type="text" class="form-control" id="createLokasi" name="location" placeholder="Contoh: Rumah Bpk. Budi" required></div>
                    <div class="mb-3"><label for="createDescription" class="form-label">Deskripsi (Opsional)</label><textarea class="form-control" id="createDescription" name="description" rows="3"></textarea></div>
                    <input type="hidden" name="status" value="Menunggu">
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" form="createForm" class="btn btn-primary">Simpan Jadwal</button></div>
        </div>
    </div>
</div>

{{-- Modal Edit Jadwal --}}
<div class="modal fade" id="editJadwalModal" tabindex="-1" aria-labelledby="editJadwalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h1 class="modal-title fs-5" id="editJadwalModalLabel">Edit Jadwal KOMSEL</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3"><label for="editKomsel" class="form-label">Nama KOMSEL</label>
                        <select class="form-select" id="editKomsel" name="komsel_id" required>
                            <option value="">Pilih KOMSEL</option>
                            {{-- [FIX 6] Gunakan sintaks ARRAY dan key 'nama' --}}
                            @foreach ($komsels as $komsel)
                                <option value="{{ $komsel['id'] }}">{{ $komsel['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label for="editDayOfWeek" class="form-label">Hari Ibadah</label><select class="form-select" id="editDayOfWeek" name="day_of_week" required><option value="">Pilih Hari</option><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option><option value="Minggu">Minggu</option></select></div>
                    <div class="mb-3"><label for="editTime" class="form-label">Waktu Ibadah</label><input type="time" class="form-control" id="editTime" name="time" required></div>
                    <div class="mb-3"><label for="editLokasi" class="form-label">Lokasi</label><input type="text" class="form-control" id="editLokasi" name="location" required></div>
                    <div class="mb-3"><label for="editDescription" class="form-label">Deskripsi (Opsional)</label><textarea class="form-control" id="editDescription" name="description" rows="3"></textarea></div>
                    <div class="mb-3"><label for="editStatus" class="form-label">Status</label><select class="form-select" id="editStatus" name="status" required><option value="Menunggu">Menunggu</option><option value="Berlangsung">Berlangsung</option><option value="Selesai">Selesai</option><option value="Gagal">Gagal</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" form="editForm" class="btn btn-primary">Simpan Perubahan</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- [PERBAIKAN]: KODE JAVASCRIPT LENGKAP DENGAN SEMUA FUNGSI --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIKA MODAL ABSENSI ---
    const absensiModalEl = document.getElementById('absensiModal');
    if (absensiModalEl) {
        const bsAbsensiModal = new bootstrap.Modal(absensiModalEl);
        const anggotaDropdown = absensiModalEl.querySelector('#anggotaDropdown');
        const addAnggotaBtn = absensiModalEl.querySelector('#addAnggotaBtn');
        const guestNameInput = absensiModalEl.querySelector('#guestNameInput');
        const addGuestBtn = absensiModalEl.querySelector('#addGuestBtn');
        const daftarHadirContainer = absensiModalEl.querySelector('#daftarHadirContainer');
        const absensiForm = absensiModalEl.querySelector('#absensiForm');
        const scheduleIdInput = absensiModalEl.querySelector('#absensiScheduleId');
        const anggotaSearchInput = absensiModalEl.querySelector('#anggotaSearchInput');

        /**
         * Helper untuk menambah orang ke daftar hadir di UI.
         */
        const addPersonToList = (id, name, isGuest = false) => {
            const uniqueId = isGuest ? `guest-${name.replace(/\s+/g, '-')}` : `user-${id}`;
            if (daftarHadirContainer.querySelector(`[data-unique-id="${uniqueId}"]`)) return;

            const listItem = document.createElement('div');
            listItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            listItem.setAttribute('data-unique-id', uniqueId);
            listItem.setAttribute('data-id', id); 
            listItem.setAttribute('data-is-guest', isGuest);
            listItem.innerHTML = `<span>${name} ${isGuest ? '<span class="badge text-bg-secondary guest-badge">Tamu</span>' : ''}</span><button type="button" class="btn btn-link text-danger btn-sm p-0 remove-anggota-btn" title="Hapus"><i class="bi bi-x-circle-fill"></i></button>`;
            daftarHadirContainer.appendChild(listItem);
        };

        absensiModalEl.addEventListener('show.bs.modal', async function(event) {
            const button = event.relatedTarget;
            const scheduleId = button.getAttribute('data-schedule-id');
            const komselId = button.getAttribute('data-komsel-id');
            const komselNama = button.getAttribute('data-komsel-nama');

            scheduleIdInput.value = scheduleId;
            absensiModalEl.querySelector('#absensiModalLabel').textContent = `Absensi: ${komselNama}`;
            
            daftarHadirContainer.innerHTML = '<p class="text-center text-secondary">Memuat...</p>';
            anggotaDropdown.innerHTML = '<option>Memuat...</option>';

            try {
                // [FIX 7] Ganti rute API komsel
                // Rute lama: /api/komsel/{komsel}/users
                // Rute baru dari Controller: /api/komsel/{id}/users (Contoh, sesuaikan)
                // Berdasarkan controller, rute ini belum ada. Kita harus tambahkan.
                // UNTUK SEMENTARA, kita asumsikan rutenya belum ada dan akan gagal.
                // Mari kita cek KomselController... ya, rute 'api.komsel.users' di-comment.
                
                // [FIX SEMENTARA] - Rute getUsersForKomsel tidak ada.
                // Mari kita ganti dengan rute yang ada
                // ... Oh, tidak ada rute untuk get users. Ini akan gagal.
                // Saya akan asumsikan Anda akan membuat rute ini:
                // Route::get('/api/komsel/{komselId}/users', [KomselController::class, 'getUsersForKomsel'])->name('api.komsel.users');

                const [usersResponse, attendanceResponse] = await Promise.all([
                    fetch(`{{ url('/api/komsel') }}/${komselId}/users`), // Asumsi URL ini
                    fetch(`{{ route('api.schedule.attendances.get', ['schedule' => ':id']) }}`.replace(':id', scheduleId))
                ]);

                if (!usersResponse.ok) throw new Error('Gagal memuat anggota. (Pastikan rute API /api/komsel/{id}/users ada)');
                if (!attendanceResponse.ok) throw new Error('Gagal memuat absensi.');
                
                const usersData = await usersResponse.json();
                const attendanceData = await attendanceResponse.json();
                const allAnggota = usersData.users;

                daftarHadirContainer.innerHTML = ''; 
                anggotaDropdown.innerHTML = '<option selected disabled value="">Pilih anggota...</option>';
                
                allAnggota.forEach(anggota => {
                    const option = document.createElement('option');
                    option.value = anggota.id;
                    option.textContent = anggota.nama;
                    anggotaDropdown.appendChild(option);
                    
                    if (attendanceData.present_users.some(p => p.id === anggota.id)) {
                        addPersonToList(anggota.id, anggota.nama, false);
                    }
                });

                attendanceData.guests.forEach(guestName => {
                    addPersonToList(guestName, guestName, true);
                });

            } catch (error) {
                console.error('Error:', error);
                daftarHadirContainer.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
                anggotaDropdown.innerHTML = `<option disabled selected>${error.message}</option>`;
            }
        });
        
        addAnggotaBtn.addEventListener('click', function() {
            const selectedOption = anggotaDropdown.options[anggotaDropdown.selectedIndex];
            if (!selectedOption || !selectedOption.value) return;
            addPersonToList(selectedOption.value, selectedOption.textContent, false);
            anggotaDropdown.selectedIndex = 0;
            anggotaSearchInput.value = '';
            anggotaSearchInput.dispatchEvent(new Event('input')); 
        });

        addGuestBtn.addEventListener('click', function() {
            const guestName = guestNameInput.value.trim();
            if (guestName === '') return;
            addPersonToList(guestName, guestName, true);
            guestNameInput.value = '';
        });

        anggotaSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = anggotaDropdown.options;
            for (let i = 0; i < options.length; i++) {
                if (i === 0) continue; 
                const optionText = options[i].text.toLowerCase();
                options[i].style.display = optionText.includes(searchTerm) ? '' : 'none';
            }
        });

        daftarHadirContainer.addEventListener('click', function(event) {
            const removeBtn = event.target.closest('.remove-anggota-btn');
            if (removeBtn) removeBtn.closest('.list-group-item').remove();
        });

        absensiForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

            const scheduleId = scheduleIdInput.value;
            const present_users = [];
            const guests = [];

            daftarHadirContainer.querySelectorAll('.list-group-item').forEach(item => {
                if (item.dataset.isGuest === 'true') {
                    guests.push(item.dataset.id); 
                } else {
                    present_users.push(item.dataset.id); 
                }
            });

            try {
                const response = await fetch(`{{ route('api.schedule.attendances.store', ['schedule' => ':id']) }}`.replace(':id', scheduleId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ present_users, guests })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Gagal menyimpan absensi.');
                }
                
                bsAbsensiModal.hide();
                window.location.reload(); 

            } catch (error) {
                console.error('Error saving attendance:', error);
                alert(`Error: ${error.message}`); 
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Simpan Absensi';
            }
        });

        absensiModalEl.addEventListener('hidden.bs.modal', () => { absensiForm.reset(); daftarHadirContainer.innerHTML = ''; });
    }

    // --- LOGIKA MODAL INFO ABSENSI (LENGKAP) ---
    const infoModalEl = document.getElementById('infoAbsensiModal');
    if (infoModalEl) {
        infoModalEl.addEventListener('show.bs.modal', async function(event) {
            const button = event.relatedTarget;
            const scheduleId = button.getAttribute('data-schedule-id');
            const komselNama = button.getAttribute('data-komsel-nama');
            
            const modalTitle = infoModalEl.querySelector('#infoAbsensiModalLabel');
            const totalKehadiranEl = infoModalEl.querySelector('#totalKehadiran');
            const listContainer = infoModalEl.querySelector('#infoDaftarHadirContainer');

            modalTitle.textContent = `Laporan Absensi: ${komselNama}`;
            listContainer.innerHTML = '<p class="text-center text-secondary">Memuat...</p>';
            totalKehadiranEl.textContent = '0';

            try {
                const response = await fetch(`{{ route('api.schedule.attendances.get', ['schedule' => ':id']) }}`.replace(':id', scheduleId));
                if (!response.ok) throw new Error('Gagal memuat data absensi.');
                
                const data = await response.json();
                listContainer.innerHTML = '';
                let total = 0;

                data.present_users.forEach(user => {
                    listContainer.innerHTML += `<div class="list-group-item">${user.nama}</div>`;
                    total++;
                });

                data.guests.forEach(guestName => {
                    listContainer.innerHTML += `<div class="list-group-item">${guestName} <span class="badge text-bg-secondary guest-badge">Tamu</span></div>`;
                    total++;
                });

                totalKehadiranEl.textContent = total;
                
                if (total === 0) {
                    listContainer.innerHTML = '<p class="text-center text-secondary">Tidak ada data kehadiran.</p>';
                }

            } catch (error) {
                console.error('Error fetching attendance info:', error);
                listContainer.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
            }
        });
    }

    // --- LOGIKA FILTER DAN MODAL EDIT/CREATE (DENGAN PERBAIKAN FILTER) ---
    const filterButtons = document.querySelectorAll('.filter-nav-btn');
    const slider = document.querySelector('.filter-slider');
    const allTableRows = document.querySelectorAll('.table tbody tr');
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

            allTableRows.forEach(row => {
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
    
    const editJadwalModalEl = document.getElementById('editJadwalModal');
    if (editJadwalModalEl) {
        editJadwalModalEl.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const komselId = button.getAttribute('data-komsel-id');
            const komselNama = button.getAttribute('data-komsel-nama');
            const day = button.getAttribute('data-day');
            const time = button.getAttribute('data-time');
            const location = button.getAttribute('data-location');
            const description = button.getAttribute('data-description');
            const status = button.getAttribute('data-status');

            editJadwalModalEl.querySelector('.modal-title').textContent = `Edit Jadwal: ${komselNama}`;
            editJadwalModalEl.querySelector('#editKomsel').value = komselId;
            editJadwalModalEl.querySelector('#editDayOfWeek').value = day;
            editJadwalModalEl.querySelector('#editTime').value = time;
            editJadwalModalEl.querySelector('#editLokasi').value = location;
            editJadwalModalEl.querySelector('#editDescription').value = description;
            editJadwalModalEl.querySelector('#editStatus').value = status;

            let url = `{{ route("jadwal.update", ["schedule" => ":id"]) }}`.replace(':id', id);
            editJadwalModalEl.querySelector('#editForm').action = url;
        });
    }
    const createJadwalModalEl = document.getElementById('createJadwalModal');
    if (createJadwalModalEl) { createJadwalModalEl.addEventListener('hidden.bs.modal', () => { document.getElementById('createForm').reset(); }); }
});
</script>
@endpush