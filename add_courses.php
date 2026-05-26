<?php
session_start();
include 'backend/db.php';

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: index");
    exit;
}

$id = $_SESSION['lecturer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCourseBtn'])) {
    $course     = trim($_POST['course_code'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $level      = trim($_POST['level'] ?? '');

    if (empty($course) || empty($department) || empty($level)) {
        $_SESSION['status'] = ['type' => 'error', 'msg' => 'Please fill in all course details.'];
        header("Location: add_courses");
        exit;
    }

    $check = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
    $check->bind_param("s", $course);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['status'] = ['type' => 'error', 'msg' => 'Course code already exists.'];
        header("Location: add_courses");
        exit;
    }

    $ins = $conn->prepare("INSERT INTO courses (lecturer_id, course_code, department, level) VALUES (?, ?, ?, ?)");
    $ins->bind_param("isss", $id, $course, $department, $level);

    if ($ins->execute()) {
        $_SESSION['status'] = ['type' => 'success', 'msg' => 'Course added successfully.'];
    } else {
        $_SESSION['status'] = ['type' => 'error', 'msg' => 'Failed to add course. Please try again.'];
    }
    header("Location: add_courses");
    exit;
}

$statusData = null;
if (isset($_SESSION['status'])) {
    $statusData = $_SESSION['status'];
    unset($_SESSION['status']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Courses - Attendance System</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/sweetalerts/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <div class="navbar">
            <h2>Add Course</h2>
            <div class="nav-actions">
                <a class="btn btn-secondary btn-small" href="lecturer_dashboard">
                    &larr; Dashboard
                </a>
                <a class="btn btn-danger btn-small" href="backend/admin_logout">Logout</a>
            </div>
        </div>

        <div class="container" style="max-width:500px;">
            <h3>Add Course for Attendance</h3>

            <form method="POST" action="add_courses">
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="course_code" class="form-control"
                        placeholder="e.g. COM214" required>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department" class="form-control" required>
                        <option value="">Choose Department</option>
                        <option value="Computer Sci.">Computer Science</option>
                        <option value="Mass Comm.">Mass Communication</option>
                        <option value="Statistics">Statistics</option>
                        <option value="OTM">Office Technology &amp; Management</option>
                        <option value="Library & Info. Sci.">Library &amp; Information Science</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Academic Level</label>
                    <select name="level" class="form-control" required>
                        <option value="">Choose Level</option>
                        <option value="ND1">ND Year 1</option>
                        <option value="ND2">ND Year 2</option>
                        <option value="HND1">HND Year 1</option>
                        <option value="HND2">HND Year 2</option>
                    </select>
                </div>

                <button type="submit" name="addCourseBtn" class="btn btn-primary btn-block">
                    Add Course
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/sweetalerts/sweetalert2.min.js"></script>
    <?php if ($statusData): ?>
    <script>
        Swal.fire({
            icon: '<?= $statusData['type'] ?>',
            title: '<?= $statusData['type'] === 'success' ? 'Success' : 'Error' ?>',
            text: '<?= addslashes($statusData['msg']) ?>',
            confirmButtonColor: '#0066cc'
        });
    </script>
    <?php endif; ?>
</body>
</html>
