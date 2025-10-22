<?php
// doctor_calendar.php — Month calendar + day schedule panel WITH sidebar
session_name('EConsultaDoctor');
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Auth: expect doctor_id in session
if (empty($_SESSION['doctor_id'])) {
  header("Location: doctor_login.php");
  exit();
}
$doctor_id = (int)$_SESSION['doctor_id'];

// (Optional) Doctor name for sidebar header
$docName = '';
$dn = $conn->query("SELECT CONCAT(first_name,' ',last_name) AS n FROM doctors WHERE id = {$doctor_id} LIMIT 1");
if ($dn && ($r = $dn->fetch_assoc())) $docName = $r['n'];

// Helper: get 30-min clinic slots for the chosen date (filtered by doctor_schedule)
function getAllowedSlotsForDoctor($conn, $doctor_id, $dateYmd) {
  $weekday = date('l', strtotime($dateYmd)); // Monday..Sunday

  $stmt = $conn->prepare("
      SELECT start_time, end_time
      FROM doctor_schedule
      WHERE doctor_id = ? AND day_of_week = ?
  ");
  $stmt->bind_param("is", $doctor_id, $weekday);
  $stmt->execute();
  $res = $stmt->get_result();
  $windows = [];
  while ($row = $res->fetch_assoc()) {
    $windows[] = [$row['start_time'], $row['end_time']];
  }
  $stmt->close();
  if (empty($windows)) return [];

  // Clinic base slots (your rule)
  $base = [
    '09:00','09:30','10:00','10:30','11:00','11:30',
    '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'
  ];

  $allowed = [];
  foreach ($base as $t) {
    $tSec = strtotime("$dateYmd $t:00");
    foreach ($windows as [$ws, $we]) {
      if (strtotime("$dateYmd $ws") <= $tSec && strtotime("$dateYmd $we") > $tSec) {
        $allowed[] = $t;
        break;
      }
    }
  }
  return $allowed;
}

// Build the day panel slots (server-rendered)
$today = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$slots = getAllowedSlotsForDoctor($conn, $doctor_id, $today);

// Get booked times for the day
$booked = [];
if ($slots) {
  $stmt = $conn->prepare("
    SELECT id, schedule_time, CONCAT(first_name,' ',COALESCE(middle_initial,''),' ',last_name) AS name
    FROM screening_data
    WHERE doctor_id = ?
      AND status='approved'
      AND DATE(schedule_time) = ?
  ");
  $stmt->bind_param("is", $doctor_id, $today);
  $stmt->execute();
  $rs = $stmt->get_result();
  while ($r = $rs->fetch_assoc()) {
    $at = date('H:i', strtotime($r['schedule_time']));
    $booked[$at] = [
      'sid' => (int)$r['id'],
      'label' => date('g:i A', strtotime($r['schedule_time'])) . ' — ' . $r['name']
    ];
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Calendar</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{margin:0;font-family:'Inter',sans-serif;background:#f5f7fb;color:#1f2a44}

  /* ===== Sidebar (same style family as doctor_dashboard) ===== */
  .sidebar{
    position:fixed;top:0;left:0;width:220px;height:100vh;background:#0f3a72;color:#fff;padding:20px 16px
  }
  .sidebar h2{font-size:16px;margin:10px 0 6px}
  .sidebar .small{font-size:12px;opacity:.9;margin-bottom:10px}
  .sidebar a{display:block;color:#fff;text-decoration:none;padding:8px 10px;border-radius:6px;font-size:13px;margin:4px 0}
  .sidebar a:hover{background:#0b2b55}
  .sidebar a.active{background:#0b2b55}

  /* ===== Main content pushed by sidebar ===== */
  .main{margin-left:240px;padding:22px 16px}

  /* ===== Calendar + Day panel (kept from your working page) ===== */
  .layout{display:grid;grid-template-columns: 1.2fr .8fr;gap:16px;max-width:1200px;margin:0 auto}
  .panel{background:#fff;border:1px solid #e6ebf5;border-radius:12px;padding:14px}
  h2{margin:0 0 10px;color:#0f3a72}
  .day-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .slots{display:flex;flex-direction:column;gap:8px;max-height:70vh;overflow:auto;padding-right:4px}
  .slot{border:1px dashed #cfd8ea;border-radius:10px;padding:10px 12px;font-size:13px;display:flex;justify-content:space-between;align-items:center;background:#fafcff}
  .slot .t{font-weight:700;color:#0f3a72}
  .slot.free{opacity:.9}
  .slot.busy{background:#ffe680;border-color:#f1b300} /* stronger yellow highlight */
  .slot .act{display:flex;gap:8px}
  .btn{padding:6px 10px;border:none;border-radius:8px;cursor:pointer;font-size:12px}
  .btn.view{background:#0f3a72;color:#fff}
  .btn.today{background:#0f3a72;color:#fff}
  .muted{color:#6b7a92}
  /* FullCalendar tweaks to keep your yellow events */
  .fc .fc-daygrid-event{
    border:1px solid #f1b300 !important;
    background:#ffc61a !important; /* bright yellow */
    color:#1a2b6d !important;
    border-radius:8px !important;
    padding:2px 4px !important;
    font-weight:600;
  }
</style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h3>Doctor Panel</h3>
    <div class="small">Logged in as<br><strong><?= htmlspecialchars($docName ?: 'Doctor #'.$doctor_id) ?></strong></div>
    <a href="doctor_dashboard.php">Dashboard</a>
    <a href="doctor_calendar.php" class="active">Calendar</a>
    <a href="doctor_logout.php">Logout</a>
  </div>

  <!-- Main content -->
  <div class="main">
    <div class="layout">
      <!-- Left: Month Calendar -->
      <div class="panel">
        <h2>Monthly Schedule</h2>
        <div id="calendar"></div>
      </div>

      <!-- Right: Day Schedule -->
      <div class="panel">
        <div class="day-header">
          <h2>Day Schedule</h2>
          <div>
            <button class="btn today" id="btnToday">Today</button>
          </div>
        </div>
        <div class="muted" id="dayLabel"><?= htmlspecialchars(date('l • M j, Y', strtotime($today))) ?></div>
        <div class="slots" id="slotsList">
          <?php if (!$slots): ?>
            <div class="muted" style="margin-top:8px;">No clinic hours set for this day.</div>
          <?php else: foreach ($slots as $t): 
            $isBooked = isset($booked[$t]);
            $label = $isBooked ? $booked[$t]['label'] : (date('g:i A', strtotime("$today $t")) . ' — Available');
            $sid = $isBooked ? (int)$booked[$t]['sid'] : 0;
          ?>
            <div class="slot <?= $isBooked ? 'busy' : 'free' ?>">
              <div class="t"><?= htmlspecialchars(date('g:i A', strtotime("$today $t"))) ?></div>
              <div class="muted" style="flex:1;margin-left:10px;"><?= htmlspecialchars($label) ?></div>
              <div class="act">
                <?php if ($isBooked): ?>
                  <a class="btn view" target="_blank" href="doctor_consult_details.php?id=<?= $sid ?>">Open</a>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
  // Helper: fetch and rebuild the right panel for a given date
  function reloadDayPanel(ymd){
    const url = new URL(window.location.href);
    url.searchParams.set('date', ymd);
    window.location.href = url.toString();
  }

  document.getElementById('btnToday').addEventListener('click', function(){
    reloadDayPanel(new Date().toISOString().slice(0,10));
  });

  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      height: 'auto',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'
      },
      events: 'doctor_events.php', // returns JSON for this doctor via session
      dateClick: function(info){
        // when clicking a date cell, refresh day panel to that date
        reloadDayPanel(info.dateStr);
      },
      eventClick: function(info){
        // open details using id= (as your working code)
        const sid = info.event.extendedProps.sid || info.event.id;
        if (sid) window.open('doctor_consult_details.php?id=' + sid, '_blank');
      }
    });
    calendar.render();
  });
</script>
</body>
</html>
