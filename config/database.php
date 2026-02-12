<?php

$host = "sql204.infinityfree.com";  
$user = "if0_41094572";            
$pass = "1ns4n1r51";       
$db   = "if0_41094572_db_insani";   


$conn = mysqli_connect($host, $user, $pass, $db);


mysqli_set_charset($conn, "utf8");

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Database connection error: " . mysqli_connect_error()
    ]);
    exit;
}
?>
