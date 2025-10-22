<?php
include 'index.php'; // database connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$user = null;
$medications = [];

if ($user_id > 0) {
    // Get user details
    $stmt = $conn->prepare("
        SELECT id, first_name, middle_initial, last_name
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }

    // Fetch medication records
    $stmt = $conn->prepare("
        SELECT med_name, med_dose, med_frequency, created_at
        FROM user_medications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $medications[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Medications - Barangay San Miguel</title>
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
  <h1>Barangay San Miguel Admin</h1>
  <div class="user-icon">👤 Admin</div>
</div>

<div class="main-content">
  <?php if ($user): ?>
    <h2>Medications for <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_initial'] . ' ' . $user['last_name']) ?></h2>

    <?php if (!empty($medications)): ?>
      <table>
        <thead>
          <tr>
            <th>Medicine Name</th>
            <th>Dosage</th>
            <th>Frequency</th>
            <th>Date Recorded</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $previousDateTime = null;
          foreach ($medications as $row): 
              if ($previousDateTime && $previousDateTime != $row['created_at']) {
                  echo '<tr class="separator-row"><td colspan="4" style="height: 60px;"></td></tr>';
              }
              $previousDateTime = $row['created_at'];
          ?>
          <tr>
            <td><?= htmlspecialchars($row['med_name']) ?></td>
            <td><?= htmlspecialchars($row['med_dose']) ?></td>
            <td><?= htmlspecialchars($row['med_frequency']) ?></td>
            <td><?= date("F j, Y — g:i A", strtotime($row['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-data">No medication records found for this user.</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="no-data">User not found.</div>
  <?php endif; ?>

  <a href="admin_residents.php" class="back-link">⬅ Back to Residents</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
