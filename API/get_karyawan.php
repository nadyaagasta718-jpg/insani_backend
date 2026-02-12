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

$query = "SELECT fs_kd_peg, fs_nm_peg FROM td_peg";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Query error",
        "error" => mysqli_error($conn)
    ]);
    $conn->close();
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        "kd_peg" => trim($row['fs_kd_peg']),
        "nama"   => trim($row['fs_nm_peg'])
    ];
}

echo json_encode([
    "status" => true,
    "data" => $data
]);

$conn->close();
exit;
?>
