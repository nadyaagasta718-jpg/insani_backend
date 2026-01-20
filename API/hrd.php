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

echo json_encode([
    "status" => false,
    "message" => "Method tidak dikenali"
]);
