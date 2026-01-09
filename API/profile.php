<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "../config/database.php";

$action = $_GET['action'] ?? '';


if ($action == 'get') {
    $kd_peg = $_GET['kd_peg'] ?? '';

    if ($kd_peg == '') {
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
    WHERE p.fs_kd_peg = '$kd_peg'
    LIMIT 1
    ";

    $q = mysqli_query($conn, $sql);

    if (mysqli_num_rows($q) == 1) {
        $d = mysqli_fetch_assoc($q);

        echo json_encode([
            "status" => true,
            "data" => [
                "nm_peg"    => $d['fs_nm_peg'],
                "nm_lokasi" => $d['fs_nm_lokasi'],
                "nm_atasan" => $d['nm_atasan'] ?? '-'
            ]
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Data pegawai tidak ditemukan"
        ]);
    }
    exit;
}


if ($action == 'update_password') {

    $data = json_decode(file_get_contents("php://input"), true);

    $kd_peg   = $data['kd_peg'] ?? '';
    $password = $data['password'] ?? '';

    if ($kd_peg == '' || $password == '') {
        echo json_encode([
            "status" => false,
            "message" => "Data tidak lengkap"
        ]);
        exit;
    }

   
    $hash = md5($password);

    $sql = "UPDATE hrdm_user SET password='$hash' WHERE kd_peg='$kd_peg'";
    $q = mysqli_query($conn, $sql);

    if ($q) {
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
    exit;
}



echo json_encode([
    "status" => false,
    "message" => "Action tidak dikenali"
]);