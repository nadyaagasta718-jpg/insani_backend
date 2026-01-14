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


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $kd_atasan = $_GET['kd_peg'] ?? '';

    if ($kd_atasan == '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode atasan tidak boleh kosong"
        ]);
        exit;
    }

    $data = [];

    $sql = "
        SELECT
            o.fs_kd_trs,
            o.fs_kd_peg,
            p.fs_nm_peg,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan
        FROM td_trs_order_cuti o
        JOIN td_peg p ON o.fs_kd_peg = p.fs_kd_peg
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        WHERE o.fs_kd_peg_atasan = '$kd_atasan'
          AND o.fb_approved = 0
          AND o.fb_ditolak = 0
        ORDER BY o.fd_tgl_trs DESC
    ";

    $query = mysqli_query($conn, $sql);

    if (!$query) {
        echo json_encode([
            "status" => false,
            "message" => "Query gagal",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => true,
        "data" => $data
    ]);
    exit;
}

//aprove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kd_trs_order = $_POST['fs_kd_trs'] ?? '';
    $kd_atasan    = $_POST['fs_kd_peg_atasan'] ?? '';

    if ($kd_trs_order == '' || $kd_atasan == '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode cuti dan kode atasan tidak boleh kosong"
        ]);
        exit;
    }

    $sql_order = "
        SELECT *
        FROM td_trs_order_cuti
        WHERE fs_kd_trs = '$kd_trs_order'
        AND fs_kd_peg_atasan = '$kd_atasan'
        AND fb_approved = 0
        AND fb_ditolak = 0
    ";

    $query_order = mysqli_query($conn, $sql_order);
    $order = mysqli_fetch_assoc($query_order);

    if (!$order) {
        echo json_encode([
            "status" => false,
            "message" => "Order cuti tidak ditemukan"
        ]);
        exit;
    }

    $sql_insert = "
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
    ";

    $insert = mysqli_query($conn, $sql_insert);

    if (!$insert) {
        echo json_encode([
            "status" => false,
            "message" => "Gagal menyimpan cuti",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_query($conn, "
        UPDATE td_trs_order_cuti
        SET fb_approved = 1
        WHERE fs_kd_trs = '$kd_trs_order'
    ");

    echo json_encode([
        "status" => true,
        "message" => "Cuti berhasil disetujui"
    ]);
    exit;
}

echo json_encode([
    "status" => false,
    "message" => "Method tidak didukung"
]);

