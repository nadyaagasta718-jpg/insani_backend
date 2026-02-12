<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => false,
        "message" => "Method tidak dikenali, gunakan POST"
    ]);
    exit();
}

$kd_trs    = $_POST['fs_kd_trs'] ?? '';
$kd_atasan = $_POST['fs_kd_peg_atasan'] ?? '';
$action    = $_POST['action'] ?? '';

if (!$kd_trs || !$kd_atasan) {
    echo json_encode([
        "status" => false,
        "message" => "Parameter tidak lengkap"
    ]);
    exit();
}

if ($action === 'approve') {
    $sql = "
        UPDATE td_trs_order_cuti
        SET
            fb_approved = 1,
            fs_kd_petugas_approved = '$kd_atasan',
            fd_tgl_trs_approved = CURDATE(),
            fs_jam_trs_approved = CURTIME()
        WHERE fs_kd_trs = '$kd_trs'
          AND fs_kd_peg_atasan = '$kd_atasan'
          AND fb_approved = 0
          AND fb_ditolak = 0
    ";
    mysqli_query($conn, $sql);

    echo json_encode([
        "status" => true,
        "message" => "Cuti berhasil disetujui atasan"
    ]);
    exit();
}

if ($action === 'reject') {
    $alasan = $_POST['alasan'] ?? '';
    if (!$alasan) {
        echo json_encode([
            "status" => false,
            "message" => "Alasan wajib diisi"
        ]);
        exit();
    }

    $sql = "
        UPDATE td_trs_order_cuti
        SET
            fb_ditolak = 1,
            fs_alasan_ditolak = '$alasan',
            fs_kd_petugas_ditolak = '$kd_atasan',
            fd_tgl_trs_ditolak = CURDATE(),
            fs_jam_trs_ditolak = CURTIME()
        WHERE fs_kd_trs = '$kd_trs'
          AND fs_kd_peg_atasan = '$kd_atasan'
    ";
    mysqli_query($conn, $sql);

    echo json_encode([
        "status" => true,
        "message" => "Cuti berhasil ditolak atasan"
    ]);
    exit();
}

echo json_encode([
    "status" => false,
    "message" => "Action tidak dikenali, gunakan approve atau reject"
]);

$conn->close();
exit;
?>
