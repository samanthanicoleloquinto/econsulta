<?php
// doctor_notes.php — READ-ONLY + PRINT MODE (same look for on-screen and PDF)
session_name('EConsultaDoctor');
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

$printMode    = isset($_GET['print']) && $_GET['print'] == '1';
if (!$printMode && empty($_SESSION['doctor_id'])) { header("Location: doctor_login.php"); exit(); }
$doctor_id    = $printMode ? (int)($_GET['doctor_id'] ?? 0) : (int)$_SESSION['doctor_id']; // optional doctor_id in print links
$screening_id = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
if ($screening_id <= 0) die("Invalid patient.");

// Ensure patient belongs to this doctor (skip only if you want admins to print too)
$stmt = $conn->prepare("SELECT * FROM screening_data WHERE id=? AND doctor_id=? LIMIT 1");
$stmt->bind_param("ii", $screening_id, $doctor_id);
$stmt->execute(); $data = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$data) die("Record not found or not assigned to you.");

// Fetch saved notes
$notes = null;
$stmt = $conn->prepare("SELECT * FROM doctor_notes WHERE screening_id=? AND doctor_id=? LIMIT 1");
$stmt->bind_param("ii", $screening_id, $doctor_id);
$stmt->execute(); $res = $stmt->get_result(); if ($res) $notes = $res->fetch_assoc(); $stmt->close();

// Doctor info (for header)
$docRow = $conn->query("SELECT CONCAT(first_name,' ',last_name) AS n FROM doctors WHERE id={$doctor_id}")->fetch_assoc();
$doctorName = $docRow['n'] ?? ('Doctor #'.$doctor_id);

// Snapshot/formatting
$fullname = trim(($data['first_name'] ?? '').' '.(($data['middle_initial'] ?? '') ? $data['middle_initial'].' ' : '').($data['last_name'] ?? '').' '.($data['suffix'] ?? ''));
$age = '—';
if (!empty($data['dob'])) { try { $dob = new DateTime($data['dob']); $age = $dob->diff(new DateTime())->y; } catch(Exception $e){ $age='—'; } }
$schedPretty = !empty($data['schedule_time']) ? date('M j, Y g:i A', strtotime($data['schedule_time'])) : '—';

// Symptoms
$sx_list   = trim($data['symptoms_selected'] ?? '');
$sx_others = trim($data['symptoms_others'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Notes<?= $printMode ? ' — Print' : '' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  html,body{margin:0;padding:0}
  body{font-family:'Inter',sans-serif;background:<?= $printMode ? '#fff' : '#f5f7fb' ?>;color:#1f2a44}

  /* Sidebar (hidden in print mode) */
  .sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:#0f3a72;color:#fff;padding:20px 16px;<?= $printMode ? 'display:none' : '' ?>}
  .sidebar h2{font-size:16px;margin:10px 0 16px}
  .sidebar a{display:block;color:#fff;text-decoration:none;padding:8px 10px;border-radius:6px;font-size:13px;margin:4px 0}
  .sidebar a:hover{background:#0b2b55}
  .main{margin-left:<?= $printMode ? '0' : '240px' ?>;padding:<?= $printMode ? '0' : '24px 24px 40px' ?>}

  /* Page container for print margins */
  .page{max-width:900px;margin:<?= $printMode ? '0 auto' : '24px auto' ?>;padding:<?= $printMode ? '18mm 14mm' : '0 16px' ?>}

  /* Barangay header */
  .brand-header{
    background:#ffffff;border:1px solid #e6ebf5;border-radius:14px;
    padding:14px 18px;box-shadow:0 2px 10px rgba(0,0,0,.04);margin-bottom:14px
  }
  .brand-row{display:flex;align-items:center;justify-content:space-between}
  .brand-row img{height:64px;width:auto}
  .brand-title{margin-top:6px;text-align:center;font-weight:800;color:#0f3a72;letter-spacing:.4px}
  .brand-sub{font-size:12px;text-align:center;color:#5e6e8c;margin-top:2px}
  .divider{height:4px;background:linear-gradient(90deg,#0f3a72 0%, #ffc61a 50%, #0f3a72 100%);border-radius:6px;margin-top:10px}

  /* Cards */
  .card{background:#fff;border:1px solid #e6ebf5;border-radius:12px;padding:16px 18px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:14px}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .muted{color:#5c6b86}
  h2{margin:0 0 8px}
  h3{margin:2px 0 10px}
  .badge{display:inline-block;background:#fff4bf;color:#7a5b00;border:1px solid #ffd34d;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:700}
  .value-box{
    background:#fafcff;border:1px solid #e6ebf5;border-radius:10px;padding:10px 12px;
    white-space:pre-wrap;word-break:break-word
  }
  .label{font-weight:700;color:#0f3a72;margin-bottom:6px}
  .thumb{max-height:160px;border:1px solid #e6ebf5;border-radius:10px}

  /* Actions (hidden in print) */
  .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
  .btn{padding:10px 14px;border:none;border-radius:8px;cursor:pointer;font-size:14px}
  .btn.back{background:#eef2fb;color:#0f3a72}
  .btn.pdf{background:#003d99;color:#fff}
  .no-print{<?= $printMode ? 'display:none !important' : '' ?>}

  /* Print rules */
  @media print {
    @page { size: A4; margin: 12mm; }
    body { background:#fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sidebar, .no-print { display:none !important; }
    .main { margin-left:0 !important; padding:0 !important; }
    .page { max-width:100%; margin:0; padding:0; }
    .brand-header, .card { box-shadow:none !important; }
  }
</style>
</head>
<body>
  <?php if (!$printMode): ?>
  <!-- Sidebar (app view) -->
  <div class="sidebar">
    <h2>Doctor Panel</h2>
    <a href="doctor_dashboard.php">Dashboard</a>
    <a href="doctor_calendar.php">Calendar</a>
    <a href="doctor_logout.php">Logout</a>
  </div>
  <?php endif; ?>

  <div class="main">
    <div class="page">
      <!-- Barangay header with twin logos -->
      <div class="brand-header">
        <div class="brand-row">
          <img src="images/barangay_pineda_logo.png" alt="Barangay Pineda Logo">
          <div style="text-align:center;flex:1;margin:0 8px">
            <div class="brand-title">Barangay Pineda Health Center — Doctor Notes</div>
            <div class="brand-sub">Pasig City • Read-Only Medical Record</div>
          </div>
          <img src="images/pasig_city_logo.png" alt="Pasig City Logo">
        </div>
        <div class="divider"></div>
      </div>

      <!-- Patient snapshot -->
      <div class="card">
        <h2><?= htmlspecialchars($fullname ?: 'Patient') ?></h2>
        <div class="muted" style="margin-bottom:10px;display:flex;gap:10px;flex-wrap:wrap">
          <span class="badge">Scheduled: <?= htmlspecialchars($schedPretty) ?></span>
          <span class="badge">Attending: <?= htmlspecialchars($doctorName) ?></span>
          <?php if (!empty($data['consultation_status'])): ?>
            <span class="badge">Status: <?= htmlspecialchars(ucfirst($data['consultation_status'])) ?></span>
          <?php endif; ?>
        </div>
        <div class="grid2">
          <div><span class="muted">Age:</span> <?= htmlspecialchars($age) ?></div>
          <div><span class="muted">Sex:</span> <?= htmlspecialchars($data['gender'] ?? '—') ?></div>
          <div><span class="muted">Temp:</span> <?= htmlspecialchars($data['temperature'] ?? '—') ?> °C</div>
          <div><span class="muted">BP:</span> <?= htmlspecialchars($data['blood_pressure'] ?? '—') ?></div>
          <div><span class="muted">Height:</span> <?= htmlspecialchars($data['height'] ?? '—') ?> cm</div>
          <div><span class="muted">Weight:</span> <?= htmlspecialchars($data['weight'] ?? '—') ?> kg</div>
        </div>
      </div>

      <!-- Symptoms -->
      <div class="card">
        <h3>Reported Symptoms</h3>
        <div class="value-box">
          <?php
            $sxParts = [];
            if ($sx_list !== '')   $sxParts[] = $sx_list;
            if ($sx_others !== '') $sxParts[] = 'Others: '.$sx_others;
            echo htmlspecialchars($sxParts ? implode("\n", $sxParts) : '—');
          ?>
        </div>
      </div>

      <!-- Doctor notes (read-only) -->
      <div class="card">
        <h3>Findings</h3>
        <div class="value-box"><?= htmlspecialchars($notes['findings'] ?? '—') ?></div>

        <h3 style="margin-top:12px">Diagnosis</h3>
        <div class="value-box"><?= htmlspecialchars($notes['diagnosis'] ?? '—') ?></div>

        <h3 style="margin-top:12px">Recommendations</h3>
        <div class="value-box"><?= htmlspecialchars($notes['recommendations'] ?? '—') ?></div>

        <h3 style="margin-top:12px">Prescription</h3>
        <?php if (!empty($notes['prescription'])): ?>
          <a href="<?= htmlspecialchars($notes['prescription']) ?>" target="_blank" title="Open prescription">
            <img class="thumb" src="<?= htmlspecialchars($notes['prescription']) ?>" alt="Prescription image">
          </a>
        <?php else: ?>
          <div class="value-box">—</div>
        <?php endif; ?>

        <div class="actions no-print">
          <button class="btn back" onclick="window.location='doctor_dashboard.php'">Back</button>
          <a class="btn pdf" href="doctor_notes.php?sid=<?= (int)$screening_id ?>&doctor_id=<?= (int)$doctor_id ?>&print=1" target="_blank">Print / Download PDF</a>
        </div>
      </div>
    </div>
  </div>

<?php if ($printMode): ?>
<script>
  // Auto-open print dialog in print mode; safe delay for logo loads.
  window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 200); });
</script>
<?php endif; ?>
</body>
</html>
