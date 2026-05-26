<?php
session_start();
include 'backend/db.php';

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: index");
    exit;
}

$id     = (int)$_SESSION['lecturer_id'];
$filter = trim($_GET['course'] ?? '');
$date   = trim($_GET['date']   ?? '');

// Validate date format
if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}

// Lecturer info
$lstmt = $conn->prepare("SELECT username FROM lecturers WHERE id = ?");
$lstmt->bind_param("i", $id);
$lstmt->execute();
$lrow = $lstmt->get_result()->fetch_assoc();

// Courses for filter dropdown
$cstmt = $conn->prepare("SELECT course_code FROM courses WHERE lecturer_id = ? ORDER BY course_code");
$cstmt->bind_param("i", $id);
$cstmt->execute();
$cres = $cstmt->get_result();
$courseList = [];
while ($c = $cres->fetch_assoc()) $courseList[] = $c['course_code'];
if ($filter && !in_array($filter, $courseList)) $filter = '';

// Distinct session dates for date dropdown (filtered by course if one is selected)
if ($filter) {
    $dstmt = $conn->prepare("SELECT DISTINCT DATE(started_at) as d FROM attendance_sessions WHERE lecturer_id = ? AND course_code = ? ORDER BY d DESC");
    $dstmt->bind_param("is", $id, $filter);
} else {
    $dstmt = $conn->prepare("SELECT DISTINCT DATE(started_at) as d FROM attendance_sessions WHERE lecturer_id = ? ORDER BY d DESC");
    $dstmt->bind_param("i", $id);
}
$dstmt->execute();
$filterDates = $dstmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Overall stats (unfiltered)
$tstmt = $conn->prepare("SELECT COUNT(*) as n FROM attendance_sessions WHERE lecturer_id = ?");
$tstmt->bind_param("i", $id);
$tstmt->execute();
$totalSessions = $tstmt->get_result()->fetch_assoc()['n'];

$atstmt = $conn->prepare("SELECT COUNT(*) as n FROM attendance WHERE session_id IN (SELECT id FROM attendance_sessions WHERE lecturer_id = ?)");
$atstmt->bind_param("i", $id);
$atstmt->execute();
$totalAttended = $atstmt->get_result()->fetch_assoc()['n'];

// Build dynamic query with optional course + date filters
$where  = "WHERE s.lecturer_id = ?";
$types  = "i";
$params = [$id];

if ($filter) { $where .= " AND s.course_code = ?";        $types .= "s"; $params[] = $filter; }
if ($date)   { $where .= " AND DATE(s.started_at) = ?";   $types .= "s"; $params[] = $date;   }

$stmt = $conn->prepare("
    SELECT s.id, s.course_code, s.started_at, s.status, s.department, s.level,
           COUNT(a.id) as attendee_count
    FROM attendance_sessions s
    LEFT JOIN attendance a ON s.id = a.session_id
    $where
    GROUP BY s.id ORDER BY s.started_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sessions = $stmt->get_result();

// Pre-fetch attendees for every session
$as = $conn->prepare("
    SELECT a.matric_number, st.name, a.timestamp, ROUND(a.distance) as distance
    FROM attendance a
    LEFT JOIN students st ON a.matric_number = st.matric_number
    WHERE a.session_id = ?
    ORDER BY a.timestamp ASC
");
$sessionData = [];
while ($s = $sessions->fetch_assoc()) {
    $as->bind_param("i", $s['id']);
    $as->execute();
    $s['attendees'] = $as->get_result()->fetch_all(MYSQLI_ASSOC);
    $sessionData[]  = $s;
}

$printDate = date('d M Y, h:i A');

// CSV link params
$csvParts = [];
if ($filter) $csvParts[] = 'course=' . urlencode($filter);
if ($date)   $csvParts[] = 'date='   . urlencode($date);
$csvParams = $csvParts ? '?' . implode('&', $csvParts) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session History - Lecturer</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
      .page-header {
        background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
        color: white; padding: 20px 25px; border-radius: 10px; margin-bottom: 25px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
      }
      .page-header h3 { margin: 0; font-size: 1.2rem; font-weight: 700; }
      .page-header p  { margin: 4px 0 0; opacity: .9; font-size: .88rem; }

      .filter-card {
        background: white; padding: 16px 20px; border-radius: 10px;
        box-shadow: var(--shadow-sm); margin-bottom: 20px; overflow-x: auto;
      }
      .filter-row {
        display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; min-width: max-content;
      }
      .filter-row label { font-weight: 600; font-size: .85rem; color: var(--text-light); margin: 0; white-space: nowrap; }
      .filter-row select { width: 180px; flex: none; }
      .filter-row .export-btns { display: flex; gap: 8px; margin-left: auto; flex-shrink: 0; }
      .filter-divider { width: 1px; height: 32px; background: var(--border-color); }

      /* active filter chips */
      .filter-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
      .filter-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600;
        background: #e8f0fe; color: var(--primary); border: 1px solid #c5d8f8;
      }

      .session-card {
        background: white; border: 2px solid var(--border-color);
        border-radius: 10px; margin-bottom: 12px; overflow: hidden;
      }
      .session-card.has-students { border-left: 4px solid var(--primary); }
      .session-card.no-students  { border-left: 4px solid var(--border-color); }

      .session-header {
        padding: 14px 18px; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center; gap: 10px;
        user-select: none;
      }
      .session-header:hover { background: #f8faff; }
      .session-meta { flex: 1; }
      .session-course { font-size: 1.05rem; font-weight: 700; color: var(--primary); }
      .session-detail { font-size: .83rem; color: var(--text-light); margin-top: 3px; }
      .session-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

      .count-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 11px; border-radius: 20px; font-size: .82rem; font-weight: 700;
        background: #e8f0fe; color: var(--primary);
      }
      .count-badge.zero { background: var(--light-bg); color: var(--text-light); }

      .status-pill { padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 700; }
      .status-pill.active   { background: #d4edda; color: #155724; }
      .status-pill.inactive { background: #e2e3e5; color: #383d41; }

      .btn-print-session {
        padding: 4px 10px; font-size: .78rem; background: var(--light-bg);
        border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;
        color: var(--text-dark); transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
      }
      .btn-print-session:hover { background: #e8f0fe; border-color: var(--primary); color: var(--primary); }

      .chevron { transition: transform .25s; color: var(--text-light); flex-shrink: 0; }
      .chevron.open { transform: rotate(180deg); }

      .attendee-panel { display: none; border-top: 1px solid var(--border-color); }
      .attendee-panel.open { display: block; }
      .attendee-panel table { margin: 0; }
      .attendee-panel thead th {
        background: #f8fafc; font-size: .79rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        padding: 10px 16px; border: none; color: var(--text-light);
      }
      .attendee-panel tbody td { padding: 11px 16px; font-size: .9rem; border-color: var(--border-color); }
      .attendee-panel tbody tr:hover { background: #f8faff; }

      .badge-dist {
        display: inline-block; padding: 3px 9px; border-radius: 20px;
        background: #d4edda; color: #155724; font-size: .8rem; font-weight: 600;
      }
      .empty-att { padding: 18px; text-align: center; color: var(--text-light); font-size: .88rem; }
      .empty-state { text-align: center; padding: 55px 20px; color: var(--text-light); }
      .empty-state i { font-size: 3rem; margin-bottom: 12px; color: var(--border-color); display: block; }

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
        .attendee-panel { display: block !important; }
        .chevron, .btn-print-session { display: none !important; }
        .session-card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ccc !important; margin-bottom: 8px !important; }
        .session-header { cursor: default !important; }
        .session-header:hover { background: white !important; }
        .count-badge { background: #e8f0fe !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .status-pill.active   { background: #d4edda !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .status-pill.inactive { background: #e2e3e5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .badge-dist { background: #d4edda !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .attendee-panel tbody tr:hover { background: white !important; }
        .filter-chips { display: none; }
      }
    </style>
</head>
<body>
<div class="dashboard">

  <!-- Print-only header -->
  <div class="print-header">
    <h2>Smart Geofencing Attendance System</h2>
    <p><strong>Session History Report</strong></p>
    <p>
      Lecturer: <?= htmlspecialchars($lrow['username'] ?? '') ?>
      <?php if ($filter): ?> &nbsp;|&nbsp; Course: <?= htmlspecialchars($filter) ?><?php endif; ?>
      <?php if ($date): ?> &nbsp;|&nbsp; Date: <?= date('d M Y', strtotime($date)) ?><?php endif; ?>
    </p>
    <p style="font-size:.75rem;color:#888;">Generated: <?= $printDate ?></p>
  </div>

  <!-- Navbar -->
  <div class="navbar no-print">
    <div class="navbar-brand">
      <img src="assets/img/logo.png" alt="Logo">
      <h2><i class="fas fa-history"></i> Session History</h2>
    </div>
    <div class="nav-actions">
      <a href="lecturer_dashboard" class="btn btn-secondary btn-small">
        <i class="fas fa-arrow-left"></i> Dashboard
      </a>
      <a href="backend/admin_logout" class="btn btn-danger btn-small">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <!-- Page header -->
  <div class="page-header">
    <div>
      <h3><i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($lrow['username'] ?? 'Lecturer') ?></h3>
      <p>Full history of all attendance sessions and student participation</p>
    </div>
  </div>

  <!-- Stats -->
   <!--
  <div class="stats-grid" style="margin-bottom: 22px;">
    <a href="lecturer_history" class="stat-card" style="text-decoration:none;">
      <div class="stat-value"><?= $totalSessions ?></div>
      <div class="stat-label">Total Sessions</div>
      <div style="font-size:.72rem;opacity:.75;margin-top:6px;">View all &rarr;</div>
    </a>
    <a href="lecturer_history" class="stat-card alt-1" style="text-decoration:none;">
      <div class="stat-value"><?= $totalAttended ?></div>
      <div class="stat-label">Total Attendance Records</div>
      <div style="font-size:.72rem;opacity:.75;margin-top:6px;">View all &rarr;</div>
    </a>
    <a href="lecturer_history" class="stat-card alt-2" style="text-decoration:none;">
      <div class="stat-value"><?= count($courseList) ?></div>
      <div class="stat-label">Courses</div>
      <div style="font-size:.72rem;opacity:.75;margin-top:6px;">Clear filters &rarr;</div>
    </a>
  </div> -->

  <!-- Filter card -->
  <?php if (!empty($courseList)): ?>
  <div class="filter-card no-print">
    <form method="GET" action="lecturer_history" class="filter-row">
      <label><i class="fas fa-book"></i></label>
      <select name="course" class="form-control">
        <option value="">All Courses</option>
        <?php foreach ($courseList as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $filter === $c ? 'selected' : '' ?>>
            <?= htmlspecialchars($c) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="filter-divider"></div>

      <label><i class="fas fa-calendar"></i></label>
      <select name="date" class="form-control">
        <option value="">All Dates</option>
        <?php foreach ($filterDates as $fd): ?>
          <option value="<?= htmlspecialchars($fd['d']) ?>" <?= $date === $fd['d'] ? 'selected' : '' ?>>
            <?= date('D, d M Y', strtotime($fd['d'])) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn btn-primary btn-small">Apply</button>
      <?php if ($filter || $date): ?>
        <a href="lecturer_history" class="btn btn-secondary btn-small">Clear</a>
      <?php endif; ?>

      <div class="export-btns" style="margin-left:auto;">
        <button type="button" onclick="window.print()" class="btn btn-secondary btn-small">
          <i class="fas fa-print"></i> Print / PDF
        </button>
        <a href="backend/export_lecturer_history.php<?= htmlspecialchars($csvParams) ?>" class="btn btn-success btn-small">
          <i class="fas fa-file-csv"></i> Export CSV
        </a>
      </div>
    </form>

    <!-- Active filter chips -->
    <?php if ($filter || $date): ?>
    <div class="filter-chips">
      <?php if ($filter): ?>
        <span class="filter-chip"><i class="fas fa-book"></i> <?= htmlspecialchars($filter) ?></span>
      <?php endif; ?>
      <?php if ($date): ?>
        <span class="filter-chip"><i class="fas fa-calendar"></i> <?= date('D, d M Y', strtotime($date)) ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Session list -->
  <?php if (!empty($sessionData)): ?>

    <div class="no-print" style="font-size:.84rem;color:var(--text-light);margin-bottom:12px;">
      <?= count($sessionData) ?> session<?= count($sessionData) !== 1 ? 's' : '' ?>
      <?php if ($filter || $date): ?>
        &mdash;
        <?= $filter ? '<strong>' . htmlspecialchars($filter) . '</strong>' : '' ?>
        <?= ($filter && $date) ? ' on ' : '' ?>
        <?= $date ? '<strong>' . date('D, d M Y', strtotime($date)) . '</strong>' : '' ?>
      <?php endif; ?>
      &bull; Click a session row to expand attendees
    </div>

    <?php foreach ($sessionData as $idx => $s):
      $dt    = new DateTime($s['started_at']);
      $count = (int)$s['attendee_count'];
    ?>
    <div class="session-card <?= $count > 0 ? 'has-students' : 'no-students' ?>">

      <div class="session-header" onclick="toggleSession(<?= $idx ?>)">
        <div class="session-meta">
          <div class="session-course"><?= htmlspecialchars($s['course_code']) ?></div>
          <div class="session-detail">
            <i class="fas fa-calendar-alt"></i> <?= $dt->format('D, d M Y') ?> at <?= $dt->format('h:i A') ?>
            &nbsp;&bull;&nbsp;
            <i class="fas fa-sitemap"></i> <?= htmlspecialchars($s['department']) ?> / <?= htmlspecialchars($s['level']) ?>
          </div>
        </div>
        <div class="session-right">
          <span class="status-pill <?= $s['status'] === 'active' ? 'active' : 'inactive' ?>">
            <i class="fas fa-circle" style="font-size:.5rem;vertical-align:1px;"></i>
            <?= ucfirst($s['status']) ?>
          </span>
          <span class="count-badge <?= $count === 0 ? 'zero' : '' ?>">
            <i class="fas fa-users"></i> <?= $count ?> student<?= $count !== 1 ? 's' : '' ?>
          </span>
          <!-- Per-session print button: stops click from bubbling to the expand toggle -->
          <a href="print_session?id=<?= $s['id'] ?>" target="_blank"
             class="btn-print-session no-print"
             onclick="event.stopPropagation()"
             title="Print this session">
            <i class="fas fa-print"></i> Print
          </a>
          <i class="fas fa-chevron-down chevron" id="chev-<?= $idx ?>"></i>
        </div>
      </div>

      <div class="attendee-panel" id="panel-<?= $idx ?>">
        <?php if (!empty($s['attendees'])): ?>
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Matric Number</th>
              <th>Student Name</th>
              <th>Time Marked</th>
              <th>Distance</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($s['attendees'] as $ai => $att):
              $adt = new DateTime($att['timestamp']);
            ?>
            <tr>
              <td style="color:var(--text-light);font-size:.8rem;"><?= $ai + 1 ?></td>
              <td style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($att['matric_number']) ?></td>
              <td><?= htmlspecialchars($att['name'] ?? 'Unknown') ?></td>
              <td><?= $adt->format('h:i A') ?></td>
              <td><span class="badge-dist"><i class="fas fa-map-marker-alt"></i> <?= $att['distance'] ?>m</span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty-att">
            <i class="fas fa-user-slash" style="margin-right:6px;"></i>
            No students marked attendance for this session.
          </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>

  <?php else: ?>
  <div class="session-card" style="border:2px solid var(--border-color);">
    <div class="empty-state">
      <i class="fas fa-calendar-times"></i>
      <h4>No Sessions Found</h4>
      <p>
        <?php if ($filter || $date): ?>
          No sessions match
          <?= $filter ? '<strong>' . htmlspecialchars($filter) . '</strong>' : '' ?>
          <?= ($filter && $date) ? ' on ' : '' ?>
          <?= $date ? '<strong>' . date('D, d M Y', strtotime($date)) . '</strong>' : '' ?>.
        <?php else: ?>
          You haven't activated any attendance sessions yet.
        <?php endif; ?>
      </p>
      <a href="lecturer_history" class="btn btn-secondary btn-small" style="margin-top:10px;">Clear Filters</a>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
  function toggleSession(idx) {
    var panel = document.getElementById('panel-' + idx);
    var chev  = document.getElementById('chev-'  + idx);
    var isOpen = panel.classList.contains('open');
    panel.classList.toggle('open', !isOpen);
    chev.classList.toggle('open',  !isOpen);
  }
</script>
</body>
</html>
