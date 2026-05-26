<?php
session_start();
include 'backend/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $_SESSION['reg_status'] = ['type' => 'warning', 'msg' => 'Please fill in all fields.'];
        header("Location: lecturer_register");
        exit;
    }

    // Check duplicate username
    $check = $conn->prepare("SELECT id FROM lecturers WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['reg_status'] = ['type' => 'error', 'msg' => 'Username already exists.'];
        header("Location: lecturer_register");
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt   = $conn->prepare("INSERT INTO lecturers (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed);

    if ($stmt->execute()) {
        $_SESSION['reg_status'] = ['type' => 'success', 'msg' => 'Account created! You can now log in.'];
        header("Location: index");
        exit;
    } else {
        $_SESSION['reg_status'] = ['type' => 'error', 'msg' => 'Registration failed. Please try again.'];
        header("Location: lecturer_register");
        exit;
    }
}

$statusData = null;
if (isset($_SESSION['reg_status'])) {
    $statusData = $_SESSION['reg_status'];
    unset($_SESSION['reg_status']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Registration - Attendance System</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/sweetalerts/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container outer">
      <div class="form-form">
        <div class="form-form-wrap">
          <div style="padding:40px 40px 30px;">
            <div style="text-align:center;margin-bottom:24px;">
                <img src="assets/img/logo.png" alt="Logo" style="height:70px;margin-bottom:12px;">
                <h4 style="font-size:1.15rem;font-weight:700;color:#1a202c;margin-bottom:4px;">Lecturer Registration</h4>
                <p style="font-size:0.83rem;color:#718096;">Create your lecturer account</p>
            </div>

            <form method="POST" action="lecturer_register">
                <div class="field-wrapper" style="position:relative;margin-bottom:18px;text-align:left;">
                    <label style="display:block;font-size:0.76rem;font-weight:700;letter-spacing:.06em;color:#4a5568;margin-bottom:7px;">USERNAME</label>
                    <input type="text" name="username" class="form-control"
                        style="padding:11px 16px;border:2px solid #e2e8f0;border-radius:8px;font-size:.95rem;width:100%;"
                        placeholder="Choose a username" required>
                </div>

                <div class="field-wrapper" style="position:relative;margin-bottom:18px;text-align:left;">
                    <label style="display:block;font-size:0.76rem;font-weight:700;letter-spacing:.06em;color:#4a5568;margin-bottom:7px;">PASSWORD</label>
                    <input type="password" name="password" class="form-control"
                        style="padding:11px 16px;border:2px solid #e2e8f0;border-radius:8px;font-size:.95rem;width:100%;"
                        placeholder="At least 6 characters" required minlength="6">
                </div>

                <button type="submit" style="width:100%;padding:12px;background:linear-gradient(135deg,#0066cc,#0052a3);color:#fff;border:none;border-radius:8px;font-size:.97rem;font-weight:700;cursor:pointer;margin-top:4px;">
                    Create Account
                </button>
            </form>

            <div style="text-align:center;margin-top:18px;font-size:.83rem;color:#718096;">
                Already have an account? <a href="index" style="color:#0066cc;font-weight:600;">Login here</a>
            </div>
          </div>

          <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:13px 40px;text-align:center;font-size:.8rem;color:#718096;">
            Smart Geofencing Attendance &mdash; &copy; 2026
          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/sweetalerts/sweetalert2.min.js"></script>
    <?php if ($statusData): ?>
    <script>
        Swal.fire({
            icon: '<?= $statusData['type'] ?>',
            title: '<?= $statusData['type'] === 'success' ? 'Success' : ($statusData['type'] === 'warning' ? 'Warning' : 'Error') ?>',
            text: '<?= addslashes($statusData['msg']) ?>',
            confirmButtonColor: '#0066cc'
        });
    </script>
    <?php endif; ?>
</body>
</html>
