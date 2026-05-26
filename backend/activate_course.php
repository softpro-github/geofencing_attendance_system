<?php
ob_start();
session_start();
include 'db.php';

// Auth guard
if (!isset($_SESSION['lecturer_id'])) {
    header("Location: ../index");
    exit;
}

// This file only handles POST — redirect GET requests back to dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../lecturer_dashboard");
    exit;
}

// DB check
if (!$conn) {
    $_SESSION['status'] = "Database connection failed. Please try again.";
    header("Location: ../lecturer_dashboard");
    exit;
}

$lecturer_id = $_SESSION['lecturer_id'];
$course      = trim($_POST['course_code'] ?? '');
$lat         = $_POST['lat'] ?? '';
$lng         = $_POST['lng'] ?? '';
$radius      = intval($_POST['radius'] ?? 50);
$accuracy    = floatval($_POST['accuracy'] ?? 0);
$duration    = intval($_POST['duration'] ?? 0); // minutes; 0 = no limit

if (empty($course)) {
    $_SESSION['status'] = "Please select a course.";
    header("Location: ../lecturer_dashboard");
    exit;
}

$action = trim($_POST['session_action'] ?? '');

if ($action === 'activate') {
    if (empty($lat) || empty($lng)) {
        $_SESSION['status'] = "Location not captured. Please allow location access and try again.";
        header("Location: ../lecturer_dashboard");
        exit;
    }

    $stmt = $conn->prepare("SELECT department, level FROM courses WHERE course_code = ?");
    $stmt->bind_param("s", $course);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $_SESSION['status'] = "Course not found.";
        header("Location: ../lecturer_dashboard");
        exit;
    }

    $row        = $res->fetch_assoc();
    $department = $row['department'];
    $level      = $row['level'];

    $upd = $conn->prepare("UPDATE attendance_sessions SET status='inactive' WHERE course_code = ?");
    $upd->bind_param("s", $course);
    $upd->execute();

    if ($duration > 0) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
        $ins = $conn->prepare("INSERT INTO attendance_sessions (course_code, lecturer_id, status, expected_lat, expected_lng, accuracy, department, level, radius, started_at, expires_at)
                               VALUES (?, ?, 'active', ?, ?, ?, ?, ?, ?, NOW(), ?)");
        $ins->bind_param("sidddssis", $course, $lecturer_id, $lat, $lng, $accuracy, $department, $level, $radius, $expires_at);
    } else {
        $ins = $conn->prepare("INSERT INTO attendance_sessions (course_code, lecturer_id, status, expected_lat, expected_lng, accuracy, department, level, radius, started_at)
                               VALUES (?, ?, 'active', ?, ?, ?, ?, ?, ?, NOW())");
        $ins->bind_param("sidddssi", $course, $lecturer_id, $lat, $lng, $accuracy, $department, $level, $radius);
    }
    $ins->execute();

    $msg = "Course activated successfully!";
    if ($duration > 0) {
        $msg .= " Session closes at " . date('h:i A', strtotime("+{$duration} minutes")) . ".";
    }
    $_SESSION['status'] = $msg;

} elseif ($action === 'deactivate') {
    $upd = $conn->prepare("UPDATE attendance_sessions SET status='inactive' WHERE course_code = ?");
    $upd->bind_param("s", $course);
    $upd->execute();

    $_SESSION['status'] = "Course deactivated successfully!";

} else {
    $_SESSION['status'] = "Invalid action. Please use the Activate or Deactivate buttons.";
}

header("Location: ../lecturer_dashboard");
exit;
