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
$UPLOAD_PATH = $BASE_URL . "/uploads/surat/";

$kdPeg = trim($_GET['kdPeg'] ?? '');

if ($kdPeg === '') {
    echo json_encode([
        "status" => false,
        "message" => "Kode pegawai wajib diisi"
    ]);
    exit;
}

$sql = "
  SELECT
    s.id_surat,
    s.judul_surat,
    s.tgl_surat,
    s.tgl_jam_trs,
    s.nama_file,
    s.user_input,
    s.id_ditujukan_ke
FROM hrdm_surat s
WHERE
    s.id_ditujukan_ke = -1
    OR EXISTS (
        SELECT 1
        FROM hrdm_surat_ditujukan_ke d
        WHERE d.id_surat = s.id_surat
          AND CAST(d.kd_peg AS CHAR) = ?
    )
ORDER BY s.tgl_jam_trs DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $kdPeg);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {

    if ($row['id_ditujukan_ke'] == -1) {
        $tujuan = ["Semua Pegawai"];
    } else {
        $tujuan = [];

        $q = $conn->prepare("
            SELECT kd_peg
            FROM hrdm_surat_ditujukan_ke
            WHERE id_surat = ?
        ");
        $q->bind_param("i", $row['id_surat']);
        $q->execute();
        $res = $q->get_result();

        while ($t = $res->fetch_assoc()) {
            $tujuan[] = $t['kd_peg'];
        }
    }

    $fileUrl = null;
    if (!empty($row['nama_file'])) {
        $fileUrl = $UPLOAD_PATH . $row['nama_file'];
    }

    $data[] = [
        "id_surat" => $row['id_surat'],
        "judul_surat" => $row['judul_surat'],
        "tgl_surat" => $row['tgl_surat'],
        "tgl_jam_trs" => $row['tgl_jam_trs'],
        "dari" => $row['user_input'],
        "ditujukan_ke" => $tujuan,
        "file_url" => $fileUrl
    ];
}

echo json_encode([
    "status" => true,
    "total" => count($data),
    "data" => $data
]);


  