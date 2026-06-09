<?php
ob_start();
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['status'] = "Invalid request.";
    header("Location: ../student_dashboard");
    exit;
}

if (!isset($_SESSION['matric_number'])) {
    $_SESSION['status'] = "You must be logged in to mark attendance.";
    header("Location: ../student_dashboard");
    exit;
}

$matric   = $_SESSION['matric_number'];
$course   = trim($_POST['course'] ?? '');
$lat      = floatval($_POST['lat'] ?? 0);
$lng      = floatval($_POST['lng'] ?? 0);
$accuracy = floatval($_POST['accuracy'] ?? 0);
$device   = substr($_POST['device'] ?? '', 0, 250);

if (empty($course)) {
    $_SESSION['status'] = "No course selected.";
    header("Location: ../student_dashboard");
    exit;
}

function haversineDistance($lat1, $lon1, $lat2, $lon2, $earthRadius = 6371000) {
    $latFrom   = deg2rad($lat1); $lonFrom = deg2rad($lon1);
    $latTo     = deg2rad($lat2); $lonTo   = deg2rad($lon2);
    $latDelta  = $latTo - $latFrom;
    $lonDelta  = $lonTo - $lonFrom;
    $angle     = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                 cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

// Get active session for this course
$stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE course_code = ? AND status = 'active'");
$stmt->bind_param("s", $course);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['status'] = "No active attendance session for this course.";
    header("Location: ../student_dashboard");
    exit;
}

$session           = $result->fetch_assoc();
$session_id        = $session['id'];
$expected_lat      = $session['expected_lat'];
$expected_lng      = $session['expected_lng'];
$expected_accuracy = $session['accuracy'];
$radius            = $session['radius'];

// Check if session has expired
if (!empty($session['expires_at']) && strtotime($session['expires_at']) <= time()) {
    $upd = $conn->prepare("UPDATE attendance_sessions SET status='inactive' WHERE id = ?");
    $upd->bind_param("i", $session_id);
    $upd->execute();
    $_SESSION['status'] = "This attendance session has expired.";
    header("Location: ../student_dashboard");
    exit;
}

$distance  = haversineDistance($lat, $lng, $expected_lat, $expected_lng);
$threshold = $accuracy + $expected_accuracy + 50;

if ($distance > $radius + $threshold) {
    $_SESSION['status'] = "You are too far from the class location. Distance: " . round($distance) . "m (Allowed: " . $radius . "m).";
    header("Location: ../student_dashboard");
    exit;
}

// Save face photo
$face_photo_path = null;
$face_data = $_POST['face_photo'] ?? '';
if (!empty($face_data) && preg_match('/^data:image\/(jpeg|png|webp);base64,/', $face_data, $m)) {
    $raw = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $face_data));
    if ($raw !== false && strlen($raw) > 1000) {
        $dir = __DIR__ . '/../uploads/faces/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $safe   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $matric);
        $fname  = $safe . '_' . $session_id . '_' . time() . '.jpg';
        if (file_put_contents($dir . $fname, $raw) !== false) {
            $face_photo_path = 'uploads/faces/' . $fname;
        }
    }
}

// Duplicate attendance check
$stmt = $conn->prepare("SELECT id FROM attendance WHERE matric_number = ? AND course_code = ? AND session_id = ?");
$stmt->bind_param("ssi", $matric, $course, $session_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['status'] = "You have already marked attendance for this course.";
    header("Location: ../student_dashboard");
    exit;
}

// Device/IP duplicate check
$ip   = $_SERVER['REMOTE_ADDR'];
$stmt = $conn->prepare("SELECT id FROM attendance WHERE ip_address = ? AND course_code = ? AND session_id = ?");
$stmt->bind_param("ssi", $ip, $course, $session_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['status'] = "Attendance has already been marked from this device/IP.";
    header("Location: ../student_dashboard");
    exit;
}

// Save attendance
$stmt = $conn->prepare("INSERT INTO attendance (matric_number, course_code, session_id, timestamp, latitude, longitude, accuracy, device, ip_address, distance, face_photo)
                        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    $_SESSION['status'] = "Database error. Please try again.";
    header("Location: ../student_dashboard");
    exit;
}

$stmt->bind_param("ssidddssds", $matric, $course, $session_id, $lat, $lng, $accuracy, $device, $ip, $distance, $face_photo_path);

if ($stmt->execute()) {
    $_SESSION['status'] = "Attendance marked successfully.";
} else {
    $_SESSION['status'] = "Failed to save attendance. Please try again.";
}

header("Location: ../student_dashboard");
exit;
