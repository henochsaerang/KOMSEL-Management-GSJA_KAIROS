<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'KOMSEL KAIROS' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <script>
        (() => {
            const getStoredTheme = () => localStorage.getItem('theme');
            const setStoredTheme = theme => localStorage.setItem('theme', theme);
            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme();
                if (storedTheme) { return storedTheme; }
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };
            const setTheme = theme => {
                let effectiveTheme = theme;
                if (theme === 'auto') {
                    effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
            };
            setTheme(getPreferredTheme());
        })();
    </script>
    
    <style>
        /* === VARIIABEL TEMA (PENTING UNTUK DARK MODE) === */
        :root {
            --primary-color: #4f46e5; 
            --primary-hover: #4338ca;
            --primary-bg-subtle: #eef2ff;
            --bs-body-bg: #f3f4f6; 
            --element-bg: #ffffff; /* Background Card/Navbar Putih */
            --element-bg-subtle: #f9fafb; /* Background abu-abu sangat muda */
            --bs-body-color: #1f2937; /* Teks Utama Hitam */
            --text-secondary: #6b7280; 
            --border-color: #e5e7eb; 
            --hover-bg: #f9fafb; 
            --input-bg: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --sidebar-width: 280px;
        }

        [data-bs-theme="dark"] {
            --primary-color: #6366f1; 
            --primary-hover: #818cf8;
            --primary-bg-subtle: rgba(99, 102, 241, 0.15);
            --bs-body-bg: #111827; /* Background Body Gelap */
            --element-bg: #1f2937; /* Background Card/Navbar Gelap */
            --element-bg-subtle: #374151; /* Background elemen sekunder gelap */
            --bs-body-color: #f9fafb; /* Teks Utama Putih */
            --text-secondary: #9ca3af; 
            --border-color: #374151; 
            --hover-bg: #374151; 
            --input-bg: #1f2937;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
            --bs-dropdown-bg: #1f2937;
            --bs-dropdown-border-color: #374151;
            --bs-dropdown-link-color: #e5e7eb;
            --bs-dropdown-link-hover-bg: #374151;
        }
        
        /* === GLOBAL RESET & TYPOGRAPHY === */
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bs-body-bg); 
            color: var(--bs-body-color);
            height: 100vh;
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [data-bs-theme="dark"] ::-webkit-scrollbar-thumb { background: #4b5563; }

        /* === LAYOUT STRUCTURE === */
        @media (min-width: 992px) {
            body { flex-direction: row; }
            
            .sidebar-desktop { 
                width: var(--sidebar-width); 
                flex-shrink: 0; 
                height: 100%; 
                overflow-y: auto; 
                border-right: 1px solid var(--border-color); 
                padding: 1.5rem 1rem;
                background-color: var(--element-bg);
                display: flex;
                flex-direction: column;
                z-index: 1030;
                transition: background-color 0.3s ease, border-color 0.3s ease;
            }

            .main-content-wrapper { 
                flex-grow: 1; 
                height: 100%; 
                overflow-y: auto; 
                overflow-x: hidden; 
                display: flex;
                flex-direction: column;
                position: relative;
            }
        }

        @media (max-width: 991.98px) {
            .main-content-wrapper {
                flex-grow: 1;
                height: 100%;
                overflow-y: auto;
                width: 100%;
            }
        }

        /* === NAVBAR (GLASSMORPHISM) === */
        .top-navbar { 
            position: sticky; 
            top: 0; 
            z-index: 1020; 
            background-color: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color); 
            transition: all 0.3s ease;
        }
        [data-bs-theme="dark"] .top-navbar {
            background-color: rgba(17, 24, 39, 0.85); /* Dark glass */
        }

        /* === SIDEBAR STYLING === */
        .sidebar-brand { 
            font-size: 1.25rem; 
            font-weight: 800; 
            padding: 0.5rem 1rem 2rem; 
            color: var(--bs-body-color);
            letter-spacing: -0.025em;
        }
        .sidebar-logo { height: 32px; margin-right: 12px; }

        .nav-link { 
            color: var(--text-secondary); 
            font-size: 0.95rem; 
            font-weight: 500; 
            padding: 0.75rem 1rem; 
            margin-bottom: 4px; 
            border-radius: 0.75rem; 
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .nav-link i { 
            font-size: 1.25rem; 
            margin-right: 12px; 
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }

        .nav-link:hover { 
            color: var(--primary-color); 
            background-color: var(--hover-bg); 
        }
        .nav-link:hover i { color: var(--primary-color); }

        .nav-link.active { 
            color: var(--primary-color); 
            background-color: var(--primary-bg-subtle); 
            font-weight: 600;
        }
        .nav-link.active i { color: var(--primary-color); }

        /* Mobile Sidebar adjustments */
        .sidebar-mobile .offcanvas-header { border-bottom: 1px solid var(--border-color); }
        .sidebar-mobile .nav-link { font-size: 1rem; padding: 1rem; }

        /* === COMPONENTS === */
        .avatar-initials { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 600; 
            color: #fff; 
            background: linear-gradient(135deg, var(--primary-color), #4338ca); 
            width: 40px; 
            height: 40px; 
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }

        /* Theme Switcher */
        .theme-switcher-container {
            position: relative;
            display: inline-flex;
            background-color: var(--hover-bg);
            border-radius: 9999px;
            padding: 4px;
            border: 1px solid var(--border-color);
        }
        .theme-btn {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            z-index: 2;
            transition: color 0.3s ease;
        }
        .theme-btn:hover { color: var(--bs-body-color); }
        .theme-btn.active { color: #fff; }
        
        .theme-slider {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 32px;
            height: 32px;
            background-color: var(--primary-color);
            border-radius: 50%;
            z-index: 1;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Dropdown Polish */
        .dropdown-menu {
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            border-radius: 1rem;
            padding: 0.5rem;
            background-color: var(--element-bg); /* FIXED: Use variable */
        }
        .dropdown-item {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: var(--bs-body-color); /* FIXED: Use variable */
        }
        .dropdown-item:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
        }
        .dropdown-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: var(--text-secondary);
        }
        
        /* Form Controls Global Fix */
        .form-control, .form-select {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--bs-body-color);
        }
        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }
    </style>
    @stack('styles')
</head>

<body>
    @php
        $user = Auth::user();
        $userRoles = $user ? ($user->roles ?? []) : [];
        
        // Cek apakah user memiliki hak akses lebih (Leader/Admin/Koordinator)
        // Jemaat biasa tidak akan masuk ke kondisi ini
        $isAdminOrLeader = $user && (
            in_array('super_admin', $userRoles) || 
            in_array('Leaders', $userRoles) || 
            $user->is_coordinator
        );
    @endphp

    {{-- DESKTOP SIDEBAR --}}
    <nav class="sidebar-desktop d-none d-lg-flex">
        <div class="sidebar-brand d-flex align-items-center">
            <img src="{{ asset('image/logo/logo_gsja_kairos.png') }}" alt="Logo" class="sidebar-logo">
            <span>KOMSEL</span>
        </div>
        
        <ul class="nav flex-column mt-2 flex-grow-1">
            {{-- Dashboard (Semua User) --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid-fill"></i>Dashboard
                </a>
            </li>

            {{-- Menu Khusus Leader/Admin --}}
            @if($isAdminOrLeader)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('oikos') || request()->routeIs('formInput') ? 'active' : '' }}" href="{{ route('oikos') }}">
                        <i class="bi bi-heart-fill"></i>OIKOS
                    </a>
                </li>
            @endif

            {{-- Daftar Anggota & Jadwal (Semua User bisa lihat) --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('daftarKomsel') || request()->routeIs('admin.komselAktif') ? 'active' : '' }}" href="{{ route('daftarKomsel') }}">
                    <i class="bi bi-people-fill"></i>Daftar Anggota
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('jadwal') ? 'active' : '' }}" href="{{ route('jadwal') }}">
                    <i class="bi bi-calendar-event-fill"></i>Jadwal Ibadah
                </a>
            </li>

            {{-- Menu Khusus Leader/Admin --}}
            @if($isAdminOrLeader)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('kunjungan*') ? 'active' : '' }}" href="{{ Route::has('kunjungan') ? route('kunjungan') : '#' }}">
                        <i class="bi bi-person-walking"></i>Kunjungan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('statistik') ? 'active' : '' }}" href="{{ route('statistik') }}">
                        <i class="bi bi-bar-chart-fill"></i>Statistik
                    </a>
                </li>
            @endif
        </ul>

        {{-- Footer Sidebar --}}
        <div class="mt-auto pt-4 border-top border-color text-center">
            <small class="text-muted" style="font-size: 0.75rem;">© {{ date('Y') }} GSJA Kairos</small>
        </div>
    </nav>

    {{-- MOBILE SIDEBAR (OFFCANVAS) --}}
    <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title sidebar-brand d-flex align-items-center p-0 mb-0">
                <img src="{{ asset('image/logo/logo_gsja_kairos.png') }}" alt="Logo" class="sidebar-logo">
                <span>KOMSEL</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-fill"></i>Dashboard</a></li>
                
                @if($isAdminOrLeader)
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('oikos') || request()->routeIs('formInput') ? 'active' : '' }}" href="{{ route('oikos') }}"><i class="bi bi-heart-fill"></i>OIKOS</a></li>
                @endif

                <li class="nav-item"><a class="nav-link {{ request()->routeIs('daftarKomsel') || request()->routeIs('admin.komselAktif') ? 'active' : '' }}" href="{{ route('daftarKomsel') }}"><i class="bi bi-people-fill"></i>Daftar Anggota</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('jadwal') ? 'active' : '' }}" href="{{ route('jadwal') }}"><i class="bi bi-calendar-event-fill"></i>Jadwal Ibadah</a></li>
                
                @if($isAdminOrLeader)
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('kunjungan*') ? 'active' : '' }}" href="{{ Route::has('kunjungan') ? route('kunjungan') : '#' }}"><i class="bi bi-person-walking"></i>Kunjungan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('statistik') ? 'active' : '' }}" href="{{ route('statistik') }}"><i class="bi bi-bar-chart-fill"></i>Statistik</a></li>
                @endif
            </ul>
        </div>
    </div>

    {{-- MAIN WRAPPER --}}
    <div class="main-content-wrapper">
        <header class="top-navbar px-4 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <button class="btn d-lg-none p-0 border-0 text-body" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list fs-2"></i>
                </button>
                
                <h5 class="d-none d-lg-block mb-0 fw-bold text-body">{{ $title ?? 'Dashboard' }}</h5>
                <h5 class="d-lg-none mb-0 fw-bold text-body">{{ $title ?? 'KOMSEL' }}</h5>
                
                <div class="d-flex align-items-center gap-3">
                    {{-- Profile Dropdown --}}
                    <div class="dropdown" id="settingsDropdown">
                        <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="text-end d-none d-md-block" style="line-height: 1.2;">
                                <small class="d-block fw-bold text-body">{{ Auth::user()->name }}</small>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    @php
                                        $roles = session('user_roles', []);
                                        $displayRole = !empty($roles) ? ucfirst($roles[0]) : 'Jemaat';
                                    @endphp
                                </small>
                            </div>
                            <div class="avatar-initials rounded-circle">{{ Auth::user()->initials }}</div>
                        </button>
                        
                        <ul class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0" style="min-width: 240px;">
                            <li class="px-3 py-2 d-md-none">
                                <strong class="d-block text-body">{{ Auth::user()->name }}</strong>
                            </li>
                            <li class="d-md-none"><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">Tampilan</h6></li>
                            <li class="px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-body">Tema:</span>
                                    <div class="theme-switcher-container">
                                        <div class="theme-slider"></div>
                                        <button type="button" class="theme-btn" data-bs-theme-value="light" title="Terang"><i class="bi bi-sun-fill small"></i></button>
                                        <button type="button" class="theme-btn" data-bs-theme-value="dark" title="Gelap"><i class="bi bi-moon-stars-fill small"></i></button>
                                        <button type="button" class="theme-btn" data-bs-theme-value="auto" title="Sistem"><i class="bi bi-circle-half small"></i></button>
                                    </div>
                                </div>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger d-flex align-items-center py-2" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-main').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar Aplikasi
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="container-fluid p-4">
            @yield('konten')
        </main>
    </div>

    <form id="logout-form-main" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === THEME LOGIC ===
            const getStoredTheme = () => localStorage.getItem('theme');
            const setStoredTheme = theme => localStorage.setItem('theme', theme);
            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme();
                if (storedTheme) return storedTheme;
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            };
            const setTheme = theme => {
                let effectiveTheme = theme;
                if (theme === 'auto') {
                    effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
            };

            const themeSlider = document.querySelector('.theme-slider');
            const themeButtons = document.querySelectorAll('.theme-btn');
            const settingsDropdown = document.getElementById('settingsDropdown');

            function moveThemeSlider(targetButton) {
                if (!targetButton || !themeSlider) return;
                const btnRect = targetButton.getBoundingClientRect();
                const containerRect = targetButton.parentElement.getBoundingClientRect();
                themeSlider.style.transform = `translateX(${btnRect.left - containerRect.left}px)`;
            }

            function initializeThemeUI() {
                const currentTheme = getPreferredTheme();
                // Reset active state
                themeButtons.forEach(btn => btn.classList.remove('active'));
                
                // Find button matching current theme (or auto if stored is auto)
                const storedRaw = getStoredTheme(); 
                const targetValue = storedRaw || 'auto'; 
                
                const activeBtn = document.querySelector(`.theme-btn[data-bs-theme-value="${targetValue}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active');
                    // Defer slider move until dropdown visible or immediately if visible
                    // Since it's in dropdown, we rely on dropdown show event mostly
                }
            }

            themeButtons.forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent dropdown close on click inside
                    const theme = toggle.getAttribute('data-bs-theme-value');
                    setStoredTheme(theme);
                    setTheme(theme);
                    
                    themeButtons.forEach(b => b.classList.remove('active'));
                    toggle.classList.add('active');
                    moveThemeSlider(toggle);
                });
            });

            if (settingsDropdown) {
                settingsDropdown.addEventListener('shown.bs.dropdown', () => {
                    const activeBtn = document.querySelector('.theme-btn.active');
                    if(activeBtn) moveThemeSlider(activeBtn);
                });
            }

            // Initial Setup
            setTheme(getPreferredTheme());
            initializeThemeUI();
            
            // System Theme Change Listener
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (getStoredTheme() !== 'light' && getStoredTheme() !== 'dark') {
                    setTheme('auto');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>