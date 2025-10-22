<?php
session_name('EConsultaAdmin');
session_start();

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Admin guard (accept new key OR legacy 'email')
if (empty($_SESSION['admin_username']) && empty($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

// ===== ADD: tiny helper to create a Jitsi meeting tied to a screening_id =====
function create_jitsi_meeting(mysqli $conn, int $screening_id, string $start, ?string $end, int $adminUserId): int {
    $slug = 'CONSULT-'.strtoupper(base_convert(time(),10,36)).'-'.strtoupper(bin2hex(random_bytes(6)));
    $url  = "https://meet.jit.si/$slug";

    $stmt = $conn->prepare("INSERT INTO video_meetings
        (provider, room_slug, url, scheduled_start, scheduled_end, status, screening_id, created_by)
        VALUES ('jitsi',?,?,?,?, 'scheduled', ?, ?)");
    $stmt->bind_param("sssiii", $slug, $url, $start, $end, $screening_id, $adminUserId);
    if (!$stmt->execute()) throw new Exception('DB error creating meeting: '.$conn->error);
    return $conn->insert_id;
}


/* =========================
   ACTIONS
   ========================= */

// 1) Mark as completed (for items in Scheduled tab)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    $screening_id = isset($_POST['screening_id']) ? (int)$_POST['screening_id'] : 0;
    if ($screening_id > 0) {
        $stmt = $conn->prepare("UPDATE screening_data SET consultation_status='completed' WHERE id=?");
        $stmt->bind_param("i", $screening_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['sched_ok'] = "Consultation marked as completed.";
    } else {
        $_SESSION['sched_error'] = "Invalid record.";
    }
    // redirect back to keep query params (tab/search)
    $redirect = $_SERVER['REQUEST_URI'] ?: 'admin_consult.php';
    header("Location: ".$redirect); exit;
}

// 2) SAVE SCHEDULE — (kept exactly as your working code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $screening_id = isset($_POST['screening_id']) ? (int)$_POST['screening_id'] : 0;
    $date_raw     = $_POST['sched_date'] ?? '';
    $time_raw     = $_POST['sched_time'] ?? '';

    if ($screening_id <= 0 || !$date_raw || !$time_raw) {
        $_SESSION['sched_error'] = "Please select both date and time.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Find the single active physician (specialization contains 'physician')
    $physStmt = $conn->prepare("
        SELECT id
        FROM doctors
        WHERE status='active'
          AND LOWER(specialization) LIKE '%physician%'
        LIMIT 1
    ");
    $physStmt->execute();
    $physRes = $physStmt->get_result();
    $physRow = $physRes->fetch_assoc();
    $physStmt->close();

    if (!$physRow) {
        $_SESSION['sched_error'] = "No active physician found. Please add/activate a physician.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }
    $doctor_id = (int)$physRow['id'];

    // Build schedule_time from date + time (Asia/Manila)
    $tz = new DateTimeZone('Asia/Manila');
    $dt = DateTime::createFromFormat('Y-m-d H:i', $date_raw.' '.$time_raw, $tz);
    if (!$dt) {
        $_SESSION['sched_error'] = "Invalid date/time.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Validate 30-min grid & clinic windows (09:00–12:00 and 13:00–17:00; no 12:xx)
    $h = (int)$dt->format('H');
    $m = (int)$dt->format('i');
    $onGrid  = ($m === 0 || $m === 30);
    $lunch   = ($h === 12);
    $startOk = ($h >= 9 && $h <= 11) || ($h === 11 && $m === 30) || ($h >= 13 && $h <= 16) || ($h === 16 && $m === 30);

    if (!$onGrid || $lunch || !$startOk) {
        $_SESSION['sched_error'] = "Time must be a 30-min slot within 09:00–12:00 or 13:00–17:00.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    $slot      = $dt->format('Y-m-d H:i:s');
    $slotDate  = $dt->format('Y-m-d');
    $dayOfWeek = $dt->format('l');
    $timeStr   = $dt->format('H:i:s');

    // Verify physician's weekly availability in doctor_schedule
    $stmt = $conn->prepare("
        SELECT 1
        FROM doctor_schedule ds
        WHERE ds.doctor_id = ?
          AND ds.day_of_week = ?
          AND ds.start_time <= ?
          AND ds.end_time   >  ?
        LIMIT 1
    ");
    $stmt->bind_param("isss", $doctor_id, $dayOfWeek, $timeStr, $timeStr);
    $stmt->execute();
    $worksNow = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$worksNow) {
        $_SESSION['sched_error'] = "Physician is not available at the selected time.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Conflict check (same physician, same date, ±30 min window, active bookings)
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM screening_data s
        WHERE s.doctor_id = ?
          AND s.id <> ?
          AND s.status = 'approved'
          AND s.consultation_status IN ('pending','in_progress')
          AND DATE(s.schedule_time) = ?
          AND ABS(TIMESTAMPDIFF(MINUTE, s.schedule_time, ?)) < 30
    ");
    $stmt->bind_param("iiss", $doctor_id, $screening_id, $slotDate, $slot);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();

    if ($cnt > 0) {
        $_SESSION['sched_error'] = "Slot unavailable for the physician. Please choose the next free 30-min slot.";
        header("Location: ".$_SERVER['REQUEST_URI']); exit;
    }

    // Save scheduling (approve + set consult pending)
    $stmt = $conn->prepare("
        UPDATE screening_data
        SET doctor_id = ?, schedule_time = ?, preferred_schedule = ?, status='approved', consultation_status='pending'
        WHERE id = ?
    ");
    $stmt->bind_param("issi", $doctor_id, $slot, $slot, $screening_id);
    $stmt->execute();
    $stmt->close();

    // ----- If ONLINE, generate Jitsi room + tokens (link-only join; no session required) -----
try {
    // Check the requested mode for this screening
    $chk = $conn->prepare("SELECT consultation_mode FROM screening_data WHERE id = ? LIMIT 1");
    $chk->bind_param("i", $screening_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($row && $row['consultation_mode'] === 'online') {
        // Create a unique public Jitsi room and separate tokens for patient/doctor
        $roomSuffix    = bin2hex(random_bytes(8));
        $meeting_url   = "https://meet.jit.si/econsulta-" . $roomSuffix;

        $patient_token = bin2hex(random_bytes(16)); // 32 hex chars
        $doctor_token  = bin2hex(random_bytes(16)); // 32 hex chars

        $stmt = $conn->prepare("
          UPDATE screening_data
          SET meeting_url = ?, patient_join_token = ?, doctor_join_token = ?
          WHERE id = ?
        ");
        $stmt->bind_param("sssi", $meeting_url, $patient_token, $doctor_token, $screening_id);
        $stmt->execute();
        $stmt->close();

        // If you notify by email/SMS, use these ready-made links:
        // Patient: join_meeting.php?sid={$screening_id}&k={$patient_token}
        // Doctor:  join_meeting.php?sid={$screening_id}&k={$doctor_token}
    } else {
        // Face-to-face: ensure online fields are cleared
        $stmt = $conn->prepare("
          UPDATE screening_data
          SET meeting_url = NULL, patient_join_token = NULL, doctor_join_token = NULL
          WHERE id = ?
        ");
        $stmt->bind_param("i", $screening_id);
        $stmt->execute();
        $stmt->close();
    }
} catch (Throwable $e) {
    error_log("[meet] Token meeting generation failed: ".$e->getMessage());
}



    $_SESSION['sched_ok'] = "Scheduled for ".htmlspecialchars($dt->format('M j, Y g:i A'))." (Physician).";
    header("Location: ".$_SERVER['REQUEST_URI']); exit;
}

/* =========================
   TABS + COUNTS
   ========================= */
$tab = $_GET['tab'] ?? 'requests';
$tab = in_array($tab, ['requests','scheduled','completed']) ? $tab : 'requests';

function scalar($conn, $sql) {
    $res = $conn->query($sql);
    if ($res && ($row = $res->fetch_row())) return (int)$row[0];
    return 0;
}
$countRequests  = scalar($conn, "SELECT COUNT(*) FROM screening_data WHERE status='pending'");
$countScheduled = scalar($conn, "SELECT COUNT(*) FROM screening_data WHERE status='approved' AND consultation_status IN ('pending','in_progress')");
$countCompleted = scalar($conn, "SELECT COUNT(*) FROM screening_data WHERE consultation_status='completed'");

/* =========================
   FETCH LIST (by TAB + optional search)
   ========================= */
$search = $conn->real_escape_string($_GET['search'] ?? '');
$where = [];
if ($tab === 'requests') {
    $where[] = "status='pending'";
} elseif ($tab === 'scheduled') {
    $where[] = "status='approved' AND consultation_status IN ('pending','in_progress')";
} else { // completed
    $where[] = "consultation_status='completed'";
}
if (!empty($search)) {
    $where[] = "CONCAT(first_name, ' ', middle_initial, ' ', last_name) LIKE '%{$search}%'";
}
$sql = "SELECT * FROM screening_data";
if ($where) $sql .= " WHERE ".implode(" AND ", $where);
$sql .= ($tab === 'requests') ? " ORDER BY created_at DESC" : " ORDER BY schedule_time DESC";
$result = $conn->query($sql);

// doctor names for display
$docRes = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM doctors");
$doctors = [];
while ($d = $docRes->fetch_assoc()) $doctors[$d['id']] = $d['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultation Records</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    * { box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; margin: 0; background-color: #f4f6fa; color: #333; }

    .sidebar { position: fixed; top: 0; left: 0; width: 200px; height: 100vh; background-color: #002c6d; padding: 20px 15px; color: white; }
    .sidebar img { width: 90px; margin: 10px auto 20px; display: block; }
    .sidebar h2 { font-size: 15px; margin-bottom: 20px; text-align: center; letter-spacing: 0.5px; color: white; }
    .sidebar a { display: block; color: white; text-decoration: none; margin: 8px 0; padding: 8px 12px; border-radius: 6px; font-size: 13px; transition: background 0.2s; }
    .sidebar a:hover { background-color: #001c47; }

    .main-content { margin-left: 220px; padding: 40px 30px; background-color: #f9fafc; min-height: 100vh; }
    h2 { font-size: 22px; color: #003d99; margin-bottom: 10px; font-weight: 600; }

    .tabs { display: flex; gap: 8px; margin: 10px 0 20px; flex-wrap: wrap; }
    .tab {
      display: inline-block; padding: 10px 14px; border-radius: 999px;
      background: #e9eefb; color: #003d99; text-decoration: none; font-weight: 600; font-size: 13px;
      border: 1px solid #cfd8f6;
    }
    .tab.active { background: #003d99; color: #fff; border-color: #003d99; }
    .tab .badge {
      background: rgba(255,255,255,0.85); color: #003d99; border-radius: 10px; padding: 2px 8px; margin-left: 6px;
      font-weight: 700;
    }
    .tab.active .badge { color: #003d99; background: #fff; }

    .alert { margin: 10px 0 20px; padding: 10px 12px; border-radius: 6px; font-size: 14px }
    .alert.ok  { background: #e6fff0; color: #0b7a3c; border: 1px solid #b7efcc; }
    .alert.err { background: #ffeeee; color: #b00020; border: 1px solid #f5c2c7; }

    .search-box { display: flex; flex-wrap: wrap; gap: 12px; margin: 10px 0 25px; }
    .search-box input[type="text"] { padding: 10px 12px; width: 250px; border-radius: 6px; border: 1px solid #ccc; }
    .search-box button { padding: 10px 16px; background-color: #003d99; border: none; border-radius: 6px; color: #fff; font-size: 14px; cursor: pointer; }
    .search-box button:hover { background-color: #002c6d; }

    table { width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 10px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 40px; }
    th, td { padding: 14px 10px; border-bottom: 1px solid #f0f0f0; text-align: center; font-size: 14px; }
    th { background-color: #003d99; color: #fff; font-weight: 500; }
    tr:nth-child(even) { background-color: #f8f9fb; }

    input[type="date"], select { padding: 8px 10px; width: 100%; font-size: 13px; border-radius: 6px; border: 1px solid #ccc; }
    input[type="date"]:focus, select:focus { border-color: #003d99; outline: none; }

    .btn { padding: 8px 12px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; width: 100%; }
    .btn.primary { background-color: #003d99; color: white; }
    .btn.primary:hover { background-color: #002c6d; }
    .btn.success { background-color: #0b7a3c; color:#fff; }
    .btn.success:hover { background-color: #096530; }

    td > form { display: flex; flex-direction: column; gap: 6px; }
  </style>
</head>
<body>
  <div class="sidebar">
    <img src="logo.png" alt="Barangay Logo">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_accountapproval.php">Account Approval</a>
    <a href="admin_residents.php">Residents</a>
    <a href="admin_consult.php" style="background:#001c47;border-radius:6px;">Consultation</a>
    <a href="admin_pregnant.php">Pregnant</a>
    <a href="admin_infant.php">Infants</a>
    <a href="admin_familyplan.php">Family Planning</a>
    <a href="admin_view_request.php">Free Medicine</a>
    <a href="admin_add_stock.php">Medicine Stocks</a>
    <a href="admin_tbdots.php">TB DOTS</a>
    <a href="admin_toothextraction.php">Free Tooth Extraction</a>
    <a href="admin_calendar.php">Schedule Calendar</a>
    <a href="admin_chat.php">message</a>
    <a href="admin_login.php">Logout</a>
  </div>

  <div class="main-content">
    <h2>Consultation</h2>

    <!-- Tabs -->
    <div class="tabs">
      <a class="tab <?= $tab==='requests'?'active':'' ?>"  href="admin_consult.php?tab=requests">Requests <span class="badge"><?= $countRequests ?></span></a>
      <a class="tab <?= $tab==='scheduled'?'active':'' ?>" href="admin_consult.php?tab=scheduled">Scheduled <span class="badge"><?= $countScheduled ?></span></a>
      <a class="tab <?= $tab==='completed'?'active':'' ?>" href="admin_consult.php?tab=completed">Completed <span class="badge"><?= $countCompleted ?></span></a>
    </div>

    <?php if (!empty($_SESSION['sched_error'])): ?>
      <div class="alert err"><?= htmlspecialchars($_SESSION['sched_error']); unset($_SESSION['sched_error']); ?></div>
    <?php elseif (!empty($_SESSION['sched_ok'])): ?>
      <div class="alert ok"><?= htmlspecialchars($_SESSION['sched_ok']); unset($_SESSION['sched_ok']); ?></div>
    <?php endif; ?>

    <!-- Search -->
    <form class="search-box" method="GET">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <input type="text" name="search" placeholder="Search by patient name..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Age</th>
          <th>Gender</th>
          <th>Address</th>
          <th>Contact No.</th>
          <th>Chief Complaint</th>
          <?php if ($tab === 'requests'): ?>
            <th>Schedule</th>
          <?php else: ?>
            <th>Final Schedule</th>
          <?php endif; ?>
          <th>Doctor</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()):
        $fullname = trim(($row['first_name'] ?? '').' '.($row['middle_initial'] ?? '').'. '.($row['last_name'] ?? ''));
        // Age safe calc
        $age = '—';
        if (!empty($row['dob'])) {
          try { $dob = new DateTime($row['dob']); $age = $dob->diff(new DateTime())->y; } catch (Exception $e) { $age = '—'; }
        }

        $prefDate = !empty($row['schedule_time']) ? date('Y-m-d', strtotime($row['schedule_time'])) : '';
        $prefTime = !empty($row['schedule_time']) ? date('H:i',   strtotime($row['schedule_time'])) : '';
        $finalPretty = !empty($row['schedule_time']) ? date('M j, Y g:i A', strtotime($row['schedule_time'])) : '—';
        $doctorName = !empty($row['doctor_id']) ? ($doctors[$row['doctor_id']] ?? '—') : 'Not assigned';
      ?>
        <tr>
          <td><?= htmlspecialchars($fullname) ?></td>
          <td><?= htmlspecialchars($age) ?></td>
          <td><?= htmlspecialchars($row['gender']) ?></td>
          <td><?= htmlspecialchars($row['address']) ?></td>
          <td><?= htmlspecialchars($row['contact_number']) ?></td>
          <td><?= htmlspecialchars($row['medical_concern']) ?></td>

          <?php if ($tab === 'requests'): ?>
            <td>
              <!-- DATE + TIME (30-MIN FIXED) - AUTO ASSIGN PHYSICIAN -->
              <form method="post" style="display:flex; flex-direction:column; gap:6px;">
                <input type="hidden" name="screening_id" value="<?= (int)$row['id'] ?>">

                <!-- Date -->
                <input type="date" name="sched_date" required value="<?= htmlspecialchars($prefDate) ?>">

                <!-- Time (fixed 30-minute slots) -->
                <select name="sched_time" required>
                  <option value="">Select Time</option>
                  <?php
                    $slots = [
                      '09:00','09:30','10:00','10:30','11:00','11:30',
                      '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'
                    ];
                    foreach ($slots as $t) {
                      $sel = ($prefTime === $t) ? 'selected' : '';
                      echo "<option value=\"$t\" $sel>".date('g:i A', strtotime($t))."</option>";
                    }
                  ?>
                </select>

                <button type="submit" name="action" value="save" class="btn primary">Save</button>
              </form>
            </td>
          <?php else: ?>
            <td><?= htmlspecialchars($finalPretty) ?></td>
          <?php endif; ?>

          <td><?= htmlspecialchars($doctorName) ?></td>

          <td>
            <?php if ($tab === 'completed'): ?>
              <?= strtoupper(htmlspecialchars($row['consultation_status'] ?: 'completed')) ?>
            <?php elseif ($tab === 'scheduled'): ?>
              <?= strtoupper(htmlspecialchars($row['consultation_status'] ?: 'pending')) ?>
            <?php else: ?>
              <?= strtoupper(htmlspecialchars($row['status'] ?: 'pending')) ?>
            <?php endif; ?>
          </td>

          <td>
            <?php if ($tab === 'scheduled'): ?>
              <form method="post" style="display:flex;flex-direction:column;gap:6px;align-items:center">
                <input type="hidden" name="screening_id" value="<?= (int)$row['id'] ?>">
                <button type="submit" name="action" value="complete" class="btn success">Mark Completed</button>
                <a href="admin_consult_details.php?id=<?= (int)$row['id'] ?>" class="btn primary" style="text-align:center;text-decoration:none;">Details</a>
              </form>
            <?php else: ?>
              <a href="admin_consult_details.php?id=<?= (int)$row['id'] ?>" class="btn primary" style="text-align:center;text-decoration:none;">Details</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
