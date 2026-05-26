<?php
ob_start();
session_start();
include 'db.php';
ob_end_clean();

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: ../index");
    exit;
}

$code = trim($_POST['course_code'] ?? '');

if (empty($code)) {
    $_SESSION['status'] = "Please select a course to export.";
    header("Location: ../lecturer_dashboard");
    exit;
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"attendance_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code) . ".xls\"");

$stmt = $conn->prepare("SELECT * FROM attendance WHERE course_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

echo "Matric Number\tLatitude\tLongitude\tDevice\tTimestamp\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['matric_number']}\t{$row['latitude']}\t{$row['longitude']}\t{$row['device']}\t{$row['timestamp']}\n";
}
