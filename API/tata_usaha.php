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
$id_ditujukan_ke = trim($_POST['id_ditujukan_ke'] ?? '');

if ($username === '' || $judul_surat === '' || $id_ditujukan_ke === '') {
    echo json_encode([
        "status" => false,
        "message" => "Data wajib tidak lengkap"
    ]);
    exit;
}

$tgl_surat = date('Y-m-d');
$tgl_jam_trs = date('Y-m-d H:i:s');

$nama_file = null;

if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === 0) {

    $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
    $allowed = ['pdf'];

    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode([
            "status" => false,
            "message" => "Hanya file PDF yang diperbolehkan"
        ]);
        exit;
    }

    $folder = "../uploads/surat/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $nama_file = "surat_" . time() . "_" . rand(100,999) . "." . $ext;
    $path = $folder . $nama_file;

    if (!move_uploaded_file($_FILES['file_surat']['tmp_name'], $path)) {
        echo json_encode([
            "status" => false,
            "message" => "Gagal menyimpan file ke server"
        ]);
        exit;
    }
}


$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        INSERT INTO hrdm_surat
        (tgl_surat, tgl_jam_trs, judul_surat, nama_file, user_input)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssss",
        $tgl_surat,
        $tgl_jam_trs,
        $judul_surat,
        $nama_file,
        $username
    );

    $stmt->execute();
    $id_surat = $stmt->insert_id;

 
    if ($id_ditujukan_ke === '-1') {

        $q = $conn->query("SELECT fs_kd_peg FROM td_peg");
        while ($r = $q->fetch_assoc()) {

            $stmt2 = $conn->prepare("
                INSERT INTO hrdm_surat_ditujukan_ke
                (id_surat, kd_peg)
                VALUES (?, ?)
            ");

            $stmt2->bind_param("is", $id_surat, $r['fs_kd_peg']);
            $stmt2->execute();
        }

    } else {

        $stmt2 = $conn->prepare("
            INSERT INTO hrdm_surat_ditujukan_ke
            (id_surat, kd_peg)
            VALUES (?, ?)
        ");

        $stmt2->bind_param("is", $id_surat, $id_ditujukan_ke);
        $stmt2->execute();
    }

    $conn->commit();

    echo json_encode([
        "status" => true,
        "message" => "Surat berhasil dikirim"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    if ($nama_file && file_exists("../uploads/surat/" . $nama_file)) {
        unlink("../uploads/surat/" . $nama_file);
    }

    echo json_encode([
        "status" => false,
        "message" => "Gagal menyimpan surat",
        "error" => $e->getMessage()
    ]);
}