<?php
// admin_screening_view.php
session_start();
$conn = new mysqli("localhost", "root", "", "econsultaaa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid request ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_date  = $_POST['schedule_date'];
    $schedule_time  = $_POST['schedule_time'];
    $height         = $_POST['height'];
    $weight         = $_POST['weight'];
    $bmi            = $_POST['bmi'];
    $temperature    = $_POST['temperature'];
    $blood_pressure = $_POST['blood_pressure'];

    $stmt = $conn->prepare("UPDATE screening_data 
        SET preferred_schedule = ?, schedule_time = ?, height = ?, weight = ?, bmi = ?, temperature = ?, blood_pressure = ?, status = 'Approved'
        WHERE id = ?");
    $stmt->bind_param("sssssssi", $schedule_date, $schedule_time, $height, $weight, $bmi, $temperature, $blood_pressure, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Schedule and vitals updated successfully.'); location.href='admin_screening_requests.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating data.');</script>";
    }
    $stmt->close();
}

// Fetch user data
$result = $conn->query("SELECT * FROM screening_data WHERE id = $id");
if ($result->num_rows !== 1) {
    die("User not found.");
}
$data = $result->fetch_assoc();

// Fetch extended medical history
$user_id = $data['user_id'];
$medical_history = $conn->query("SELECT * FROM personal_medical_history WHERE user_id = $user_id")->fetch_assoc();

function getWeekdays($selected = '') {
    $options = '';
    $today = new DateTime();
    $count = 0;
    while ($count < 14) {
        if (!in_array($today->format('N'), ['6', '7'])) {
            $val = $today->format('Y-m-d');
            $label = $today->format('l, F j, Y');
            $sel = ($selected == $val) ? 'selected' : '';
            $options .= "<option value='$val' $sel>$label</option>";
            $count++;
        }
        $today->modify('+1 day');
    }
    return $options;
}

$timeSlots = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patient Screening Details</title>
  <style>
    /* General Body */
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 60px 20px;
      background-color: #f0f2f5;
      color: #2c3e50;
    }

    /* Main Container */
    .container {
      position: relative;
      max-width: 900px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 50px;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }

    /* Close Button (X) */
    .close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #1a2b49;
    text-decoration: none;
    background: #e0e6ed;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    transition: background-color 0.3s ease, color 0.3s ease;
    }

    .close-btn:hover {
    background-color: #1a2b49;
    color: #fff;
    }


    /* Page Title */
    h2 {
      text-align: center;
      color: #1a2b49;
      margin-bottom: 40px;
      font-size: 32px;
      letter-spacing: 1px;
    }

    /* Section Titles */
    .section-title {
      margin-top: 40px;
      font-size: 20px;
      font-weight: 600;
      color: #003366;
      border-left: 5px solid #003366;
      padding-left: 12px;
      margin-bottom: 20px;
    }

    /* Paragraphs and Labels */
    p {
      margin: 8px 0;
      line-height: 1.6;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: 500;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border-radius: 6px;
      border: 1px solid #d1d9e6;
      background-color: #fafbfc;
      font-size: 14px;
      box-sizing: border-box;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #1a2b49;
      box-shadow: 0 0 4px rgba(26, 43, 73, 0.3);
    }

    /* Button Styling */
    .btn {
      display: inline-block;
      background-color: #1a2b49;
      color: #ffffff;
      padding: 12px 28px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      margin-top: 25px;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn:hover {
      background-color: #142033;
      transform: translateY(-2px);
    }

    /* Table / List Style (Optional for future) */
    .data-list {
      background-color: #fafbfc;
      padding: 20px;
      border-radius: 6px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

  </style>
</head>
<body>
  <div class="container">
    <a href="admin_screening_requests.php" class="close-btn">&times;</a>
    <h2>Patient Screening Details</h2>

    <div class="section-title">Personal Information</div>
    <div class="data-list">
      <p><strong>Name:</strong> <?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($data['email']) ?></p>
      <p><strong>Contact:</strong> <?= htmlspecialchars($data['contact_number']) ?></p>
      <p><strong>Address:</strong> <?= htmlspecialchars($data['address']) ?></p>
      <p><strong>Medical Concern:</strong> <?= htmlspecialchars($data['medical_concern']) ?></p>
    </div>

    <div class="section-title">Symptoms</div>
    <div class="data-list">
      <p><?= nl2br(htmlspecialchars($data['symptoms_selected'])) ?></p>
    </div>

    <div class="section-title">Medical History</div>
    <div class="data-list">
      <p><strong>Had Surgery:</strong> <?= htmlspecialchars($medical_history['had_surgery'] ?? 'No data') ?></p>
      <p><strong>Surgery Type:</strong> <?= htmlspecialchars($medical_history['surgery_type'] ?? 'None') ?></p>
      <p><strong>Surgery Year:</strong> <?= htmlspecialchars($medical_history['surgery_year'] ?? 'None') ?></p>
      <p><strong>Recent Hospitalization:</strong> <?= htmlspecialchars($medical_history['recent_hospitalization'] ?? 'None') ?></p>
      <p><strong>Current Medications:</strong> <?= htmlspecialchars($medical_history['current_medications'] ?? 'None') ?></p>
      <p><strong>Allergic to Medications:</strong> <?= htmlspecialchars($medical_history['allergic_meds'] ?? 'No') ?></p>
      <p><strong>Medication Allergy Details:</strong> <?= htmlspecialchars($medical_history['meds_allergy_details'] ?? 'None') ?></p>
      <p><strong>Allergic to Foods:</strong> <?= htmlspecialchars($medical_history['allergic_foods'] ?? 'No') ?></p>
      <p><strong>Food Allergy Details:</strong> <?= htmlspecialchars($medical_history['foods_allergy_details'] ?? 'None') ?></p>
    </div>

    <form method="POST">
      <div class="section-title">Schedule</div>
      <label for="schedule_date">Preferred Schedule Date:</label>
      <select name="schedule_date" required>
        <option value="">-- Select Date --</option>
        <?= getWeekdays($data['preferred_schedule']) ?>
      </select>

      <label for="schedule_time">Preferred Time:</label>
      <select name="schedule_time" required>
        <option value="">-- Select Time --</option>
        <?php foreach ($timeSlots as $slot): ?>
          <option value="<?= $slot ?>" <?= $data['schedule_time'] === $slot ? 'selected' : '' ?>>
            <?= date("h:i A", strtotime($slot)) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="section-title">Vitals</div>
      <label for="height">Height (cm):</label>
      <input type="text" name="height" value="<?= htmlspecialchars($data['height']) ?>">

      <label for="weight">Weight (kg):</label>
      <input type="text" name="weight" value="<?= htmlspecialchars($data['weight']) ?>">

      <label for="bmi">BMI:</label>
      <input type="text" name="bmi" value="<?= htmlspecialchars($data['bmi']) ?>">

      <label for="temperature">Temperature (°C):</label>
      <input type="text" name="temperature" value="<?= htmlspecialchars($data['temperature']) ?>">

      <label for="blood_pressure">Blood Pressure:</label>
      <input type="text" name="blood_pressure" value="<?= htmlspecialchars($data['blood_pressure']) ?>">

      <button type="submit" class="btn">Save</button>
    </form>
  </div>
</body>
</html>
