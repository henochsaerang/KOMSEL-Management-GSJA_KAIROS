@extends('layouts.app')

@section('title', 'Formulir Jadwal OIKOS')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
{{-- Flatpickr CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<style>
.ts-control {
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    min-height: calc(1.5em + 0.75rem + 2px);
}
.input-group-text {
    background-color: #fff;
    border-left: 0;
}
.form-control.datepicker {
    border-right: 0;
}
</style>
@endpush

@section('konten')
    <div class="row justify-content-center">
        <div class="col-lg-8">
             {{-- ALERT JIKA DILUAR JADWAL --}}
             @if(isset($isAllowedDay) && !$isAllowedDay)
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    <div>
                        <strong>Peringatan!</strong><br>
                        Pembuatan jadwal biasanya hanya hari <strong>Minggu - Selasa</strong>.<br>
                        Anda saat ini tidak dapat menyimpan jadwal kecuali memiliki <em>akses darurat</em> dari Admin.
                    </div>
                </div>
             @endif

             <div class="card">
                 <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h1 class="h3 fw-bold mb-0">Atur Jadwal Kunjungan OIKOS Baru</h1>
                 </div>
                 <div class="card-body p-4">
                     
                     <form action="{{ route('oikos.store') }}" method="POST">
                         @csrf
                         <input type="hidden" name="input_type" id="inputType" value="manual">

                         {{-- SECTION 1: TARGET OIKOS --}}
                         <div id="inputManualContainer">
                             <label for="Anggota_tidakTerdaftar" class="form-label fw-bold">Nama OIKOS (Baru / Tidak Terdaftar):</label>
                             <input type="text" id="Anggota_tidakTerdaftar" name="Anggota_tidakTerdaftar" class="form-control" placeholder="Contoh: Keluarga Bapak Budi">
                         </div>

                         <div id="inputTerdaftarContainer" class="d-none">
                             <label for="Nama_Anggota" class="form-label fw-bold">Pilih dari Anggota Terdaftar:</label>
                             <select name="Nama_Anggota" id="Nama_Anggota" class="form-select">
                                 <option selected disabled value="">Pilih Nama Anggota</option>
                                 @foreach ($users as $user)
                                     <option value="{{ $user['id'] }}">{{ $user['nama'] }}</option>
                                 @endforeach
                             </select> 
                         </div>

                         <div class="form-check form-switch my-3">
                             <input class="form-check-input" type="checkbox" role="switch" id="toggleInputType">
                             <label class="form-check-label" for="toggleInputType">Pilih dari Anggota Terdaftar</label>
                         </div>
                         
                         <hr>

                         {{-- SECTION 2: PELAYAN (OTOMATIS / MANUAL) --}}
                         <div class="mb-3">
                             <label class="form-label fw-bold">Pelayan (Yang Mengunjungi)</label>
                             
                             @if($isAdmin)
                                 {{-- Jika Admin: Boleh pilih siapa saja --}}
                                 <select class="form-select" id="pelayan" name="pelayan" required>
                                     <option value="" disabled selected>Pilih Pelayan..</option>
                                     @foreach ($pelayans as $pelayan)
                                         <option value="{{ $pelayan['id'] }}">{{ $pelayan['nama'] }}</option>
                                     @endforeach
                                 </select>
                                 <div class="form-text">Sebagai Admin, Anda bisa menugaskan pelayan lain.</div>
                             @else
                                 {{-- Jika Leader Biasa: Otomatis Diri Sendiri --}}
                                 <div class="input-group">
                                     <span class="input-group-text bg-light"><i class="bi bi-person-check-fill text-success"></i></span>
                                     <input type="text" class="form-control bg-light" value="{{ $currentUser->name }} (Anda)" readonly>
                                 </div>
                                 <div class="form-text text-muted">Pelayan otomatis diatur sebagai diri Anda sendiri.</div>
                             @endif
                         </div>
                         
                         <div class="row">
                             <div class="col-md-6 mb-3">
                                 <label for="tanggalDari" class="form-label">Perkiraan Mulai (Rabu - Sabtu):</label>
                                 <div class="input-group">
                                     <input type="text" id="tanggalDari" name="tanggalDari" class="form-control datepicker" placeholder="Pilih tanggal..." required>
                                     <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                 </div>
                             </div>
                             <div class="col-md-6 mb-3">
                                 <label for="tanggalSampai" class="form-label">Perkiraan Selesai (Rabu - Sabtu):</label>
                                 <div class="input-group">
                                     <input type="text" id="tanggalSampai" name="tanggalSampai" class="form-control datepicker" placeholder="Pilih tanggal..." required>
                                     <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                 </div>
                             </div>
                         </div>

                         <div class="mt-4 d-flex justify-content-end gap-2">
                             <a href="{{ route('oikos') }}" class="btn btn-secondary">Batal</a>
                             {{-- Disable tombol jika hari salah --}}
                             <button type="submit" class="btn btn-primary" {{ (isset($isAllowedDay) && !$isAllowedDay) ? 'disabled' : '' }}>
                                 <i class="bi bi-check-circle-fill"></i> Simpan Jadwal
                             </button>
                         </div>
                    </form>

                 </div>
             </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleSwitch = document.getElementById('toggleInputType');
    const manualContainer = document.getElementById('inputManualContainer');
    const terdaftarContainer = document.getElementById('inputTerdaftarContainer');
    const inputTypeHidden = document.getElementById('inputType');

    const tomSelectAnggota = new TomSelect("#Nama_Anggota", {
        create: false,
        sortField: { field: "text", direction: "asc" },
        placeholder: "Ketik untuk mencari nama..."
    });

    const pelayanSelect = document.getElementById('pelayan');
    if (pelayanSelect) {
        new TomSelect("#pelayan", {
             create: false,
             sortField: { field: "text", direction: "asc" },
            placeholder: "Pilih Pelayan.."
        });
    }

    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                manualContainer.classList.add('d-none');
                terdaftarContainer.classList.remove('d-none');
                inputTypeHidden.value = 'terdaftar';
                document.getElementById('Anggota_tidakTerdaftar').value = '';
                tomSelectAnggota.focus();
            } else {
                manualContainer.classList.remove('d-none');
                terdaftarContainer.classList.add('d-none');
                inputTypeHidden.value = 'manual';
                tomSelectAnggota.clear();
            }
        });
    }

    // === KONFIGURASI TANGGAL (RABU - SABTU MINGGU INI) ===
    // Aturan:
    // User mengakses form ini biasanya di hari Minggu - Selasa.
    // Tanggal yang boleh dipilih untuk kunjungan adalah Rabu - Sabtu di minggu yang sama.
    
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 (Minggu) - 6 (Sabtu)
    
    // Kita hitung hari Rabu di minggu ini
    // Minggu (0) -> Rabu (+3)
    // Senin (1) -> Rabu (+2)
    // Selasa (2) -> Rabu (+1)
    // Rabu (3) -> Rabu (0)
    
    // Rumus: diff = 3 (Rabu) - dayOfWeek. 
    // Jika hasilnya negatif (misal hari Kamis/Jumat), itu urusan lain, tapi asumsinya user akses di Min-Sel.
    
    const diffToWednesday = 3 - dayOfWeek; 
    
    const wednesdayThisWeek = new Date(today);
    wednesdayThisWeek.setDate(today.getDate() + diffToWednesday);

    // Hitung hari Sabtu (Rabu + 3 hari)
    const saturdayThisWeek = new Date(wednesdayThisWeek);
    saturdayThisWeek.setDate(wednesdayThisWeek.getDate() + 3);

    const fpDari = flatpickr("#tanggalDari", {
        locale: "id",
        dateFormat: "Y-m-d",
        // Kunci kalender hanya di antara Rabu s/d Sabtu minggu ini
        minDate: wednesdayThisWeek,
        maxDate: saturdayThisWeek,
        onChange: function(selectedDates, dateStr, instance) {
            // Update minDate untuk tanggal sampai agar tidak lebih kecil dari tanggal mulai
            fpSampai.set('minDate', dateStr);
            fpSampai.set('maxDate', saturdayThisWeek);
        }
    });

    const fpSampai = flatpickr("#tanggalSampai", {
        locale: "id",
        dateFormat: "Y-m-d",
        minDate: wednesdayThisWeek,
        maxDate: saturdayThisWeek
    });
});
</script>
@endpush