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
