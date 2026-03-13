<?php
/**
 * API: Phân công Phản biện Tiểu ban
 * Luồng chấm OFFLINE: admin phân công GV → GV nhập điểm theo bộ tiêu chí vòng thi.
 * Bảng riêng: tieuban_phan_bien (tách với phancong_doclap).
 * Điểm lưu vào: chamtieuchi + phancongcham (tái dùng hạ tầng).
 */
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
header('Content-Type: application/json; charset=utf-8');

$idSK  = isset($_GET['id_sk']) ? (int)$_GET['id_sk'] : 0;
$_body = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_body = json_decode(file_get_contents('php://input'), true) ?? [];
    if ($idSK <= 0) $idSK = (int)($_body['id_sk'] ?? 0);
}
if ($idSK <= 0) { _fail(400,'Thiếu id_sk'); exit; }

$actor = auth_require_bat_ky_quyen_su_kien($idSK, [
    'phan_cong_cham','cauhinh_sukien','nhap_diem','xem_bai_phan_cong',
]);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') handleGet($conn, $actor, $idSK);
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') handlePost($conn, $actor, $idSK, $_body);
    else { http_response_code(405); echo _err('Phương thức không hợp lệ'); }
} catch (Throwable $e) {
    error_log('phan_cong_phan_bien: '.$e->getMessage());
    http_response_code(500);
    echo _err('Lỗi hệ thống');
}

// ── GET ──────────────────────────────────────────────────────
function handleGet(PDO $conn, array $actor, int $idSK): void {
    $action    = trim($_GET['action'] ?? 'danh_sach_bai');
    $idSanPham = (int)($_GET['id_san_pham'] ?? 0);

    switch ($action) {
        case 'danh_sach_bai':
            echo _ok(layDanhSachBai($conn, $idSK)); break;
        case 'gv_hop_le':
            if (!$idSanPham) { _fail(400,'Thiếu id_san_pham'); return; }
            echo _ok(layGVHopLe($conn, $idSK, $idSanPham)); break;
        case 'phan_cong_cua_toi':
            $gv = _idGV($conn, $actor['idTK']);
            if (!$gv) { _fail(403,'Không phải giảng viên'); return; }
            echo _ok(layPhanCongCuaGV($conn, $idSK, $gv)); break;
        case 'chi_tiet_phieu':
            if (!$idSanPham) { _fail(400,'Thiếu id_san_pham'); return; }
            $gv = _idGV($conn, $actor['idTK']);
            if (!$gv) { _fail(403,'Không phải giảng viên'); return; }
            $d = layChiTietPhieu($conn, $idSK, $idSanPham, $gv);
            if ($d === null) { _fail(403,'Bạn không được phân công bài này'); return; }
            echo _ok($d); break;
        default: _fail(400,'Action không hợp lệ');
    }
}

// ── POST ─────────────────────────────────────────────────────
function handlePost(PDO $conn, array $actor, int $idSK, array $b): void {
    $action    = trim($b['action'] ?? '');
    $idSanPham = (int)($b['id_san_pham'] ?? 0);
    $idGV      = (int)($b['id_gv'] ?? 0);

    switch ($action) {
        case 'phan_cong':
            if (!$idSanPham||!$idGV){_fail(400,'Thiếu tham số');return;}
            echo _res(doPhanCong($conn,$idSK,$idSanPham,$idGV)); break;
        case 'go_phan_cong':
            if (!$idSanPham||!$idGV){_fail(400,'Thiếu tham số');return;}
            echo _res(doGoPhanCong($conn,$idSK,$idSanPham,$idGV)); break;
        case 'luu_diem':
            $gv = _idGV($conn,$actor['idTK']);
            if (!$gv){_fail(403,'Không phải giảng viên');return;}
            if (!$idSanPham){_fail(400,'Thiếu id_san_pham');return;}
            echo _res(doLuuDiem($conn,$idSK,$idSanPham,$gv,is_array($b['diem']??null)?$b['diem']:[])); break;
        case 'nop_phieu':
            $gv = _idGV($conn,$actor['idTK']);
            if (!$gv){_fail(403,'Không phải giảng viên');return;}
            if (!$idSanPham){_fail(400,'Thiếu id_san_pham');return;}
            echo _res(doNopPhieu($conn,$idSK,$idSanPham,$gv)); break;
        default: _fail(400,'Action không hợp lệ');
    }
}

// ── READ FUNCTIONS ───────────────────────────────────────────
function layDanhSachBai(PDO $conn, int $idSK): array {
    $stmt = $conn->prepare("
        SELECT tb.idTieuBan, tb.tenTieuBan, tb.idVongThi, v.tenVongThi,
               tb.ngayBaoCao, tb.diaDiem,
               ct_sk.idBoTieuChi, btc.tenBoTieuChi,
               COALESCE((SELECT COUNT(*) FROM botieuchi_tieuchi WHERE idBoTieuChi=ct_sk.idBoTieuChi),0) AS soTieuChi,
               sp.idSanPham, sp.tenSanPham, sp.trangThai,
               n.maNhom AS manhom, ttn.tennhom
        FROM tieuban_sanpham tbs
        JOIN tieuban tb         ON tbs.idTieuBan = tb.idTieuBan
        JOIN sanpham sp         ON tbs.idSanPham  = sp.idSanPham
        LEFT JOIN vongthi v     ON tb.idVongThi   = v.idVongThi
        LEFT JOIN cauhinh_tieuchi_sk ct_sk ON ct_sk.idSK=:idSK1 AND ct_sk.idVongThi=tb.idVongThi
        LEFT JOIN botieuchi btc ON btc.idBoTieuChi=ct_sk.idBoTieuChi
        LEFT JOIN nhom n        ON sp.idNhom=n.idnhom
        LEFT JOIN thongtinnhom ttn ON n.idnhom=ttn.idnhom
        WHERE tb.idSK=:idSK AND tb.isActive=1
        ORDER BY tb.idTieuBan ASC, sp.tenSanPham ASC
    ");
    $stmt->execute([':idSK'=>$idSK,':idSK1'=>$idSK]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) return [];

    $ids = array_unique(array_column($rows,'idSanPham'));
    $ph  = implode(',',array_fill(0,count($ids),'?'));
    $s   = $conn->prepare("SELECT tpb.idSanPham,tpb.idGV,tpb.trangThaiCham,gv.tenGV
        FROM tieuban_phan_bien tpb JOIN giangvien gv ON tpb.idGV=gv.idGV
        WHERE tpb.idSanPham IN ($ph) AND tpb.idSK=? ORDER BY gv.tenGV ASC");
    $s->execute(array_merge($ids,[$idSK]));
    $gvMap=[];
    foreach($s->fetchAll(PDO::FETCH_ASSOC) as $g) $gvMap[(int)$g['idSanPham']][]=$g;

    foreach($rows as &$r){
        $r['phan_bien']    = $gvMap[(int)$r['idSanPham']] ?? [];
        $r['so_phan_bien'] = count($r['phan_bien']);
        $r['da_nop']       = count(array_filter($r['phan_bien'],fn($g)=>$g['trangThaiCham']==='Đã nộp'));
    }
    unset($r);
    return $rows;
}

function layGVHopLe(PDO $conn, int $idSK, int $idSanPham): array {
    $stmt = $conn->prepare("
        SELECT DISTINCT tbg.idGV, gv.tenGV, tbg.vaiTro,
               IF(tpb.idGV IS NOT NULL,1,0) AS daPhanCong, tpb.trangThaiCham
        FROM tieuban_sanpham tbs
        JOIN tieuban tb             ON tbs.idTieuBan=tb.idTieuBan
        JOIN tieuban_giangvien tbg  ON tb.idTieuBan=tbg.idTieuBan
        JOIN giangvien gv           ON tbg.idGV=gv.idGV
        LEFT JOIN tieuban_phan_bien tpb ON tpb.idSanPham=tbs.idSanPham AND tpb.idGV=tbg.idGV AND tpb.idSK=:sk2
        LEFT JOIN nhom n_chk ON n_chk.idnhom=(SELECT idNhom FROM sanpham WHERE idSanPham=:sp2 LIMIT 1)
        LEFT JOIN nhom_gvhd gvhd ON gvhd.idNhom=n_chk.idnhom AND gvhd.idTK=gv.idTK
        WHERE tbs.idSanPham=:sp AND tb.idSK=:sk AND tb.isActive=1 AND gvhd.idTK IS NULL
        ORDER BY gv.tenGV ASC
    ");
    $stmt->execute([':sp'=>$idSanPham,':sp2'=>$idSanPham,':sk'=>$idSK,':sk2'=>$idSK]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function layPhanCongCuaGV(PDO $conn, int $idSK, int $idGV): array {
    $stmt = $conn->prepare("
        SELECT tpb.idPhanBien, tpb.idSanPham, tpb.trangThaiCham, tpb.ngayPhanCong, tpb.ngayNop,
               sp.tenSanPham, n.maNhom AS manhom, ttn.tennhom,
               tb.idTieuBan, tb.tenTieuBan, tb.ngayBaoCao, tb.diaDiem, tb.idVongThi, v.tenVongThi,
               ct_sk.idBoTieuChi, btc.tenBoTieuChi,
               COALESCE((SELECT COUNT(*) FROM botieuchi_tieuchi WHERE idBoTieuChi=ct_sk.idBoTieuChi),0) AS soTieuChiTong,
               COALESCE((SELECT COUNT(*) FROM chamtieuchi ct
                         JOIN phancongcham pcc ON ct.idPhanCongCham=pcc.idPhanCongCham
                         WHERE ct.idSanPham=tpb.idSanPham AND pcc.idGV=tpb.idGV
                           AND pcc.idSK=tpb.idSK AND pcc.idBoTieuChi=ct_sk.idBoTieuChi),0) AS soTieuChiDaNhap
        FROM tieuban_phan_bien tpb
        JOIN sanpham sp            ON tpb.idSanPham=sp.idSanPham
        LEFT JOIN nhom n           ON sp.idNhom=n.idnhom
        LEFT JOIN thongtinnhom ttn ON n.idnhom=ttn.idnhom
        JOIN tieuban_sanpham tbs   ON tbs.idSanPham=tpb.idSanPham
        JOIN tieuban tb            ON tbs.idTieuBan=tb.idTieuBan AND tb.idSK=tpb.idSK
        LEFT JOIN vongthi v        ON tb.idVongThi=v.idVongThi
        LEFT JOIN cauhinh_tieuchi_sk ct_sk ON ct_sk.idSK=tpb.idSK AND ct_sk.idVongThi=tb.idVongThi
        LEFT JOIN botieuchi btc    ON btc.idBoTieuChi=ct_sk.idBoTieuChi
        WHERE tpb.idGV=:gv AND tpb.idSK=:sk
        ORDER BY tb.ngayBaoCao ASC, sp.tenSanPham ASC
    ");
    $stmt->execute([':gv'=>$idGV,':sk'=>$idSK]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function layChiTietPhieu(PDO $conn, int $idSK, int $idSanPham, int $idGV): ?array {
    $s = $conn->prepare("
        SELECT tpb.trangThaiCham, tb.idVongThi, v.tenVongThi,
               sp.tenSanPham, n.maNhom AS manhom, ttn.tennhom,
               ct_sk.idBoTieuChi, btc.tenBoTieuChi
        FROM tieuban_phan_bien tpb
        JOIN tieuban_sanpham tbs ON tbs.idSanPham=tpb.idSanPham
        JOIN tieuban tb          ON tbs.idTieuBan=tb.idTieuBan AND tb.idSK=tpb.idSK
        LEFT JOIN vongthi v      ON tb.idVongThi=v.idVongThi
        LEFT JOIN cauhinh_tieuchi_sk ct_sk ON ct_sk.idSK=tpb.idSK AND ct_sk.idVongThi=tb.idVongThi
        LEFT JOIN botieuchi btc  ON btc.idBoTieuChi=ct_sk.idBoTieuChi
        JOIN sanpham sp          ON tpb.idSanPham=sp.idSanPham
        LEFT JOIN nhom n         ON sp.idNhom=n.idnhom
        LEFT JOIN thongtinnhom ttn ON n.idnhom=ttn.idnhom
        WHERE tpb.idSanPham=:sp AND tpb.idGV=:gv AND tpb.idSK=:sk LIMIT 1
    ");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    $pb = $s->fetch(PDO::FETCH_ASSOC);
    if (!$pb) return null;

    $idBTC = (int)$pb['idBoTieuChi'];
    if (!$idBTC) return array_merge($pb,[
        'san_pham'=>['idSanPham'=>$idSanPham,'tenSanPham'=>$pb['tenSanPham'],'manhom'=>$pb['manhom'],'tennhom'=>$pb['tennhom']],
        'bo_tieu_chi'=>null,'ds_tieu_chi'=>[],'loi'=>'Vòng thi chưa được cấu hình bộ tiêu chí.',
    ]);

    $s = $conn->prepare("SELECT idPhanCongCham FROM phancongcham WHERE idGV=:gv AND idSK=:sk AND idBoTieuChi=:btc AND isActive=1 LIMIT 1");
    $s->execute([':gv'=>$idGV,':sk'=>$idSK,':btc'=>$idBTC]);
    $pcc = $s->fetch(PDO::FETCH_ASSOC);
    $idPCC = $pcc ? (int)$pcc['idPhanCongCham'] : 0;

    $s = $conn->prepare("
        SELECT btc.idTieuChi, tc.noiDungTieuChi, btc.diemToiDa, btc.tyTrong, ct.diem, ct.nhanXet
        FROM botieuchi_tieuchi btc
        JOIN tieuchi tc ON btc.idTieuChi=tc.idTieuChi
        LEFT JOIN chamtieuchi ct ON ct.idTieuChi=btc.idTieuChi AND ct.idPhanCongCham=:pcc AND ct.idSanPham=:sp
        WHERE btc.idBoTieuChi=:btc ORDER BY btc.idTieuChi ASC
    ");
    $s->execute([':pcc'=>$idPCC,':sp'=>$idSanPham,':btc'=>$idBTC]);
    $dsTieuChi = array_map(fn($tc)=>array_merge($tc,[
        'diem'=>$tc['diem']!==null?(float)$tc['diem']:null,
        'diemToiDa'=>(float)$tc['diemToiDa'],'tyTrong'=>(float)$tc['tyTrong'],
    ]), $s->fetchAll(PDO::FETCH_ASSOC));

    return [
        'san_pham'      => ['idSanPham'=>$idSanPham,'tenSanPham'=>$pb['tenSanPham'],'manhom'=>$pb['manhom'],'tennhom'=>$pb['tennhom']],
        'trangThaiCham' => $pb['trangThaiCham'],
        'bo_tieu_chi'   => ['idBoTieuChi'=>$idBTC,'tenBoTieuChi'=>$pb['tenBoTieuChi'],'idVongThi'=>$pb['idVongThi'],'tenVongThi'=>$pb['tenVongThi']],
        'ds_tieu_chi'   => $dsTieuChi,
    ];
}

// ── WRITE FUNCTIONS ──────────────────────────────────────────
function doPhanCong(PDO $conn, int $idSK, int $idSanPham, int $idGV): array {
    $s=$conn->prepare("SELECT 1 FROM tieuban_phan_bien WHERE idSanPham=:sp AND idGV=:gv AND idSK=:sk LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    if($s->fetch()) return ['success'=>false,'message'=>'Giảng viên đã được phân công bài này rồi'];

    $s=$conn->prepare("SELECT tb.idVongThi FROM tieuban_sanpham tbs JOIN tieuban tb ON tbs.idTieuBan=tb.idTieuBan
        JOIN tieuban_giangvien tbg ON tb.idTieuBan=tbg.idTieuBan
        WHERE tbs.idSanPham=:sp AND tb.idSK=:sk AND tbg.idGV=:gv AND tb.isActive=1 LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':sk'=>$idSK,':gv'=>$idGV]);
    $tb=$s->fetch(PDO::FETCH_ASSOC);
    if(!$tb) return ['success'=>false,'message'=>'Giảng viên không thuộc tiểu ban chứa bài này'];

    $s=$conn->prepare("SELECT 1 FROM sanpham sp JOIN nhom n ON sp.idNhom=n.idnhom JOIN nhom_gvhd hd ON n.idnhom=hd.idNhom
        JOIN giangvien gv ON hd.idTK=gv.idTK WHERE sp.idSanPham=:sp AND gv.idGV=:gv LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV]);
    if($s->fetch()) return ['success'=>false,'message'=>'Không thể phân công: giảng viên này là GVHD của nhóm nộp bài'];

    $idVT=(int)$tb['idVongThi'];
    $idBTC=0;
    if($idVT>0){
        $s=$conn->prepare("SELECT idBoTieuChi FROM cauhinh_tieuchi_sk WHERE idSK=:sk AND idVongThi=:vt LIMIT 1");
        $s->execute([':sk'=>$idSK,':vt'=>$idVT]);
        $r=$s->fetch(PDO::FETCH_ASSOC);
        $idBTC=$r?(int)$r['idBoTieuChi']:0;
    }
    if(!$idBTC) return ['success'=>false,'message'=>'Vòng thi chưa được cấu hình bộ tiêu chí. Vui lòng thiết lập trước.'];

    try {
        $conn->beginTransaction();
        $conn->prepare("INSERT INTO tieuban_phan_bien (idSanPham,idGV,idSK,trangThaiCham,ngayPhanCong) VALUES (:sp,:gv,:sk,'Chờ chấm',NOW())")
             ->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);

        $s=$conn->prepare("SELECT idTK FROM giangvien WHERE idGV=:gv LIMIT 1");
        $s->execute([':gv'=>$idGV]);
        $tk=$s->fetch(PDO::FETCH_ASSOC);
        if($tk){
            $conn->prepare("INSERT INTO taikhoan_vaitro_sukien (idTK,idSK,idVaiTro,nguonTao,isActive)
                SELECT :tk,:sk,2,'PHAN_CONG_PHAN_BIEN',1 WHERE NOT EXISTS
                (SELECT 1 FROM taikhoan_vaitro_sukien WHERE idTK=:tk2 AND idSK=:sk2 AND idVaiTro=2 AND isActive=1)")
                 ->execute([':tk'=>$tk['idTK'],':sk'=>$idSK,':tk2'=>$tk['idTK'],':sk2'=>$idSK]);
        }

        $s=$conn->prepare("SELECT idPhanCongCham FROM phancongcham WHERE idGV=:gv AND idSK=:sk AND idBoTieuChi=:btc AND isActive=1 LIMIT 1");
        $s->execute([':gv'=>$idGV,':sk'=>$idSK,':btc'=>$idBTC]);
        if(!$s->fetch()){
            $conn->prepare("INSERT INTO phancongcham (idGV,idSK,idVongThi,idBoTieuChi,trangThaiXacNhan,ngayXacNhan,isActive)
                VALUES (:gv,:sk,:vt,:btc,'Chờ chấm','1000-01-01 00:00:00',1)")
                 ->execute([':gv'=>$idGV,':sk'=>$idSK,':vt'=>$idVT,':btc'=>$idBTC]);
        }

        $conn->commit();
        return ['success'=>true,'message'=>'Phân công phản biện thành công'];
    } catch(Throwable $e){ $conn->rollBack(); error_log('doPhanCong:'.$e->getMessage()); return ['success'=>false,'message'=>'Lỗi hệ thống']; }
}

function doGoPhanCong(PDO $conn, int $idSK, int $idSanPham, int $idGV): array {
    $s=$conn->prepare("SELECT trangThaiCham FROM tieuban_phan_bien WHERE idSanPham=:sp AND idGV=:gv AND idSK=:sk LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    $row=$s->fetch(PDO::FETCH_ASSOC);
    if(!$row) return ['success'=>false,'message'=>'Không tìm thấy phân công'];
    if($row['trangThaiCham']==='Đã nộp') return ['success'=>false,'message'=>'Không thể gỡ: giảng viên đã nộp phiếu chấm'];
    $conn->prepare("DELETE FROM tieuban_phan_bien WHERE idSanPham=:sp AND idGV=:gv AND idSK=:sk")
         ->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    return ['success'=>true,'message'=>'Đã gỡ phân công'];
}

function doLuuDiem(PDO $conn, int $idSK, int $idSanPham, int $idGV, array $dsDiem): array {
    if(empty($dsDiem)) return ['success'=>false,'message'=>'Không có dữ liệu điểm'];
    $s=$conn->prepare("SELECT ct_sk.idBoTieuChi FROM tieuban_phan_bien tpb
        JOIN tieuban_sanpham tbs ON tbs.idSanPham=tpb.idSanPham
        JOIN tieuban tb          ON tbs.idTieuBan=tb.idTieuBan AND tb.idSK=tpb.idSK
        LEFT JOIN cauhinh_tieuchi_sk ct_sk ON ct_sk.idSK=tpb.idSK AND ct_sk.idVongThi=tb.idVongThi
        WHERE tpb.idSanPham=:sp AND tpb.idGV=:gv AND tpb.idSK=:sk AND tpb.trangThaiCham!='Đã nộp' LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    $ctx=$s->fetch(PDO::FETCH_ASSOC);
    if(!$ctx) return ['success'=>false,'message'=>'Bạn không được phân công hoặc phiếu đã nộp'];
    $idBTC=(int)$ctx['idBoTieuChi'];
    if(!$idBTC) return ['success'=>false,'message'=>'Vòng thi chưa có bộ tiêu chí'];

    $s=$conn->prepare("SELECT idPhanCongCham FROM phancongcham WHERE idGV=:gv AND idSK=:sk AND idBoTieuChi=:btc AND isActive=1 LIMIT 1");
    $s->execute([':gv'=>$idGV,':sk'=>$idSK,':btc'=>$idBTC]);
    $pcc=$s->fetch(PDO::FETCH_ASSOC);
    if(!$pcc) return ['success'=>false,'message'=>'Không tìm thấy phiếu chấm. Vui lòng liên hệ BTC'];
    $idPCC=(int)$pcc['idPhanCongCham'];

    $stmtMax=$conn->prepare("SELECT diemToiDa FROM botieuchi_tieuchi WHERE idBoTieuChi=:btc AND idTieuChi=:tc LIMIT 1");
    $validated=[];
    foreach($dsDiem as $item){
        $idTC=(int)($item['id_tieu_chi']??0);
        $diem=isset($item['diem'])&&$item['diem']!==''?(float)$item['diem']:null;
        $nhan=trim((string)($item['nhan_xet']??''));
        if(!$idTC||$diem===null) continue;
        $stmtMax->execute([':btc'=>$idBTC,':tc'=>$idTC]);
        $mx=$stmtMax->fetch(PDO::FETCH_ASSOC);
        if(!$mx) continue;
        if($diem<0||$diem>(float)$mx['diemToiDa']) return ['success'=>false,'message'=>"Điểm TC #{$idTC} vượt mức tối đa ({$mx['diemToiDa']})"];
        $validated[]=['idTC'=>$idTC,'diem'=>$diem,'nhan'=>$nhan];
    }
    if(empty($validated)) return ['success'=>false,'message'=>'Không có điểm hợp lệ'];

    try {
        $conn->beginTransaction();
        $conn->prepare("DELETE FROM chamtieuchi WHERE idPhanCongCham=:pcc AND idSanPham=:sp")
             ->execute([':pcc'=>$idPCC,':sp'=>$idSanPham]);
        $ins=$conn->prepare("INSERT INTO chamtieuchi (idPhanCongCham,idSanPham,idTieuChi,diem,nhanXet,thoiGianCham) VALUES (:pcc,:sp,:tc,:d,:nx,NOW())");
        foreach($validated as $v) $ins->execute([':pcc'=>$idPCC,':sp'=>$idSanPham,':tc'=>$v['idTC'],':d'=>$v['diem'],':nx'=>$v['nhan']]);
        $conn->prepare("UPDATE tieuban_phan_bien SET trangThaiCham='Đang chấm' WHERE idSanPham=:sp AND idGV=:gv AND idSK=:sk AND trangThaiCham='Chờ chấm'")
             ->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
        $conn->commit();
        return ['success'=>true,'message'=>'Lưu nháp điểm thành công'];
    } catch(Throwable $e){ $conn->rollBack(); error_log('doLuuDiem:'.$e->getMessage()); return ['success'=>false,'message'=>'Lỗi hệ thống']; }
}

function doNopPhieu(PDO $conn, int $idSK, int $idSanPham, int $idGV): array {
    $s=$conn->prepare("SELECT ct_sk.idBoTieuChi FROM tieuban_phan_bien tpb
        JOIN tieuban_sanpham tbs ON tbs.idSanPham=tpb.idSanPham
        JOIN tieuban tb          ON tbs.idTieuBan=tb.idTieuBan AND tb.idSK=tpb.idSK
        LEFT JOIN cauhinh_tieuchi_sk ct_sk ON ct_sk.idSK=tpb.idSK AND ct_sk.idVongThi=tb.idVongThi
        WHERE tpb.idSanPham=:sp AND tpb.idGV=:gv AND tpb.idSK=:sk AND tpb.trangThaiCham!='Đã nộp' LIMIT 1");
    $s->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    $ctx=$s->fetch(PDO::FETCH_ASSOC);
    if(!$ctx) return ['success'=>false,'message'=>'Không tìm thấy phân công hoặc đã nộp rồi'];
    $idBTC=(int)$ctx['idBoTieuChi'];

    $s=$conn->prepare("SELECT idPhanCongCham FROM phancongcham WHERE idGV=:gv AND idSK=:sk AND idBoTieuChi=:btc AND isActive=1 LIMIT 1");
    $s->execute([':gv'=>$idGV,':sk'=>$idSK,':btc'=>$idBTC]);
    $pcc=$s->fetch(PDO::FETCH_ASSOC);
    if(!$pcc) return ['success'=>false,'message'=>'Không tìm thấy phiếu chấm'];
    $idPCC=(int)$pcc['idPhanCongCham'];

    $s=$conn->prepare("SELECT COUNT(*) FROM botieuchi_tieuchi WHERE idBoTieuChi=:btc");
    $s->execute([':btc'=>$idBTC]); $soTC=(int)$s->fetchColumn();
    $s=$conn->prepare("SELECT COUNT(*) FROM chamtieuchi WHERE idPhanCongCham=:pcc AND idSanPham=:sp");
    $s->execute([':pcc'=>$idPCC,':sp'=>$idSanPham]); $soDone=(int)$s->fetchColumn();
    if($soDone<$soTC) return ['success'=>false,'message'=>"Chưa nhập đủ điểm ({$soDone}/{$soTC} tiêu chí). Vui lòng hoàn thành trước khi nộp phiếu."];

    $conn->prepare("UPDATE tieuban_phan_bien SET trangThaiCham='Đã nộp',ngayNop=NOW() WHERE idSanPham=:sp AND idGV=:gv AND idSK=:sk")
         ->execute([':sp'=>$idSanPham,':gv'=>$idGV,':sk'=>$idSK]);
    return ['success'=>true,'message'=>'Nộp phiếu chấm thành công'];
}

// ── HELPERS ──────────────────────────────────────────────────
function _idGV(PDO $conn, int $idTK): ?int {
    $s=$conn->prepare("SELECT idGV FROM giangvien WHERE idTK=:tk LIMIT 1");
    $s->execute([':tk'=>$idTK]);
    $r=$s->fetch(PDO::FETCH_ASSOC);
    return $r?(int)$r['idGV']:null;
}
function _ok($d): string { return json_encode(['status'=>'success','message'=>'OK','data'=>$d],JSON_UNESCAPED_UNICODE); }
function _res(array $r): string { return json_encode(['status'=>$r['success']?'success':'error','message'=>$r['message'],'data'=>null],JSON_UNESCAPED_UNICODE); }
function _err(string $m): string { return json_encode(['status'=>'error','message'=>$m,'data'=>null],JSON_UNESCAPED_UNICODE); }
function _fail(int $c,string $m): void { http_response_code($c); echo _err($m); }