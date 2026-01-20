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

require_once "../config/database.php";

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "status" => false,
        "message" => "Username dan password wajib diisi"
    ]);
    exit;
}

$sql = "
SELECT
    u.username,
    u.password,
    TRIM(u.kd_peg)            AS kd_peg,
    p.fs_nm_peg               AS nm_peg,
    TRIM(p.fs_kd_lokasi)      AS kd_lokasi,
    IFNULL(l.fs_nm_lokasi,'') AS nm_lokasi,
    (
        SELECT COUNT(*)
        FROM td_peg x
        WHERE TRIM(x.fs_kd_peg_atasan) = TRIM(p.fs_kd_peg)
    ) AS jml_bawahan
FROM hrdm_user u
JOIN td_peg p ON TRIM(u.kd_peg) = TRIM(p.fs_kd_peg)
LEFT JOIN td_lokasi l ON TRIM(p.fs_kd_lokasi) = TRIM(l.fs_kd_lokasi)
WHERE u.username = ?
AND u.is_aktif = 1
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "status" => false,
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    echo json_encode([
        "status" => false,
        "message" => "Username atau password salah"
    ]);
    exit;
}

$stmt->bind_result(
    $username_db,
    $password_db,
    $kd_peg,
    $nm_peg,
    $kd_lokasi,
    $nm_lokasi,
    $jml_bawahan
);

$stmt->fetch();

if (strlen($password_db) === 32) {
    if (md5($password) !== $password_db) {
        echo json_encode(["status"=>false,"message"=>"Username atau password salah"]);
        exit;
    }
} else {
    if (!password_verify($password, $password_db)) {
        echo json_encode(["status"=>false,"message"=>"Username atau password salah"]);
        exit;
    }
}

if ($kd_lokasi === 'L003') {
    $role = 'HRD';
} elseif ($kd_lokasi === 'L009') {
    $role = 'TU';
} elseif ((int)$jml_bawahan > 0) {
    $role = 'ATASAN';
} else {
    $role = 'PEGAWAI';
}

echo json_encode([
    "status" => true,
    "message" => "Login berhasil",
    "data" => [
        "username" => $username_db,
        "kd_peg" => $kd_peg,
        "nm_peg" => $nm_peg,
        "kd_lokasi" => $kd_lokasi,
        "nm_lokasi" => $nm_lokasi,
        "role" => $role,
        "jml_bawahan" => (int)$jml_bawahan
    ]
]);

