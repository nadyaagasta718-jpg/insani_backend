<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


if (!isset($_SESSION['username'])) {
    echo json_encode([
        "status" => false,
        "message" => "Belum login"
    ]);
    exit;
}

$user_input = $_SESSION['username'];


$judul_surat     = isset($_POST['judul_surat']) ? trim($_POST['judul_surat']) : '';
$id_ditujukan_ke = isset($_POST['id_ditujukan_ke']) ? $_POST['id_ditujukan_ke'] : '';

$tgl_surat   = date('Y-m-d');
$tgl_jam_trs = date('Y-m-d H:i:s');

if ($judul_surat == '' || $id_ditujukan_ke == '') {
    echo json_encode([
        "status" => false,
        "message" => "Data belum lengkap"
    ]);
    exit;
}


$nama_file = null;

if (isset($_FILES['file_surat'])) {
    
    $nama_file = $_FILES['file_surat']['name'];
}

echo json_encode([
    "status" => true,
    "message" => "Alur kirim surat siap",
    "data" => [
        "judul_surat"     => $judul_surat,
        "id_ditujukan_ke" => $id_ditujukan_ke,
        "nama_file"       => $nama_file,
        "tgl_surat"       => $tgl_surat,
        "user_input"      => $user_input
    ]
]);
