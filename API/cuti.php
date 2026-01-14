<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/database.php";

$action = $_GET['action'] ?? $_POST['action'] ?? '';


if ($action === 'jenis_cuti') {
    $data = [];

    $query = mysqli_query(
        $conn,
        "SELECT fs_kd_jenis_cuti, fs_nm_jenis_cuti FROM td_jenis_cuti"
    );

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
    exit;
}


if ($action === 'submit') {
    $kdPeg    = $_POST['kd_peg'] ?? '';
    $kdJenis  = $_POST['kd_jenis_cuti'] ?? '';
    $tglMulai = $_POST['tgl_mulai'] ?? '';
    $tglAkhir = $_POST['tgl_selesai'] ?? '';
    $ket      = $_POST['keterangan'] ?? '';

    if (!$kdPeg || !$kdJenis || !$tglMulai || !$tglAkhir) {
        echo json_encode([
            "status"  => false,
            "message" => "Data belum lengkap"
        ]);
        exit;
    }

    
    $qAtasan = mysqli_query(
        $conn,
        "SELECT fs_kd_peg_atasan 
         FROM td_peg 
         WHERE fs_kd_peg='$kdPeg' 
         LIMIT 1"
    );

    $rowAtasan = mysqli_fetch_assoc($qAtasan);
    $kdAtasan  = $rowAtasan['fs_kd_peg_atasan'] ?? '';

    if (!$kdAtasan) {
        echo json_encode([
            "status"  => false,
            "message" => "belum ditemukan atasan"
        ]);
        exit;
    }

    $kodeTrs = "TRS" . date("YmdHis");

    mysqli_query(
        $conn,
        "INSERT INTO td_trs_order_cuti (
            fs_kd_trs, fd_tgl_trs, fs_jam_trs,
            fs_kd_peg, fs_kd_peg_atasan,
            fd_tgl_mulai, fs_jam_mulai,
            fd_tgl_akhir, fs_jam_akhir,
            fs_kd_jenis_cuti, fs_keterangan,
            fb_approved, fb_ditolak
        ) VALUES (
            '$kodeTrs', CURDATE(), CURTIME(),
            '$kdPeg', '$kdAtasan',
            '$tglMulai','00:00:00',
            '$tglAkhir','23:59:59',
            '$kdJenis','$ket',0,0
        )"
    );

    echo json_encode([
        "status"  => true,
        "message" => "Cuti berhasil diajukan"
    ]);
    exit;
}

// list cuti
if ($action === 'list') {
    $data  = [];
    $kdPeg = $_GET['kd_peg'] ?? '';

    $query = mysqli_query(
        $conn,
        "SELECT 
            o.fs_kd_trs,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan,
            CASE
                WHEN o.fb_ditolak = 1 THEN 'REJECTED'
                WHEN o.fb_approved = 1 THEN 'APPROVED'
                ELSE 'PENDING'
            END AS fs_status
         FROM td_trs_order_cuti o
         JOIN td_jenis_cuti j 
           ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
         WHERE o.fs_kd_peg='$kdPeg'
         ORDER BY o.fd_tgl_trs DESC"
    );

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
    exit;
}

// list cuti atasan
if ($action === 'list_atasan') {
    $data      = [];
    $kdAtasan  = $_GET['kd_peg'] ?? '';

    $query = mysqli_query(
        $conn,
        "SELECT 
            o.fs_kd_trs,
            p.fs_nm_peg,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan,
            CASE
                WHEN o.fb_ditolak = 1 THEN 'REJECTED'
                WHEN o.fb_approved = 1 THEN 'APPROVED'
                ELSE 'PENDING'
            END AS fs_status
         FROM td_trs_order_cuti o
         JOIN td_peg p 
           ON o.fs_kd_peg = p.fs_kd_peg
         JOIN td_jenis_cuti j 
           ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
         WHERE o.fs_kd_peg_atasan='$kdAtasan'
         ORDER BY o.fd_tgl_trs DESC"
    );

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
    exit;
}

//approve $ reject
if ($action === 'approve' || $action === 'reject') {
    $kdTrs    = $_POST['kd_trs'] ?? '';
    $kdAtasan = $_POST['kd_peg'] ?? '';

    if (!$kdTrs || !$kdAtasan) {
        echo json_encode([
            "status"  => false,
            "message" => "Parameter tidak lengkap"
        ]);
        exit;
    }

    $field = ($action === 'approve')
        ? 'fb_approved = 1'
        : 'fb_ditolak = 1';

    mysqli_query(
        $conn,
        "UPDATE td_trs_order_cuti
         SET $field
         WHERE fs_kd_trs='$kdTrs'
           AND fs_kd_peg_atasan='$kdAtasan'
           AND fb_approved=0
           AND fb_ditolak=0"
    );

    echo json_encode([
        "status"  => true,
        "message" => "Berhasil"
    ]);
    exit;
}

echo json_encode([
    "status"  => false,
    "message" => "Action tidak dikenal"
]);
