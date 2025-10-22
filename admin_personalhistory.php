<?php
include 'index.php'; // Database connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$user = null;
$history = [];

if ($user_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.middle_initial, u.last_name
        FROM users u
        WHERE u.id = ? AND EXISTS (
            SELECT 1 FROM personal_medical_history p WHERE p.user_id = u.id
        )
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $stmt = $conn->prepare("
            SELECT had_surgery, surgery_type, surgery_year, recent_hospitalization,
                   allergic_meds, meds_allergy_details,
                   allergic_foods, foods_allergy_details,
                   created_at
            FROM personal_medical_history
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Personal Medical History - Barangay San Miguel</title>
  <link href="https://fonts.googleapis.com/css2?family=Moul&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background-color: #f4f6fa;
      color: #333;
    }

    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 200px;
      height: 100vh;
      background-color: #002c6d;
      padding: 20px 15px;
      color: white;
    }

    .sidebar img {
      width: 90px;
      margin: 10px auto 20px;
      display: block;
    }

    .sidebar h2 {
      font-size: 15px;
      margin-bottom: 20px;
      text-align: center;
      letter-spacing: 0.5px;
      color: white;
    }

    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      margin: 8px 0;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 13px;
      transition: background 0.2s;
    }

    .sidebar a:hover {
      background-color: #001c47;
    }
    
    .topbar {
      margin-left: 200px;
      padding: 20px 30px;
      background-color: #ffffff;
      border-bottom: 1px solid #dce0e7;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topbar h1 {
      font-size: 24px;
      color: #0033a0;
      font-weight: bolder;
      text-transform: uppercase;
    }

    .topbar .user-icon {
      font-size: 15px;
      background-color: #e6ecff;
      padding: 8px 18px;
      border-radius: 30px;
      color: #0033a0;
      font-weight: 500;
    }

    .main-content {
      margin-left: 200px;
      padding: 40px 40px;
    }

    .main-content h2 {
      font-size: 22px;
      margin-bottom: 25px;
      color: #1a1a1a;
      font-weight: bold;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      border-radius: 8px;
      overflow: hidden;
    }
    th, td {
      padding: 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #0033a0;
      color: white;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    tr:hover {
      background-color: #eef1ff;
    }
    .blue-divider {
      height: 10px;
      background-color: #0033a0;
    }
    .back-link {
      margin-top: 20px;
      display: inline-block;
      padding: 10px 15px;
      background-color: #0033a0;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }
    .back-link:hover {
      background-color: #002266;
    }
    .no-data {
      padding: 15px;
      background: #fff3cd;
      border: 1px solid #ffeeba;
      color: #856404;
      border-radius: 5px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <img src="logo.png" alt="Barangay Logo">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_accountapproval.php">Account Approval</a>
    <a href="admin_residents.php">Residents</a>
    <a href="admin_consult.php">Consultation</a>
    <a href="admin_pregnant.php">Pregnant</a>
    <a href="admin_infant.php">Infants</a>
    <a href="admin_familyplan.php">Family Planning</a>
    <a href="admin_view_request.php">Free Medicine</a>
    <a href="admin_add_stock.php">Medicine Stocks</a>
    <a href="admin_tbdots.php">TB DOTS</a>
    <a href="admin_toothextraction.php">Free Tooth Extraction</a>
    <a href="admin_calendar.php">Schedule Calendar</a>
    <a href="admin_login.php">Logout</a>
  </div>

<div class="topbar">
  <h1>Barangay Pineda Admin</h1>
  <div class="user-icon">👤 Admin</div>
</div>

<div class="main-content">
  <?php if ($user): ?>
    <h2>Personal Medical History for <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_initial'] . ' ' . $user['last_name']) ?></h2>

    <?php if (!empty($history)): ?>
      <table>
        <thead>
          <tr>
            <th>Surgery</th>
            <th>Surgery Year</th>
            <th>Hospitalization</th>
            <th>Allergic to Medicine</th>
            <th>Medicine Allergy Details</th>
            <th>Allergic to Food</th>
            <th>Food Allergy Details</th>
            <th>Date Recorded</th>
          </tr>
        </thead>
        <tbody>
          <?php 
            $previousDate = null;
            foreach ($history as $row): 
              $currentDate = date('Y-m-d', strtotime($row['created_at']));
              if ($previousDate !== null && $currentDate === $previousDate) {
                  echo '<tr class="blue-divider"><td colspan="8" style="height: 60px;""></td></tr>';
              }
              $previousDate = $currentDate;
          ?>
            <tr>
              <td><?= htmlspecialchars($row['had_surgery'] === 'Yes' ? $row['surgery_type'] : 'No') ?></td>
              <td><?= htmlspecialchars($row['surgery_year'] ?: '-') ?></td>
              <td><?= htmlspecialchars($row['recent_hospitalization']) ?></td>
              <td><?= htmlspecialchars($row['allergic_meds']) ?></td>
              <td><?= htmlspecialchars($row['meds_allergy_details']) ?></td>
              <td><?= htmlspecialchars($row['allergic_foods']) ?></td>
              <td><?= htmlspecialchars($row['foods_allergy_details']) ?></td>
              <td><?= date("F j, Y — g:i A", strtotime($row['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-data">No personal medical history found for this user.</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="no-data">User not found or has no personal medical history.</div>
  <?php endif; ?>

  <a href="admin_residents.php" class="back-link">⬅ Back to Residents</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
