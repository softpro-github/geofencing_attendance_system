<?php
session_start();
include 'backend/db.php';

if (!isset($_SESSION['matric_number'])) {
    header("Location: index");
    exit;
}

$matric = $_SESSION['matric_number'];

// Fetch student using prepared statement
$stmt = $conn->prepare("SELECT * FROM students WHERE matric_number = ?");
$stmt->bind_param("s", $matric);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    session_destroy();
    header("Location: index");
    exit;
}

$student        = $result->fetch_assoc();
$student_name   = $student['name'];
$student_mat_no = $student['matric_number'];
$student_depart = $student['department'];
$student_level  = $student['level'];

// Total attended
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE matric_number = ?");
$stmt->bind_param("s", $matric);
$stmt->execute();
$totalAttended = $stmt->get_result()->fetch_assoc()['total'];

// Auto-deactivate any expired sessions before querying
$conn->query("UPDATE attendance_sessions SET status='inactive' WHERE status='active' AND expires_at IS NOT NULL AND expires_at <= NOW()");

// Active courses count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance_sessions WHERE status = 'active' AND department = ? AND level = ?");
$stmt->bind_param("ss", $student_depart, $student_level);
$stmt->execute();
$activeCount = $stmt->get_result()->fetch_assoc()['total'];

// Active courses list
$stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE status = 'active' AND department = ? AND level = ?");
$stmt->bind_param("ss", $student_depart, $student_level);
$stmt->execute();
$activeCourses = $stmt->get_result();

$statusMessage = null;
$messageType   = 'info';
if (isset($_SESSION['status'])) {
    $statusMessage = $_SESSION['status'];
    $messageType   = (strpos($statusMessage, 'successfully') !== false || strpos($statusMessage, 'success') !== false) ? 'success' : 'danger';
    unset($_SESSION['status']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Attendance System</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="plugins/sweetalerts/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
      .student-info {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white; padding: 25px; border-radius: 10px; margin-bottom: 30px;
      }
      .info-item { display: flex; align-items: center; margin-bottom: 12px; }
      .info-item i { font-size: 1.1rem; margin-right: 14px; min-width: 22px; }

      .course-card {
        background: white; border: 2px solid var(--border-color);
        border-radius: 10px; padding: 18px; margin-bottom: 12px;
        cursor: pointer; transition: all 0.25s ease;
      }
      .course-card:hover { border-color: var(--primary); box-shadow: var(--shadow-md); transform: translateY(-2px); }
      .course-card.selected { background: #f0f7ff; border-color: var(--primary); }
      .course-code { font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
      .course-info { font-size: 0.88rem; color: var(--text-light); }

      .attendance-form { background: white; padding: 25px; border-radius: 10px; box-shadow: var(--shadow-md); }
      .attendance-form h3 { color: var(--primary); margin-bottom: 20px; }

      .location-badge {
        display: inline-block; padding: 8px 16px; border-radius: 20px;
        font-size: 0.85rem; font-weight: 600; margin-top: 15px;
      }
      .location-badge.success { background: #d4edda; color: #155724; }
      .location-badge.error   { background: #f8d7da; color: #721c24; }
      .location-badge.pending { background: #fff3cd; color: #856404; }

      .selected-course-display {
        padding: 10px 14px; background: #f0f7ff;
        border: 2px solid var(--primary); border-radius: 8px;
        font-weight: 600; color: var(--primary); margin-bottom: 12px;
        min-height: 42px;
      }
    </style>
</head>
<body>
  <div class="dashboard">
    <div class="navbar">
      <h2>Student Dashboard</h2>
      <div class="nav-actions">
        <a href="student_history" class="btn btn-secondary btn-small">
          <i class="fas fa-history"></i> History
        </a>
        <a href="backend/logout" class="btn btn-danger btn-small">Logout</a>
      </div>
    </div>

    <?php if ($statusMessage): ?>
      <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($statusMessage) ?>
      </div>
    <?php endif; ?>

    <!-- Student Info -->
    <div class="student-info">
      <div class="info-item">
        <i class="fas fa-user-circle"></i>
        <div><strong>Name:</strong> <?= htmlspecialchars($student_name) ?></div>
      </div>
      <div class="info-item">
        <i class="fas fa-id-card"></i>
        <div><strong>Matric:</strong> <?= htmlspecialchars($student_mat_no) ?></div>
      </div>
      <div class="info-item">
        <i class="fas fa-building"></i>
        <div><strong>Department:</strong> <?= htmlspecialchars($student_depart) ?></div>
      </div>
      <div class="info-item">
        <i class="fas fa-layer-group"></i>
        <div><strong>Level:</strong> <?= htmlspecialchars($student_level) ?></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <a href="student_history" class="stat-card" style="text-decoration:none;">
        <div class="stat-value"><?= $totalAttended ?></div>
        <div class="stat-label">Classes Attended</div>
        <div style="font-size:.72rem;opacity:.75;margin-top:6px;">View history &rarr;</div>
      </a>
      <a href="#available-courses" class="stat-card alt-1" style="text-decoration:none;">
        <div class="stat-value"><?= $activeCount ?></div>
        <div class="stat-label">Active Courses</div>
        <div style="font-size:.72rem;opacity:.75;margin-top:6px;">See below &darr;</div>
      </a>
    </div>

    <!-- Available Courses -->
    <h2 id="available-courses">Available Courses</h2>

    <?php if ($activeCourses->num_rows > 0): ?>
      <div style="margin-bottom:30px;">
        <?php while ($course = $activeCourses->fetch_assoc()): ?>
          <div class="course-card"
               data-expires="<?= htmlspecialchars($course['expires_at'] ?? '') ?>"
               onclick="selectCourse('<?= htmlspecialchars($course['course_code']) ?>', this)">
            <div class="course-code"><?= htmlspecialchars($course['course_code']) ?></div>
            <div class="course-info" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <span>Location-based attendance &bull; Active</span>
              <?php if (!empty($course['expires_at'])): ?>
                <span class="card-timer" style="font-size:.8rem;font-weight:700;color:var(--warning);" data-expires="<?= htmlspecialchars($course['expires_at']) ?>">...</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- Attendance Form -->
      <div class="attendance-form">
        <h3>Mark Attendance</h3>

        <form method="POST" action="backend/mark_attendance" id="attendance-form" onsubmit="return validateForm()">
          <div class="form-group">
            <label>Selected Course</label>
            <!-- Hidden input holds the value; display div shows it to the user -->
            <input type="hidden" name="course" id="course" value="">
            <div class="selected-course-display" id="course-display">No course selected — click a course above</div>
          </div>

          <div id="location-status" class="location-badge pending">
            Waiting for course selection...
          </div>

          <div id="session-timer" style="display:none;margin-top:8px;padding:8px 14px;
               border-radius:8px;background:#fff3cd;color:#856404;font-weight:700;
               font-size:.88rem;text-align:center;"></div>

          <input type="hidden" name="device"   id="device">
          <input type="hidden" name="lat"      id="lat">
          <input type="hidden" name="lng"      id="lng">
          <input type="hidden" name="accuracy" id="accuracy">

          <button id="mark-btn" class="btn btn-success btn-block" disabled style="margin-top:20px;">
            Mark Attendance
          </button>
        </form>
      </div>

    <?php else: ?>
      <div class="alert alert-info">
        No active courses available for your department and level at this time.
      </div>
    <?php endif; ?>

  </div>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="plugins/sweetalerts/sweetalert2.min.js"></script>

  <?php if ($statusMessage): ?>
  <script>
    Swal.fire({
      icon: '<?= $messageType === 'success' ? 'success' : 'error' ?>',
      title: '<?= $messageType === 'success' ? 'Success' : 'Notice' ?>',
      text: '<?= addslashes($statusMessage) ?>',
      confirmButtonColor: '#0066cc'
    });
  </script>
  <?php endif; ?>

  <script>
    function haversineDistance(lat1, lon1, lat2, lon2) {
      const R = 6371000;
      const φ1 = lat1 * Math.PI/180, φ2 = lat2 * Math.PI/180;
      const Δφ = (lat2-lat1) * Math.PI/180, Δλ = (lon2-lon1) * Math.PI/180;
      const a = Math.sin(Δφ/2)**2 + Math.cos(φ1)*Math.cos(φ2)*Math.sin(Δλ/2)**2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    var _countdownInterval = null;

    function selectCourse(courseCode, element) {
      document.querySelectorAll('.course-card').forEach(c => c.classList.remove('selected'));
      element.classList.add('selected');

      document.getElementById('course').value = courseCode;
      document.getElementById('course-display').textContent = courseCode;

      updateLocationStatus('pending', 'Getting your location...');
      document.getElementById('mark-btn').disabled = true;

      // Clear any running countdown
      if (_countdownInterval) { clearInterval(_countdownInterval); _countdownInterval = null; }
      var timerEl = document.getElementById('session-timer');
      timerEl.style.display = 'none';
      timerEl.textContent = '';

      fetch("backend/check_distance?course_code=" + encodeURIComponent(courseCode))
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            if (data.expires_at) startCountdown(data.expires_at);
            getLocationAndCheck(data.expected_lat, data.expected_lng, data.radius);
          } else {
            updateLocationStatus('error', data.message || 'Course session not found');
          }
        })
        .catch(() => updateLocationStatus('error', 'Error fetching course details'));
    }

    function startCountdown(expiresAt) {
      var deadline = new Date(expiresAt.replace(' ', 'T'));
      var timerEl  = document.getElementById('session-timer');
      timerEl.style.display = 'block';

      function tick() {
        var remaining = Math.floor((deadline - new Date()) / 1000);
        if (remaining <= 0) {
          clearInterval(_countdownInterval);
          _countdownInterval = null;
          timerEl.style.background = '#f8d7da';
          timerEl.style.color = '#721c24';
          timerEl.textContent = 'Session has expired';
          document.getElementById('mark-btn').disabled = true;
          updateLocationStatus('error', 'Session has expired');
          return;
        }
        var h = Math.floor(remaining / 3600);
        var m = Math.floor((remaining % 3600) / 60);
        var s = remaining % 60;
        timerEl.textContent = 'Session closes in ' +
          (h > 0 ? h + 'h ' : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's';
        // Turn red in last 2 minutes
        if (remaining <= 120) {
          timerEl.style.background = '#f8d7da';
          timerEl.style.color = '#721c24';
        } else {
          timerEl.style.background = '#fff3cd';
          timerEl.style.color = '#856404';
        }
      }
      tick();
      _countdownInterval = setInterval(tick, 1000);
    }

    // Per-card mini-timers on the course list
    function startCardTimers() {
      var cards = document.querySelectorAll('.card-timer[data-expires]');
      if (!cards.length) return;
      function tick() {
        cards.forEach(function(el) {
          var remaining = Math.floor((new Date(el.dataset.expires.replace(' ', 'T')) - new Date()) / 1000);
          if (remaining <= 0) {
            el.textContent = 'Expired';
            el.style.color = 'var(--danger)';
          } else {
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;
            el.textContent = '⏱ ' + (h > 0 ? h + 'h ' : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's';
          }
        });
      }
      tick();
      setInterval(tick, 1000);
    }

    function getLocationAndCheck(expectedLat, expectedLng, allowedRadius) {
      if (!navigator.geolocation) {
        updateLocationStatus('error', 'Geolocation not supported by your browser');
        return;
      }
      navigator.geolocation.getCurrentPosition(function(position) {
        const lat      = position.coords.latitude;
        const lng      = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        document.getElementById('lat').value      = lat;
        document.getElementById('lng').value      = lng;
        document.getElementById('accuracy').value = accuracy;
        document.getElementById('device').value   = navigator.userAgent;

        const distance  = haversineDistance(lat, lng, expectedLat, expectedLng);
        const threshold = accuracy + 50;

        if (distance <= allowedRadius + threshold) {
          document.getElementById('mark-btn').disabled = false;
          updateLocationStatus('success', 'Within ' + Math.round(distance) + 'm of class (Allowed: ' + allowedRadius + 'm)');
        } else {
          document.getElementById('mark-btn').disabled = true;
          updateLocationStatus('error', 'Too far: ' + Math.round(distance) + 'm from class (Allowed: ' + allowedRadius + 'm)');
        }
      }, function(error) {
        updateLocationStatus('error', 'Location error: ' + error.message);
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    }

    function updateLocationStatus(type, message) {
      const el = document.getElementById('location-status');
      el.className = 'location-badge ' + type;
      el.textContent = message;
    }

    document.addEventListener('DOMContentLoaded', startCardTimers);

    function validateForm() {
      if (!document.getElementById('course').value) {
        Swal.fire({ icon: 'warning', title: 'No Course Selected', text: 'Please click a course above first.', confirmButtonColor: '#0066cc' });
        return false;
      }
      if (!document.getElementById('lat').value) {
        Swal.fire({ icon: 'warning', title: 'Location Needed', text: 'Your location has not been captured yet.', confirmButtonColor: '#0066cc' });
        return false;
      }
      const btn = document.getElementById('mark-btn');
      btn.disabled = true;
      btn.textContent = 'Processing...';
      return true;
    }
  </script>
</body>
</html>
