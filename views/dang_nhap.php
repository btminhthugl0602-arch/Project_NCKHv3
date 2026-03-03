<?php

/**
 * Trang đăng nhập - ezManagement NCKH
 * views/dang_nhap.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['idTK'])) {
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
    <title>Đăng nhập - ezManagement</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <link href="/assets/css/soft-ui-dashboard-tailwind.css?v=1.0.5" rel="stylesheet" />
</head>

<body class="m-0 font-sans antialiased font-normal leading-default text-slate-500" style="min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;
           background: linear-gradient(135deg, #e8ecf7 0%, #dce3f0 40%, #e6eaf5 100%);">

    <!-- Card -->
    <div class="bg-white shadow-soft-xl rounded-2xl"
        style="width:100%; max-width:460px; padding:2.5rem 2.5rem 2rem; margin:2rem 1rem;">

        <!-- Logo + Tiêu đề -->
        <div class="text-center" style="margin-bottom:2rem">
            <div
                style="display:inline-flex; align-items:center; justify-content:center; gap:0.75rem; margin-bottom:0.75rem">
                <div class="inline-flex items-center justify-center rounded-xl shadow-soft-md"
                    style="width:52px; height:52px; background:linear-gradient(135deg,#3b5bdb,#4c6ef5); flex-shrink:0">
                    <i class="fas fa-graduation-cap text-white" style="font-size:1.4rem"></i>
                </div>
                <h3 class="font-bold text-slate-800 mb-0" style="font-size:1.6rem">ezManagement</h3>
            </div>
            <p class="text-sm text-slate-500 mb-0">Hệ thống quản lý hội thảo khoa học</p>
        </div>

        <!-- Error box (ẩn mặc định) -->
        <div id="errorBox" class="rounded-lg hidden"
            style="padding:0.75rem 1rem; margin-bottom:1.25rem; background:#fef2f2; border:1px solid #fecaca;">
            <div style="display:flex; align-items:center; gap:0.5rem">
                <i class="fas fa-exclamation-circle text-sm" style="color:#ef4444; flex-shrink:0"></i>
                <span id="errorMsg" class="text-sm" style="color:#b91c1c"></span>
            </div>
        </div>

        <!-- Tên đăng nhập -->
        <div style="margin-bottom:1.25rem">
            <label class="font-bold text-xs text-slate-700" for="tenTK" style="display:block; margin-bottom:0.5rem">Tên
                đăng nhập</label>
            <div class="relative">
                <span class="absolute text-slate-400"
                    style="left:1rem; top:50%; transform:translateY(-50%); pointer-events:none">
                    <i class="fas fa-user text-sm"></i>
                </span>
                <input id="tenTK" type="text"
                    class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow"
                    style="padding:0.875rem 1rem 0.875rem 2.75rem" placeholder="Nhập tên đăng nhập của bạn" autofocus />
            </div>
        </div>

        <!-- Mật khẩu -->
        <div style="margin-bottom:1rem">
            <label class="font-bold text-xs text-slate-700" for="matKhau"
                style="display:block; margin-bottom:0.5rem">Mật khẩu</label>
            <div class="relative">
                <span class="absolute text-slate-400"
                    style="left:1rem; top:50%; transform:translateY(-50%); pointer-events:none">
                    <i class="fas fa-lock text-sm"></i>
                </span>
                <input id="matKhau" type="password"
                    class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow"
                    style="padding:0.875rem 3rem 0.875rem 2.75rem" placeholder="Nhập mật khẩu" />
                <button type="button" onclick="togglePassword()"
                    class="absolute border-0 bg-transparent cursor-pointer text-slate-400 hover:text-slate-600 transition-all"
                    style="right:1rem; top:50%; transform:translateY(-50%); padding:0">
                    <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Ghi nhớ + Quên mật khẩu -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
            <div style="display:flex; align-items:center; gap:0.5rem">
                <input id="remember_me" type="checkbox"
                    class="mt-0.54 rounded-10 duration-250 ease-soft-in-out after:rounded-circle after:shadow-soft-2xl after:duration-250 checked:after:translate-x-5.25 h-5 relative float-left w-10 cursor-pointer appearance-none border border-solid border-gray-200 bg-slate-800/10 bg-none bg-contain bg-left bg-no-repeat align-top transition-all after:absolute after:top-px after:h-4 after:w-4 after:translate-x-px after:bg-white after:content-[''] checked:border-slate-800/95 checked:bg-slate-800/95 checked:bg-none checked:bg-right" />
                <label class="font-normal cursor-pointer select-none text-sm text-slate-600" for="remember_me">
                    Ghi nhớ đăng nhập
                </label>
            </div>
            <a href="#" class="text-sm font-semibold" style="color:#3b5bdb; text-decoration:none">
                Quên mật khẩu?
            </a>
        </div>

        <!-- Nút đăng nhập -->
        <button type="button" id="loginBtn" onclick="doLogin()"
            class="inline-block w-full font-bold text-center text-white uppercase align-middle transition-all border-0 rounded-lg cursor-pointer shadow-soft-md leading-pro text-xs ease-soft-in tracking-tight-soft hover:scale-102 hover:shadow-soft-xs active:opacity-85"
            style="padding:1rem; margin-bottom:1.25rem; background:linear-gradient(310deg,#3b5bdb 0%,#4dabf7 100%)">
            <span id="btnText">Đăng nhập &nbsp;<i class="fas fa-sign-in-alt"></i></span>
            <span id="btnLoading" class="hidden"><i class="fas fa-spinner fa-spin"></i>&nbsp;Đang xử lý...</span>
        </button>

        <!-- Link khách -->
        <div class="text-center">
            <button type="button" id="guestBtn" onclick="doGuest()"
                class="bg-transparent border-0 cursor-pointer text-sm text-slate-500 hover:text-slate-700 transition-all"
                style="padding:0.25rem 0">
                <span id="guestText">hoặc tiếp tục với tư cách khách →</span>
                <span id="guestLoading" class="hidden">
                    <i class="fas fa-spinner fa-spin" style="margin-right:0.35rem"></i>Đang xử lý...
                </span>
            </button>
        </div>

        <!-- Redirect ẩn -->
        <input type="hidden" id="redirectUrl" value="<?= htmlspecialchars($redirect) ?>" />

    </div>

    <!-- Footer -->
    <p class="text-xs text-slate-400" style="margin-bottom:1.5rem">
        © <?= date('Y') ?> University Research Management System. All rights reserved.
    </p>

    <script src="/assets/js/plugins/perfect-scrollbar.min.js" async></script>
    <script src="/assets/js/soft-ui-dashboard-tailwind.js?v=1.0.5" async></script>
    <script>
    function togglePassword() {
        const input = document.getElementById('matKhau');
        const icon = document.getElementById('eyeIcon');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }

    function showError(msg) {
        document.getElementById('errorMsg').textContent = msg;
        document.getElementById('errorBox').classList.remove('hidden');
    }

    function hideError() {
        document.getElementById('errorBox').classList.add('hidden');
    }

    function setLoginLoading(on) {
        document.getElementById('btnText').classList.toggle('hidden', on);
        document.getElementById('btnLoading').classList.toggle('hidden', !on);
        document.getElementById('loginBtn').disabled = on;
    }

    function setGuestLoading(on) {
        document.getElementById('guestText').classList.toggle('hidden', on);
        document.getElementById('guestLoading').classList.toggle('hidden', !on);
        document.getElementById('guestBtn').disabled = on;
    }

    function doLogin() {
        hideError();
        const tenTK = document.getElementById('tenTK').value.trim();
        const matKhau = document.getElementById('matKhau').value;
        const redirect = document.getElementById('redirectUrl').value;

        if (!tenTK || !matKhau) {
            showError('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.');
            return;
        }

        setLoginLoading(true);

        const fd = new FormData();
        fd.append('tenTK', tenTK);
        fd.append('matKhau', matKhau);
        fd.append('redirect', redirect);

        fetch('/api/tai_khoan/dang_nhap.php', {
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

        fetch('/api/tai_khoan/dang_nhap.php', {
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

    // Enter để đăng nhập
    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') doLogin();
    });
    </script>
</body>

</html>