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
    <link rel="icon" type="image/x-icon" href="assets/img/logo.png">
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

      /* ── Face Capture Modal ── */
      #fc-modal {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.88); z-index: 9999;
        align-items: center; justify-content: center;
      }
      #fc-modal.open { display: flex; }
      .fc-box {
        background: white; border-radius: 16px; padding: 24px;
        width: min(400px, 95vw); text-align: center;
      }
      .fc-box h4 { margin: 0 0 16px; color: var(--primary); font-size: 1.05rem; }
      .fc-vid-wrap { position: relative; display: inline-block; width: 100%; max-width: 300px; }
      #fc-vid  { width: 100%; border-radius: 10px; display: block; }
      #fc-cvs  {
        position: absolute; top: 0; left: 0;
        width: 100%; height: 100%;
        border-radius: 10px; pointer-events: none;
      }
      #fc-status { margin-top: 10px; padding: 9px 14px; border-radius: 8px; font-size: .88rem; font-weight: 600; }
      .fc-prog-wrap { margin-top: 8px; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden; }
      #fc-prog { height: 100%; width: 0; border-radius: 3px; transition: width .12s; }
      #fc-img { width: 100%; max-width: 260px; border-radius: 10px; border: 3px solid #28a745; }
      .fc-cancel-btn { background: none; border: none; color: #aaa; cursor: pointer; font-size: .82rem; margin-top: 12px; }
      .fc-cancel-btn:hover { color: #666; }
      @keyframes fcSlideLeft  { 0%,100%{transform:translateX(0)} 50%{transform:translateX(-10px)} }
      @keyframes fcSlideRight { 0%,100%{transform:translateX(0)} 50%{transform:translateX( 10px)} }
      @keyframes fcBlink      { 0%,40%,60%,100%{opacity:1} 50%{opacity:.15} }
    </style>
</head>
<body>
  <div class="dashboard">
    <div class="navbar">
      <div class="navbar-brand">
        <img src="assets/img/logo.png" alt="Logo">
        <h2>Student Dashboard</h2>
      </div>
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
          <input type="hidden" name="accuracy"   id="accuracy">
          <input type="hidden" name="face_photo" id="face_photo" value="">

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

  <!-- ── Face Capture Modal ── -->
  <div id="fc-modal">
    <div class="fc-box">
      <h4><i class="fas fa-camera"></i> Face Verification</h4>

      <!-- Step: loading MediaPipe model -->
      <div id="fc-loading">
        <div style="padding:24px 0;">
          <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;"></div>
          <p style="margin-top:14px;color:var(--text-light);font-size:.9rem;">
            Preparing face verification&hellip;<br>
            <small style="color:#aaa;" id="fc-load-hint">initialising face detection&hellip;</small>
          </p>
        </div>
      </div>

      <!-- Step: camera + challenge -->
      <div id="fc-camera" style="display:none;">
        <div class="fc-vid-wrap">
          <video id="fc-vid" autoplay playsinline muted></video>
          <canvas id="fc-cvs"></canvas>
        </div>
        <div id="fc-status"></div>
        <div id="fc-guide" style="display:none;text-align:center;padding:6px 0 2px;"></div>
        <div class="fc-prog-wrap"><div id="fc-prog"></div></div>
      </div>

      <!-- Step: preview + confirm -->
      <div id="fc-preview" style="display:none;">
        <img id="fc-img" src="" alt="Captured face">
        <p style="margin-top:10px;color:#155724;font-weight:600;font-size:.9rem;">
          <i class="fas fa-check-circle"></i> Face captured!
        </p>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;">
          <button type="button" onclick="fcRetake()" class="btn btn-secondary btn-small">
            <i class="fas fa-redo"></i> Retake
          </button>
          <button type="button" onclick="fcConfirm()" class="btn btn-success">
            <i class="fas fa-check"></i> Confirm &amp; Submit
          </button>
        </div>
      </div>

      <div><button type="button" class="fc-cancel-btn" onclick="fcCancel()">
        <i class="fas fa-times"></i> Cancel
      </button></div>
    </div>
  </div>

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
      // Open face capture — actual form submission happens from fcConfirm()
      fcOpen();
      return false;
    }
  </script>

  <script>
  // ─── Face Capture + Liveness Detection ──────────────────────────────────
  // Requires internet (MediaPipe CDN). Blocks attendance if unavailable.
  // Phase 1: pixel-diff motion detection (defeats static photos / screen replays)
  // Phase 2: random face challenge — blink / turn head left / turn head right

  const FC = {
    lm: null,           // cached FaceLandmarker instance
    stream: null, raf: null, photo: null,
    phase: 'motion',    // 'motion' | 'challenge' | 'done'
    challenge: null,    // 'blink' | 'turn_left' | 'turn_right'
    mFrames: 0, prevPx: null, offCvs: null,
    earClosed: 0, turnF: 0,
    facePresent: false
  };

  // ── Load MediaPipe (all files served locally from assets/mediapipe/) ──────
  function tryLoadMediaPipe(timeoutMs) {
    return new Promise(function(resolve) {
      if (FC.lm) { resolve(true); return; }
      var settled = false;
      FC._mpError = 'timeout';
      function finish(ok) { if (!settled) { settled = true; clearTimeout(timer); resolve(ok); } }
      var timer = setTimeout(function(){ finish(false); }, timeoutMs || 30000);

      function initModel(vis) {
        if (!vis || !vis.FaceLandmarker) {
          FC._mpError = 'script_missing'; finish(false); return;
        }
        vis.FilesetResolver.forVisionTasks('assets/mediapipe/wasm')
          .then(function(fs) {
            var opts = {
              baseOptions: { modelAssetPath: 'assets/mediapipe/face_landmarker.task', delegate: 'GPU' },
              runningMode: 'VIDEO', numFaces: 1
            };
            // Try GPU first; fall back to CPU if WebGL is unavailable
            return vis.FaceLandmarker.createFromOptions(fs, opts)
              .catch(function() {
                opts.baseOptions.delegate = 'CPU';
                return vis.FaceLandmarker.createFromOptions(fs, opts);
              });
          }).then(function(lm) { FC.lm = lm; finish(true); })
          .catch(function(e) {
            console.error('MediaPipe init failed:', e);
            FC._mpError = 'model_failed'; finish(false);
          });
      }

      // Use already-loaded module if available (e.g. on retry)
      if (window.mpTasksVision) { initModel(window.mpTasksVision); return; }

      // Derive absolute URL so dynamic import works from an inline script
      var base = location.href.substring(0, location.href.lastIndexOf('/') + 1);
      import(base + 'assets/mediapipe/vision_bundle.mjs')
        .then(function(mod) {
          window.mpTasksVision = mod;
          initModel(mod);
        })
        .catch(function(e) {
          console.error('MediaPipe bundle load failed:', e);
          FC._mpError = 'script_missing'; finish(false);
        });
    });
  }

  // Specific error messages keyed by failure reason — helps whoever sets up the project
  var MP_ERRORS = {
    script_missing: '&#9888; <b>assets/mediapipe/vision_bundle.js</b> is missing from the server. '
                  + 'Re-download the project or run the MediaPipe asset setup.',
    model_failed:  '&#9888; Face model failed to initialise. '
                  + 'Check that all files inside <b>assets/mediapipe/</b> are present and not corrupted.',
    timeout:       '&#9888; Face detection took too long to start. '
                  + 'Make sure the XAMPP server is running and <b>assets/mediapipe/</b> files are intact.'
  };

  async function fcOpen() {
    document.getElementById('fc-modal').classList.add('open');
    Object.assign(FC, { photo:null, phase:'motion', mFrames:0, prevPx:null,
                        earClosed:0, turnF:0, facePresent:false });
    if (!FC.offCvs) { FC.offCvs = document.createElement('canvas'); FC.offCvs.width=80; FC.offCvs.height=60; }
    document.getElementById('fc-cvs').style.display = 'none';

    fcStep('loading'); // spinner while MediaPipe loads

    var mpReady = await tryLoadMediaPipe(30000);
    if (!mpReady) {
      fcStep('camera');
      var errMsg = MP_ERRORS[FC._mpError] || '&#9888; Face detection failed to load.';
      fcStat('red', errMsg + ' &nbsp;<button type="button" onclick="fcOpen()" style="background:none;'
        + 'border:none;color:#0066cc;text-decoration:underline;cursor:pointer;padding:0;'
        + 'font-size:inherit;">Try again</button>');
      return;
    }
    FC.challenge = ['blink','turn_left','turn_right'][Math.floor(Math.random()*3)];

    try {
      FC.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 320 }, height: { ideal: 240 } }
      });
      var vid = document.getElementById('fc-vid');
      vid.srcObject = FC.stream;
      await new Promise(function(r){ vid.onloadedmetadata = r; });
      vid.play();

      var cvs = document.getElementById('fc-cvs');
      cvs.width = vid.videoWidth || 320; cvs.height = vid.videoHeight || 240;
      cvs.style.display = '';

      fcStep('camera');
      fcStat('warn', '&#128993; Position your face in the camera and move slightly to confirm you\'re live&hellip;');
      fcProg(0, '#0066cc');
      FC.raf = requestAnimationFrame(fcTick);

    } catch(e) {
      fcStep('camera');
      fcStat('red', '&#9888;&#65039; ' + (e.message || 'Camera not available — check permissions.'));
    }
  }

  function fcTick() {
    var vid = document.getElementById('fc-vid');
    if (!vid.videoWidth) { FC.raf = requestAnimationFrame(fcTick); return; }

    // ── Phase 1: motion detection (defeats static photos) ──
    if (FC.phase === 'motion') {
      var oc = FC.offCvs.getContext('2d');
      oc.drawImage(vid, 0, 0, 80, 60);
      var px = oc.getImageData(0, 0, 80, 60).data;
      if (FC.prevPx) {
        var diff = 0;
        for (var i = 0; i < px.length; i += 4) {
          diff += Math.abs(px[i]   - FC.prevPx[i]  ) +
                  Math.abs(px[i+1] - FC.prevPx[i+1]) +
                  Math.abs(px[i+2] - FC.prevPx[i+2]);
        }
        if (diff > 5000) FC.mFrames++;
        fcProg(FC.mFrames / 14 * 100, '#0066cc');
        if (FC.mFrames >= 14) {
          FC.phase = 'challenge';
          var msgs = {
            blink:      '&#9989; Live confirmed! Now please <strong>BLINK</strong> once.',
            turn_left:  '&#9989; Live confirmed! Slowly turn your head <strong>LEFT</strong>.',
            turn_right: '&#9989; Live confirmed! Slowly turn your head <strong>RIGHT</strong>.'
          };
          fcStat('green', msgs[FC.challenge]);
          fcProg(0, '#28a745');
          fcShowGuide(FC.challenge);
          FC.raf = requestAnimationFrame(fcTick); return;
        }
      }
      FC.prevPx = new Uint8ClampedArray(px);
      FC.raf = requestAnimationFrame(fcTick); return;
    }

    // ── Phase 2: face-challenge via MediaPipe ──
    if (FC.phase === 'challenge') {
      var cvs = document.getElementById('fc-cvs');
      var ctx = cvs.getContext('2d');
      ctx.clearRect(0, 0, cvs.width, cvs.height);
      var res = FC.lm.detectForVideo(vid, performance.now());

      if (res.faceLandmarks && res.faceLandmarks.length > 0) {
        FC.facePresent = true;
        var lm = res.faceLandmarks[0];
        fcDrawOutline(ctx, lm, cvs.width, cvs.height);

        // Capture clean forward-facing photo on the first frame a face is detected
        if (!FC.photo) {
          var pc = document.createElement('canvas');
          pc.width = vid.videoWidth; pc.height = vid.videoHeight;
          pc.getContext('2d').drawImage(vid, 0, 0);
          FC.photo = pc.toDataURL('image/jpeg', 0.85);
        }

        if (FC.challenge === 'blink') {
          var ear = fcEAR(lm);
          if (ear < 0.22) { FC.earClosed++; }
          else if (FC.earClosed >= 2) { FC.phase='done'; fcCapture(); return; }
          else { FC.earClosed = 0; }
        } else {
          var yaw = lm[1].x - (lm[234].x + lm[454].x) / 2;
          var hit = FC.challenge === 'turn_left' ? yaw > 0.06 : yaw < -0.06;
          FC.turnF = hit ? FC.turnF+1 : Math.max(0, FC.turnF-1);
          fcProg(FC.turnF / 7 * 100, '#28a745');
          if (FC.turnF >= 7) { FC.phase='done'; fcCapture(); return; }
        }
      } else {
        FC.facePresent = false;
        var noFaceMsgs = {
          blink:      '&#128064; No face detected — look directly at the camera, then blink once.',
          turn_left:  '&#128064; No face detected — look directly at the camera and slowly turn <strong>LEFT</strong>.',
          turn_right: '&#128064; No face detected — look directly at the camera and slowly turn <strong>RIGHT</strong>.'
        };
        fcStat('warn', noFaceMsgs[FC.challenge]);
      }
      FC.raf = requestAnimationFrame(fcTick);
    }
  }

  function fcEAR(lm) {
    var d = function(a,b){ return Math.hypot(lm[a].x-lm[b].x, lm[a].y-lm[b].y); };
    return ((d(160,144)+d(158,153))/(2*d(33,133)) + (d(385,380)+d(387,373))/(2*d(362,263))) / 2;
  }

  function fcDrawOutline(ctx, lm, w, h) {
    var pts = [10,338,297,332,284,251,389,356,454,323,361,288,397,365,
               379,378,400,377,152,148,176,149,150,136,172,58,132,93,234,127,162,21,54,103,67,109];
    ctx.beginPath(); ctx.strokeStyle='rgba(0,200,80,0.85)'; ctx.lineWidth=1.5;
    pts.forEach(function(i,j){ j===0?ctx.moveTo(lm[i].x*w,lm[i].y*h):ctx.lineTo(lm[i].x*w,lm[i].y*h); });
    ctx.closePath(); ctx.stroke();
  }

  // Photo is already captured at the start of challenge phase — just stop and show it
  function fcCapture() {
    cancelAnimationFrame(FC.raf);
    document.getElementById('fc-guide').style.display = 'none';
    fcStopCam();
    document.getElementById('fc-img').src = FC.photo;
    fcStep('preview');
  }

  function fcShowGuide(challenge) {
    var cfg = {
      blink:      { icon:'&#128065;', label:'Blink your eyes <strong>ONCE</strong>',          anim:'fcBlink'      },
      turn_left:  { icon:'&#128072;', label:'Slowly turn your head to the <strong>LEFT</strong>',  anim:'fcSlideLeft'  },
      turn_right: { icon:'&#128073;', label:'Slowly turn your head to the <strong>RIGHT</strong>', anim:'fcSlideRight' }
    };
    var c = cfg[challenge];
    var g = document.getElementById('fc-guide');
    g.innerHTML = '<span style="font-size:.95rem;color:#155724;font-weight:500;display:inline-block;'
      + 'animation:' + c.anim + ' 1.4s ease-in-out infinite;">'
      + c.icon + ' ' + c.label + '</span>';
    g.style.display = '';
  }

  function fcRetake() {
    Object.assign(FC, { photo:null, phase:'motion', mFrames:0, prevPx:null, earClosed:0, turnF:0, facePresent:false });
    document.getElementById('fc-guide').style.display = 'none';
    document.getElementById('fc-cvs').style.display = '';
    FC.challenge = ['blink','turn_left','turn_right'][Math.floor(Math.random()*3)];
    fcStat('warn','&#128993; Position your face in the camera and move slightly to confirm you\'re live&hellip;');
    fcProg(0,'#0066cc'); fcStep('camera');
    if (FC.stream) { FC.raf = requestAnimationFrame(fcTick); }
    else { navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}}).then(function(s){
      FC.stream=s; document.getElementById('fc-vid').srcObject=s; FC.raf=requestAnimationFrame(fcTick);
    }); }
  }

  function fcConfirm() {
    document.getElementById('face_photo').value = FC.photo;
    document.getElementById('fc-modal').classList.remove('open');
    document.getElementById('mark-btn').disabled = true;
    document.getElementById('mark-btn').textContent = 'Processing...';
    document.getElementById('attendance-form').submit();
  }

  function fcCancel() {
    cancelAnimationFrame(FC.raf); fcStopCam();
    document.getElementById('fc-guide').style.display = 'none';
    document.getElementById('fc-modal').classList.remove('open');
    document.getElementById('mark-btn').disabled = false;
    document.getElementById('mark-btn').textContent = 'Mark Attendance';
  }

  function fcStopCam() {
    if (FC.stream) { FC.stream.getTracks().forEach(function(t){ t.stop(); }); FC.stream=null; }
  }

  function fcStep(s) {
    ['loading','camera','preview'].forEach(function(n){
      document.getElementById('fc-'+n).style.display = s===n?'':'none';
    });
  }

  function fcStat(type, html) {
    var el=document.getElementById('fc-status');
    var c={warn:['#fff3cd','#856404'],green:['#d4edda','#155724'],red:['#f8d7da','#721c24']};
    el.style.background=(c[type]||c.warn)[0]; el.style.color=(c[type]||c.warn)[1]; el.innerHTML=html;
  }

  function fcProg(pct, color) {
    var p=document.getElementById('fc-prog');
    p.style.width=Math.min(pct,100)+'%'; p.style.background=color;
  }
  </script>
</body>
</html>
