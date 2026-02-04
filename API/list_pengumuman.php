<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config/database.php";

$data = [];

$sql = "
    SELECT
        id_surat,
        judul_surat,
        tgl_surat,
        tgl_jam_trs,
        nama_file,
        user_input
    FROM hrdm_surat
    WHERE id_ditujukan_ke = -1
    ORDER BY tgl_jam_trs DESC
    LIMIT 1
";

$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    $data = $row;
}

echo json_encode([
    "status" => true,
    "data" => $data
]);