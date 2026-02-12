<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $kd_atasan = $_GET['kd_peg'] ?? '';

    if ($kd_atasan == '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode atasan tidak boleh kosong"
        ]);
        exit;
    }

    $data = [];

    $sql = "
        SELECT
            o.fs_kd_trs,
            o.fs_kd_peg,
            p.fs_nm_peg,
            j.fs_nm_jenis_cuti,
            o.fd_tgl_mulai,
            o.fd_tgl_akhir,
            o.fs_keterangan
        FROM td_trs_order_cuti o
        JOIN td_peg p ON o.fs_kd_peg = p.fs_kd_peg
        JOIN td_jenis_cuti j ON o.fs_kd_jenis_cuti = j.fs_kd_jenis_cuti
        WHERE o.fs_kd_peg_atasan = '$kd_atasan'
          AND o.fb_approved = 0
          AND o.fb_ditolak = 0
        ORDER BY o.fd_tgl_trs DESC
    ";

    $query = mysqli_query($conn, $sql);

    if (!$query) {
        echo json_encode([
            "status" => false,
            "message" => "Query gagal",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => true,
        "data" => $data
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kd_trs_order = $_POST['fs_kd_trs'] ?? '';
    $kd_atasan    = $_POST['fs_kd_peg_atasan'] ?? '';
    $action       = $_POST['action'] ?? '';

    if ($kd_trs_order == '' || $kd_atasan == '') {
        echo json_encode([
            "status" => false,
            "message" => "Kode cuti dan kode atasan tidak boleh kosong"
        ]);
        exit;
    }

    $sql_order = "
        SELECT *
        FROM td_trs_order_cuti
        WHERE fs_kd_trs = '$kd_trs_order'
          AND fs_kd_peg_atasan = '$kd_atasan'
          AND fb_approved = 0
          AND fb_ditolak = 0
    ";

    $query_order = mysqli_query($conn, $sql_order);
    $order = mysqli_fetch_assoc($query_order);

    if (!$order) {
        echo json_encode([
            "status" => false,
            "message" => "Order cuti tidak ditemukan"
        ]);
        exit;
    }

    if ($action === 'approve') {
        mysqli_query($conn, "
            UPDATE td_trs_order_cuti
            SET 
                fb_approved = 1,
                fs_kd_petugas_approved = '$kd_atasan',
                fd_tgl_trs_approved = CURDATE(),
                fs_jam_trs_approved = CURTIME()
            WHERE fs_kd_trs = '$kd_trs_order'
              AND fs_kd_peg_atasan = '$kd_atasan'
              AND fb_approved = 0
              AND fb_ditolak = 0
        ");

        echo json_encode([
            "status" => true,
            "message" => "Cuti berhasil disetujui atasan"
        ]);
        exit;
    }

    if ($action === 'reject') {
        $alasan = $_POST['alasan'] ?? '';

        if ($alasan == '') {
            echo json_encode([
                "status" => false,
                "message" => "Alasan penolakan wajib diisi"
            ]);
            exit;
        }

        mysqli_query($conn, "
            UPDATE td_trs_order_cuti
            SET fb_ditolak = 1,
                fs_alasan_ditolak = '$alasan',
                fd_tgl_trs_ditolak = CURDATE(),
                fs_jam_trs_ditolak = CURTIME(),
                fs_kd_petugas_ditolak = '$kd_atasan'
            WHERE fs_kd_trs = '$kd_trs_order'
              AND fs_kd_peg_atasan = '$kd_atasan'
        ");

        echo json_encode([
            "status" => true,
            "message" => "Cuti berhasil ditolak"
        ]);
        exit;
    }

    echo json_encode([
        "status" => false,
        "message" => "Action tidak dikenali"
    ]);
    exit;
}

echo json_encode([
    "status" => false,
    "message" => "Method tidak dikenali"
]);

$conn->close();
exit;
?>
