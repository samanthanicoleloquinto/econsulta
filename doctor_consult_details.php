<?php
// doctor_consult_details.php — doctor-only: view patient + add structured findings + dropdown diagnosis
session_name('EConsultaDoctor');
session_start();
date_default_timezone_set('Asia/Manila');

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

/* --------------------------
   Resolve logged-in doctor
   -------------------------- */
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
    header("Location: doctor_login.php"); // adjust if your login path differs
    exit;
}

/* --------------------------
   Load consultation by id
   -------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid consultation ID.");

$stmt = $conn->prepare("SELECT * FROM screening_data WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$caseRes = $stmt->get_result();
$data = $caseRes ? $caseRes->fetch_assoc() : null;
$stmt->close();

if (!$data) die("Consultation record not found.");

$assigned_doctor_id = (int)($data['doctor_id'] ?? 0);
if ($assigned_doctor_id <= 0) {
    $_SESSION['err'] = "This consultation has not been assigned to a doctor yet.";
} elseif ($assigned_doctor_id !== $doctor_id) {
    die("This consultation is not assigned to you.");
}

/* --------------------------
   Diagnosis pick-list (common barangay)
   key saved as `label|ICD10` (you can parse later)
   -------------------------- */
$DIAG_LIST = [
  // Respiratory
  'influenza_like_illness|J11'      => 'Influenza-like illness (Flu)',
  'acute_uri|J06.9'                 => 'Acute upper respiratory infection',
  'pharyngitis|J02.9'               => 'Pharyngitis (unspecified)',
  'tonsillitis|J03.9'               => 'Tonsillitis (unspecified)',
  'pneumonia_unspecified|J18.9'     => 'Pneumonia (unspecified)',
  'asthma_exacerbation|J45'         => 'Asthma exacerbation',
  'allergic_rhinitis|J30.9'         => 'Allergic rhinitis',
  'covid19_suspected|U07.2'         => 'COVID-19 (suspected)',

  // Gastrointestinal
  'viral_gastroenteritis|A08.4'     => 'Viral gastroenteritis',
  'acute_gastroenteritis|K52.9'     => 'Acute gastroenteritis (unspecified)',
  'gastritis_dyspepsia|K29'         => 'Gastritis / Dyspepsia',
  'vomiting_unspecified|R11'        => 'Vomiting (unspecified)',

  // Febrile/Tropical
  'fever_unspecified|R50.9'         => 'Fever (unspecified)',
  'dengue_suspected|A97.9'          => 'Dengue (suspected)',
  'leptospirosis_suspected|A27.9'   => 'Leptospirosis (suspected)',
  'typhoid_suspected|A01.0'         => 'Typhoid fever (suspected)',

  // Skin
  'dermatitis_unspecified|L30.9'    => 'Dermatitis (unspecified)',
  'scabies|B86'                     => 'Scabies',
  'cellulitis|L03.9'                => 'Cellulitis (unspecified site)',
  'impetigo|L01.0'                  => 'Impetigo',
  'tinea_corporis|B35.4'            => 'Tinea corporis',

  // ENT / Eye
  'acute_otitis_media|H66.9'        => 'Acute otitis media',
  'conjunctivitis|H10.9'            => 'Conjunctivitis (unspecified)',

  // GU
  'uti_uncomplicated|N39.0'         => 'Urinary tract infection (uncomplicated)',
  'acute_cystitis|N30.0'            => 'Acute cystitis',

  // MSK / Chronic common
  'low_back_pain|M54.5'             => 'Low back pain',
  'myalgia|M79.1'                   => 'Myalgia',
  'sprain_strain|T14.8'             => 'Sprain/Strain (unspecified)',
  'hypertension_uncomplicated|I10'  => 'Hypertension (uncomplicated)',
  'type2_diabetes_unspecified|E11.9'=> 'Type 2 diabetes mellitus (unspecified)',

  // TB / Bite / Dental (common in primary care)
  'tb_suspected|A16.0'              => 'Pulmonary TB (suspected)',
  'animal_bite_exposure|W54'        => 'Dog bite exposure (rabies prophylaxis)',
  'dental_caries|K02.9'             => 'Dental caries',
  'toothache_pulpitis|K04.0'        => 'Pulpitis (toothache)',
  'gingivitis|K05.9'                => 'Gingivitis'
];

/* --------------------------
   Save findings -> doctor_notes + complete
   -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_doctor_notes'])) {
    if ($assigned_doctor_id !== $doctor_id) {
        $_SESSION['err'] = "You are not assigned to this consultation.";
        header("Location: doctor_consult_details.php?id=".$id);
        exit;
    }

    $findings        = trim($_POST['findings'] ?? '');
    $diag_select     = $_POST['diagnosis_select'] ?? '';
    $diag_other      = trim($_POST['diagnosis_other'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');

    if ($findings === '') {
        $_SESSION['err'] = "Findings is required.";
        header("Location: doctor_consult_details.php?id=".$id);
        exit;
    }

    // Determine final diagnosis string to save
    if ($diag_select === 'other') {
        if ($diag_other === '') {
            $_SESSION['err'] = "Please type the diagnosis in the 'Other' field.";
            header("Location: doctor_consult_details.php?id=".$id);
            exit;
        }
        $diagnosis_to_save = $diag_other; // free text for unseen labels
    } else {
        if (empty($diag_select) || !array_key_exists($diag_select, $DIAG_LIST)) {
            $_SESSION['err'] = "Please select a valid diagnosis.";
            header("Location: doctor_consult_details.php?id=".$id);
            exit;
        }
        $diagnosis_to_save = $diag_select; // e.g., 'viral_gastroenteritis|A08.4'
    }

    // Optional prescription image upload
    $prescription_path = null;
    if (!empty($_FILES['prescription_image']['name'])) {
        $file = $_FILES['prescription_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $_SESSION['err'] = "Prescription must be a JPG or PNG image.";
                header("Location: doctor_consult_details.php?id=".$id);
                exit;
            }
            $dir = __DIR__ . '/uploads/prescriptions';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $ext = $allowed[$mime];
            $fname = 'rx-'.$id.'-'.time().'.'.$ext;
            $full = $dir . '/' . $fname;
            if (!move_uploaded_file($file['tmp_name'], $full)) {
                $_SESSION['err'] = "Failed to save prescription image.";
                header("Location: doctor_consult_details.php?id=".$id);
                exit;
            }
            $prescription_path = 'uploads/prescriptions/'.$fname; // web path
        } else {
            $_SESSION['err'] = "Error uploading prescription image (code {$file['error']}).";
            header("Location: doctor_consult_details.php?id=".$id);
            exit;
        }
    }

    // Insert doctor_notes
    $stmt = $conn->prepare("
        INSERT INTO doctor_notes (screening_id, doctor_id, findings, diagnosis, prescription, recommendations)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissss", $id, $doctor_id, $findings, $diagnosis_to_save, $prescription_path, $recommendations);
    $stmt->execute();
    $stmt->close();

    // Mark consult completed + mirror latest findings for quick view
    $stmt = $conn->prepare("UPDATE screening_data SET consultation_status='completed', findings=? WHERE id=?");
    $stmt->bind_param("si", $findings, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['ok'] = "Notes saved. Diagnosis recorded. Consultation marked as Completed.";
    header("Location: doctor_consult_details.php?id=".$id);
    exit;
}

/* --------------------------
   Fresh data & helpers for view
   -------------------------- */
$stmt = $conn->prepare("SELECT CONCAT(first_name,' ',last_name) AS n FROM doctors WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$dn = $stmt->get_result();
$docName = ($dn && ($r = $dn->fetch_assoc())) ? $r['n'] : "Doctor #{$doctor_id}";
$stmt->close();

$preferredDate = !empty($data['preferred_schedule']) ? date("F j, Y g:i A", strtotime($data['preferred_schedule'])) : '';
$finalSchedule = !empty($data['schedule_time'])      ? date("F j, Y g:i A", strtotime($data['schedule_time'])) : '';
$createdAtDate = !empty($data['created_at'])         ? date("F j, Y",       strtotime($data['created_at']))    : '';
$createdAtTime = !empty($data['created_at'])         ? date("h:i A",        strtotime($data['created_at']))    : '';
$dobFormatted  = !empty($data['dob'])                ? date("F j, Y",       strtotime($data['dob']))           : '';

// Notes history
$stmt = $conn->prepare("
    SELECT dn.*, CONCAT(d.first_name,' ',d.last_name) AS doctor_name
    FROM doctor_notes dn
    JOIN doctors d ON d.id = dn.doctor_id
    WHERE dn.screening_id = ?
    ORDER BY dn.created_at DESC, dn.id DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$notes = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Consultation Details (Doctor)</title>
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

  .badges{display:flex;gap:10px;flex-wrap:wrap;margin:6px 0 18px}
  .badge{display:inline-block;background:#eef4ff;color:#003366;border:1px solid #d7e2ff;
         padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700}

  .section{background:#fff;border:1px solid #e6ebf5;border-radius:12px;padding:16px 18px;margin-bottom:16px}
  .section h3{margin:0 0 10px;font-size:16px;color:#0f3a72}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px 8px;border-bottom:1px solid #eef2fa;text-align:left;font-size:13px}
  th{width:28%;color:#36507a;background:#f7f9fe}
  tr:last-child td{border-bottom:none}

  textarea,input[type="text"],select{width:100%;padding:8px 10px;border:1px solid #cfd8ea;border-radius:8px}
  textarea{min-height:68px}
  input[type="file"]{margin-top:6px}
  .buttons{display:flex;gap:10px;justify-content:center;margin-top:12px}
  .btn{padding:10px 14px;border:none;border-radius:8px;cursor:pointer}
  .btn.primary{background:#0f3a72;color:#fff}
  .btn.secondary{background:#e6ebf5;color:#0f3a72}

  .alert{margin:8px 0 16px;padding:10px 12px;border-radius:8px;font-size:13px}
  .ok{background:#e6fff0;color:#0b7a3c;border:1px solid #b7efcc}
  .err{background:#ffeeee;color:#b00020;border:1px solid #f5c2c7}

  .note-card{border:1px solid #e5eaf3;background:#fff;border-radius:10px;padding:12px 14px;margin-bottom:10px}
  .note-head{font-size:13px;color:#5a6b85;margin-bottom:6px}
  .rx-img{max-width:200px;border:1px solid #e5eaf3;border-radius:8px;display:block;margin-top:6px}
</style>
<script>
  function onDiagChange(sel){
    var other = document.getElementById('diagnosis_other_wrap');
    if(sel.value === 'other'){ other.style.display='block'; }
    else { other.style.display='none'; }
  }
</script>
</head>
<body>
  <div class="sidebar">
    <h2>Doctor Panel</h2>
    <div style="font-size:13px;opacity:.85;margin-bottom:12px;">Logged in as<br><strong>
      <?php echo htmlspecialchars($docName); ?>
    </strong></div>
    <a href="doctor_dashboard.php">Dashboard</a>
    <a href="#" style="background:#0b2b55">Consultation Details</a>
    <a href="doctor_logout.php">Logout</a>
  </div>

  <div class="main">
    <h1>Consultation Details</h1>
    <div class="muted">Patient summary and findings entry</div>

    <?php if (!empty($_SESSION['ok'])): ?>
      <div class="alert ok"><?= htmlspecialchars($_SESSION['ok']); unset($_SESSION['ok']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['err'])): ?>
      <div class="alert err"><?= htmlspecialchars($_SESSION['err']); unset($_SESSION['err']); ?></div>
    <?php endif; ?>

    <div class="badges">
      <span class="badge">REQUEST: <?= htmlspecialchars(strtoupper($data['status'])) ?></span>
      <span class="badge">CONSULTATION: <?= htmlspecialchars(strtoupper($data['consultation_status'])) ?></span>
      <?php if ($finalSchedule): ?><span class="badge">SCHEDULED: <?= htmlspecialchars($finalSchedule) ?></span><?php endif; ?>
    </div>

    <div class="section">
      <h3>Personal Information</h3>
      <table>
        <tr><th>Full Name</th><td><?= htmlspecialchars("{$data['first_name']} {$data['middle_initial']} {$data['last_name']} {$data['suffix']}") ?></td></tr>
        <tr><th>Gender</th><td><?= htmlspecialchars($data['gender']) ?></td></tr>
        <tr><th>Date of Birth</th><td><?= htmlspecialchars($dobFormatted ?? '') ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($data['address']) ?></td></tr>
        <tr><th>Contact No.</th><td><?= htmlspecialchars($data['contact_number']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Medical History</h3>
      <table>
        <tr><th>Conditions</th><td><?= htmlspecialchars($data['medical_conditions']) ?></td></tr>
        <tr><th>Others</th><td><?= htmlspecialchars($data['medical_others']) ?></td></tr>
        <tr><th>Past Surgery</th><td><?= htmlspecialchars(trim(($data['had_surgery']??'').' '.($data['surgery_type']??'').' '.($data['surgery_year']??''))) ?></td></tr>
        <tr><th>Recent Hospitalization</th><td><?= htmlspecialchars($data['recent_hospitalization']) ?></td></tr>
        <tr><th>Medications</th><td><?= htmlspecialchars($data['medications']) ?></td></tr>
        <tr><th>Allergies (Meds)</th><td><?= htmlspecialchars($data['allergies_meds']) ?></td></tr>
        <tr><th>Allergies (Foods)</th><td><?= htmlspecialchars($data['allergies_foods']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Symptoms & Vitals</h3>
      <table>
        <tr><th>Symptoms</th><td><?= htmlspecialchars($data['symptoms_selected']) ?></td></tr>
        <?php if (!empty($data['symptoms_others'])): ?>
        <tr><th>Other Symptoms</th><td><?= htmlspecialchars($data['symptoms_others']) ?></td></tr>
        <?php endif; ?>
        <tr><th>Temperature</th><td><?= htmlspecialchars($data['temperature']) ?><?= $data['temperature']!==''?' °C':'' ?></td></tr>
        <tr><th>Blood Pressure</th><td><?= htmlspecialchars($data['blood_pressure']) ?></td></tr>
        <tr><th>Height</th><td><?= htmlspecialchars($data['height']) ?><?= $data['height']!==''?' cm':'' ?></td></tr>
        <tr><th>Weight</th><td><?= htmlspecialchars($data['weight']) ?><?= $data['weight']!==''?' kg':'' ?></td></tr>
        <tr><th>BMI</th><td><?= htmlspecialchars($data['bmi']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Doctor Findings & Diagnosis</h3>

      <?php if ($assigned_doctor_id <= 0): ?>
        <div class="alert err">This case is not yet assigned to a doctor. Please ask admin to schedule/assign it.</div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" style="opacity: <?= ($assigned_doctor_id===$doctor_id ? '1' : '.5') ?>; pointer-events: <?= ($assigned_doctor_id===$doctor_id ? 'auto' : 'none') ?>;">
        <table>
          <tr>
            <th>Findings <span style="color:#b00020">*</span></th>
            <td><textarea name="findings" placeholder="Objective/subjective findings (required)..."></textarea></td>
          </tr>

          <tr>
            <th>Diagnosis <span style="color:#b00020">*</span></th>
            <td>
              <select name="diagnosis_select" id="diagnosis_select" required onchange="onDiagChange(this)">
                <option value="">— Select diagnosis —</option>
                <?php foreach ($DIAG_LIST as $val => $label): ?>
                  <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
                <option value="other">Other (type below)</option>
              </select>
              <div id="diagnosis_other_wrap" style="display:none;margin-top:8px;">
                <input type="text" name="diagnosis_other" placeholder="Type specific diagnosis (e.g., measles, migraine)">
              </div>
              <div style="font-size:12px;color:#6b7a92;margin-top:6px;">
                Saved value is the label (with ICD-10 code if from the list), or your typed text if “Other.”
              </div>
            </td>
          </tr>

          <tr>
            <th>Recommendations</th>
            <td><textarea name="recommendations" placeholder="Care plan, follow-up, referrals..."></textarea></td>
          </tr>

          <tr>
            <th>Prescription (Photo)</th>
            <td>
              <input type="file" name="prescription_image" accept="image/*">
              <div style="font-size:12px;color:#6b7a92;margin-top:4px;">
                Optional: upload a clear photo of the paper prescription (JPG/PNG).
              </div>
            </td>
          </tr>
        </table>

        <div class="buttons">
          <button type="submit" name="save_doctor_notes" class="btn primary">Save Notes & Complete</button>
          <a href="doctor_dashboard.php" class="btn secondary" style="text-decoration:none;">Back to Dashboard</a>
        </div>
      </form>
    </div>

    <div class="section">
      <h3>Notes History</h3>
      <?php if ($notes && $notes->num_rows > 0): ?>
        <?php while ($n = $notes->fetch_assoc()): ?>
          <div class="note-card">
            <div class="note-head">
              <strong><?= htmlspecialchars($n['doctor_name'] ?? 'Doctor') ?></strong>
              <span style="opacity:.8"> • <?= htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at']))) ?></span>
            </div>
            <?php if (!empty($n['findings'])): ?>
              <div><strong>Findings:</strong> <?= nl2br(htmlspecialchars($n['findings'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($n['diagnosis'])): ?>
              <div style="margin-top:6px;"><strong>Diagnosis:</strong> <?= nl2br(htmlspecialchars($n['diagnosis'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($n['recommendations'])): ?>
              <div style="margin-top:6px;"><strong>Recommendations:</strong> <?= nl2br(htmlspecialchars($n['recommendations'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($n['prescription'])): ?>
              <div style="margin-top:6px;">
                <strong>Prescription:</strong><br>
                <a href="<?= htmlspecialchars($n['prescription']) ?>" target="_blank" rel="noopener">
                  <img class="rx-img" src="<?= htmlspecialchars($n['prescription']) ?>" alt="Prescription">
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="color:#6b7a92;">No notes have been added yet.</div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
