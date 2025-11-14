@extends('layouts.app')

@section('title', 'Daftar OIKOS')

@push('styles')
{{-- CSS KHUSUS HANYA UNTUK HALAMAN INI --}}
<style>
    /* Style umum */
    .table thead th { border-bottom: 2px solid var(--border-color); font-weight: 600; }
    .schedule-badge { font-size: 0.8rem; padding: 0.4em 0.7em; background-color: var(--hover-bg); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .modal-content { background-color: var(--element-bg); }
    
    /* Style untuk filter animasi */
    .filter-nav-container { position: relative; display: inline-flex; background-color: var(--hover-bg); border-radius: 0.85rem; padding: 5px; box-shadow: var(--shadow); }
    .filter-nav-btn { border: none; background: transparent; color: var(--text-secondary); font-weight: 500; padding: 8px 20px; cursor: pointer; position: relative; z-index: 1; transition: color 0.3s ease; }
    .filter-nav-btn.active { color: #fff; }
    .filter-slider { position: absolute; top: 5px; left: 5px; height: calc(100% - 10px); background-color: var(--primary-color); border-radius: 0.75rem; z-index: 0; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }

    /* Style untuk detail laporan di modal */
    .report-detail-group dt { font-weight: 500; color: var(--bs-body-color); }
    .report-detail-group dd { color: var(--text-secondary); }
</style>
@endpush

@section('konten')

    {{-- Filter Navigasi --}}
    <div class="d-flex justify-content-center mb-4">
        <div class="filter-nav-container">
            <div class="filter-slider"></div>
            <button type="button" class="filter-nav-btn active" data-filter="all">Semua</button>
            <button type="button" class="filter-nav-btn" data-filter="Direncanakan">Direncanakan</button>
            <button type="button" class="filter-nav-btn" data-filter="Diproses">Diproses</button>
            <button type="button" class="filter-nav-btn" data-filter="Selesai">Selesai</button>
            <button type="button" class="filter-nav-btn" data-filter="Gagal">Gagal</button>
        </div>
    </div>
    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <h5 class="card-title fw-bold mb-0"><i class="bi bi-house-heart-fill me-2" style="color: var(--primary-color);"></i>Daftar Kunjungan OIKOS</h5>
                <a href="{{ route('formInput') }}" class="btn btn-primary fw-semibold"><i class="bi bi-plus-circle-fill me-2"></i>Buat Jadwal OIKOS</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">No.</th><th scope="col">Nama OIKOS</th><th scope="col">Pelayan</th><th scope="col">Status</th><th scope="col">Jadwal</th><th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($oikosVisits as $visit)
                        <tr data-status="{{ $visit->status }}">
                            <td class="fw-bold">{{ $loop->iteration }}</td>
                            
                            {{-- [FIX 1] Baca dari array $visit->jemaat_data (dari API) --}}
                            <td>{{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}</td>
                            
                            {{-- [FIX 2] Baca dari array $visit->pelayan_data (dari API) --}}
                            <td>{{ $visit->pelayan_data['nama'] ?? 'N/A' }}</td>
                            
                            <td>
                                {{-- [FIX SYNTAX] Pastikan tidak ada spasi non-standar --}}
                                <span @class([
                                    'badge',
                                    'text-bg-warning' => $visit->status == 'Direncanakan',
                                    'text-bg-primary' => $visit->status == 'Berlangsung',
                                    'text-bg-info' => $visit->status == 'Diproses',
                                    'text-bg-success' => $visit->status == 'Selesai',
                                    'text-bg-danger' => $visit->status == 'Gagal',
                                    'text-bg-secondary' => !in_array($visit->status, ['Direncanakan', 'Berlangsung', 'Diproses', 'Selesai', 'Gagal']),
                                ])>
                                    {{ $visit->status }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill schedule-badge">
                                    {{ \Carbon\Carbon::parse($visit->start_date)->format('j M') }} - {{ \Carbon\Carbon::parse($visit->end_date)->format('j M Y') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    
                                    @if($visit->status == 'Direncanakan' || $visit->status == 'Berlangsung')
                                        {{-- [FIX 3] Baca dari $visit->jemaat_data['nama'] --}}
                                        <button class="btn btn-sm btn-success" title="Input Laporan" data-bs-toggle="modal" data-bs-target="#laporanModal" data-visit-id="{{ $visit->id }}" data-oikos-nama="{{ $visit->jemaat_data['nama'] ?? $visit->oikos_name }}"><i class="bi bi-pencil-square"></i></button>
                                    
                                    @elseif ($visit->status == 'Diproses')
                                        <button class="btn btn-sm btn-info" title="Lihat Laporan" data-bs-toggle="modal" data-bs-target="#viewLaporanModal" data-visit-id="{{ $visit->id }}"><i class="bi bi-eye-fill"></i></button>
                                        
                                        @if (Auth::check() && is_array(Auth::user()->roles) && in_array('super_admin', Auth::user()->roles))
                                            <form action="{{ route('oikos.confirm', $visit->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengkonfirmasi laporan ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="Konfirmasi Laporan">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                    @elseif ($visit->status == 'Selesai')
                                        <button class="btn btn-sm btn-info" title="Lihat Laporan" data-bs-toggle="modal" data-bs-target="#viewLaporanModal" data-visit-id="{{ $visit->id }}"><i class="bi bi-eye-fill"></i></button>
                                    
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary disabled" title="Tidak ada aksi tersedia"><i class="bi bi-x-circle"></i></button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="empty-row">
                            <td colspan="6" class="text-center text-secondary py-4">
                                Belum ada jadwal kunjungan OIKOS yang dibuat.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- Modal Input Laporan --}}
    <div class="modal fade" id="laporanModal" tabindex="-1" aria-labelledby="laporanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h1 class="modal-title fs-5 fw-bold" id="laporanModalLabel">Input Laporan Kunjungan</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <form id="laporanForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h3 class="h5 fw-semibold">Bagian Realisasi</h3>
                        <div class="mb-3">
                            <label for="realisasi_date" class="form-label">Realisasi Kunjungan:</label>
                            <input type="date" id="realisasi_date" name="realisasi_date" class="form-control" required>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_doa_5_jari" name="is_doa_5_jari" value="1">
                            <label class="form-check-label" for="is_doa_5_jari">Doa 5 Jari</label>
                        </div>
                        <div id="dateInputContainerDLJ" class="d-none mb-3">
                            <label for="realisasi_doa_5_jari_date" class="form-label">Tanggal Realisasi Doa 5 Jari</label>
                            <div class="input-group">
                                <input type="date" id="realisasi_doa_5_jari_date" name="realisasi_doa_5_jari_date" class="form-control">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            </div>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_doa_syafaat" name="is_doa_syafaat" value="1">
                            <label class="form-check-label" for="is_doa_syafaat">Doa Syafaat</label>
                        </div>
                        <div id="dateInputContainerDS" class="d-none mb-3">
                            <label for="realisasi_doa_syafaat_date" class="form-label">Tanggal Realisasi Doa Syafaat</label>
                            <div class="input-group">
                                <input type="date" id="realisasi_doa_syafaat_date" name="realisasi_doa_syafaat_date" class="form-control">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            </div>
                        </div>

                        <h3 class="h5 fw-semibold mt-4 pt-3 border-top">Dua Tindakan</h3>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="tindakanCintaOikos" role="switch">
                            <label class="form-check-label" for="tindakanCintaOikos">1. Tindakan Cinta OIKOS</label>
                        </div>
                        <div id="hiddenInputContainerTCO" class="d-none mb-3 p-3 border rounded bg-body">
                            <h4 class="h6">Bukti Tindakan Cinta OIKOS</h4>
                            <div class="mb-3">
                                <label for="tindakan_cinta_desc" class="form-label">Deskripsi Teks:</label>
                                <textarea id="tindakan_cinta_desc" name="tindakan_cinta_desc" rows="3" class="form-control"></textarea>
                            </div>
                            <div>
                                <label for="tindakan_cinta_photo_path" class="form-label">Foto:</label>
                                <input type="file" id="tindakan_cinta_photo_path" name="tindakan_cinta_photo_path" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            </div>
                        </div>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="tindakanPedulihOikos" role="switch">
                            <label class="form-check-label" for="tindakanPedulihOikos">2. Tindakan Peduli OIKOS</label>
                        </div>
                        <div id="hiddenInputContainerTPO" class="d-none mb-3 p-3 border rounded bg-body">
                            <h4 class="h6">Bukti Tindakan Peduli OIKOS</h4>
                            <div class="mb-3">
                                <label for="tindakan_peduli_desc" class="form-label">Deskripsi Teks:</label>
                                <textarea id="tindakan_peduli_desc" name="tindakan_peduli_desc" rows="3" class="form-control"></textarea>
                            </div>
                        </div>

                        <h3 class="h5 fw-semibold mt-4 pt-3 border-top">Respon Terhadap Injil</h3>
                        <div class="form-check"><input type="radio" id="option1" name="respon_injil" value="bermusuhan" class="form-check-input"><label for="option1" class="form-check-label">a. Sikap Bermusuhan</label></div>
                        <div class="form-check"><input type="radio" id="option2" name="respon_injil" value="netral" class="form-check-input"><label for="option2" class="form-check-label">b. Netral</label></div>
                        <div class="form-check"><input type="radio" id="option3" name="respon_injil" value="tertarik" class="form-check-input"><label for="option3" class="form-check-label">c. Tertarik</label></div>
                        <div class="form-check"><input type="radio" id="option4" name="respon_injil" value="tertarik_murni" class="form-check-input"><label for="option4" class="form-check-label">d. Tertarik Murni</label></div>
                        <div class="form-check mb-3"><input type="radio" id="option5" name="respon_injil" value="keputusan" class="form-check-input"><label for="option5" class="form-check-label">e. Keputusan</label></div>

                        <h3 class="h5 fw-semibold mt-4 pt-3 border-top">Bagian Teks/Dokumentasi</h3>
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan:</label>
                            <textarea id="catatan" name="catatan" rows="4" class="form-control"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" form="laporanForm" class="btn btn-primary">Kirim Laporan</button></div>
            </div>
        </div>
    </div>

    {{-- Modal Lihat Laporan --}}
    <div class="modal fade" id="viewLaporanModal" tabindex="-1" aria-labelledby="viewLaporanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 fw-bold" id="viewLaporanModalLabel">Detail Laporan Kunjungan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center text-secondary">Memuat Laporan...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIKA MODAL LIHAT LAPORAN ---
    const viewLaporanModalEl = document.getElementById('viewLaporanModal');
    if (viewLaporanModalEl) {
        viewLaporanModalEl.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const visitId = button.getAttribute('data-visit-id');
            const modalTitle = viewLaporanModalEl.querySelector('#viewLaporanModalLabel');
            const modalBody = viewLaporanModalEl.querySelector('.modal-body');
            
            modalTitle.textContent = `Laporan Kunjungan...`;
            modalBody.innerHTML = '<p class="text-center text-secondary">Memuat Laporan...</p>';

            try {
                let url = "{{ route('oikos.report.show', ':id') }}";
                url = url.replace(':id', visitId);

                const response = await fetch(url);
                if (!response.ok) throw new Error('Gagal memuat data laporan.');
                
                const data = await response.json();

                // [FIX 4] Baca dari object data.jemaat_data (dari API)
                const oikosNama = data.jemaat_data ? data.jemaat_data.nama : data.oikos_name;
                modalTitle.textContent = `Laporan Kunjungan: ${oikosNama}`;

                const formatDate = (dateString) => {
                    if (!dateString) return '-';
                    const date = new Date(dateString.split(' ')[0] + 'T00:00:00');
                    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                };

                const photoHtml = data.tindakan_cinta_photo_path 
                    ? `<a href="/storage/${data.tindakan_cinta_photo_path}" target="_blank">Lihat Foto</a>`
                    : 'Tidak ada foto';

                // [FIX 5] Baca dari object data.pelayan_data (dari API)
                modalBody.innerHTML = `
                    <dl class="row report-detail-group">
                        <dt class="col-sm-4">Nama OIKOS</dt>
                        <dd class="col-sm-8">${oikosNama}</dd>
                        <dt class="col-sm-4">Pelayan Bertugas</dt>
                        <dd class="col-sm-8">${data.pelayan_data ? data.pelayan_data.nama : 'N/A'}</dd>
                        <dt class="col-sm-4">Realisasi Kunjungan</dt>
                        <dd class="col-sm-8">${formatDate(data.realisasi_date)}</dd>
                    </dl>
                    <hr>
                    <dl class="row report-detail-group">
                        <dt class="col-sm-4">Doa 5 Jari</dt>
                        <dd class="col-sm-8">${data.is_doa_5_jari ? `Dilakukan (${formatDate(data.realisasi_doa_5_jari_date)})` : 'Tidak'}</dd>
                        <dt class="col-sm-4">Doa Syafaat</dt>
                        <dd class="col-sm-8">${data.is_doa_syafaat ? `Dilakukan (${formatDate(data.realisasi_doa_syafaat_date)})` : 'Tidak'}</dd>
                    </dl>
                    <hr>
                    <dl class="row report-detail-group">
                        <dt class="col-sm-4">Tindakan Cinta OIKOS</dt>
                        <dd class="col-sm-8">${data.tindakan_cinta_desc || '-'}</dd>
                        <dt class="col-sm-4">Tindakan Peduli OIKOS</dt>
                        <dd class="col-sm-8">${data.tindakan_peduli_desc || '-'}</dd>
                    </dl>
                    <hr>
                    <dl class="row report-detail-group">
                        <dt class="col-sm-4">Respon Terhadap Injil</dt>
                        <dd class="col-sm-8">${data.respon_injil || '-'}</dd>
                        <dt class="col-sm-4">Catatan</dt>
                        <dd class="col-sm-8">${data.catatan || '-'}</dd>
                        <dt class="col-sm-4">Dokumentasi</dt>
                        <dd class="col-sm-8">${photoHtml}</dd>
                    </dl>
                `;

            } catch (error) {
                modalBody.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
                console.error('Gagal mengambil detail laporan:', error);
            }
        });
    }

    // --- LOGIKA MODAL INPUT LAPORAN ---
    const laporanModalEl = document.getElementById('laporanModal');
    if (laporanModalEl) {
        const laporanForm = document.getElementById('laporanForm');
        laporanModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const oikosNama = button.getAttribute('data-oikos-nama');
            const visitId = button.getAttribute('data-visit-id');
            
            const modalTitle = laporanModalEl.querySelector('#laporanModalLabel');
            modalTitle.textContent = `Laporan Kunjungan: ${oikosNama}`;

            let url = "{{ route('oikos.report.store', ':id') }}";
            url = url.replace(':id', visitId);
            laporanForm.action = url;
        });

        laporanModalEl.addEventListener('hidden.bs.modal', function () {
            laporanForm.reset();
            document.getElementById('dateInputContainerDLJ').classList.add('d-none');
            document.getElementById('dateInputContainerDS').classList.add('d-none');
            document.getElementById('hiddenInputContainerTCO').classList.add('d-none');
            document.getElementById('hiddenInputContainerTPO').classList.add('d-none');
        });
    }

    // --- SCRIPT INTERAKTIF FORM --- 
    const setupToggle = (checkboxId, containerId) => {
        const checkbox = document.getElementById(checkboxId);
        const container = document.getElementById(containerId);
        if (checkbox && container) {
            checkbox.addEventListener('change', function() {
                container.classList.toggle('d-none', !this.checked);
            });
        }
    };

    setupToggle('is_doa_5_jari', 'dateInputContainerDLJ');
    setupToggle('is_doa_syafaat', 'dateInputContainerDS');
    setupToggle('tindakanCintaOikos', 'hiddenInputContainerTCO');
    setupToggle('tindakanPedulihOikos', 'hiddenInputContainerTPO');
    
    // --- SCRIPT FILTER ---
    const filterButtons = document.querySelectorAll('.filter-nav-btn');
    const slider = document.querySelector('.filter-slider');
    const tableRows = document.querySelectorAll('.table tbody tr');
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

            tableRows.forEach(row => {
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
});
</script>
@endpush