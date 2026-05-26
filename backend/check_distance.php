<?php
ob_start();
include 'db.php';
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_GET['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$course_code = trim($_GET['course_code']);

$stmt = $conn->prepare("SELECT expected_lat, expected_lng, radius, expires_at FROM attendance_sessions WHERE course_code = ? AND status = 'active'");
$stmt->bind_param("s", $course_code);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    // Auto-expire if time has passed
    if ($row['expires_at'] !== null && strtotime($row['expires_at']) <= time()) {
        $upd = $conn->prepare("UPDATE attendance_sessions SET status='inactive' WHERE course_code = ? AND status='active'");
        $upd->bind_param("s", $course_code);
        $upd->execute();
        echo json_encode(['success' => false, 'message' => 'This session has expired.']);
        exit;
    }

    echo json_encode([
        'success'      => true,
        'expected_lat' => floatval($row['expected_lat']),
        'expected_lng' => floatval($row['expected_lng']),
        'radius'       => floatval($row['radius']),
        'expires_at'   => $row['expires_at'] // datetime string or null
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No active session found for this course.']);
}
