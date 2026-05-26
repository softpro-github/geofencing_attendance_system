<?php
ob_start(); // buffer any stray output (warnings, notices, db errors)
session_start();

function json_out($data) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'message' => 'Invalid request.']);
}

$role       = trim($_POST['role'] ?? '');
$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

if (!in_array($role, ['student', 'lecturer'])) {
    json_out(['success' => false, 'message' => 'Invalid role selected.']);
}

if (!$identifier || !$password) {
    json_out(['success' => false, 'message' => 'Please fill in all fields.']);
}

// Include db after validation so errors are caught cleanly
include 'db.php';

if (!isset($conn) || $conn->connect_error) {
    json_out(['success' => false, 'message' => 'Database connection failed. Contact the administrator.']);
}

if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE matric_number = ?");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        json_out(['success' => false, 'message' => 'Student not found. Check your matric number.']);
    }

    $user = $res->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        json_out(['success' => false, 'message' => 'Incorrect password.']);
    }

    $_SESSION['matric_number'] = $user['matric_number'];
    $_SESSION['student_name']  = $user['name'];
    json_out(['success' => true, 'redirect' => 'student_dashboard']);

} else {
    $stmt = $conn->prepare("SELECT * FROM lecturers WHERE username = ?");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        json_out(['success' => false, 'message' => 'Lecturer account not found.']);
    }

    $user = $res->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        json_out(['success' => false, 'message' => 'Incorrect password.']);
    }

    $_SESSION['lecturer_id']       = $user['id'];
    $_SESSION['lecturer_username'] = $user['username'];
    json_out(['success' => true, 'redirect' => 'lecturer_dashboard']);
}
