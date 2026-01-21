<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/database.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $data = [];

    $sql = "
        SELECT
            o.fs_kd_trs,
            o.fs_kd_peg,
            p.fs_nm_peg,
            l.fs_nm_lokasi,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan
        FROM td_trs_order_cuti o
        JOIN td_peg p ON o.fs_kd_peg = p.fs_kd_peg
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
       LEFT JOIN td_lokasi l ON p.fs_kd_lokasi = l.fs_kd_lokasi
        WHERE o.fb_approved = 1
          AND o.fb_ditolak = 0
        ORDER BY o.fd_tgl_trs DESC
    ";

    $q = mysqli_query($conn, $sql);

    if (!$q) {
        echo json_encode([
            "status" => false,
            "message" => "Query gagal",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode([
        "status" => true,
        "data" => $data
    ]);
    exit;
}

// verif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kd_trs = $_POST['fs_kd_trs'] ?? '';
    if (kd_trs == ''){
        echo json_encode([
            "status" => false,
            "message" => "Kode transaksi wajib diisi"
        ]);
        exit;
    }

    $q = mysqli_query($conn,"
    SELECT * 
    FROM td_trs_order_cuti
    WHERE fs_kd_trs ='$kd_trs'
    AND fb_approved = 1
    AND fb_ditolak = 0
    LIMIT 1
    ");

    $order = mysqli_fetch_assoc($q);
    if (!$order) {
        echo json_encode([
            "status" => false,
            "message" => "Data cuti tidak valid untuk diverifikasi"
        ]);
        exit;
    }
       mysqli_query($conn, "
        INSERT INTO td_trs_cuti
        (fs_kd_peg, fs_kd_jenis_cuti, fd_tgl_mulai, fd_tgl_akhir, fs_keterangan, fd_tgl_trs)
        VALUES (
            '{$order['fs_kd_peg']}',
            '{$order['fs_kd_jenis_cuti']}',
            '{$order['fd_tgl_mulai']}',
            '{$order['fd_tgl_akhir']}',
            '{$order['fs_keterangan']}',
            NOW()
        )
    ");

    echo json_encode([
        "status" => true,
        "message" => "Cuti berhasil diverifikasi HRD"
    ]);
    exit;
}

echo json_encode([
    "status" => false,
    "message" => "Method tidak dikenali"
]);
