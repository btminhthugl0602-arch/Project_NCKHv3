<?php

/**
 * Public URL: /diem-danh?token=XYZ&id_phien_dd=N
 * Dùng cho khán giả và SV quét QR tại cửa phòng.
 * Không cần sidebar, không cần role trong sự kiện.
 * Chỉ cần đăng nhập tài khoản hệ thống.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('_AUTHEN')) define('_AUTHEN', true);
require_once __DIR__ . '/../api/core/base.php';

// ── Auth: chưa đăng nhập → redirect login ─────────────────────
$isLoggedIn = !empty($_SESSION['idTK']);
if (!$isLoggedIn) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header("Location: /sign-in?redirect={$redirect}");
    exit;
}

$idTKLogin = (int) $_SESSION['idTK'];

// ── Lấy params ────────────────────────────────────────────────
$token     = trim($_GET['token']    ?? '');
$idPhienDD = (int) ($_GET['id_phien_dd'] ?? 0);

// Xác định script + base path
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath   = dirname($scriptPath);
if ($basePath === '\\' || $basePath === '/') $basePath = '';

require_once __DIR__ . '/../api/su_kien/quan_ly_to_chuc.php';

// ── Validate token & phiên ────────────────────────────────────
$phien      = null;
$tokenError = '';
$idSK       = 0;
$lichTrinh  = null;

if (empty($token) || $idPhienDD <= 0) {
    $tokenError = 'Link điểm danh không hợp lệ.';
} else {
    $phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $idPhienDD);
    if (!$phien) {
        $tokenError = 'Phiên điểm danh không tồn tại.';
    } elseif (!xac_thuc_token_qr($token, $idPhienDD, $phien['thoiGianMo'])) {
        $tokenError = 'QR code không hợp lệ hoặc đã hết hạn.';
    } else {
        // Lazy check auto open/close
        $phien = kiem_tra_tu_dong_phien($conn, $phien);
        $lichTrinh = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', (int) $phien['idLichTrinh']);
        $idSK = $lichTrinh ? (int) $lichTrinh['idSK'] : 0;
    }
}

// ── Check đã điểm danh chưa ───────────────────────────────────
$daDiemDanh = false;
if ($phien && !$tokenError) {
    try {
        $stmt = $conn->prepare("SELECT thoiGianDiemDanh FROM diemdanh WHERE idPhienDD = ? AND idTK = ? LIMIT 1");
        $stmt->execute([$idPhienDD, $idTKLogin]);
        $row = $stmt->fetch();
        if ($row) $daDiemDanh = $row['thoiGianDiemDanh'];
    } catch (Throwable $e) {
    }
}

$trangThaiPhien = $phien['trangThai'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điểm danh — ezManagement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400" rel="stylesheet" />
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f8f5ff;
            min-height: 100vh;
        }

        .dd-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 8px 40px rgba(146, 19, 236, .12);
            max-width: 440px;
            width: 92%;
            margin: 0 auto;
            overflow: hidden;
        }

        .dd-header {
            background: linear-gradient(135deg, #9213ec, #c026d3);
            padding: 2rem;
            text-align: center;
            color: #fff;
        }

        .dd-header-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, .2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .dd-body {
            padding: 2rem;
        }

        .dd-meta-row {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .6rem 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: .85rem;
        }

        .dd-meta-row:last-child {
            border-bottom: none;
        }

        .dd-meta-label {
            color: #94a3b8;
            min-width: 100px;
            font-size: .78rem;
        }

        .dd-meta-value {
            color: #1e1b4b;
            font-weight: 600;
            flex: 1;
        }

        .dd-btn {
            width: 100%;
            padding: .875rem;
            border-radius: .875rem;
            border: none;
            font-size: .95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: opacity .15s, transform .1s;
        }

        .dd-btn:active {
            transform: scale(.98);
        }

        .dd-btn-primary {
            background: linear-gradient(135deg, #9213ec, #c026d3);
            color: #fff;
            box-shadow: 0 4px 20px rgba(146, 19, 236, .3);
        }

        .dd-btn-primary:hover {
            opacity: .9;
        }

        .dd-btn-primary:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .dd-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .875rem;
            border-radius: 99px;
            font-size: .78rem;
            font-weight: 600;
        }

        .badge-open {
            background: #dcfce7;
            color: #166534;
        }

        .badge-closed {
            background: #f1f5f9;
            color: #64748b;
        }

        .badge-pending {
            background: #fef9c3;
            color: #92400e;
        }

        .dd-success-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 20px rgba(34, 197, 94, .3);
        }

        #dd-gps-info {
            display: none;
        }

        #dd-status-msg {
            margin-top: .75rem;
            font-size: .82rem;
            text-align: center;
        }
    </style>
</head>

<body class="flex items-center justify-center py-10 px-4">

    <?php if ($tokenError): ?>
        <!-- ── Token lỗi ── -->
        <div class="dd-card">
            <div class="dd-header" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <div class="dd-header-icon">
                    <span class="material-symbols-outlined" style="font-size:36px">error</span>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0">Link không hợp lệ</h2>
            </div>
            <div class="dd-body text-center">
                <p class="text-slate-600 text-sm mb-4"><?= htmlspecialchars($tokenError) ?></p>
                <p class="text-slate-400 text-xs">Vui lòng quét lại QR code mới nhất từ BTC.</p>
                <a href="/dashboard" class="dd-btn dd-btn-primary mt-5" style="text-decoration:none">
                    <span class="material-symbols-outlined">home</span>
                    Về trang chủ
                </a>
            </div>
        </div>

    <?php elseif ($daDiemDanh): ?>
        <!-- ── Đã điểm danh ── -->
        <div class="dd-card">
            <div class="dd-header">
                <div class="dd-header-icon">
                    <span class="material-symbols-outlined" style="font-size:36px">how_to_reg</span>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 .25rem">Điểm danh</h2>
                <p style="opacity:.85;font-size:.85rem;margin:0">
                    <?= $lichTrinh ? htmlspecialchars($lichTrinh['tenHoatDong']) : '' ?></p>
            </div>
            <div class="dd-body">
                <div class="dd-success-icon">
                    <span class="material-symbols-outlined" style="color:#fff;font-size:40px">check_circle</span>
                </div>
                <h3 style="text-align:center;font-size:1.1rem;font-weight:700;color:#1e1b4b;margin-bottom:.25rem">Đã điểm
                    danh!</h3>
                <p style="text-align:center;font-size:.83rem;color:#94a3b8;margin-bottom:1.5rem">
                    Lúc <?= date('H:i', strtotime($daDiemDanh)) ?> — <?= date('d/m/Y', strtotime($daDiemDanh)) ?>
                </p>
                <a href="/dashboard" class="dd-btn dd-btn-primary" style="text-decoration:none">
                    <span class="material-symbols-outlined">home</span>
                    Về trang chủ
                </a>
            </div>
        </div>

    <?php elseif ($trangThaiPhien === 'CHUAN_BI'): ?>
        <!-- ── Phiên chưa mở ── -->
        <div class="dd-card">
            <div class="dd-header" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <div class="dd-header-icon">
                    <span class="material-symbols-outlined" style="font-size:36px">schedule</span>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0">Phiên chưa mở</h2>
            </div>
            <div class="dd-body text-center">
                <p class="text-slate-600 text-sm mb-2">Phiên điểm danh chưa được mở.</p>
                <p class="text-slate-400 text-xs">Vui lòng chờ BTC mở phiên, sau đó quét lại QR.</p>
                <?php if ($lichTrinh): ?>
                    <div class="mt-4 p-3 bg-slate-50 rounded-xl text-left">
                        <div class="dd-meta-row">
                            <span class="dd-meta-label">Hoạt động</span>
                            <span class="dd-meta-value"><?= htmlspecialchars($lichTrinh['tenHoatDong']) ?></span>
                        </div>
                        <?php if ($lichTrinh['thoiGianBatDau']): ?>
                            <div class="dd-meta-row">
                                <span class="dd-meta-label">Bắt đầu</span>
                                <span class="dd-meta-value"><?= date('H:i d/m/Y', strtotime($lichTrinh['thoiGianBatDau'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($trangThaiPhien === 'DA_DONG'): ?>
        <!-- ── Phiên đã đóng ── -->
        <div class="dd-card">
            <div class="dd-header" style="background:linear-gradient(135deg,#64748b,#475569)">
                <div class="dd-header-icon">
                    <span class="material-symbols-outlined" style="font-size:36px">lock</span>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0">Phiên đã đóng</h2>
            </div>
            <div class="dd-body text-center">
                <p class="text-slate-600 text-sm mb-2">Phiên điểm danh này đã kết thúc.</p>
                <p class="text-slate-400 text-xs">Nếu bạn có mặt nhưng chưa điểm danh, liên hệ BTC để ghi nhận thủ công.</p>
                <a href="/dashboard" class="dd-btn dd-btn-primary mt-5" style="text-decoration:none">
                    <span class="material-symbols-outlined">home</span>
                    Về trang chủ
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- ── Phiên DANG_MO — màn hình điểm danh chính ── -->
        <div class="dd-card">
            <div class="dd-header">
                <div class="dd-header-icon">
                    <span class="material-symbols-outlined" style="font-size:36px">how_to_reg</span>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 .25rem">Điểm danh</h2>
                <?php if ($lichTrinh): ?>
                    <p style="opacity:.85;font-size:.85rem;margin:0"><?= htmlspecialchars($lichTrinh['tenHoatDong']) ?></p>
                <?php endif; ?>
            </div>

            <div class="dd-body">
                <!-- Info -->
                <div style="background:#fdf8ff;border-radius:.875rem;padding:.875rem 1rem;margin-bottom:1.25rem;">
                    <?php if ($lichTrinh): ?>
                        <div class="dd-meta-row">
                            <span class="dd-meta-label">Địa điểm</span>
                            <span class="dd-meta-value"><?= htmlspecialchars($lichTrinh['diaDiem'] ?: '—') ?></span>
                        </div>
                        <?php if ($lichTrinh['thoiGianBatDau']): ?>
                            <div class="dd-meta-row">
                                <span class="dd-meta-label">Thời gian</span>
                                <span class="dd-meta-value">
                                    <?= date('H:i', strtotime($lichTrinh['thoiGianBatDau'])) ?>
                                    <?= $lichTrinh['thoiGianKetThuc'] ? '– ' . date('H:i', strtotime($lichTrinh['thoiGianKetThuc'])) : '' ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="dd-meta-row" style="border:none">
                        <span class="dd-meta-label">Trạng thái</span>
                        <span class="dd-status-badge badge-open">🟢 Đang mở</span>
                    </div>
                </div>

                <!-- GPS info (hiện sau khi lấy) -->
                <div id="dd-gps-info"
                    style="background:#f0fdf4;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
                    <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle">location_on</span>
                    <span id="dd-gps-text">Đang lấy vị trí...</span>
                </div>

                <!-- Nút điểm danh -->
                <button class="dd-btn dd-btn-primary" id="dd-btn" onclick="DDPage.startDiemDanh()">
                    <span class="material-symbols-outlined">how_to_reg</span>
                    Xác nhận điểm danh
                </button>

                <div id="dd-status-msg"></div>
            </div>
        </div>

        <script>
            const DDPage = (() => {
                const BASE = '<?= addslashes($basePath) ?>';
                const ID_PHIEN = <?= (int) $idPhienDD ?>;
                const ID_SK = <?= (int) $idSK ?>;
                const TOKEN = '<?= addslashes($token) ?>';

                let _lat = null,
                    _lng = null;

                function setStatus(msg, color = '#94a3b8') {
                    const el = document.getElementById('dd-status-msg');
                    if (el) {
                        el.textContent = msg;
                        el.style.color = color;
                    }
                }

                function startDiemDanh() {
                    const btn = document.getElementById('dd-btn');
                    btn.disabled = true;
                    btn.innerHTML =
                        `<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;display:inline-block">refresh</span> Đang xử lý...`;

                    if (navigator.geolocation) {
                        setStatus('Đang lấy vị trí GPS...', '#94a3b8');
                        navigator.geolocation.getCurrentPosition(
                            pos => {
                                _lat = pos.coords.latitude;
                                _lng = pos.coords.longitude;
                                document.getElementById('dd-gps-info').style.display = 'flex';
                                document.getElementById('dd-gps-info').style.alignItems = 'center';
                                document.getElementById('dd-gps-info').style.gap = '.35rem';
                                document.getElementById('dd-gps-text').textContent =
                                    `${_lat.toFixed(5)}, ${_lng.toFixed(5)}`;
                                submit('GPS');
                            },
                            () => submit('QR'), // GPS fail: user arrived via QR link, token still valid
                            {
                                timeout: 8000,
                                enableHighAccuracy: true
                            }
                        );
                    } else {
                        submit('QR'); // no geolocation: user arrived via QR link
                    }
                }

                async function submit(phuongThuc) {
                    try {
                        const payload = {
                            id_sk: ID_SK,
                            id_phien_dd: ID_PHIEN,
                            token: TOKEN,
                            phuong_thuc: phuongThuc,
                        };
                        if (_lat !== null) {
                            payload.vi_tri_lat = _lat;
                            payload.vi_tri_lng = _lng;
                        }

                        const res = await fetch(`${BASE}/api/su_kien/ghi_nhan_diemdanh.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload),
                        });
                        const json = await res.json();

                        if (json.status === 'success') {
                            document.querySelector('.dd-card').innerHTML = `
                    <div class="dd-header">
                        <div class="dd-header-icon">
                            <span class="material-symbols-outlined" style="font-size:36px">how_to_reg</span>
                        </div>
                    </div>
                    <div class="dd-body">
                        <div class="dd-success-icon">
                            <span class="material-symbols-outlined" style="color:#fff;font-size:40px">check_circle</span>
                        </div>
                        <h3 style="text-align:center;font-size:1.1rem;font-weight:700;color:#1e1b4b;margin-bottom:.25rem">Điểm danh thành công!</h3>
                        <p style="text-align:center;font-size:.83rem;color:#94a3b8;margin-bottom:1.5rem">Lúc ${new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'})}</p>
                        <a href="${BASE}/dashboard" class="dd-btn dd-btn-primary" style="text-decoration:none">
                            <span class="material-symbols-outlined">home</span> Về trang chủ
                        </a>
                    </div>`;
                        } else {
                            const btn2 = document.getElementById('dd-btn');
                            if (btn2) {
                                btn2.disabled = false;
                                btn2.innerHTML =
                                    '<span class="material-symbols-outlined">how_to_reg</span> Xác nhận điểm danh';
                            }
                            setStatus(json.message || 'Có lỗi xảy ra', '#ef4444');
                        }
                    } catch (e) {
                        const btn2 = document.getElementById('dd-btn');
                        if (btn2) {
                            btn2.disabled = false;
                            btn2.innerHTML =
                                '<span class="material-symbols-outlined">how_to_reg</span> Xác nhận điểm danh';
                        }
                        setStatus('Lỗi kết nối. Vui lòng thử lại.', '#ef4444');
                    }
                }

                return {
                    startDiemDanh
                };
            })();
        </script>
        <style>
            @keyframes spin {
                to {
                    transform: rotate(360deg)
                }
            }
        </style>
    <?php endif; ?>

</body>

</html>