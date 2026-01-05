<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once "../config/database.php";


$action = $_GET['action'] ?? $_POST['action'] ?? '';


if ($action === 'jenis_cuti') {

    $q = mysqli_query($conn, "
        SELECT fs_kd_jenis_cuti, fs_nm_jenis_cuti
        FROM td_jenis_cuti
        ORDER BY fs_nm_jenis_cuti ASC
    ");

    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
    exit;
}


if ($action === 'submit') {

    $kd_peg     = $_POST['kd_peg'] ?? '';
    $kd_jenis   = $_POST['kd_jenis_cuti'] ?? '';
    $tgl_mulai  = $_POST['tgl_mulai'] ?? '';
    $tgl_akhir  = $_POST['tgl_selesai'] ?? '';
    $ket        = $_POST['keterangan'] ?? '';

    if ($kd_peg=='' || $kd_jenis=='' || $tgl_mulai=='' || $tgl_akhir=='') {
        echo json_encode([
            "status"  => false,
            "message" => "Data wajib belum lengkap"
        ]);
        exit;
    }

    
    $kode = "TRS" . date("YmdHis");

    $insert = mysqli_query($conn, "
        INSERT INTO td_trs_order_cuti (
            fs_kd_trs,
            fd_tgl_trs,
            fs_jam_trs,
            fs_kd_peg,
            fd_tgl_mulai,
            fd_tgl_akhir,
            fs_kd_jenis_cuti,
            fs_keterangan,
            fb_approved,
            fb_ditolak
        ) VALUES (
            '$kode',
            CURDATE(),
            CURTIME(),
            '$kd_peg',
            '$tgl_mulai',
            '$tgl_akhir',
            '$kd_jenis',
            '$ket',
            0,
            0
        )
    ");

    if (!$insert) {
        echo json_encode([
            "status"  => false,
            "message" => "Gagal submit cuti",
            "error"   => mysqli_error($conn)
        ]);
        exit;
    }

    echo json_encode([
        "status"  => true,
        "message" => "Cuti berhasil diajukan"
    ]);
    exit;
}


if ($action === 'list') {

    $kd_peg = $_GET['kd_peg'] ?? '';

    if ($kd_peg == '') {
        echo json_encode([
            "status"  => false,
            "message" => "Kode pegawai kosong"
        ]);
        exit;
    }

    $q = mysqli_query($conn, "
        SELECT 
            o.fs_kd_trs,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan,
            o.fb_approved,
            o.fb_ditolak
        FROM td_trs_order_cuti o
        JOIN td_jenis_cuti j
          ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        WHERE o.fs_kd_peg = '$kd_peg'
        ORDER BY o.fd_tgl_trs DESC, o.fs_jam_trs DESC
    ");

    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode([
        "status" => true,
        "data"   => $data
    ]);
    exit;
}


echo json_encode([
    "status"  => false,
    "message" => "Action tidak dikenal"
]);