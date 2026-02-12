<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
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

$stmt = $conn->prepare($sql);

if(!$stmt){
    echo json_encode([
        "status" => false,
        "message" => "Prepare statement gagal: ". $conn->error
    ]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $data = $row;
}

$stmt->close();

echo json_encode([
    "status" => true,
    "data"   => $data
]);
?>
