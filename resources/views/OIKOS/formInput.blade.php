@extends('layouts.app')

@section('title', 'Formulir Jadwal OIKOS')

{{-- [BARU] Tambahkan stack styles untuk CSS Tom Select --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
/* * Memperbaiki tampilan TomSelect yang sedikit 'off' 
 * saat di dalam container yang awalnya 'd-none'
 */
.ts-control {
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    min-height: calc(1.5em + 0.75rem + 2px);
}
</style>
@endpush


@section('konten')
    <div class="row justify-content-center">
        <div class="col-lg-8">
             <div class="card">
                 <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h1 class="h3 fw-bold mb-0">Atur Jadwal Kunjungan OIKOS Baru</h1>
                 </div>
                 <div class="card-body p-4">
                     
                     <form action="{{ route('oikos.store') }}" method="POST">
                         @csrf
                         <input type="hidden" name="input_type" id="inputType" value="manual">

                         {{-- OPSI 1 (DEFAULT): INPUT MANUAL UNTUK OIKOS BARU --}}
                         <div id="inputManualContainer">
                             <label for="Anggota_tidakTerdaftar" class="form-label fw-bold">Nama OIKOS (Baru / Tidak Terdaftar):</label>
                             <input type="text" id="Anggota_tidakTerdaftar" name="Anggota_tidakTerdaftar" class="form-control" placeholder="Contoh: Keluarga Bapak Budi">
                         </div>

                         {{-- OPSI 2 (TERSEMBUNYI): DROPDOWN UNTUK ANGGOTA TERDAFTAR --}}
                         <div id="inputTerdaftarContainer" class="d-none">
                             <label for="Nama_Anggota" class="form-label fw-bold">Pilih dari Anggota Terdaftar:</label>
                             
                             {{-- ID "Nama_Anggota" akan digunakan oleh Tom Select --}}
                             <select name="Nama_Anggota" id="Nama_Anggota" class="form-select">
                                 <option selected disabled value="">Pilih Nama Anggota</option>
                                 @foreach ($users as $user)
                                     <option value="{{ $user['id'] }}">{{ $user['nama'] }}</option>
                                 @endforeach
                             </select> 
                         </div>

                         {{-- TOGGLE SWITCH UNTUK MEMILIH ANTARA DUA OPSI DI ATAS --}}
                         <div class="form-check form-switch my-3">
                             <input class="form-check-input" type="checkbox" role="switch" id="toggleInputType">
                             <label class="form-check-label" for="toggleInputType">Pilih dari Anggota Terdaftar</label>
                         </div>
                         
                         <hr>

                         <div class="mb-3">
                             <label for="pelayan" class="form-label">Pelayan (Yang Mengunjungi)</label>
                             
                             {{-- [FIX] ID 'pelayan' ditambahkan untuk konsistensi --}}
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
                             <button type="submit" class="btn btn-primary">
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
{{-- [BARU] 1. Load library Tom Select --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- 2. Inisialisasi Tom Select dan gabungkan dengan script toggle Anda --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleSwitch = document.getElementById('toggleInputType');
    const manualContainer = document.getElementById('inputManualContainer');
    const terdaftarContainer = document.getElementById('inputTerdaftarContainer');
    const inputTypeHidden = document.getElementById('inputType');

    // [BARU] Inisialisasi Tom Select untuk dropdown Anggota
    // Kita inisialisasi di luar toggle agar siap pakai
    const tomSelectAnggota = new TomSelect("#Nama_Anggota", {
        create: false, // Tidak izinkan user menambah nama baru
        sortField: {
            field: "text",
            direction: "asc"
        },
        placeholder: "Ketik untuk mencari nama..."
    });

    // [BARU] Inisialisasi Tom Select untuk dropdown Pelayan (Opsional, tapi konsisten)
    const tomSelectPelayan = new TomSelect("#pelayan", {
         create: false,
         sortField: {
            field: "text",
            direction: "asc"
        },
        placeholder: "Pilih Pelayan.."
    });


    // Script toggle Anda yang sudah ada
    if (toggleSwitch && manualContainer && terdaftarContainer && inputTypeHidden) {
        toggleSwitch.addEventListener('change', function() {
            if (this.checked) {
                // Tampilkan dropdown, sembunyikan input manual
                manualContainer.classList.add('d-none');
                terdaftarContainer.classList.remove('d-none');
                inputTypeHidden.value = 'terdaftar';
                
                // [BARU] Hapus nilai dari input manual
                document.getElementById('Anggota_tidakTerdaftar').value = '';
                // [BARU] Fokus ke Tom Select
                tomSelectAnggota.focus();

            } else {
                // Tampilkan input manual, sembunyikan dropdown
                manualContainer.classList.remove('d-none');
                terdaftarContainer.classList.add('d-none');
                inputTypeHidden.value = 'manual';
                
                // [BARU] Hapus nilai dari Tom Select
                tomSelectAnggota.clear();
            }
        });
    }
});
</script>
@endpush