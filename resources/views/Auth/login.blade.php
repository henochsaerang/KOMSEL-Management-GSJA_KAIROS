<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KOMSEL</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Custom CSS (dari layouts/app.blade.php) -->
    <style>
        :root {
            --primary-color: #4f46e5; --bs-body-bg: #f8f9fa; --element-bg: #ffffff; --bs-body-color: #1f2937; --text-secondary: #6b7280; --border-color: #e5e7eb; --hover-bg: #f3f4f6; --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        [data-bs-theme="dark"] {
            --primary-color: #6366f1; --bs-body-bg: #111827; --element-bg: #1f2937; --bs-body-color: #f9fafb; --text-secondary: #9ca3af; --border-color: #374151; --hover-bg: #374151; --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2); --bs-offcanvas-bg: var(--element-bg); --bs-offcanvas-border-color: var(--border-color); --bs-btn-close-color: #fff; --bs-btn-outline-secondary-color: #adb5bd; --bs-btn-outline-secondary-border-color: #6c757d; --bs-btn-outline-secondary-hover-bg: #6c757d; --bs-btn-outline-secondary-hover-color: #fff; --bs-btn-outline-secondary-active-bg: #5c636a; --bs-btn-outline-secondary-active-color: #fff;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bs-body-bg); color: var(--bs-body-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { border: 1px solid var(--border-color); box-shadow: var(--shadow); border-radius: 1rem; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(var(--primary-color-rgb), .25); }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--primary-color); border-color: var(--primary-color); opacity: 0.9; }
        .text-primary { color: var(--primary-color) !important; }
        .link-primary { color: var(--primary-color); }
        .link-primary:hover { color: var(--primary-color); opacity: 0.8; }
        .logo-login { max-height: 60px; }
    </style>

    <!-- Style khusus untuk Theme Switcher -->
    <style>
        .theme-switcher-container {
            position: relative;
            display: inline-flex;
            background-color: var(--hover-bg);
            border-radius: 0.85rem;
            padding: 5px;
        }
        .theme-btn {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 500;
            padding: 6px 16px;
            cursor: pointer;
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }
        .theme-btn.active {
            color: #fff;
        }
        .theme-slider {
            position: absolute;
            top: 5px;
            left: 5px;
            height: calc(100% - 10px);
            background-color: var(--primary-color);
            border-radius: 0.75rem;
            z-index: 0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
    </style>
</head>

<body>
    <!-- === KONTENER THEME SWITCHER BARU === -->
    <div class="theme-switcher-container position-absolute top-0 end-0 mt-3 me-3">
        <div class="theme-slider"></div>
        <button type="button" class="theme-btn" data-bs-theme-value="light" title="Tema Terang"><i class="bi bi-sun-fill"></i></button>
        <button type="button" class="theme-btn" data-bs-theme-value="dark" title="Tema Gelap"><i class="bi bi-moon-stars-fill"></i></button>
        <button type="button" class="theme-btn" data-bs-theme-value="auto" title="Tema Sistem"><i class="bi bi-circle-half"></i></button>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <img src="{{ asset('image/logo/logo_gsja_kairos.png') }}" alt="KOMSEL Logo" class="logo-login mb-3">
                        <h4 class="fw-bold">Login ke Akun Anda</h4>
                        <p class="text-secondary">Masukkan email dan password Anda untuk masuk.</p>
                    </div>

                    @if(session('succes'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('succes') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('autentikasi') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Masuk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Fungsi-fungsi tema global
        const getStoredTheme = () => localStorage.getItem('theme');
        const setStoredTheme = theme => localStorage.setItem('theme', theme);
        const getPreferredTheme = () => {
            const storedTheme = getStoredTheme();
            if (storedTheme) { return storedTheme; }
            return 'light'; // Default ke terang jika tidak ada yang tersimpan
        };
        const setTheme = theme => {
            let effectiveTheme = theme;
            if (theme === 'auto') {
                effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
        };

        // Set tema halaman segera setelah skrip dimuat
        setTheme(getPreferredTheme());

        document.addEventListener('DOMContentLoaded', function() {
            const themeSlider = document.querySelector('.theme-slider');
            const themeButtons = document.querySelectorAll('.theme-btn');

            function moveThemeSlider(targetButton) {
                if (!targetButton) return;
                const targetRect = targetButton.getBoundingClientRect();
                const containerRect = targetButton.parentElement.getBoundingClientRect();
                themeSlider.style.width = `${targetRect.width}px`;
                themeSlider.style.transform = `translateX(${targetRect.left - containerRect.left}px)`;
            }

            function initializeThemeUI() {
                const currentTheme = getPreferredTheme();
                themeButtons.forEach(btn => btn.classList.remove('active'));
                const activeBtn = document.querySelector(`.theme-btn[data-bs-theme-value="${currentTheme}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active');
                }
                moveThemeSlider(activeBtn);
            }

            themeButtons.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const theme = toggle.getAttribute('data-bs-theme-value');
                    setStoredTheme(theme);
                    setTheme(theme);
                    initializeThemeUI();
                });
            });

            initializeThemeUI();
            
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (getStoredTheme() === 'auto') {
                    setTheme('auto');
                    initializeThemeUI();
                }
            });
        });
    </script>
</body>
</html>
