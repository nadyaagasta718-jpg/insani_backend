<?php
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

$username = trim($_POST['username'] ?? '');
$judul_surat = trim($_POST['judul_surat'] ?? '');
$id_ditujukan_ke = trim($_POST['id_ditujukan_ke'] ?? '');

$tgl_surat = date('Y-m-d');
$tgl_jam_trs = date('Y-m-d H:i:s');

if ($username === '') {
    echo json_encode([
        "status" => false,
        "tahap" => "login",
        "message" => "Belum Login"
    ]);
    exit;
}

if ($judul_surat === '' || $id_ditujukan_ke === '') {
    echo json_encode([
        "status" => false,
        "tahap" => "validasi",
        "message" => "Judul surat dan tujuan wajib diisi"
    ]);
    exit;
}

$nama_file = null;
$upload_file = false;

if (!empty($_FILES['file_surat']['name'])) {
    $folder_upload = "../uploads/surat/";

    if (!is_dir($folder_upload)) {
        mkdir($folder_upload, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "status" => false,
            "tahap" => "upload",
            "message" => "Tipe file tidak diizinkan"
        ]);
        exit;
    }

    if ($_FILES['file_surat']['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            "status" => false,
            "tahap" => "upload",
            "message" => "Ukuran file maksimal 5MB"
        ]);
        exit;
    }

    $nama_file = "surat_" . time() . "." . $ext;

    if (!move_uploaded_file(
        $_FILES['file_surat']['tmp_name'],
        $folder_upload . $nama_file
    )) {
        echo json_encode([
            "status" => false,
            "tahap" => "upload",
            "message" => "Gagal upload file"
        ]);
        exit;
    }

    $upload_file = true;
}

echo json_encode([
    "status" => true,
    "tahap" => "sukses",
    "message" => "Surat berhasil dikirim",
    "data" => [
        "judul_surat"     => $judul_surat,
        "id_ditujukan_ke" => $id_ditujukan_ke,
        "upload_file"     => $upload_file,
        "nama_file"       => $nama_file,
        "tgl_surat"       => $tgl_surat,
        "tgl_jam_trs"     => $tgl_jam_trs,
        "user_input"      => $username
    ]
]);
