<?php
header("Content-Type: application/json");
require_once __DIR__ . "/config/database.php";

if ($conn) {
    echo json_encode([
        "status" => true,
        "message" => "Database connected"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Database gagal connect"
    ]);
}
