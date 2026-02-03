<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/database.php";

$username = trim($_POST['username'] ?? '');
$judul_surat = trim($_POST['judul_surat'] ?? '');
$id_ditujukan_ke = $_POST['id_ditujukan_ke'] ?? '';

if ($username === '' || $judul_surat === '' || $id_ditujukan_ke === '') {
    echo json_encode([
        "status" => false,
        "message" => "Data wajib tidak lengkap"
    ]);
    exit;
}

/* decode array dari flutter */
$tujuanList = json_decode($id_ditujukan_ke, true);

if (!is_array($tujuanList) || count($tujuanList) == 0) {
    echo json_encode([
        "status" => false,
        "message" => "Tujuan surat tidak valid"
    ]);
    exit;
}

$tgl_surat = date('Y-m-d');
$tgl_jam_trs = date('Y-m-d H:i:s');

$nama_file = null;

if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === 0) {

    $ext = strtolower(pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png'];

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "status" => false,
            "message" => "Format file tidak diperbolehkan"
        ]);
        exit;
    }

    $folder = "../uploads/surat/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $nama_file = "surat_" . time() . "_" . rand(100,999) . "." . $ext;

    if (!move_uploaded_file($_FILES['file_surat']['tmp_name'], $folder.$nama_file)) {
        echo json_encode([
            "status" => false,
            "message" => "Gagal upload file"
        ]);
        exit;
    }
}

$conn->begin_transaction();

try {

    /* flag tujuan */
    $flag_tujuan = in_array('-1', $tujuanList) ? -1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO hrdm_surat
        (tgl_surat, tgl_jam_trs, judul_surat, nama_file, id_ditujukan_ke, user_input)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssss",
        $tgl_surat,
        $tgl_jam_trs,
        $judul_surat,
        $nama_file,
        $flag_tujuan,
        $username
    );

    $stmt->execute();
    $id_surat = $stmt->insert_id;

    /* SIMPAN PERORANGAN / MULTI */
    if ($flag_tujuan === 0) {
        $stmt2 = $conn->prepare("
            INSERT INTO hrdm_surat_ditujukan_ke (id_surat, kd_peg)
            VALUES (?, ?)
        ");

        foreach ($tujuanList as $kdPeg) {
            $stmt2->bind_param("is", $id_surat, $kdPeg);
            $stmt2->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        "status" => true,
        "message" => "Surat berhasil dikirim"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    if ($nama_file && file_exists("../uploads/surat/".$nama_file)) {
        unlink("../uploads/surat/".$nama_file);
    }

    echo json_encode([
        "status" => false,
        "message" => "Gagal menyimpan surat"
    ]);
}
