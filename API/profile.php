<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/database.php";

$kd_peg = trim($_GET['kd_peg'] ?? '');

if ($kd_peg == '') {
    echo json_encode([
        "status" => false,
        "message" => "Kode pegawai tidak ditemukan"
    ]);
    exit;
}

$sql = "
SELECT 
    p.fs_kd_peg,
    p.fs_nm_peg,
    p.fs_kd_peg_atasan,
    l.fs_nm_lokasi,
    a.fs_nm_peg AS nm_atasan
FROM td_peg p
LEFT JOIN td_lokasi l 
    ON p.fs_kd_lokasi = l.fs_kd_lokasi
LEFT JOIN td_peg a 
    ON p.fs_kd_peg_atasan = a.fs_kd_peg
WHERE p.fs_kd_peg = '$kd_peg'
LIMIT 1
";

$q = mysqli_query($conn, $sql);

if (!$q) {
    echo json_encode([
        "status" => false,
        "message" => "Query error",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

if (mysqli_num_rows($q) == 1) {
    $data = mysqli_fetch_assoc($q);

    echo json_encode([
        "status" => true,
        "data" => [
            "kd_peg"        => $data['fs_kd_peg'],
            "nm_peg"        => $data['fs_nm_peg'],
            "nm_lokasi"     => $data['fs_nm_lokasi'],
            "kd_atasan"     => $data['fs_kd_peg_atasan'], 
            "nm_atasan"     => $data['nm_atasan'] ?? '-' 
        ]
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Data pegawai tidak ditemukan"
    ]);
}