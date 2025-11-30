@extends('layouts.app')

@section('title', 'Formulir Jadwal OIKOS')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
.ts-control {
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    min-height: calc(1.5em + 0.75rem + 2px);
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

                         <div class="mb-3">
                             <label for="pelayan" class="form-label">Pelayan (Yang Mengunjungi)</label>
                             <select class="form-select" id="pelayan" name="pelayan" required>
                                 <option value="" disabled selected>Pilih Pelayan..</option>
                                 @foreach ($pelayans as $pelayan)
                                     <option value="{{ $pelayan['id'] }}">{{ $pelayan['nama'] }}</option>
                                 @endforeach
                             </select>
                         </div>
                         
                         <div class="row">
                             <div class="col-md-6 mb-3">
                                 <label for="tanggalDari" class="form-label">Jadwal dari Tanggal:</label>
                                 <input type="date" id="tanggalDari" name="tanggalDari" class="form-control" required>
                             </div>
                             <div class="col-md-6 mb-3">
                                 <label for="tanggalSampai" class="form-label">s/d Tanggal:</label>
                                 <input type="date" id="tanggalSampai" name="tanggalSampai" class="form-control" required>
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

    const tomSelectPelayan = new TomSelect("#pelayan", {
         create: false,
         sortField: { field: "text", direction: "asc" },
        placeholder: "Pilih Pelayan.."
    });

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
});
</script>
@endpush