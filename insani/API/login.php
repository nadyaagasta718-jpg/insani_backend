<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

require_once "../config/database.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username == '' || $password == '') {
    echo json_encode([
        "status" => false,
        "message" => "Username & password wajib"
    ]);
    exit;
}

$password_hash = md5($password);

$sql = "
SELECT 
    u.username,
    u.kd_peg,
    p.fs_nm_peg,
    p.fs_kd_lokasi,
    l.fs_nm_lokasi
FROM hrdm_user u
JOIN td_peg p ON u.kd_peg = p.fs_kd_peg
JOIN td_lokasi l ON p.fs_kd_lokasi = l.fs_kd_lokasi
WHERE u.username='$username'
AND u.password='$password_hash'
AND u.is_aktif = 1
LIMIT 1
";

$q = mysqli_query($conn, $sql);

if (mysqli_num_rows($q) == 1) {
    $data = mysqli_fetch_assoc($q);

   
    $lokasi = $data['fs_kd_lokasi'];

    if ($lokasi == 'L002') {
        $role = 2; // HRD
    } elseif ($lokasi == 'L009') {
        $role = 3; // TU
    } else {
        $role = 1; // PEGAWAI
    }

    echo json_encode([
        "status" => true,
        "message" => "Login berhasil",
        "data" => [
            "username" => $data['username'],
            "kd_peg"   => $data['kd_peg'],
            "nama"     => $data['fs_nm_peg'],
            "lokasi"   => $data['fs_nm_lokasi'],
            "kd_lokasi"=> $lokasi,
            "role"     => $role
        ]
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Username / password salah"
    ]);
}