<?php
ob_start();
session_start();
include 'db.php';

ob_end_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$name           = trim($_POST['name'] ?? '');
$matric_number  = trim($_POST['matric_number'] ?? '');
$department     = trim($_POST['department'] ?? '');
$level          = trim($_POST['level'] ?? '');
$password_raw   = $_POST['password'] ?? '';

if (!$name || !$matric_number || !$department || !$level || !$password_raw) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (strlen($password_raw) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// Check for duplicate matric number
$check = $conn->prepare("SELECT id FROM students WHERE matric_number = ?");
$check->bind_param("s", $matric_number);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A student with this matric number already exists.']);
    exit;
}

$password = password_hash($password_raw, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO students (name, matric_number, department, level, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $matric_number, $department, $level, $password);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
