<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "insani"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["status"=>false,"message"=>"DB error"]);
    exit;
} 



