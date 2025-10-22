<?php
// doctor_dashboard.php — Doctor view: metrics + today's schedule
session_name('EConsultaDoctor');
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

/* --------------------------
   Identify the logged-in doctor
   -------------------------- */
// Preferred: store doctor_id at login time.
// Fallback: resolve via email if only $_SESSION['email'] is set for the doctor.
$doctor_id = isset($_SESSION['doctor_id']) ? (int)$_SESSION['doctor_id'] : 0;

if ($doctor_id <= 0 && !empty($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ? AND status='active' LIMIT 1");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $doctor_id = (int)$row['id'];
    $stmt->close();
}

if ($doctor_id <= 0) {
    // You can change this redirect to your doctor login page
    echo "Doctor not authenticated. Please log in as a doctor.";
    exit;
}

/* --------------------------
   Helpers
   -------------------------- */
function scalar($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $val = 0;
    if ($res && ($row = $res->fetch_row())) $val = (int)$row[0];
    $stmt->close();
    return $val;
}

// Build 30-min slots for clinic window (09:00–12:00, 13:00–17:00),
// then filter by doctor_schedule for the given weekday.
function getAllowedSlotsForDoctor($conn, $doctor_id, $dateYmd) {
    $weekday = date('l', strtotime($dateYmd)); // Monday..Sunday

    // Load doctor's raw availability windows for that weekday
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
        $windows[] = [$row['start_time'], $row['end_time']]; // strings "HH:MM:SS"
    }
    $stmt->close();

    if (empty($windows)) return []; // not working that day

    // Define clinic windows
    $baseSlots = [
        '09:00','09:30','10:00','10:30','11:00','11:30',
        '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'
    ];

    // Keep only slots that fall within ANY doctor window
    $allowed = [];
    foreach ($baseSlots as $t) {
        $tSec = strtotime("$dateYmd $t:00");
        foreach ($windows as [$ws, $we]) {
            $startOk = strtotime("$dateYmd $ws") <= $tSec;
            $endOk   = strtotime("$dateYmd $we")  >  $tSec; // slot start must be strictly before end
            if ($startOk && $endOk) {
                $allowed[] = $t; // e.g., "09:30"
                break;
            }
        }
    }
    return $allowed;
}

/* --------------------------
   Actions (from table)
   -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sid    = isset($_POST['screening_id']) ? (int)$_POST['screening_id'] : 0;

    if ($action === 'start' && $sid > 0) {
        $stmt = $conn->prepare("
            UPDATE screening_data
            SET consultation_status='in_progress'
            WHERE id = ? AND doctor_id = ?
        ");
        $stmt->bind_param("ii", $sid, $doctor_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['ok'] = "Consultation set to In-Progress.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'complete' && $sid > 0) {
        $findings = trim($_POST['findings'] ?? '');
        if ($findings !== '') {
            $stmt = $conn->prepare("
                UPDATE screening_data
                SET findings = ?, consultation_status='completed'
                WHERE id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("sii", $findings, $sid, $doctor_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['ok'] = "Findings saved. Consultation marked as Completed.";
        } else {
            $_SESSION['err'] = "Please enter findings before completing.";
        }
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }
}

/* --------------------------
   Date filter (defaults to today)
   -------------------------- */
$viewDate = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');

// Metrics
$totalAssigned = scalar(
    $conn,
    "SELECT COUNT(*) FROM screening_data WHERE doctor_id = ?",
    "i",
    [$doctor_id]
);

$todayScheduled = scalar(
    $conn,
    "SELECT COUNT(*) FROM screening_data
     WHERE doctor_id = ? AND status='approved' AND DATE(schedule_time) = ?",
    "is",
    [$doctor_id, $viewDate]
);

$todayCompleted = scalar(
    $conn,
    "SELECT COUNT(*) FROM screening_data
     WHERE doctor_id = ? AND consultation_status='completed' AND DATE(schedule_time) = ?",
    "is",
    [$doctor_id, $viewDate]
);

$overdue = scalar(
    $conn,
    "SELECT COUNT(*) FROM screening_data
     WHERE doctor_id = ?
       AND status='approved'
       AND schedule_time < NOW()
       AND consultation_status <> 'completed'",
    "i",
    [$doctor_id]
);

// Slots left today (based on doctor_schedule & booked)
$allowedSlots = getAllowedSlotsForDoctor($conn, $doctor_id, $viewDate);

// Count booked slots for the date (any status except rejected/pending without schedule)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM screening_data
    WHERE doctor_id = ?
      AND DATE(schedule_time) = ?
      AND status = 'approved'
");
$stmt->bind_param("is", $doctor_id, $viewDate);
$stmt->execute();
$res = $stmt->get_result();
$bookedCount = ($row = $res->fetch_assoc()) ? (int)$row['cnt'] : 0;
$stmt->close();

$slotsLeft = max(0, max(0, count($allowedSlots)) - $bookedCount);

/* --------------------------
   Fetch today's list
   -------------------------- */
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_initial, last_name,
       medical_concern, health_concern,
       symptoms_selected, symptoms_others,
       temperature, blood_pressure, height, weight, bmi,
       schedule_time, consultation_status

        FROM screening_data
        WHERE doctor_id = ?
          AND DATE(schedule_time) = ?
          AND status='approved'
          AND CONCAT(first_name,' ',COALESCE(middle_initial,''),' ',last_name) LIKE CONCAT('%',?,'%')
        ORDER BY schedule_time ASC
    ");
    $stmt->bind_param("iss", $doctor_id, $viewDate, $search);
} else {
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_initial, last_name,
       medical_concern, health_concern,
       symptoms_selected, symptoms_others,
       temperature, blood_pressure, height, weight, bmi,
       schedule_time, consultation_status

        FROM screening_data
        WHERE doctor_id = ?
          AND DATE(schedule_time) = ?
          AND status='approved'
        ORDER BY schedule_time ASC
    ");
    $stmt->bind_param("is", $doctor_id, $viewDate);
}
$stmt->execute();
$listRes = $stmt->get_result();

/* --------------------------
   Fetch doctor name (header)
   -------------------------- */
$docName = '';
$dn = $conn->query("SELECT CONCAT(first_name,' ',last_name) AS n FROM doctors WHERE id = {$doctor_id} LIMIT 1");
if ($dn && ($r = $dn->fetch_assoc())) $docName = $r['n'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{margin:0;font-family:'Inter',sans-serif;background:#f5f7fb;color:#24324b}
  .sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0f3a72;color:#fff;padding:20px 16px}
  .sidebar h2{font-size:16px;margin:10px 0 16px}
  .sidebar a{display:block;color:#fff;text-decoration:none;padding:8px 10px;border-radius:6px;font-size:13px;margin:4px 0}
  .sidebar a:hover{background:#0b2b55}
  .main{margin-left:240px;padding:28px}

  h1{margin:0 0 6px;font-size:22px}
  .muted{color:#6b7a92;font-size:13px;margin-bottom:18px}

  .cards{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin:18px 0 24px}
  .card{background:#fff;border:1px solid #e6ebf5;border-radius:12px;padding:14px 16px}
  .card h3{font-size:12px;color:#6b7a92;margin:0 0 8px;text-transform:uppercase;letter-spacing:.3px}
  .card .num{font-size:22px;font-weight:700}

  .filters{display:flex;gap:10px;align-items:center;margin:8px 0 18px}
  .filters input[type="date"], .filters input[type="text"]{padding:8px 10px;border-radius:8px;border:1px solid #cfd8ea}
  .filters button{padding:8px 12px;border:none;border-radius:8px;background:#0f3a72;color:#fff;cursor:pointer}
  .filters button:hover{background:#0b2b55}

  table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;border:1px solid #e6ebf5;overflow:hidden}
  th,td{padding:12px 10px;border-bottom:1px solid #eef2fa;text-align:center;font-size:13px}
  th{background:#0f3a72;color:#fff;text-transform:uppercase;font-size:12px;letter-spacing:.3px}
  tr:nth-child(even){background:#fafcff}
  .status{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700}
  .chip-upcoming{background:#eef4ff;color:#174ea6;border:1px solid #cfe0ff}
  .chip-now{background:#eafff1;color:#0a7a37;border:1px solid #c2f1d4}
  .chip-overdue{background:#fff1f1;color:#b00020;border:1px solid #f6c2c2}
  .chip-done{background:#e8fff8;color:#11695f;border:1px solid #bfeee7}
  .actions{display:flex;gap:6px;justify-content:center;flex-wrap:wrap}
  .btn{padding:8px 10px;border:none;border-radius:8px;cursor:pointer;font-size:12px}
  .btn.start{background:#174ea6;color:#fff}
  .btn.complete{background:#0a7a37;color:#fff}
  .btn.link{background:#eef2fb;color:#174ea6}
  .btn.pdf{background:#003d99;color:#fff}
  .btn:hover{opacity:.92}

  .alert{margin:8px 0 16px;padding:10px 12px;border-radius:8px;font-size:13px}
  .ok{background:#e6fff0;color:#0b7a3c;border:1px solid #b7efcc}
  .err{background:#ffeeee;color:#b00020;border:1px solid #f5c2c7}

  details summary{cursor:pointer;color:#174ea6}
  textarea{width:100%;min-height:70px;border:1px solid #cfd8ea;border-radius:8px;padding:8px}
</style>
</head>
<body>
  <div class="sidebar">
    <h2>Doctor Panel</h2>
    <div style="font-size:13px;opacity:.85;margin-bottom:12px;">Logged in as<br><strong><?= htmlspecialchars($docName ?: 'Doctor #'.$doctor_id) ?></strong></div>
    <a href="doctor_dashboard.php" style="background:#0b2b55">Dashboard</a>
    <a href="doctor_calendar.php">Calendar</a>
    <a href="doctor_logout.php">Logout</a>
  </div>

  <div class="main">
    <h1>Dashboard</h1>
    <div class="muted">Overview and today’s schedule</div>

    <?php if (!empty($_SESSION['ok'])): ?>
      <div class="alert ok"><?= htmlspecialchars($_SESSION['ok']); unset($_SESSION['ok']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['err'])): ?>
      <div class="alert err"><?= htmlspecialchars($_SESSION['err']); unset($_SESSION['err']); ?></div>
    <?php endif; ?>

    <div class="cards">
      <div class="card"><h3>Total Assigned</h3><div class="num"><?= $totalAssigned ?></div></div>
      <div class="card"><h3>Scheduled (<?= htmlspecialchars(date('M j, Y', strtotime($viewDate))) ?>)</h3><div class="num"><?= $todayScheduled ?></div></div>
      <div class="card"><h3>Completed Today</h3><div class="num"><?= $todayCompleted ?></div></div>
      <div class="card"><h3>Overdue</h3><div class="num"><?= $overdue ?></div></div>
      <div class="card"><h3>Slots Left Today</h3><div class="num"><?= $slotsLeft ?></div></div>
    </div>

    <form class="filters" method="get">
      <label for="date">Date:</label>
      <input type="date" id="date" name="date" value="<?= htmlspecialchars($viewDate) ?>">
      <input type="text" name="search" placeholder="Search patient..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Apply</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>Time</th>
          <th>Patient</th>
          <th>Chief Complaint</th>
          <th>Vitals (T / BP / BMI)</th>
          <th>Status</th>
          <th style="width:320px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $now = time();
        if ($listRes && $listRes->num_rows > 0):
          while ($row = $listRes->fetch_assoc()):
            $startTs = strtotime($row['schedule_time']);
            $endTs   = $startTs + 30*60;
            // status label
            if ($row['consultation_status'] === 'completed') {
              $chip = '<span class="status chip-done">Completed</span>';
            } elseif ($now >= $startTs && $now < $endTs) {
              $chip = '<span class="status chip-now">Now</span>';
            } elseif ($now >= $endTs && $row['consultation_status'] !== 'completed') {
              $chip = '<span class="status chip-overdue">Overdue</span>';
            } elseif ($row['consultation_status'] === 'in_progress') {
              $chip = '<span class="status chip-now">In-Progress</span>';
            } else {
              $chip = '<span class="status chip-upcoming">Upcoming</span>';
            }

            $name = trim(($row['first_name'] ?? '').' '.(($row['middle_initial'] ?? '') ? $row['middle_initial'].'. ' : '').($row['last_name'] ?? ''));
            $timeLabel = date('g:i A', $startTs).' - '.date('g:i A', $endTs);
            $vitals = [];
            if ($row['temperature'] !== null && $row['temperature'] !== '') $vitals[] = htmlspecialchars($row['temperature']).'°C';
            if ($row['blood_pressure'] !== null && $row['blood_pressure'] !== '') $vitals[] = htmlspecialchars($row['blood_pressure']);
            if ($row['bmi'] !== null && $row['bmi'] !== '') $vitals[] = 'BMI '.htmlspecialchars($row['bmi']);
            $vitalStr = $vitals ? implode(' / ', $vitals) : '—';
        ?>
        <tr>
          <td><?= htmlspecialchars($timeLabel) ?></td>
          <td><?= htmlspecialchars($name) ?></td>
          <td>
          <?php
            $symptoms = trim(
              implode(', ',
                array_filter([
                  $row['symptoms_selected'] ?? '',
                  $row['symptoms_others'] ?? ''
                ])
              )
            );
            echo htmlspecialchars($symptoms !== '' ? $symptoms : '—');
          ?>
        </td>

          <td><?= $vitalStr ?></td>
          <td><?= $chip ?></td>
          <td>
            <div class="actions">
              <?php if ($row['consultation_status'] === 'completed'): ?>
                <a class="btn link" href="doctor_notes.php?sid=<?= (int)$row['id'] ?>">View Notes</a>
                <a class="btn pdf" href="doctor_visit_summary.php?sid=<?= (int)$row['id'] ?>&print=1" target="_blank">Visit Summary (PDF)</a>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="screening_id" value="<?= (int)$row['id'] ?>">
                  <button class="btn start" name="action" value="start" type="submit">Start</button>
                </form>

                <a href="doctor_consult_details.php?id=<?= (int)$row['id'] ?>" class="btn link">View / Add Findings</a>

                
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php
          endwhile;
        else:
        ?>
        <tr><td colspan="6" style="color:#6b7a92;">No scheduled patients for this date.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
