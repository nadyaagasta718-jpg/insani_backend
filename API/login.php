<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");


require_once "../config/database.php";


$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "status" => false,
        "message" => "Username dan password wajib diisi"
    ]);
    exit;
}


$password_hash = md5($password); 

$sql = "
SELECT 
    u.username,
    TRIM(u.kd_peg)        AS kd_peg,
    p.fs_nm_peg           AS nm_peg,
    TRIM(p.fs_kd_lokasi)  AS kd_lokasi,
    l.fs_nm_lokasi        AS nm_lokasi
FROM hrdm_user u
JOIN td_peg p ON TRIM(u.kd_peg) = TRIM(p.fs_kd_peg)
LEFT JOIN td_lokasi l ON TRIM(p.fs_kd_lokasi) = TRIM(l.fs_kd_lokasi)
WHERE 
    u.username = '$username'
    AND u.password = '$password_hash'
    AND u.is_aktif = 1
LIMIT 1
";

$query = mysqli_query($conn, $sql);

if (!$query) {
    echo json_encode([
        "status" => false,
        "message" => "Query error",
        "error" => mysqli_error($conn)
    ]);
    exit;
}


if (mysqli_num_rows($query) === 1) {
    $data = mysqli_fetch_assoc($query);

    
    if ($data['kd_lokasi'] === 'L002') {
        $role = 'HRD';
    } elseif ($data['kd_lokasi'] === 'L009') {
        $role = 'TU';
    } else {
        $role = 'PEGAWAI';
    }

    echo json_encode([
        "status" => true,
        "message" => "Login berhasil",
        "data" => [
            "username"   => $data['username'],
            "kd_peg"     => $data['kd_peg'],
            "nm_peg"     => $data['nm_peg'],    
            "kd_lokasi"  => $data['kd_lokasi'],
            "nm_lokasi"  => $data['nm_lokasi'],
            "role"       => $role
        ]
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Username atau password salah"
    ]);
}