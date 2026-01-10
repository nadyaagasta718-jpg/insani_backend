<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");


require_once "../config/database.php";


$action = $_GET['action'] ?? '';


if ($action === 'get') {

    $kd_peg = $_GET['kd_peg'] ?? '';

    if (empty($kd_peg)) {
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

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $kd_peg);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
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

    mysqli_stmt_close($stmt);
    exit;
}


if ($action === 'update_password') {

    $data = json_decode(file_get_contents("php://input"), true);

    $kd_peg   = $data['kd_peg'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($kd_peg) || empty($password)) {
        echo json_encode([
            "status" => false,
            "message" => "Data tidak lengkap"
        ]);
        exit;
    }


    $hash = md5($password);

    $sql = "UPDATE hrdm_user SET password = ? WHERE kd_peg = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $hash, $kd_peg);
    $success = mysqli_stmt_execute($stmt);

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

    mysqli_stmt_close($stmt);
    exit;
}


echo json_encode([
    "status" => false,
    "message" => "Action tidak dikenali"
]);
exit;
