<?php
session_start();
include 'db.php';

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: ../index");
    exit;
}

$id     = (int)$_SESSION['lecturer_id'];
$course = trim($_GET['course'] ?? '');
$date   = trim($_GET['date']   ?? '');

if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';

// Validate course belongs to this lecturer
if ($course) {
    $chk = $conn->prepare("SELECT 1 FROM courses WHERE lecturer_id = ? AND course_code = ? LIMIT 1");
    $chk->bind_param("is", $id, $course);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) $course = '';
}

$parts = ['session_history'];
if ($course) $parts[] = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course);
if ($date)   $parts[] = $date;
$filename = implode('_', $parts) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel

fputcsv($out, [
    'Course Code', 'Department', 'Level',
    'Session Date', 'Session Time', 'Session Status',
    'Student Matric', 'Student Name', 'Time Marked', 'Distance (m)',
]);

// Build dynamic query
$where  = "WHERE s.lecturer_id = ?";
$types  = "i";
$params = [$id];

if ($course) { $where .= " AND s.course_code = ?";        $types .= "s"; $params[] = $course; }
if ($date)   { $where .= " AND DATE(s.started_at) = ?";   $types .= "s"; $params[] = $date;   }

$stmt = $conn->prepare("
    SELECT s.course_code, s.department, s.level, s.started_at, s.status,
           a.matric_number, st.name, a.timestamp, ROUND(a.distance) as distance
    FROM attendance_sessions s
    LEFT JOIN attendance a  ON s.id = a.session_id
    LEFT JOIN students   st ON a.matric_number = st.matric_number
    $where
    ORDER BY s.started_at DESC, a.timestamp ASC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $sdt = new DateTime($row['started_at']);
    $adt = $row['timestamp'] ? new DateTime($row['timestamp']) : null;

    fputcsv($out, [
        $row['course_code'],
        $row['department'],
        $row['level'],
        $sdt->format('Y-m-d'),
        $sdt->format('H:i:s'),
        $row['status'],
        $row['matric_number'] ?? '',
        $row['name']          ?? '',
        $adt ? $adt->format('H:i:s') : '',
        $row['distance']      ?? '',
    ]);
}

fclose($out);
