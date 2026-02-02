<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/database.php";


$BASE_URL = "http://localhost/api";
$UPLOAD_PATH = "$BASE_URL/uploads/surat/";

$kdPeg = trim($_GET['kdPeg'] ?? '');

if ($kdPeg === '') {
    echo json_encode([
        "status" => false,
        "message" => "Kode pegawai tidak ditemukan"
    ]);
    exit;
}

$sql = "
    SELECT DISTINCT
        s.id_surat,
        s.judul_surat,
        s.tgl_surat,
        s.tgl_jam_trs,
        s.nama_file,
        s.user_input
    FROM hrdm_surat s
    LEFT JOIN hrdm_surat_ditujukan_ke d
        ON s.id_surat = d.id_surat
    WHERE
        s.id_ditujukan_ke = '-1'
        OR d.kd_peg = ?
    ORDER BY s.tgl_jam_trs DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $kdPeg);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {

    $fileUrl = null;
    if (!empty($row['nama_file'])) {
        $fileUrl = $UPLOAD_PATH . $row['nama_file'];
    }

    $data[] = [
        "id_surat"    => $row['id_surat'],
        "judul_surat" => $row['judul_surat'],
        "tgl_surat"   => $row['tgl_surat'],
        "tgl_jam_trs" => $row['tgl_jam_trs'],
        "nama_file"   => $row['nama_file'],
        "file_url"    => $fileUrl,
        "user_input"  => $row['user_input'],
    ];
}

echo json_encode([
    "status" => true,
    "total"  => count($data),
    "data"   => $data
]);