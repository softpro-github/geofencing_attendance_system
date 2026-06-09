<?php
    session_start();
    include 'backend/db.php';
    if (!isset($_SESSION['lecturer_id'])) {
        header("Location: index");
        exit;
    }

    $id = (int)$_SESSION['lecturer_id'];

    // Get lecturer info
    $stmt = $conn->prepare("SELECT * FROM lecturers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $lecturer = $stmt->get_result()->fetch_assoc();

    // Count active sessions
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance_sessions WHERE lecturer_id = ? AND status = 'active'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $activeCount = $stmt->get_result()->fetch_assoc()['total'];

    // Count total attendance records for this lecturer
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE session_id IN (SELECT id FROM attendance_sessions WHERE lecturer_id = ?)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $attendanceTotal = $stmt->get_result()->fetch_assoc()['total'];

    if (isset($_SESSION['status'])) {
        $statusMessage = $_SESSION['status'];
        $messageType = (strpos($statusMessage, 'successfully') !== false || strpos($statusMessage, 'deleted') !== false) ? 'success' : 'danger';
        unset($_SESSION['status']);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Dashboard - Attendance System</title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="plugins/sweetalerts/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
      .lecturer-info {
        background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
      }

      .tab-pane {
        padding: 25px;
        background: white;
        border-radius: 8px;
        box-shadow: var(--shadow-md);
      }

      .nav-tabs {
        border: none;
        gap: 10px;
        margin-bottom: 20px;
        background: white;
        padding: 10px;
        border-radius: 8px;
        box-shadow: var(--shadow-md);
      }

      .nav-tabs .nav-link {
        color: var(--text-light);
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 600;
        transition: all 0.3s ease;
      }

      .nav-tabs .nav-link:hover {
        color: var(--primary);
        border-bottom-color: var(--primary);
      }

      .nav-tabs .nav-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: transparent;
      }

      .course-item {
        background: white;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
      }

      .course-item:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
      }

      .course-info {
        flex: 1;
      }

      .course-code {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
      }

      .course-details {
        font-size: 0.9rem;
        color: var(--text-light);
      }

      .course-actions {
        display: flex;
        gap: 10px;
      }

      .form-section {
        background: var(--light-bg);
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 25px;
      }

      .form-section h4 {
        color: var(--primary);
        margin-bottom: 20px;
        font-weight: 600;
      }

      .location-indicator {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 15px;
      }

      .location-indicator.active {
        background: #d4edda;
        color: #155724;
      }

      .location-indicator.pending {
        background: #fff3cd;
        color: #856404;
      }

      .location-indicator.error {
        background: #f8d7da;
        color: #721c24;
      }

      .course-item { cursor: pointer; }
      .course-item .manage-hint {
        font-size: .78rem; color: var(--text-light);
        margin-top: 10px; display: flex; align-items: center; gap: 5px;
      }
      .course-item:hover .manage-hint { color: var(--primary); }

      .session-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
      }

      .session-badge.active {
        background: #d4edda;
        color: #155724;
      }

      .session-badge.inactive {
        background: #e2e3e5;
        color: #383d41;
      }
    </style>
</head>
<body>
  <div class="dashboard">
    <!-- Navigation Bar -->
    <div class="navbar">
      <div class="navbar-brand">
        <img src="assets/img/logo.png" alt="Logo">
        <h2><i class="fas fa-chalkboard-user"></i> Lecturer Dashboard</h2>
      </div>
      <div class="nav-actions">
        <a href="add_courses" class="btn btn-secondary btn-small">
          <i class="fas fa-plus-circle"></i> Add Course
        </a>
        <a href="lecturer_history" class="btn btn-secondary btn-small">
          <i class="fas fa-history"></i> History
        </a>
        <a href="backend/admin_logout" class="btn btn-danger btn-small">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>

    <!-- Status Message -->
    <?php if (isset($statusMessage)): ?>
      <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($statusMessage) ?>
      </div>
    <?php endif; ?>

    <!-- Lecturer Info -->
    <div class="lecturer-info">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h4 style="margin: 0; font-size: 1.3rem;">
            <i class="fas fa-user-tie"></i> 
            <?= $lecturer ? htmlspecialchars($lecturer['username'] ?? 'Lecturer') : 'Welcome' ?>
          </h4>
          <p style="margin: 8px 0 0 0; opacity: 0.95;">Manage your courses and track student attendance</p>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
      <a href="lecturer_history" class="stat-card" style="text-decoration:none;">
        <div class="stat-value"><?= $activeCount ?></div>
        <div class="stat-label">Active Sessions</div>
        <div style="font-size:.72rem;opacity:.75;margin-top:6px;">View sessions &rarr;</div>
      </a>
      <a href="lecturer_dashboard#courses" class="stat-card alt-1" style="text-decoration:none;">
        <div class="stat-value"><?php
          $stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE lecturer_id = ?");
          $stmt2->bind_param("i", $id);
          $stmt2->execute();
          echo $stmt2->get_result()->fetch_assoc()['total'];
        ?></div>
        <div class="stat-label">Total Courses</div>
        <div style="font-size:.72rem;opacity:.75;margin-top:6px;">Manage courses &rarr;</div>
      </a>
      <a href="lecturer_history" class="stat-card alt-2" style="text-decoration:none;">
        <div class="stat-value"><?= $attendanceTotal ?></div>
        <div class="stat-label">Attendance Records</div>
        <div style="font-size:.72rem;opacity:.75;margin-top:6px;">View history &rarr;</div>
      </a>
    </div>

    <!-- Main Tabs -->
    <ul class="nav nav-tabs" id="lecturerTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab">
          <i class="fas fa-book"></i> Manage Courses
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="activate-tab" data-bs-toggle="tab" data-bs-target="#activate" type="button" role="tab">
          <i class="fas fa-play-circle"></i> Session Control
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab">
          <i class="fas fa-download"></i> Export Data
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="lecturerTabContent">
      
      <!-- Courses Tab -->
      <div class="tab-pane fade show active" id="courses" role="tabpanel">
        <h3><i class="fas fa-list"></i> Your Courses</h3>
        
        <?php
        $cstmt = $conn->prepare("SELECT * FROM courses WHERE lecturer_id = ?");
        $cstmt->bind_param("i", $id);
        $cstmt->execute();
        $courses = $cstmt->get_result();

        // Prepare session-check once, reuse in loop
        $schk = $conn->prepare("SELECT expires_at FROM attendance_sessions WHERE course_code = ? AND lecturer_id = ? AND status = 'active' LIMIT 1");

        if ($courses->num_rows > 0):
        ?>
          <div style="margin-top: 20px;">
            <?php while ($course = $courses->fetch_assoc()):
              $schk->bind_param("si", $course['course_code'], $id);
              $schk->execute();
              $schkRow = $schk->get_result()->fetch_assoc();
              $hasActiveSession = ($schkRow !== null);
              $sessionExpiresAt = $hasActiveSession ? $schkRow['expires_at'] : null;
            ?>
              <div class="course-item"
                   data-course="<?= htmlspecialchars($course['course_code']) ?>"
                   data-dept="<?= htmlspecialchars($course['department'] ?? 'N/A') ?>"
                   data-level="<?= htmlspecialchars($course['level'] ?? 'N/A') ?>"
                   data-active="<?= $hasActiveSession ? '1' : '0' ?>"
                   data-expires="<?= htmlspecialchars($sessionExpiresAt ?? '') ?>"
                   onclick="openCourseModal(this)">
                <div class="course-info">
                  <div class="course-code"><?= htmlspecialchars($course['course_code']) ?></div>
                  <div class="course-details" style="margin-top: 8px;">
                    <i class="fas fa-sitemap"></i>
                    <?= htmlspecialchars($course['department'] ?? 'N/A') ?> |
                    <i class="fas fa-layer-group"></i>
                    <?= htmlspecialchars($course['level'] ?? 'N/A') ?>
                  </div>
                  <div style="margin-top: 10px;">
                    <span class="session-badge <?= $hasActiveSession ? 'active' : 'inactive' ?>">
                      <i class="fas fa-circle"></i>
                      <?php if ($hasActiveSession): ?>
                        Session Active
                        <?php if ($sessionExpiresAt): ?>
                          &mdash; <span class="session-timer" data-expires="<?= htmlspecialchars($sessionExpiresAt) ?>">...</span>
                        <?php else: ?>
                          &mdash; No time limit
                        <?php endif; ?>
                      <?php else: ?>
                        No Active Session
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="manage-hint">
                    <i class="fas fa-hand-pointer"></i>
                    <?= $hasActiveSession ? 'Click to deactivate or restart' : 'Click to activate' ?>
                  </div>
                </div>
                <div class="course-actions">
                  <button type="button" class="btn btn-danger btn-small"
                          onclick="deleteCourse('<?= htmlspecialchars($course['course_code']) ?>', event)"
                          title="Delete course">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No courses found. <a href="add_courses">Add your first course</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Activate Course Tab -->
      <div class="tab-pane fade" id="activate" role="tabpanel">
        <h3><i class="fas fa-cogs"></i> Session Control</h3>
        
        <div class="form-section">
          <h4><i class="fas fa-play-circle"></i> Activate/Deactivate Course Session</h4>
          
          <form method="POST" action="backend/activate_course" id="activate-form">
            <div class="form-group">
              <label for="course_code"><i class="fas fa-book"></i> Select Course</label>
              <select name="course_code" id="course_code" required>
                <option value="">-- Choose a course --</option>
                <?php
                $cl2 = $conn->prepare("SELECT course_code, department, level FROM courses WHERE lecturer_id = ?");
                $cl2->bind_param("i", $id);
                $cl2->execute();
                $cl2res = $cl2->get_result();
                while ($course = $cl2res->fetch_assoc()):
                ?>
                  <option value="<?= htmlspecialchars($course['course_code']) ?>">
                    <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['department'] . ' | ' . $course['level']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="radius"><i class="fas fa-ruler"></i> Allowed Radius (Meters)</label>
              <input type="number" name="radius" id="radius" class="form-control" placeholder="Radius in meters" value="50" min="10" max="500" required>
              <small class="form-hint">Students must be within this radius to mark attendance</small>
            </div>

            <div class="form-group">
              <label for="duration"><i class="fas fa-clock"></i> Session Duration</label>
              <select name="duration" id="duration" class="form-control">
                <option value="0">No limit (manual close)</option>
                <option value="15">15 minutes</option>
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60">1 hour</option>
                <option value="90">1 hour 30 minutes</option>
                <option value="120">2 hours</option>
              </select>
              <small class="form-hint">Session auto-closes after the selected time</small>
            </div>

            <div class="location-indicator pending" id="location-status">
              <i class="fas fa-spinner fa-spin"></i> Getting your location...
            </div>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">
            <input type="hidden" name="accuracy" id="accuracy">
            <input type="hidden" name="session_action" id="session_action" value="">

            <div style="display: flex; gap: 10px; margin-top: 20px;">
              <button type="button" class="btn btn-success" onclick="submitSessionAction('activate')">
                <i class="fas fa-play-circle"></i> Activate Session
              </button>
              <button type="button" class="btn btn-warning" onclick="submitSessionAction('deactivate')">
                <i class="fas fa-stop-circle"></i> Deactivate Session
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Export Data Tab -->
      <div class="tab-pane fade" id="export" role="tabpanel">
        <h3><i class="fas fa-download"></i> Export Attendance Data</h3>
        
        <div class="form-section">
          <h4><i class="fas fa-file-csv"></i> Export to Excel</h4>
          
          <form method="POST" action="backend/export_attendance">
            <div class="form-group">
              <label for="export_course"><i class="fas fa-book"></i> Select Course</label>
              <select name="course_code" id="export_course" required>
                <option value="">-- Choose a course --</option>
                <?php
                $cl3 = $conn->prepare("SELECT course_code, department, level FROM courses WHERE lecturer_id = ?");
                $cl3->bind_param("i", $id);
                $cl3->execute();
                $cl3res = $cl3->get_result();
                while ($course = $cl3res->fetch_assoc()): 
                ?>
                  <option value="<?= htmlspecialchars($course['course_code']) ?>">
                    <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['department'] . ' | ' . $course['level']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="fas fa-download"></i> Export to Excel
            </button>
          </form>
        </div>

        <div class="alert alert-info" style="margin-top: 20px;">
          <i class="fas fa-info-circle"></i>
          <strong>Info:</strong> Exporting will generate a CSV file with all attendance records for the selected course.
        </div>
      </div>

    </div>
  </div>

  <!-- Hidden delete form -->
  <form id="delete-course-form" method="POST" action="backend/delete_course" style="display:none;">
    <input type="hidden" name="course_code" id="delete-course-code">
  </form>

  <!-- Course Session Modal -->
  <div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background:var(--primary);color:white;border-radius:8px 8px 0 0;">
          <h5 class="modal-title">
            <i class="fas fa-book"></i> <span id="mc-code"></span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="padding:20px 24px;">
          <p style="color:var(--text-light);font-size:.9rem;margin-bottom:16px;">
            <i class="fas fa-sitemap"></i> <span id="mc-info"></span>
          </p>

          <!-- Active status alert -->
          <div id="mc-status-active" class="alert alert-success" style="display:none;margin-bottom:16px;">
            <i class="fas fa-check-circle"></i> Session is currently <strong>active</strong>
            <span id="mc-timer" style="font-weight:700;margin-left:4px;"></span>
          </div>

          <!-- Location indicator -->
          <div id="mc-loc" class="location-indicator pending" style="margin:0 0 16px;display:block;">
            <i class="fas fa-spinner fa-spin"></i> Checking location...
          </div>

          <form id="mc-form" method="POST" action="backend/activate_course">
            <input type="hidden" name="course_code"    id="mc-course-input">
            <input type="hidden" name="lat"            id="mc-lat">
            <input type="hidden" name="lng"            id="mc-lng">
            <input type="hidden" name="accuracy"       id="mc-accuracy">
            <input type="hidden" name="session_action" id="mc-action">

            <div id="mc-activate-fields">
              <div class="form-group">
                <label><i class="fas fa-ruler"></i> Allowed Radius (meters)</label>
                <input type="number" name="radius" id="mc-radius" class="form-control"
                       value="50" min="10" max="500">
              </div>
              <div class="form-group" style="margin-top:12px;">
                <label><i class="fas fa-clock"></i> Session Duration</label>
                <select name="duration" id="mc-duration" class="form-control">
                  <option value="0">No limit (manual close)</option>
                  <option value="15">15 minutes</option>
                  <option value="30">30 minutes</option>
                  <option value="45">45 minutes</option>
                  <option value="60">1 hour</option>
                  <option value="90">1 hour 30 minutes</option>
                  <option value="120">2 hours</option>
                </select>
              </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
              <button type="button" id="mc-activate-btn" class="btn btn-success"
                      onclick="submitModal('activate')">
                <i class="fas fa-play-circle"></i>
                <span id="mc-activate-label">Activate Session</span>
              </button>
              <button type="button" id="mc-deactivate-btn" class="btn btn-warning"
                      onclick="submitModal('deactivate')" style="display:none;">
                <i class="fas fa-stop-circle"></i> Deactivate Session
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="plugins/sweetalerts/sweetalert2.min.js"></script>
  <script>
    function getLocationAndDisplay() {
      if (!navigator.geolocation) {
        updateLocationStatus('error', 'Geolocation not supported by your browser');
        return;
      }

      navigator.geolocation.getCurrentPosition(
        function (position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          const accuracy = position.coords.accuracy;

          document.getElementById("lat").value = lat;
          document.getElementById("lng").value = lng;
          document.getElementById("accuracy").value = accuracy;

          updateLocationStatus('active', `📍 Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
        },
        function (error) {
          updateLocationStatus('error', `Error: ${error.message}`);
        },
        {
          enableHighAccuracy: true,
          timeout: 20000,
          maximumAge: 0
        }
      );
    }

    function updateLocationStatus(status, message) {
      const indicator = document.getElementById("location-status");
      indicator.className = `location-indicator ${status}`;
      indicator.innerHTML = `<i class="fas fa-${status === 'active' ? 'check-circle' : status === 'error' ? 'times-circle' : 'spinner fa-spin'}"></i> ${message}`;
    }

    function submitSessionAction(action) {
      document.getElementById('session_action').value = action;
      const buttons = document.querySelectorAll('#activate-form button[type="button"]');
      buttons.forEach(function(btn) { btn.disabled = true; });
      document.getElementById('activate-form').submit();
    }

    function deleteCourse(courseCode, event) {
      event.stopPropagation();
      Swal.fire({
        title: 'Delete ' + courseCode + '?',
        html: 'This will permanently remove the course and <strong>all attendance records</strong> associated with it. This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Yes, delete it',
        cancelButtonText: 'Cancel'
      }).then(function(result) {
        if (result.isConfirmed) {
          document.getElementById('delete-course-code').value = courseCode;
          document.getElementById('delete-course-form').submit();
        }
      });
    }

    var _mcTimerInterval = null;

    function openCourseModal(el) {
      var courseCode = el.dataset.course;
      var hasActive  = el.dataset.active === '1';
      var expiresAt  = el.dataset.expires;

      document.getElementById('mc-code').textContent   = courseCode;
      document.getElementById('mc-info').textContent   = el.dataset.dept + ' | ' + el.dataset.level;
      document.getElementById('mc-course-input').value = courseCode;

      // Location status
      var lat   = document.getElementById('lat').value;
      var mcLoc = document.getElementById('mc-loc');
      if (lat) {
        mcLoc.className = 'location-indicator active';
        mcLoc.innerHTML = '<i class="fas fa-check-circle"></i> Location captured';
      } else {
        mcLoc.className = 'location-indicator pending';
        mcLoc.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Waiting for location...';
      }

      var statusEl      = document.getElementById('mc-status-active');
      var activateBtn   = document.getElementById('mc-activate-btn');
      var deactivateBtn = document.getElementById('mc-deactivate-btn');
      var activateLabel = document.getElementById('mc-activate-label');
      var timerEl       = document.getElementById('mc-timer');

      if (_mcTimerInterval) { clearInterval(_mcTimerInterval); _mcTimerInterval = null; }
      timerEl.textContent = '';

      if (hasActive) {
        statusEl.style.display    = 'block';
        deactivateBtn.style.display = 'inline-flex';
        activateLabel.textContent = 'Restart Session';

        if (expiresAt) {
          var deadline = new Date(expiresAt.replace(' ', 'T'));
          function tickModal() {
            var rem = Math.floor((deadline - new Date()) / 1000);
            if (rem <= 0) {
              clearInterval(_mcTimerInterval);
              timerEl.textContent = '(Expired)';
            } else {
              var h = Math.floor(rem / 3600), m = Math.floor((rem % 3600) / 60), s = rem % 60;
              timerEl.textContent = '— ' + (h > 0 ? h + 'h ' : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's left';
            }
          }
          tickModal();
          _mcTimerInterval = setInterval(tickModal, 1000);
        } else {
          timerEl.textContent = '— No time limit';
        }
      } else {
        statusEl.style.display      = 'none';
        deactivateBtn.style.display = 'none';
        activateLabel.textContent   = 'Activate Session';
      }

      new bootstrap.Modal(document.getElementById('courseModal')).show();
    }

    function submitModal(action) {
      if (action === 'activate') {
        var lat = document.getElementById('lat').value;
        if (!lat) {
          var mcLoc = document.getElementById('mc-loc');
          mcLoc.className = 'location-indicator error';
          mcLoc.innerHTML = '<i class="fas fa-times-circle"></i> Location not available — please allow location access.';
          return;
        }
        document.getElementById('mc-lat').value      = lat;
        document.getElementById('mc-lng').value      = document.getElementById('lng').value;
        document.getElementById('mc-accuracy').value = document.getElementById('accuracy').value;
      }
      document.getElementById('mc-action').value = action;
      document.getElementById('mc-form').submit();
    }

    function startSessionTimers() {
      var timers = document.querySelectorAll('.session-timer[data-expires]');
      if (!timers.length) return;
      function tick() {
        timers.forEach(function(el) {
          var remaining = Math.floor((new Date(el.dataset.expires.replace(' ', 'T')) - new Date()) / 1000);
          if (remaining <= 0) {
            el.textContent = 'Expired';
            el.style.color = '#721c24';
          } else {
            var h = Math.floor(remaining / 3600);
            var m = Math.floor((remaining % 3600) / 60);
            var s = remaining % 60;
            el.textContent = (h > 0 ? h + 'h ' : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's left';
          }
        });
      }
      tick();
      setInterval(tick, 1000);
    }

    window.addEventListener('load', function() {
      getLocationAndDisplay();
      startSessionTimers();

      // Activate tab from URL hash (e.g. lecturer_dashboard#courses)
      var hash = window.location.hash;
      if (hash) {
        var tabBtn = document.querySelector('[data-bs-target="' + hash + '"]');
        if (tabBtn) {
          new bootstrap.Tab(tabBtn).show();
          tabBtn.closest('.nav-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  </script>
</body>
</html>
