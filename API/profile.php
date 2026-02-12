<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

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

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $kd_peg = $_GET['kd_peg'] ?? '';

    if ($kd_peg === '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode pegawai kosong"
        ]);
        exit;
    }

    $sql = "
        SELECT 
            p.fs_nm_peg,
            l.fs_nm_lokasi,
            a.fs_nm_peg AS nm_atasan
        FROM td_peg p
        LEFT JOIN td_lokasi l 
            ON p.fs_kd_lokasi = l.fs_kd_lokasi
        LEFT JOIN td_peg a 
            ON p.fs_kd_peg_atasan = a.fs_kd_peg
        WHERE p.fs_kd_peg = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kd_peg);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "status" => true,
            "data" => [
                "nm_peg"    => $row['fs_nm_peg'],
                "nm_lokasi" => $row['fs_nm_lokasi'],
                "nm_atasan" => $row['nm_atasan'] ?? '-'
            ]
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Data pegawai tidak ditemukan"
        ]);
    }

    $stmt->close();
    exit;
}

if ($action === 'update_password') {
    $data = json_decode(file_get_contents("php://input"), true);

    $kd_peg   = trim($data['kd_peg'] ?? '');
    $password = $data['password'] ?? '';

    if ($kd_peg === '' || $password === '') {
        echo json_encode([
            "status" => false,
            "message" => "Data tidak lengkap"
        ]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE hrdm_user SET password = ? WHERE kd_peg = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hash, $kd_peg);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode([
            "status" => true,
            "message" => "Password berhasil diubah"
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Gagal update password"
        ]);
    }

    $stmt->close();
    exit;
}

echo json_encode([
    "status" => false,
    "message" => "Action tidak dikenali"
]);

$conn->close();
exit;
?>
