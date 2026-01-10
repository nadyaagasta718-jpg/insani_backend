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
    $q = mysqli_query($conn, "SELECT fs_kd_jenis_cuti, fs_nm_jenis_cuti FROM td_jenis_cuti");

    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode(["status" => true, "data" => $data]);
    exit;
}


if ($action === 'submit') {
    $kd_peg    = $_POST['kd_peg'] ?? '';
    $kd_jenis  = $_POST['kd_jenis_cuti'] ?? '';
    $tgl_mulai = $_POST['tgl_mulai'] ?? '';
    $tgl_akhir = $_POST['tgl_selesai'] ?? '';
    $ket       = $_POST['keterangan'] ?? '';

    if (!$kd_peg || !$kd_jenis || !$tgl_mulai || !$tgl_akhir) {
        echo json_encode(["status" => false, "message" => "Data belum lengkap"]);
        exit;
    }

   
    $qA = mysqli_query($conn, "SELECT fs_kd_peg_atasan FROM td_peg WHERE fs_kd_peg='$kd_peg' LIMIT 1");
    $rowA = mysqli_fetch_assoc($qA);
    $atasan = $rowA['fs_kd_peg_atasan'] ?? '';

    if (!$atasan) {
        echo json_encode(["status" => false, "message" => "Atasan belum diset"]);
        exit;
    }

    
    $kode = "TRS" . date("YmdHis");

    $sql = "
        INSERT INTO td_trs_order_cuti (
            fs_kd_trs, fd_tgl_trs, fs_jam_trs,
            fs_kd_peg, fs_kd_peg_atasan,
            fd_tgl_mulai, fs_jam_mulai,
            fd_tgl_akhir, fs_jam_akhir,
            fs_kd_jenis_cuti, fs_keterangan,
            fb_approved, fb_ditolak
        ) VALUES (
            '$kode', CURDATE(), CURTIME(),
            '$kd_peg', '$atasan',
            '$tgl_mulai','00:00:00',
            '$tgl_akhir','23:59:59',
            '$kd_jenis','$ket',0,0
        )
    ";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => true, "message" => "Cuti dikirim ke atasan"]);
    } else {
        echo json_encode(["status" => false, "error" => mysqli_error($conn)]);
    }
    exit;
}


if ($action === 'list') {
    $data = [];
    $kd_peg = $_GET['kd_peg'] ?? '';

    $q = mysqli_query($conn, "
        SELECT 
            o.fs_kd_trs,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan,
            CASE
                WHEN o.fb_ditolak = 1 THEN 'REJECTED'
                WHEN o.fb_approved = 1 THEN 'APPROVED'
                ELSE 'PENDING'
            END AS status
        FROM td_trs_order_cuti o
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        WHERE o.fs_kd_peg='$kd_peg'
        ORDER BY o.fd_tgl_trs DESC
    ");

    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode(["status" => true, "data" => $data]);
    exit;
}


if ($action === 'list_atasan') {
    $data = [];
    $kd_atasan = $_GET['kd_peg'] ?? '';

    $q = mysqli_query($conn, "
        SELECT 
            o.fs_kd_trs,
            p.fs_nm_peg,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan
        FROM td_trs_order_cuti o
        JOIN td_peg p ON o.fs_kd_peg = p.fs_kd_peg
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        WHERE o.fs_kd_peg_atasan='$kd_atasan'
          AND o.fb_approved=0
          AND o.fb_ditolak=0
        ORDER BY o.fd_tgl_trs DESC
    ");

    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode(["status" => true, "data" => $data]);
    exit;
}


if ($action === 'approve' || $action === 'reject') {
    $kd_trs = $_POST['kd_trs'] ?? '';

    if (!$kd_trs) {
        echo json_encode(["status" => false, "message" => "kd_trs kosong"]);
        exit;
    }

    $field = ($action === 'approve') ? 'fb_approved=1' : 'fb_ditolak=1';

    mysqli_query($conn, "UPDATE td_trs_order_cuti SET $field WHERE fs_kd_trs='$kd_trs'");

    echo json_encode(["status" => true, "message" => "Berhasil"]);
    exit;
}

echo json_encode(["status" => false, "message" => "Action tidak dikenal"]);
