# Quy che Runtime, Error Contract, va Observability

## 1) Error contract chuan hoa

Ap dung cho cac API quy che chinh:
- api/su_kien/luu_quy_che.php
- api/su_kien/quy_che_metadata.php
- api/su_kien/chi_tiet_quy_che.php

Quy uoc ma loi:
- 422: loi du lieu dau vao/validation
- 403: khong du quyen
- 500: loi he thong

Frontend map ma loi de hien thi ro:
- 422 -> Loi du lieu
- 403 -> Khong du quyen
- 500 -> Loi he thong

## 2) Runtime performance cho chi_tiet_quy_che

Thay doi quan trong:
- Bo fetch de quy N+1 tren tung node.
- Chuyen sang preload theo batch:
  - dieukien
  - tohop_dieukien
  - dieukien_don
  - toantu
  - thuoctinh_kiemtra

Structured log bo sung:
- module=quy_che
- event=chi_tiet_quy_che
- tongNode
- durationMs
- status

Muc tieu:
- p95 durationMs giam ro ret tren cay lon.

## 3) Structured log cho save/evaluate

Log event da co:
- save_rule_start
- save_rule_result
- evaluate_rules

Truong trong yeu:
- idSK
- maNguCanh
- tongQuyChe
- viPhamCount
- durationMs
- status

## 4) Dashboard de xac dinh ti le fail

Nguon:
- JSON logs tu backend (error_log) duoc thu gom vao Loki/ELK.

Chi so dashboard de nghi:
1. evaluate_rules_total by maNguCanh
2. evaluate_rules_error_rate
3. evaluate_rules_failed_rules_rate
4. save_rule_validation_error_rate
5. chi_tiet_quy_che_p95_durationMs

## 5) Alert de xuat

Rule canh bao:
- Neu evaluate_rules_error_rate > 20% trong 15 phut => critical
- Neu chi_tiet_quy_che p95 > baseline x 2 trong 15 phut => warning

Script baseline de monitor file log:
- scripts/monitor_rule_eval.php

Vi du:
```bash
php scripts/monitor_rule_eval.php /var/log/php/error.log 2000 0.2
```

Exit code:
- 0: healthy
- 1: vuot nguong canh bao
- 2: loi input/runtime monitor
