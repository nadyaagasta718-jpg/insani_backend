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

$kdPeg = trim($_GET['kdPeg'] ?? '');
$role  = trim($_GET['role'] ?? 'pegawai');

if ($role === 'pegawai' && $kdPeg === '') {
    echo json_encode([
        "status" => false,
        "message" => "Kode pegawai tidak ditemukan"
    ]);
    exit;
}

$data = [];

try {

    
    if ($role === 'tu') {

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
            ORDER BY s.tgl_jam_trs DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

    }


    else {

        $sql = "
            SELECT DISTINCT
                s.id_surat,
                s.judul_surat,
                s.tgl_surat,
                s.tgl_jam_trs,
                s.nama_file,
                s.user_input,
                s.id_ditujukan_ke
            FROM hrdm_surat s
            LEFT JOIN hrdm_surat_ditujukan_ke d
                ON s.id_surat = d.id_surat
            WHERE
                s.id_ditujukan_ke = -1
                OR d.kd_peg = ?
            ORDER BY s.tgl_jam_trs DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $kdPeg);
        $stmt->execute();
        $result = $stmt->get_result();
    }


    while ($row = $result->fetch_assoc()) {

     
        if ($row['id_ditujukan_ke'] == -1) {
            $tujuan = ["SEMUA PEGAWAI"];
        } else {
            $tujuan = [];
            $q = $conn->prepare("
                SELECT kd_peg
                FROM hrdm_surat_ditujukan_ke
                WHERE id_surat = ?
            ");
            $q->bind_param("i", $row['id_surat']);
            $q->execute();
            $r = $q->get_result();
            while ($t = $r->fetch_assoc()) {
                $tujuan[] = $t['kd_peg'];
            }
        }

        $data[] = [
            "id_surat"     => $row['id_surat'],
            "judul_surat"  => $row['judul_surat'],
            "tgl_surat"    => $row['tgl_surat'],
            "tgl_jam_trs"  => $row['tgl_jam_trs'],
            "nama_file"    => $row['nama_file'],
            "user_input"   => $row['user_input'],
            "ditujukan_ke" => $tujuan
        ];
    }

    echo json_encode([
        "status" => true,
        "total"  => count($data),
        "data"   => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

