<?php
// Copy this file to db.php and fill in your credentials
$host = "localhost";
$user = "root";
$pass = "";
$db   = "geofencing_attendance";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    $conn = null;
}
$conn->set_charset("utf8mb4");
