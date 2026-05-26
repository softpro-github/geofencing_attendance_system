<?php
session_start();
include 'backend/db.php';

if (!isset($_SESSION['lecturer_id'])) {
    header("Location: index");
    exit;
}

$lecturer_id = (int)$_SESSION['lecturer_id'];
$session_id  = (int)($_GET['id'] ?? 0);

if (!$session_id) {
    header("Location: lecturer_history");
    exit;
}

// Auth: session must belong to this lecturer
$stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ? AND lecturer_id = ?");
$stmt->bind_param("ii", $session_id, $lecturer_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    header("Location: lecturer_history");
    exit;
}

// Fetch attendees
$stmt = $conn->prepare("
    SELECT a.matric_number, st.name, a.timestamp, ROUND(a.distance) as distance
    FROM attendance a
    LEFT JOIN students st ON a.matric_number = st.matric_number
    WHERE a.session_id = ?
    ORDER BY a.timestamp ASC
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lecturer name
$lrow = $conn->query("SELECT username FROM lecturers WHERE id = $lecturer_id")->fetch_assoc();

$dt        = new DateTime($session['started_at']);
$printDate = date('d M Y, h:i A');
$count     = count($attendees);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Report — <?= htmlspecialchars($session['course_code']) ?> <?= $dt->format('d M Y') ?></title>
    <style>
      * { margin: 0; padding: 0; box-sizing: border-box; }
      body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 13px;
        color: #1a202c;
        background: #fff;
        padding: 30px 40px;
      }

      /* ---- Header ---- */
      .report-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        border-bottom: 3px solid #0066cc; padding-bottom: 14px; margin-bottom: 20px;
      }
      .report-title h1 { font-size: 1.15rem; color: #0066cc; margin-bottom: 3px; }
      .report-title p  { font-size: .8rem; color: #718096; }
      .report-meta     { text-align: right; font-size: .78rem; color: #718096; }
      .report-meta strong { display: block; color: #1a202c; font-size: .88rem; margin-bottom: 2px; }

      /* ---- Info grid ---- */
      .info-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
        margin-bottom: 22px;
      }
      .info-box {
        border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px;
      }
      .info-box .label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #718096; margin-bottom: 4px; }
      .info-box .value { font-size: .95rem; font-weight: 700; color: #1a202c; }
      .info-box.highlight { border-color: #0066cc; background: #f0f7ff; }
      .info-box.highlight .value { color: #0066cc; font-size: 1.3rem; }

      /* ---- Table ---- */
      .section-title {
        font-size: .85rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: #0066cc; margin-bottom: 10px;
        border-left: 3px solid #0066cc; padding-left: 10px;
      }
      table {
        width: 100%; border-collapse: collapse; font-size: .88rem;
      }
      thead th {
        background: #f0f4f8; padding: 9px 12px; text-align: left;
        font-size: .76rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: #4a5568;
        border-bottom: 2px solid #0066cc;
      }
      tbody tr { border-bottom: 1px solid #e2e8f0; }
      tbody tr:last-child { border-bottom: none; }
      tbody tr:nth-child(even) { background: #f8fafc; }
      tbody td { padding: 9px 12px; }
      .num-col { color: #a0aec0; font-size: .78rem; width: 36px; }
      .matric  { font-weight: 700; font-size: .85rem; }
      .dist-badge {
        display: inline-block; padding: 2px 8px; border-radius: 12px;
        background: #d4edda; color: #155724; font-weight: 600; font-size: .78rem;
      }

      /* empty state */
      .empty { text-align: center; padding: 40px; color: #a0aec0; }

      /* ---- Footer ---- */
      .report-footer {
        margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: space-between;
        font-size: .72rem; color: #a0aec0;
      }

      /* ---- No-print toolbar ---- */
      .toolbar {
        position: fixed; top: 16px; right: 20px;
        display: flex; gap: 8px; z-index: 99;
      }
      .btn {
        padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;
        font-size: .85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
      }
      .btn-primary { background: #0066cc; color: white; }
      .btn-primary:hover { background: #0052a3; }
      .btn-secondary { background: #e2e8f0; color: #1a202c; }
      .btn-secondary:hover { background: #cbd5e0; }

      @media print {
        .toolbar { display: none !important; }
        body { padding: 15px 20px; }
        tbody tr:nth-child(even) { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        thead th { background: #f0f4f8 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .dist-badge { background: #d4edda !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .info-box.highlight { background: #f0f7ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
    </style>
</head>
<body>

  <!-- Toolbar (hidden when printing) -->
  <div class="toolbar">
    <button class="btn btn-primary" onclick="window.print()">&#128438; Print / Save PDF</button>
    <a href="javascript:window.close()" class="btn btn-secondary">✕ Close</a>
  </div>

  <!-- Report header -->
  <div class="report-header">
    <div class="report-title">
      <h1>Attendance Session Report</h1>
      <p>Smart Geofencing Attendance System</p>
    </div>
    <div class="report-meta">
      <strong><?= htmlspecialchars($lrow['username'] ?? 'Lecturer') ?></strong>
      Generated: <?= $printDate ?>
    </div>
  </div>

  <!-- Session info grid -->
  <div class="info-grid">
    <div class="info-box">
      <div class="label">Course</div>
      <div class="value"><?= htmlspecialchars($session['course_code']) ?></div>
    </div>
    <div class="info-box">
      <div class="label">Department</div>
      <div class="value"><?= htmlspecialchars($session['department']) ?></div>
    </div>
    <div class="info-box">
      <div class="label">Level</div>
      <div class="value"><?= htmlspecialchars($session['level']) ?></div>
    </div>
    <div class="info-box">
      <div class="label">Date</div>
      <div class="value"><?= $dt->format('D, d M Y') ?></div>
    </div>
    <div class="info-box">
      <div class="label">Start Time</div>
      <div class="value"><?= $dt->format('h:i A') ?></div>
    </div>
    <div class="info-box highlight">
      <div class="label">Students Present</div>
      <div class="value"><?= $count ?></div>
    </div>
  </div>

  <!-- Attendee table -->
  <div class="section-title">Attendance Record</div>

  <?php if (!empty($attendees)): ?>
  <table>
    <thead>
      <tr>
        <th class="num-col">#</th>
        <th>Matric Number</th>
        <th>Student Name</th>
        <th>Time Marked</th>
        <th>Distance</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($attendees as $i => $att):
        $adt = new DateTime($att['timestamp']);
      ?>
      <tr>
        <td class="num-col"><?= $i + 1 ?></td>
        <td class="matric"><?= htmlspecialchars($att['matric_number']) ?></td>
        <td><?= htmlspecialchars($att['name'] ?? 'Unknown') ?></td>
        <td><?= $adt->format('h:i A') ?></td>
        <td><span class="dist-badge"><?= $att['distance'] ?>m</span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No students marked attendance for this session.</div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="report-footer">
    <span>Session ID: #<?= $session_id ?></span>
    <span>Smart Geofencing Attendance &mdash; &copy; <?= date('Y') ?></span>
  </div>

</body>
</html>
