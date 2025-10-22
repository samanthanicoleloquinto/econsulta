<?php
include 'index.php'; // Database connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$user = null;
$history = [];

if ($user_id > 0) {
    // Get user info only if medical history exists
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.middle_initial, u.last_name
        FROM users u
        WHERE u.id = ? AND EXISTS (
            SELECT 1 FROM medical_history m WHERE m.user_id = u.id
        )
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Fetch medical history sorted by timestamp (newest first)
        $stmt = $conn->prepare("
            SELECT condition_value, specify, created_at
            FROM medical_history
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
  <title>Medical History - Barangay San Miguel</title>
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

/* Table Styling */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

th, td {
  padding: 16px 20px;
  text-align: center;
  font-size: 15px;
  font-weight: normal;
}

th {
  background-color: #0033a0;
  color: white;
  font-weight: 600;
  letter-spacing: 0.5px;
}

tr:nth-child(even) {
  background-color: #f9f9f9;
}

tr:hover {
  background-color: #eef1ff;
}

td {
  color: #333;
}

/* Links */
.back-link {
  margin-top: 20px;
  display: inline-block;
  padding: 12px 18px;
  background-color: #0033a0;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-size: 16px;
  transition: background-color 0.3s ease, transform 0.3s ease;
}

.back-link:hover {
  background-color: #002266;
  transform: scale(1.05);
}

/* No Data Message */
.no-data {
  padding: 20px;
  background: #fff3cd;
  border: 1px solid #ffeeba;
  color: #856404;
  border-radius: 5px;
  margin-top: 20px;
  text-align: center;
}

/* Popup and overlay */
.overlay {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 999;
}

.popup-container {
  display: none;
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  background-color: #fff;
  padding: 30px 25px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  width: 380px;
  max-width: 90%;
  text-align: center;
}

.popup-close {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 24px;
  color: #0033a0;
  cursor: pointer;
  transition: color 0.2s ease;
}

.popup-close:hover {
  color: #e60000;
  transform: rotate(90deg);
}

.popup-btn {
  display: block;
  width: 100%;
  margin: 15px 0;
  padding: 15px;
  background-color: #0033a0;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.2s;
}

.popup-btn:hover {
  background-color: #001f5c;
  transform: scale(1.05);
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
  <h1>Barangay Pienda Admin</h1>
  <div class="user-icon">👤 Admin</div>
</div>

<div class="main-content">
  <?php if ($user): ?>
    <h2>Medical History for <?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_initial'] . ' ' . $user['last_name']) ?></h2>

    <?php if (!empty($history)): ?>
      <table>
        <thead>
          <tr>
            <th>Condition</th>
            <th>Date Recorded</th>
          </tr>
        </thead>
        <tbody>
          <?php 
            $previousTimestamp = null;
            foreach ($history as $record): 
              if ($previousTimestamp !== null && $previousTimestamp !== $record['created_at']) {
                  echo '<tr><td colspan="2" style="height: 60px;"></td></tr>';
              }
              $previousTimestamp = $record['created_at'];
          ?>
            <tr>
              <td>
                <?= htmlspecialchars($record['condition_value']) ?>
                <?php if ($record['condition_value'] === 'Others' && !empty($record['specify'])): ?>
                  &nbsp;&nbsp;—&nbsp;<em><?= htmlspecialchars($record['specify']) ?></em>
                <?php endif; ?>
              </td>
              <td><?= date("F j, Y — g:i A", strtotime($record['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-data">No medical history found for this user.</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="no-data">User not found or has no medical history.</div>
  <?php endif; ?>

  <a href="admin_residents.php" class="back-link">⬅ Back to Residents</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
