<?php

/**
 * Trang đăng nhập - ezManagement NCKH
 * views/sign-in.php
 *
 * REBUILT UI : Burgundy + Sand, card giữa trang, grid background
 * COMPLIANCE : Vercel Web Interface Guidelines + Senior FE/UX standards
 * Stack      : Tailwind CDN + Manrope + Material Symbols (đồng bộ app)
 * Logic PHP/JS: giữ nguyên 100%
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_alreadyLoggedIn = isset($_SESSION['idTK']) && (int)$_SESSION['idTK'] > 0;
if ($_alreadyLoggedIn) {
    header('Location: /dashboard');
    exit();
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/dashboard';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="/assets/img/favicon.png" />
    <title>Đăng nhập — ezManagement</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#7d1f2e',
                        'primary-hov': '#621828',
                        accent: '#9a6c2e',
                        sand: '#f5f0e8',
                        'sand-border': '#ede7da',
                    },
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif']
                    },
                    ringColor: {
                        primary: '#7d1f2e'
                    },
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            line-height: 1;
            user-select: none;
        }

        /* Grid nền — tile liên tục mọi kích thước */
        .grid-bg {
            background-color: #f5f0e8;
            background-image:
                linear-gradient(rgba(120, 100, 80, .13) 1px, transparent 1px),
                linear-gradient(90deg, rgba(120, 100, 80, .13) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Chỉ animate transform + opacity — không dùng transition:all */
        .btn-primary {
            background-color: #7d1f2e;
            transition: background-color 0.15s ease, transform 0.1s ease, opacity 0.15s ease;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #621828;
        }

        .btn-primary:active:not(:disabled) {
            transform: scale(0.99);
        }

        .btn-primary:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .btn-ghost {
            transition: opacity 0.15s ease;
            color: #9a6c2e;
        }

        .btn-ghost:hover:not(:disabled) {
            opacity: .72;
        }

        .btn-ghost:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        /* Respects prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin .8s linear infinite;
            display: inline-block;
        }

        /* Input border transition — chỉ border-color + box-shadow */
        .field-input {
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .field-input:hover {
            border-color: #c4b8a8;
        }

        .field-input:focus {
            outline: none;
            border-color: #7d1f2e;
            box-shadow: 0 0 0 3px rgba(125, 31, 46, .14);
        }

        /* Checkbox */
        .chk:checked {
            background-color: #7d1f2e;
            border-color: #7d1f2e;
        }

        .chk:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(125, 31, 46, .18);
        }

        /* Eye button — focus ring thay outline */
        .eye-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(125, 31, 46, .35);
            border-radius: 4px;
        }

        /* Guest link focus */
        .guest-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(154, 108, 46, .4);
            border-radius: 4px;
        }
    </style>
</head>

<body class="grid-bg min-h-screen flex flex-col items-center justify-center px-4 py-10">

    <!-- Card -->
    <div class="w-full max-w-md bg-white rounded-2xl border border-sand-border px-8 pt-8 pb-7"
        style="box-shadow: 0 1px 4px rgba(80,50,30,.07), 0 4px 24px rgba(80,50,30,.07);">
        <!-- Logo + tên -->
        <div class="flex flex-col items-center mb-7">
            <div class="w-14 h-14 rounded-full flex items-center justify-center mb-4 select-none"
                style="background-color:#7d1f2e;" role="img" aria-label="Logo ezManagement">
                <span class="text-white font-bold text-xl tracking-tight" aria-hidden="true">ez</span>
            </div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight mb-0.5">ezManagement</h1>
            <p class="text-[11px] font-semibold tracking-[.12em] text-slate-400 uppercase">Academic Portal</p>
        </div>

        <!-- Error box — aria-live để screen reader thông báo khi update async -->
        <div id="errorBox" role="alert" aria-live="polite" aria-atomic="true"
            class="hidden rounded-lg px-4 py-3 mb-5 bg-red-50 border border-red-200">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-red-500 shrink-0"
                    aria-hidden="true">error</span>
                <span id="errorMsg" class="text-sm text-red-700"></span>
            </div>
        </div>

        <!-- Tên đăng nhập -->
        <div class="mb-4">
            <label for="tenTK" class="block text-sm font-semibold text-slate-700 mb-1.5">
                Tên đăng nhập
            </label>
            <input id="tenTK" type="text" name="tenTK" autocomplete="username" placeholder="Nhập tên đăng nhập của bạn"
                autofocus
                class="field-input w-full px-4 py-3 text-sm text-slate-800 bg-white border border-slate-200 rounded-lg placeholder-slate-300" />
        </div>

        <!-- Mật khẩu -->
        <div class="mb-4">
            <label for="matKhau" class="block text-sm font-semibold text-slate-700 mb-1.5">
                Mật khẩu
            </label>
            <div class="relative">
                <input id="matKhau" type="password" name="matKhau" autocomplete="current-password"
                    placeholder="••••••••"
                    class="field-input w-full px-4 py-3 pr-11 text-sm text-slate-800 bg-white border border-slate-200 rounded-lg placeholder-slate-300" />
                <button type="button" id="eyeBtn"
                    class="eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 bg-transparent border-0 p-1 cursor-pointer"
                    aria-label="Hiện mật khẩu" aria-pressed="false" onclick="togglePassword()"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword();}">
                    <span id="eyeIcon" class="material-symbols-outlined text-[20px]"
                        aria-hidden="true">visibility</span>
                </button>
            </div>
        </div>

        <!-- Ghi nhớ + Quên mật khẩu -->
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 cursor-pointer select-none group">
                <input id="remember_me" type="checkbox" name="remember_me"
                    class="chk w-4 h-4 rounded border-slate-300 cursor-pointer" />
                <span class="text-sm text-slate-600 group-hover:text-slate-800 transition-colors duration-150">Ghi nhớ
                    tôi</span>
            </label>
            <a href="#"
                class="text-sm font-semibold hover:opacity-75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded transition-opacity duration-150"
                style="color:#9a6c2e;">
                Quên mật khẩu?
            </a>
        </div>

        <!-- Nút đăng nhập -->
        <button type="button" id="loginBtn"
            class="btn-primary w-full py-3 rounded-lg text-white font-semibold text-sm tracking-wide mb-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            onclick="doLogin()" onkeydown="if(event.key==='Enter'){doLogin();}" aria-label="Đăng nhập vào hệ thống">
            <span id="btnText">Đăng nhập</span>
            <span id="btnLoading" class="hidden items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px] spin" aria-hidden="true">progress_activity</span>
                Đang xử lý…
            </span>
        </button>

        <!-- Link khách -->
        <div class="text-center">
            <button type="button" id="guestBtn"
                class="guest-btn bg-transparent border-0 cursor-pointer text-sm py-1 font-medium" onclick="doGuest()"
                onkeydown="if(event.key==='Enter'){doGuest();}"
                aria-label="Tiếp tục với tư cách khách, không cần đăng nhập">
                <span id="guestText">hoặc tiếp tục với tư cách khách →</span>
                <span id="guestLoading" class="hidden items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] spin" aria-hidden="true">progress_activity</span>
                    Đang xử lý…
                </span>
            </button>
        </div>

        <input type="hidden" id="redirectUrl" value="<?= htmlspecialchars($redirect) ?>" />
    </div>

    <!-- Footer -->
    <p class="text-xs text-slate-400 mt-6 text-center leading-relaxed">
        © <?= date('Y') ?> ezManagement Academic Event Management System.
        <span class="block sm:inline"> Bảo mật bởi tiêu chuẩn quốc tế.</span>
    </p>

    <script>
        function togglePassword() {
            const input = document.getElementById('matKhau');
            const icon = document.getElementById('eyeIcon');
            const btn = document.getElementById('eyeBtn');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.textContent = isHidden ? 'visibility_off' : 'visibility';
            btn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        }

        function showError(msg) {
            const box = document.getElementById('errorBox');
            document.getElementById('errorMsg').textContent = msg;
            box.classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('errorBox').classList.add('hidden');
        }

        function setLoginLoading(on) {
            document.getElementById('btnText').classList.toggle('hidden', on);
            const l = document.getElementById('btnLoading');
            l.classList.toggle('hidden', !on);
            l.classList.toggle('flex', on);
            const btn = document.getElementById('loginBtn');
            btn.disabled = on;
            btn.setAttribute('aria-busy', on ? 'true' : 'false');
        }

        function setGuestLoading(on) {
            document.getElementById('guestText').classList.toggle('hidden', on);
            const l = document.getElementById('guestLoading');
            l.classList.toggle('hidden', !on);
            l.classList.toggle('flex', on);
            const btn = document.getElementById('guestBtn');
            btn.disabled = on;
            btn.setAttribute('aria-busy', on ? 'true' : 'false');
        }

        function doLogin() {
            hideError();
            const tenTK = document.getElementById('tenTK').value.trim();
            const matKhau = document.getElementById('matKhau').value;
            const redirect = document.getElementById('redirectUrl').value;

            if (!tenTK || !matKhau) {
                showError('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.');
                document.getElementById('tenTK').focus();
                return;
            }

            setLoginLoading(true);

            const fd = new FormData();
            fd.append('tenTK', tenTK);
            fd.append('matKhau', matKhau);
            fd.append('redirect', redirect);

            fetch('/api/tai_khoan/sign-in.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = data.data.redirect || '/dashboard';
                    } else {
                        showError(data.message || 'Đăng nhập thất bại.');
                        setLoginLoading(false);
                        document.getElementById('matKhau').focus();
                    }
                })
                .catch(() => {
                    showError('Lỗi kết nối, vui lòng thử lại.');
                    setLoginLoading(false);
                });
        }

        function doGuest() {
            setGuestLoading(true);

            const fd = new FormData();
            fd.append('guest', '1');

            fetch('/api/tai_khoan/sign-in.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = data.data.redirect || '/su-kien';
                    } else {
                        showError(data.message || 'Có lỗi xảy ra.');
                        setGuestLoading(false);
                    }
                })
                .catch(() => {
                    showError('Lỗi kết nối, vui lòng thử lại.');
                    setGuestLoading(false);
                });
        }

        /* Enter để submit — bỏ qua nếu đang loading */
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !document.getElementById('loginBtn').disabled) {
                doLogin();
            }
        });
    </script>
</body>

</html>