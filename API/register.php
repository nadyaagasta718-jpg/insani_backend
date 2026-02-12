<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$servername = "sql204.infinityfree.com";
$usernameDB = "if0_41094572";
$passwordDB = "1ns4n1r51";
$dbname     = "if0_41094572_db_insani";


$conn = new mysqli($servername, $usernameDB, $passwordDB, $dbname);


if ($conn->connect_error) {
    echo json_encode([
        "status" => false,
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}


$username  = trim($_POST['username'] ?? '');
$password  = $_POST['password'] ?? '';
$fs_kd_peg = trim($_POST['fs_kd_peg'] ?? '');


if ($username === '' || $password === '' || $fs_kd_peg === '') {
    echo json_encode([
        "status" => false,
        "message" => "Lengkapi data!"
    ]);
    exit;
}


$stmt = $conn->prepare("SELECT 1 FROM hrdm_user WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Username sudah terdaftar!"
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);


$stmt = $conn->prepare("INSERT INTO hrdm_user (kd_peg, username, password, is_aktif) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sss", $fs_kd_peg, $username, $password_hash);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Register berhasil!"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Gagal register: " . $stmt->error
    ]);
}


$stmt->close();
$conn->close();
exit;
?>
