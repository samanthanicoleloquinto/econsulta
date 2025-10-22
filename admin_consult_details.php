<?php
// ADMIN-ONLY: dedicated session namespace
session_name('EConsultaAdmin');
session_start();

// Guard (accept new key OR legacy 'email')
if (empty($_SESSION['admin_username']) && empty($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid consultation ID.");
}

$result = $conn->query("SELECT * FROM screening_data WHERE id = {$id}");
$data = $result ? $result->fetch_assoc() : null;

if (!$data) {
    die("Consultation record not found.");
}

// Handle vitals update (no scheduling changes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $height         = $_POST['height'] ?? '';
    $weight         = $_POST['weight'] ?? '';
    $bmi            = $_POST['bmi'] ?? '';
    $temperature    = $_POST['temperature'] ?? '';
    $blood_pressure = $_POST['blood_pressure'] ?? '';

    $stmt = $conn->prepare("UPDATE screening_data 
        SET height = ?, weight = ?, bmi = ?, temperature = ?, blood_pressure = ?
        WHERE id = ?");
    $stmt->bind_param("sssssi", $height, $weight, $bmi, $temperature, $blood_pressure, $id);
    $stmt->execute();
    $stmt->close();

    // Refresh data after update
    $result = $conn->query("SELECT * FROM screening_data WHERE id = {$id}");
    $data = $result->fetch_assoc();
}

// Format date and time
$preferredDate = !empty($data['preferred_schedule']) ? date("F j, Y g:i A", strtotime($data['preferred_schedule'])) : '';
$finalSchedule = !empty($data['schedule_time']) ? date("F j, Y g:i A", strtotime($data['schedule_time'])) : '';
$createdAtDate = !empty($data['created_at']) ? date("F j, Y", strtotime($data['created_at'])) : '';
$createdAtTime = !empty($data['created_at']) ? date("h:i A", strtotime($data['created_at'])) : '';
$dobFormatted  = !empty($data['dob']) ? date("F j, Y", strtotime($data['dob'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultation Summary</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 60px 20px;
      background-color: #f0f2f5;
      color: #2c3e50;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 50px;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }
    h2 {
      text-align: center;
      color: #1a2b49;
      margin-bottom: 40px;
      font-size: 32px;
      letter-spacing: 1px;
    }
    .section {
      margin-bottom: 48px;
    }
    .section h3 {
      color: #003366;
      font-size: 20px;
      margin-bottom: 18px;
      border-left: 5px solid #003366;
      padding-left: 12px;
    }
    .section table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fafbfc;
      border-radius: 6px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }
    .section th,
    .section td {
      padding: 14px 20px;
      border-bottom: 1px solid #e0e6ed;
      vertical-align: top;
    }
    .section th {
      width: 35%;
      background-color: #f5f9ff;
      font-weight: 600;
      color: #2a3f5f;
    }
    .section tr:last-child td {
      border-bottom: none;
    }
    input {
      width: 95%;
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #d1d9e6;
      margin-top: 4px;
    }
    .buttons {
      text-align: center;
      margin-top: 30px;
    }
    .buttons a, .buttons button {
      background-color: #1a2b49;
      color: #ffffff;
      padding: 12px 28px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 16px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .buttons a:hover, .buttons button:hover {
      background-color: #142033;
    }
    .badges {
      display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin:-20px 0 24px;
    }
    .badge {
      display:inline-block;background:#eef4ff;color:#003366;border:1px solid #d7e2ff;
      padding:6px 10px;border-radius:999px;font-size:12px;font-weight:600;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Consultation Summary</h2>

    <div class="badges">
      <span class="badge">REQUEST: <?= htmlspecialchars(strtoupper($data['status'])) ?></span>
      <span class="badge">CONSULTATION: <?= htmlspecialchars(strtoupper($data['consultation_status'])) ?></span>
      <?php if ($finalSchedule): ?>
        <span class="badge">SCHEDULED: <?= htmlspecialchars($finalSchedule) ?></span>
      <?php elseif ($preferredDate): ?>
        <span class="badge">PREFERRED: <?= htmlspecialchars($preferredDate) ?></span>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>Personal Information</h3>
      <table>
        <tr><th>Full Name</th><td><?= htmlspecialchars("{$data['first_name']} {$data['middle_initial']} {$data['last_name']} {$data['suffix']}") ?></td></tr>
        <tr><th>Gender</th><td><?= htmlspecialchars($data['gender']) ?></td></tr>
        <tr><th>Date of Birth</th><td><?= htmlspecialchars($dobFormatted) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($data['email']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($data['address']) ?></td></tr>
        <tr><th>Contact No.</th><td><?= htmlspecialchars($data['contact_number']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Guardian Information</h3>
      <table>
        <tr><th>Guardian Name</th><td><?= htmlspecialchars($data['guardian_name']) ?></td></tr>
        <tr><th>Relationship</th><td><?= htmlspecialchars($data['guardian_relationship']) ?></td></tr>
        <tr><th>Contact No.</th><td><?= htmlspecialchars($data['guardian_contact']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Medical History</h3>
      <table>
        <tr><th>Conditions</th><td><?= htmlspecialchars($data['medical_conditions']) ?></td></tr>
        <tr><th>Others</th><td><?= htmlspecialchars($data['medical_others']) ?></td></tr>
        <tr><th>Had Surgery</th><td><?= htmlspecialchars($data['had_surgery']) ?></td></tr>
        <tr><th>Surgery Type</th><td><?= htmlspecialchars($data['surgery_type']) ?></td></tr>
        <tr><th>Surgery Year</th><td><?= htmlspecialchars($data['surgery_year']) ?></td></tr>
        <tr><th>Recent Hospitalization</th><td><?= htmlspecialchars($data['recent_hospitalization']) ?></td></tr>
      </table>
    </div>

    <div class="section">
      <h3>Medications & Allergies</h3>
      <table>
        <tr><th>Medications</th><td><?= htmlspecialchars($data['medications']) ?></td></tr>
        <tr><th>Allergies to Medicines</th><td><?= htmlspecialchars($data['allergies_meds']) ?></td></tr>
        <tr><th>Allergies to Foods</th><td><?= htmlspecialchars($data['allergies_foods']) ?></td></tr>
        <?php if (!empty($data['foods_allergy_details'])): ?>
          <tr><th>Foods Allergy Details</th><td><?= htmlspecialchars($data['foods_allergy_details']) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="section">
      <h3>Symptoms</h3>
      <table>
        <tr><th>Selected Symptoms</th><td><?= htmlspecialchars($data['symptoms_selected']) ?></td></tr>
        <tr><th>Others</th><td><?= htmlspecialchars($data['symptoms_others']) ?></td></tr>
      </table>
    </div>

    <!-- Vitals (editable) -->
    <form method="POST">
      <div class="section">
        <h3>Check-Up Info / Vitals</h3>
        <table>
          <tr><th>Height (cm)</th><td><input type="text" name="height" value="<?= htmlspecialchars($data['height']) ?>"></td></tr>
          <tr><th>Weight (kg)</th><td><input type="text" name="weight" value="<?= htmlspecialchars($data['weight']) ?>"></td></tr>
          <tr><th>BMI</th><td><input type="text" name="bmi" value="<?= htmlspecialchars($data['bmi']) ?>"></td></tr>
          <tr><th>Temperature (°C)</th><td><input type="text" name="temperature" value="<?= htmlspecialchars($data['temperature']) ?>"></td></tr>
          <tr><th>Blood Pressure</th><td><input type="text" name="blood_pressure" value="<?= htmlspecialchars($data['blood_pressure']) ?>"></td></tr>
        </table>
        <div class="buttons">
          <button type="submit">Save Vitals</button>
        </div>
      </div>
    </form>

    <div class="section">
      <h3>Other Check-Up Info</h3>
      <table>
        <tr><th>Medical Concern</th><td><?= htmlspecialchars($data['medical_concern']) ?></td></tr>
        <tr><th>Returning Patient</th><td><?= htmlspecialchars($data['returning_patient']) ?></td></tr>
        <tr><th>Preferred Schedule</th><td><?= $preferredDate ?: '—' ?></td></tr>
        <tr><th>Final Schedule</th><td><?= $finalSchedule ?: '—' ?></td></tr>
        <tr><th>Health Concern</th><td><?= htmlspecialchars($data['health_concern']) ?></td></tr>
        <tr><th>Date Submitted</th><td><?= htmlspecialchars($createdAtDate . ' at ' . $createdAtTime) ?></td></tr>
      </table>
    </div>

    <div class="buttons">
      <a href="admin_consult.php">← Back to Consultations</a>
    </div>
  </div>
</body>
</html>
