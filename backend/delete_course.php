<?php
ob_start();
session_start();
include 'db.php';

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: ../index");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../lecturer_dashboard");
    exit;
}

$id     = (int)$_SESSION['lecturer_id'];
$course = trim($_POST['course_code'] ?? '');

if (empty($course)) {
    $_SESSION['status'] = "No course specified.";
    header("Location: ../lecturer_dashboard");
    exit;
}

// Verify ownership
$stmt = $conn->prepare("SELECT course_code FROM courses WHERE course_code = ? AND lecturer_id = ?");
$stmt->bind_param("si", $course, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['status'] = "Course not found or access denied.";
    header("Location: ../lecturer_dashboard");
    exit;
}

// Delete attendance records for this course
$stmt = $conn->prepare("DELETE FROM attendance WHERE course_code = ?");
$stmt->bind_param("s", $course);
$stmt->execute();

// Delete attendance sessions
$stmt = $conn->prepare("DELETE FROM attendance_sessions WHERE course_code = ? AND lecturer_id = ?");
$stmt->bind_param("si", $course, $id);
$stmt->execute();

// Delete the course
$stmt = $conn->prepare("DELETE FROM courses WHERE course_code = ? AND lecturer_id = ?");
$stmt->bind_param("si", $course, $id);
$stmt->execute();

$_SESSION['status'] = "Course '{$course}' and all its attendance data have been deleted.";
header("Location: ../lecturer_dashboard");
exit;
