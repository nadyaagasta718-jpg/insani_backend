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

    $data = [];

    $sql = "
        SELECT
            o.fs_kd_trs,
            o.fs_kd_peg,
            p.fs_nm_peg,
            p.fs_kd_lokasi,
            l.fs_nm_lokasi,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan,
            o.fs_kd_petugas_approved,
            pa.fs_nm_peg AS fs_nm_atasan
        FROM td_trs_order_cuti o
        JOIN td_peg p ON o.fs_kd_peg = p.fs_kd_peg
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        LEFT JOIN td_lokasi l ON p.fs_kd_lokasi = l.fs_kd_lokasi
        LEFT JOIN td_peg pa ON o.fs_kd_petugas_approved = pa.fs_kd_peg
        WHERE o.fb_approved = 1
          AND o.fb_ditolak = 0
          AND NOT EXISTS (
              SELECT 1
              FROM td_trs_cuti c
              WHERE c.fs_kd_trs_order = o.fs_kd_trs
          )
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kd_trs = $_POST['fs_kd_trs'] ?? '';

    if ($kd_trs == '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode transaksi wajib diisi"
        ]);
        exit;
    }

    $q = mysqli_query($conn, "
        SELECT *
        FROM td_trs_order_cuti
        WHERE fs_kd_trs = '$kd_trs'
          AND fb_approved = 1
          AND fb_ditolak = 0
        LIMIT 1
    ");

    $order = mysqli_fetch_assoc($q);

    if (!$order) {
        echo json_encode([
            "status" => false,
            "message" => "Data cuti tidak valid"
        ]);
        exit;
    }

    $insert = mysqli_query($conn, "
        INSERT INTO td_trs_cuti (
            fs_kd_peg,
            fs_kd_trs_order,
            fs_kd_jenis_cuti,
            fs_keterangan,
            fd_tgl_mulai,
            fs_jam_mulai,
            fd_tgl_akhir,
            fs_jam_akhir,
            fd_tgl_trs,
            fs_jam_trs,
            fs_kd_petugas
        ) VALUES (
            '{$order['fs_kd_peg']}',
            '{$order['fs_kd_trs']}',
            '{$order['fs_kd_jenis_cuti']}',
            '{$order['fs_keterangan']}',
            '{$order['fd_tgl_mulai']}',
            '00:00:00',
            '{$order['fd_tgl_akhir']}',
            '23:59:59',
            CURDATE(),
            CURTIME(),
            '{$order['fs_kd_petugas_approved']}'
        )
    ");

    if (!$insert) {
        echo json_encode([
            "status" => false,
            "message" => mysqli_error($conn)
        ]);
        exit;
    }

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
