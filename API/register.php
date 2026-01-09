<?php
error_reporting(0);
ini_set('display_errors', 0);



header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");


require_once "../config/database.php";


$username   = $_POST['username'] ?? '';
$password   = $_POST['password'] ?? '';
$fs_kd_peg  = $_POST['fs_kd_peg'] ?? '';

if ($username == '' || $password == '' || $fs_kd_peg == '') {
    echo json_encode([
        "status" => false,
        "message" => "Lengkapi data!"
    ]);
    exit;
}

$password_hash = md5($password);


$check_sql = "SELECT * FROM hrdm_user WHERE username='$username' LIMIT 1";
$check_q   = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_q) > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Username sudah terdaftar!"
    ]);
    exit;
}


$insert_sql = "INSERT INTO hrdm_user (kd_peg, username, password, is_aktif)
               VALUES ('$fs_kd_peg', '$username', '$password_hash', 1)";

if (mysqli_query($conn, $insert_sql)) {
    echo json_encode([
        "status" => true,
        "message" => "Register berhasil!"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Gagal register: " . mysqli_error($conn)
    ]);
}

