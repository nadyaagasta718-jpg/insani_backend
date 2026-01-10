<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

include "../config/database.php"; 

$query = mysqli_query($conn, "SELECT fs_kd_peg, fs_nm_peg FROM td_peg");
$data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = [
        'kd_peg' => $row['fs_kd_peg'],
        'nama'   => $row['fs_nm_peg']
    ];
}

echo json_encode([
    'status' => true,
    'data'   => $data
]);
?>