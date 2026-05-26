<?php
session_start();
include 'db.php';

if (!isset($_SESSION['matric_number'])) {
    header("Location: ../index");
    exit;
}

$matric = trim($_SESSION['matric_number']);
$course = trim($_GET['course'] ?? '');
$date   = trim($_GET['date']   ?? '');

if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';

// Validate course belongs to this student
if ($course) {
    $chk = $conn->prepare("SELECT 1 FROM attendance WHERE matric_number = ? AND course_code = ? LIMIT 1");
    $chk->bind_param("ss", $matric, $course);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) $course = '';
}

$safe   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $matric);
$parts  = ['attendance_history', $safe];
if ($course) $parts[] = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course);
if ($date)   $parts[] = $date;
$filename = implode('_', $parts) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel

fputcsv($out, ['Matric Number', 'Course Code', 'Date', 'Time', 'Distance (m)']);

// Build dynamic query
$where  = "WHERE matric_number = ?";
$types  = "s";
$params = [$matric];

if ($course) { $where .= " AND course_code = ?";        $types .= "s"; $params[] = $course; }
if ($date)   { $where .= " AND DATE(timestamp) = ?";    $types .= "s"; $params[] = $date;   }

$stmt = $conn->prepare("
    SELECT course_code, timestamp, ROUND(distance) as distance
    FROM attendance
    $where
    ORDER BY timestamp DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $dt = new DateTime($row['timestamp']);
    fputcsv($out, [
        $matric,
        $row['course_code'],
        $dt->format('Y-m-d'),
        $dt->format('H:i:s'),
        $row['distance'],
    ]);
}

fclose($out);
