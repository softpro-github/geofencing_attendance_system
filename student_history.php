<?php
session_start();
include 'backend/db.php';

if (!isset($_SESSION['matric_number'])) {
    header("Location: index");
    exit;
}

$matric = $_SESSION['matric_number'];

$stmt = $conn->prepare("SELECT name, department, level FROM students WHERE matric_number = ?");
$stmt->bind_param("s", $matric);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    session_destroy();
    header("Location: index");
    exit;
}

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT course_code) as courses FROM attendance WHERE matric_number = ?");
$stmt->bind_param("s", $matric);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Per-course attendance percentage
$csum_stmt = $conn->prepare("
    SELECT
        s.course_code,
        COUNT(DISTINCT s.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN a.matric_number = ? THEN a.session_id END) as attended
    FROM attendance_sessions s
    LEFT JOIN attendance a ON s.id = a.session_id AND a.matric_number = ?
    WHERE s.course_code IN (SELECT DISTINCT course_code FROM attendance WHERE matric_number = ?)
    GROUP BY s.course_code
    ORDER BY s.course_code
");
$csum_stmt->bind_param("sss", $matric, $matric, $matric);
$csum_stmt->execute();
$courseSummary = $csum_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Distinct courses for filter dropdown
$stmt = $conn->prepare("SELECT DISTINCT course_code FROM attendance WHERE matric_number = ? ORDER BY course_code");
$stmt->bind_param("s", $matric);
$stmt->execute();
$filterCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Distinct dates for filter dropdown (all dates across all courses)
$stmt = $conn->prepare("SELECT DISTINCT DATE(timestamp) as d FROM attendance WHERE matric_number = ? ORDER BY d DESC");
$stmt->bind_param("s", $matric);
$stmt->execute();
$filterDates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// All records (load all; JS handles filtering)
$stmt = $conn->prepare("
    SELECT course_code, timestamp, ROUND(distance) as distance, face_photo
    FROM attendance
    WHERE matric_number = ?
    ORDER BY timestamp DESC
");
$stmt->bind_param("s", $matric);
$stmt->execute();
$allRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$printDate = date('d M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - <?= htmlspecialchars($matric) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
      .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white; padding: 20px 25px; border-radius: 10px; margin-bottom: 25px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
      }
      .page-header h3 { margin: 0; font-size: 1.2rem; font-weight: 700; }
      .page-header p  { margin: 4px 0 0; opacity: .9; font-size: .88rem; }

      .filter-card {
        background: white; padding: 16px 20px; border-radius: 10px;
        box-shadow: var(--shadow-sm); margin-bottom: 20px;
      }
      .filter-row {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      }
      .filter-row label { font-weight: 600; font-size: .85rem; color: var(--text-light); margin: 0; white-space: nowrap; min-width: 40px; }
      .filter-row select { flex: 1; min-width: 150px; max-width: 220px; }
      .filter-row .export-btns { display: flex; gap: 8px; margin-left: auto; }
      .filter-divider { width: 1px; height: 32px; background: var(--border-color); }
      #record-count { font-size: .82rem; color: var(--text-light); white-space: nowrap; }

      .history-table { background: white; border-radius: 10px; box-shadow: var(--shadow-sm); overflow: hidden; }
      .history-table table { margin: 0; }
      .history-table thead th {
        background: var(--light-bg); font-size: .82rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em; border: none; padding: 13px 16px;
      }
      .history-table tbody td { padding: 13px 16px; vertical-align: middle; border-color: var(--border-color); font-size: .9rem; }
      .history-table tbody tr:hover { background: #f8faff; }

      .badge-course {
        display: inline-block; padding: 4px 10px; border-radius: 20px;
        background: #e8f0fe; color: var(--primary); font-weight: 700; font-size: .82rem;
      }
      .badge-dist {
        display: inline-block; padding: 3px 9px; border-radius: 20px;
        background: #d4edda; color: #155724; font-size: .82rem; font-weight: 600;
      }
      .empty-state { text-align: center; padding: 50px 20px; color: var(--text-light); }
      .empty-state i { font-size: 3rem; margin-bottom: 12px; color: var(--border-color); display: block; }

      /* active filter chips */
      .filter-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
      .filter-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600;
        background: #e8f0fe; color: var(--primary); border: 1px solid #c5d8f8;
      }
      .filter-chip button {
        background: none; border: none; padding: 0; cursor: pointer;
        color: var(--primary); font-size: .75rem; line-height: 1;
      }

      .summary-card {
        background: white; border-radius: 10px; box-shadow: var(--shadow-sm);
        overflow: hidden; margin-bottom: 22px;
      }
      .summary-title {
        padding: 13px 18px; font-weight: 700; font-size: .95rem;
        background: var(--light-bg); border-bottom: 1px solid var(--border-color);
        color: var(--text-dark);
      }
      .summary-card table thead th {
        background: #f8fafc; font-size: .79rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        padding: 10px 16px; border: none; color: var(--text-light);
      }
      .summary-card table tbody td {
        padding: 12px 16px; vertical-align: middle;
        border-color: var(--border-color); font-size: .9rem;
      }
      .pct-bar-wrap { display: flex; align-items: center; gap: 10px; min-width: 160px; }
      .pct-bar-track { flex: 1; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; }
      .pct-bar { height: 100%; border-radius: 4px; }
      .pct-label { font-weight: 700; font-size: .88rem; min-width: 44px; }
      .pill-qualified     { display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:.78rem;font-weight:700;background:#d4edda;color:#155724; }
      .pill-not-qualified { display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:.78rem;font-weight:700;background:#f8d7da;color:#721c24; }

      /* ---- Print styles ---- */
      .print-header { display: none; }
      @media print {
        .no-print { display: none !important; }
        .print-header {
          display: block; text-align: center;
          border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 18px;
        }
        .print-header h2 { font-size: 1.15rem; margin: 0 0 4px; color: #000; border: none; }
        .print-header p  { font-size: .8rem; color: #444; margin: 2px 0; }
        body { background: white !important; }
        .dashboard { padding: 0 !important; max-width: 100% !important; }
        .page-header { background: #1a4a8a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border-radius: 4px !important; }
        .stat-card { box-shadow: none !important; border: 1px solid #ddd !important; }
        .history-table { box-shadow: none !important; border: 1px solid #ddd !important; }
        .badge-course { background: #e8f0fe !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .badge-dist   { background: #d4edda !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .filter-chips { display: none; }
      }
    </style>
</head>
<body>
<div class="dashboard">

  <!-- Print-only header -->
  <div class="print-header">
    <h2>Smart Geofencing Attendance System</h2>
    <p><strong>Student Attendance History Report</strong></p>
    <p>
      <?= htmlspecialchars($student['name']) ?> &nbsp;|&nbsp;
      <?= htmlspecialchars($matric) ?> &nbsp;|&nbsp;
      <?= htmlspecialchars($student['department']) ?> &nbsp;|&nbsp;
      <?= htmlspecialchars($student['level']) ?>
    </p>
    <p id="print-filter-info" style="font-size:.78rem;color:#666;"></p>
    <p style="font-size:.75rem;color:#888;">Generated: <?= $printDate ?></p>
  </div>

  <!-- Navbar -->
  <div class="navbar no-print">
    <div class="navbar-brand">
      <img src="assets/img/logo.png" alt="Logo">
      <h2><i class="fas fa-history"></i> Attendance History</h2>
    </div>
    <div class="nav-actions">
      <a href="student_dashboard" class="btn btn-secondary btn-small">
        <i class="fas fa-arrow-left"></i> Dashboard
      </a>
      <a href="backend/logout" class="btn btn-danger btn-small">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <!-- Page header -->
  <div class="page-header">
    <div>
      <h3><i class="fas fa-user-circle"></i> <?= htmlspecialchars($student['name']) ?></h3>
      <p><?= htmlspecialchars($matric) ?> &bull; <?= htmlspecialchars($student['department']) ?> &bull; <?= htmlspecialchars($student['level']) ?></p>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom: 22px;">
    <div class="stat-card" onclick="safeScrollAndClear()" style="cursor:pointer;">
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Classes Attended</div>
      <div style="font-size:.72rem;opacity:.75;margin-top:6px;">Show all &darr;</div>
    </div>
    <div class="stat-card alt-1" onclick="safeScrollAndClear()" style="cursor:pointer;">
      <div class="stat-value"><?= $stats['courses'] ?></div>
      <div class="stat-label">Courses</div>
      <div style="font-size:.72rem;opacity:.75;margin-top:6px;">Show all &darr;</div>
    </div>
  </div>

  <!-- Course Attendance Summary -->
  <?php if (!empty($courseSummary)): ?>
  <div class="summary-card">
    <div class="summary-title"><i class="fas fa-chart-bar"></i> Attendance Summary by Course</div>
    <table class="table" style="margin:0;">
      <thead>
        <tr>
          <th>Course</th>
          <th>Sessions Attended</th>
          <th>Total Sessions</th>
          <th>Percentage</th>
          <th>Exam Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($courseSummary as $cs):
          $pct       = $cs['total_sessions'] > 0 ? round($cs['attended'] / $cs['total_sessions'] * 100, 1) : 0;
          $qualified = $pct >= 70;
          $barColor  = $qualified ? '#28a745' : '#dc3545';
          $pctColor  = $qualified ? '#155724' : '#721c24';
        ?>
        <tr>
          <td><span class="badge-course"><?= htmlspecialchars($cs['course_code']) ?></span></td>
          <td><?= $cs['attended'] ?></td>
          <td><?= $cs['total_sessions'] ?></td>
          <td>
            <div class="pct-bar-wrap">
              <div class="pct-bar-track">
                <div class="pct-bar" style="width:<?= min($pct,100) ?>%;background:<?= $barColor ?>"></div>
              </div>
              <span class="pct-label" style="color:<?= $pctColor ?>"><?= $pct ?>%</span>
            </div>
          </td>
          <td>
            <?php if ($qualified): ?>
              <span class="pill-qualified"><i class="fas fa-check-circle"></i> Qualified</span>
            <?php else: ?>
              <span class="pill-not-qualified"><i class="fas fa-times-circle"></i> Not Qualified</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($allRecords)): ?>
  <!-- Filter card -->
  <div id="records-section"></div>
  <div class="filter-card no-print">
    <div class="filter-row">
      <label><i class="fas fa-book"></i></label>
      <select id="course-filter" class="form-control" onchange="onCourseChange()">
        <option value="">All Courses</option>
        <?php foreach ($filterCourses as $fc): ?>
          <option value="<?= htmlspecialchars($fc['course_code']) ?>"><?= htmlspecialchars($fc['course_code']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="filter-divider"></div>

      <label><i class="fas fa-calendar"></i></label>
      <select id="date-filter" class="form-control" onchange="applyFilters()">
        <option value="">All Dates</option>
        <?php foreach ($filterDates as $fd): ?>
          <option value="<?= htmlspecialchars($fd['d']) ?>"><?= date('D, d M Y', strtotime($fd['d'])) ?></option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-secondary btn-small" onclick="clearFilters()">
        <i class="fas fa-times"></i> Clear
      </button>

      <div class="filter-divider"></div>
      <span id="record-count"></span>

      <div class="export-btns" style="margin-left:auto;">
        <button onclick="doPrint()" class="btn btn-secondary btn-small">
          <i class="fas fa-print"></i> Print / PDF
        </button>
        <a href="backend/export_student_history" id="csv-btn" class="btn btn-success btn-small">
          <i class="fas fa-file-csv"></i> Export CSV
        </a>
      </div>
    </div>

    <!-- Active filter chips -->
    <div class="filter-chips" id="filter-chips"></div>
  </div>

  <!-- Table -->
  <div class="history-table">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Photo</th>
          <th>Course</th>
          <th>Date</th>
          <th>Time</th>
          <th>Distance</th>
        </tr>
      </thead>
      <tbody id="history-tbody">
        <?php foreach ($allRecords as $i => $row):
          $dt = new DateTime($row['timestamp']);
        ?>
        <tr data-course="<?= htmlspecialchars($row['course_code']) ?>"
            data-date="<?= $dt->format('Y-m-d') ?>">
          <td class="row-num" style="color:var(--text-light);font-size:.82rem;"><?= $i + 1 ?></td>
          <td>
            <?php if (!empty($row['face_photo'])): ?>
              <img src="../<?= htmlspecialchars($row['face_photo']) ?>"
                   class="face-thumb" onclick="fcZoom(this)" title="Click to enlarge"
                   style="width:38px;height:38px;object-fit:cover;border-radius:50%;border:2px solid var(--primary);cursor:pointer;">
            <?php else: ?>
              <span style="color:var(--text-light);font-size:.8rem;">&mdash;</span>
            <?php endif; ?>
          </td>
          <td><span class="badge-course"><?= htmlspecialchars($row['course_code']) ?></span></td>
          <td><?= $dt->format('D, d M Y') ?></td>
          <td><?= $dt->format('h:i A') ?></td>
          <td><span class="badge-dist"><i class="fas fa-map-marker-alt"></i> <?= $row['distance'] ?>m</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="no-results" style="display:none;" class="empty-state">
      <i class="fas fa-search"></i>
      <h4>No Records Found</h4>
      <p>No attendance records match the selected filters.</p>
    </div>
  </div>

  <?php else: ?>
  <div class="history-table">
    <div class="empty-state">
      <i class="fas fa-clipboard-list"></i>
      <h4>No Attendance Records</h4>
      <p>You haven't marked attendance for any class yet.</p>
      <a href="student_dashboard" class="btn btn-primary" style="margin-top:10px;">Go to Dashboard</a>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
  var csvBase = 'backend/export_student_history';

  // Called when course changes: refresh date dropdown then re-filter
  function onCourseChange() {
    var course = document.getElementById('course-filter').value;
    updateDateDropdown(course);
    applyFilters();
  }

  // Rebuild date <select> to only show dates that have rows matching the selected course
  function updateDateDropdown(course) {
    var rows    = document.querySelectorAll('#history-tbody tr');
    var seen    = new Set();
    var ordered = [];
    rows.forEach(function(row) {
      var match = !course || row.dataset.course === course;
      if (match && !seen.has(row.dataset.date)) {
        seen.add(row.dataset.date);
        ordered.push(row.dataset.date);
      }
    });
    // dates arrive newest-first from PHP, keep that order
    var select  = document.getElementById('date-filter');
    var current = select.value;
    select.innerHTML = '<option value="">All Dates</option>';
    ordered.forEach(function(d) {
      var opt = document.createElement('option');
      opt.value = d;
      opt.textContent = formatDateLabel(d);
      if (d === current) opt.selected = true;
      select.appendChild(opt);
    });
    // clear selection if it's no longer available for this course
    if (current && !seen.has(current)) select.value = '';
  }

  function applyFilters() {
    var course = document.getElementById('course-filter').value;
    var date   = document.getElementById('date-filter').value;
    var rows   = document.querySelectorAll('#history-tbody tr');
    var visible = 0, rowNum = 1;

    rows.forEach(function(row) {
      var show = (!course || row.dataset.course === course) &&
                 (!date   || row.dataset.date   === date);
      row.style.display = show ? '' : 'none';
      if (show) { row.querySelector('.row-num').textContent = rowNum++; visible++; }
    });

    var countEl = document.getElementById('record-count');
    if (countEl) countEl.textContent = visible + ' record' + (visible !== 1 ? 's' : '');

    var noRes = document.getElementById('no-results');
    if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';

    updateChips(course, date);

    var params = [];
    if (course) params.push('course=' + encodeURIComponent(course));
    if (date)   params.push('date='   + encodeURIComponent(date));
    var csvBtn = document.getElementById('csv-btn');
    if (csvBtn) csvBtn.href = csvBase + (params.length ? '?' + params.join('&') : '');

    var info = [];
    if (course) info.push('Course: ' + course);
    if (date)   info.push('Date: ' + formatDateLabel(date));
    var pfi = document.getElementById('print-filter-info');
    if (pfi) pfi.textContent = info.length ? info.join('  |  ') : 'All records';
  }

  function clearFilters() {
    document.getElementById('course-filter').value = '';
    updateDateDropdown(''); // restore all dates
    document.getElementById('date-filter').value = '';
    applyFilters();
  }

  function updateChips(course, date) {
    var container = document.getElementById('filter-chips');
    if (!container) return;
    container.innerHTML = '';
    if (course) container.appendChild(makeChip('Course: ' + course, function() {
      document.getElementById('course-filter').value = '';
      updateDateDropdown('');
      applyFilters();
    }));
    if (date) container.appendChild(makeChip('Date: ' + formatDateLabel(date), function() {
      document.getElementById('date-filter').value = '';
      applyFilters();
    }));
  }

  function makeChip(label, onClose) {
    var chip = document.createElement('span');
    chip.className = 'filter-chip';
    chip.innerHTML = label + ' ';
    var btn = document.createElement('button');
    btn.innerHTML = '<i class="fas fa-times"></i>';
    btn.addEventListener('click', onClose);
    chip.appendChild(btn);
    return chip;
  }

  function formatDateLabel(iso) {
    if (!iso) return '';
    var days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d = new Date(iso + 'T00:00:00');
    var p = iso.split('-');
    return days[d.getDay()] + ', ' + parseInt(p[2]) + ' ' + months[d.getMonth()] + ' ' + p[0];
  }

  function doPrint() {
    var course = document.getElementById('course-filter').value;
    var date   = document.getElementById('date-filter').value;
    var info   = [];
    if (course) info.push('Course: ' + course);
    if (date)   info.push('Date: ' + formatDateLabel(date));
    var pfi = document.getElementById('print-filter-info');
    if (pfi) pfi.textContent = info.length ? info.join('  |  ') : 'All records';
    window.print();
  }

  function safeScrollAndClear() {
    var section = document.getElementById('records-section');
    if (section) {
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      clearFilters();
    }
  }

  document.addEventListener('DOMContentLoaded', function() { applyFilters(); });

  function fcZoom(img) {
    var modal = document.getElementById('face-zoom-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'face-zoom-modal';
      modal.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:9999;align-items:center;justify-content:center;cursor:pointer;';
      modal.innerHTML = '<img id="face-zoom-img" style="max-width:90vw;max-height:90vh;border-radius:12px;border:3px solid white;">';
      modal.addEventListener('click', function(){ modal.style.display='none'; });
      document.body.appendChild(modal);
    }
    document.getElementById('face-zoom-img').src = img.src;
    modal.style.display = 'flex';
  }
</script>
</body>
</html>
