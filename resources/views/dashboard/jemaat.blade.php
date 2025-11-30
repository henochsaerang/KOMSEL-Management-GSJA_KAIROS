@extends('layouts.app')

@section('title', 'Dashboard Anggota')

@push('styles')
<style>
    /* Dark Mode Support */
    body { background-color: var(--bs-body-bg); }

    /* Welcome Banner */
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), #6366f1);
        border-radius: 1rem;
        color: white;
        padding: 2rem;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::after {
        content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    /* Komsel Card */
    .komsel-card {
        background-color: var(--element-bg);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s;
    }
    .komsel-header {
        background-color: var(--element-bg-subtle);
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        text-align: center;
    }
    .komsel-body { padding: 2rem 1.5rem; }
    
    .leader-avatar {
        width: 64px; height: 64px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: 700;
        margin: 0 auto 1rem;
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
    }

    /* Schedule Item */
    .schedule-box {
        background-color: var(--primary-bg-subtle);
        border: 1px solid rgba(79, 70, 229, 0.2);
        border-radius: 1rem;
        padding: 1.25rem;
        text-align: center;
        margin-top: 1.5rem;
    }
    
    /* Empty State */
    .empty-state-card {
        background-color: var(--element-bg);
        border: 1px dashed var(--border-color);
        border-radius: 1.5rem;
        padding: 3rem;
        text-align: center;
        color: var(--text-secondary);
    }
    .empty-icon { font-size: 3rem; margin-bottom: 1rem; color: #cbd5e1; }
    
    /* Text Utilities */
    .text-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; color: var(--text-secondary); }
    .text-val { font-size: 1.1rem; font-weight: 600; color: var(--bs-body-color); }
</style>
@endpush

@section('konten')
<div class="container-fluid px-0 pb-5" style="max-width: 800px;">

    {{-- WELCOME SECTION --}}
    <div class="welcome-banner mb-5">
        <div class="d-flex align-items-center position-relative z-1">
            <div class="me-3">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: 700;">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1">Shalom, {{ explode(' ', Auth::user()->name)[0] }}!</h3>
                <p class="mb-0 opacity-90 small">Selamat datang di aplikasi KOMSEL KAIROS.</p>
            </div>
        </div>
    </div>

    {{-- KOMSEL INFO SECTION --}}
    <h5 class="fw-bold mb-3 ps-1 text-val" style="font-size: 1.2rem;">Rumah Rohani Anda</h5>

    @if($myKomsel)
        <div class="komsel-card">
            <div class="komsel-header">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-3 fw-bold">ANGGOTA KOMSEL</span>
                <h2 class="fw-bold text-val mb-1" style="font-size: 1.75rem;">{{ $myKomsel['nama'] }}</h2>
                <p class="text-secondary small mb-0">Komunitas Sel yang bertumbuh bersama</p>
            </div>
            
            <div class="komsel-body">
                {{-- LEADER INFO --}}
                <div class="text-center mb-4">
                    <div class="leader-avatar">
                        {{ substr($myLeader['nama'] ?? 'L', 0, 1) }}
                    </div>
                    <div class="text-label mb-1">Digembalakan Oleh</div>
                    <div class="text-val fs-5">{{ $myLeader['nama'] ?? 'Belum Ada Leader' }}</div>
                    @if(isset($myLeader['email']))
                        <div class="small text-secondary">{{ $myLeader['email'] }}</div>
                    @endif
                </div>

                <hr style="border-color: var(--border-color);">

                {{-- JADWAL --}}
                <div class="schedule-box">
                    @if($nextSchedule)
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-calendar-event me-2"></i>Jadwal Ibadah Berikutnya</h6>
                        <div class="d-flex justify-content-center gap-4 flex-wrap">
                            <div>
                                <div class="text-label">HARI</div>
                                <div class="text-val">{{ $nextSchedule->day_of_week }}</div>
                            </div>
                            <div>
                                <div class="text-label">JAM</div>
                                <div class="text-val">{{ \Carbon\Carbon::parse($nextSchedule->time)->format('H:i') }}</div>
                            </div>
                            <div>
                                <div class="text-label">LOKASI</div>
                                <div class="text-val">{{ $nextSchedule->location }}</div>
                            </div>
                        </div>
                        @if($nextSchedule->description)
                            <div class="mt-3 pt-3 border-top border-primary border-opacity-10 small text-secondary">
                                <em>"{{ $nextSchedule->description }}"</em>
                            </div>
                        @endif
                    @else
                        <div class="text-secondary">
                            <i class="bi bi-calendar-x fs-3 d-block mb-2 opacity-50"></i>
                            Belum ada jadwal ibadah terdekat yang dibuat.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- EMPTY STATE: BELUM PUNYA KOMSEL --}}
        <div class="empty-state-card">
            <i class="bi bi-people-fill empty-icon"></i>
            <h5 class="fw-bold text-val">Belum Tergabung dalam KOMSEL</h5>
            <p class="text-secondary small mb-4" style="max-width: 400px; margin: 0 auto;">
                Saat ini data Anda belum terhubung dengan Komunitas Sel manapun. Silakan hubungi Admin atau Leader untuk dimasukkan ke dalam kelompok.
            </p>
            <button class="btn btn-outline-primary rounded-pill px-4 disabled">
                Hubungi Admin
            </button>
        </div>
    @endif

</div>
@endsection