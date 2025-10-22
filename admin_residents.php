<?php
include 'index.php'; // DB connection

$query = "SELECT id, first_name, middle_initial, last_name, suffix, dob, gender, email, contact_number, address FROM users";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Barangay San Miguel</title>
  <link href="https://fonts.googleapis.com/css2?family=Moul&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
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

/* Main Content Area */
.main-content {
  margin-left: 200px;
  padding: 30px;
}
.main-content h2 {
  font-size: 22px;
  margin-bottom: 20px;
  color: #002c6d;
  font-weight: 600;
}

/* Table Styling */
table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
}
th, td {
  padding: 14px 12px;
  text-align: center;
  font-size: 14px;
}
th {
  background-color: #0033a0;
  color: #ffffff;
  font-weight: 600;
  letter-spacing: 0.5px;
}
tr:nth-child(even) {
  background-color: #f4f7fc;
}
tr:hover {
  background-color: #eef3ff;
  transition: background-color 0.2s ease;
}

/* View Button */
.btn-view {
  background-color: #0033a0;
  color: #fff;
  padding: 8px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease-in-out, transform 0.1s;
}
.btn-view:hover {
  background-color: #001f5c;
  transform: scale(1.03);
}

/* Popup Overlay */
.overlay {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 999;
  backdrop-filter: blur(2px);
}

/* Popup Container */
.popup-container {
  display: none;
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  background-color: #fff;
  padding: 30px 25px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  z-index: 1000;
  width: 380px;
  max-width: 90%;
  text-align: center;
}

/* Popup Heading */
.popup-container h3 {
  margin-bottom: 25px;
  color: #0033a0;
  font-size: 20px;
  font-weight: 600;
}

.btn-view {
  background-color: #0033a0;
  color: #fff;
  padding: 10px 20px;
  border-radius: 12px;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: background 0.3s ease-in-out, transform 0.2s, box-shadow 0.3s;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-view:hover {
  background-color: #001f5c;
  transform: scale(1.05);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Popup Buttons */
.popup-btn {
  display: block;
  width: 100%;
  margin: 12px 0;
  padding: 15px;
  background-color: #0033a0;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.2s, box-shadow 0.3s;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.popup-btn:hover {
  background-color: #001f5c;
  transform: scale(1.05);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Close Button */
.popup-close {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 24px;
  font-weight: bold;
  color: #0033a0;
  cursor: pointer;
  transition: color 0.2s ease, transform 0.2s;
}

.popup-close:hover {
  color: #e60000;
  transform: rotate(90deg);
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
    <a href="forecast_results.php">Diseases Forecasting</a>
    <a href="admin_login.php">Logout</a>
  </div>


<!-- Topbar -->
<div class="topbar">
  <h1>Barangay Pineda Admin</h1>
  <div class="user-icon">👤 Admin</div>
</div>

<!-- Popup and overlay -->
<div id="overlay" class="overlay" onclick="closePopup()"></div>
<div id="popup" class="popup-container">
  <span class="popup-close" onclick="closePopup()">×</span>
  <h3>Select Medical Record to View</h3>
  <button class="popup-btn" id="btnMedicalHistory">🩺 Medical History</button>
  <button class="popup-btn" id="btnPersonalMedical">📋 Personal Medical History</button>
  <button class="popup-btn" id="btnMedications">💊 User Medications</button>
  <button class="popup-btn" id="btnScreeningCheckup">🩻 Screening Check-up</button>
</div>

<!-- Main content -->
<div class="main-content">
  <h2>Resident Information</h2>
  <table>
    <thead>
      <tr>
        <th>First Name</th>
        <th>Middle Initial</th>
        <th>Last Name</th>
        <th>Suffix</th>
        <th>Date of Birth</th>
        <th>Gender</th>
        <th>Email</th>
        <th>Contact</th>
        <th>Address</th>
        <th>History</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['first_name']) ?></td>
            <td><?= htmlspecialchars($row['middle_initial']) ?></td>
            <td><?= htmlspecialchars($row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['suffix']) ?></td>
            <td><?= htmlspecialchars($row['dob']) ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['contact_number']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><button class="btn-view" onclick="showPopup(<?= $row['id'] ?>)">View</button></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="10">No residents found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  let selectedUserId = 0;

  function showPopup(userId) {
    selectedUserId = userId;
    document.getElementById("popup").style.display = "block";
    document.getElementById("overlay").style.display = "block";
  }

  function closePopup() {
    document.getElementById("popup").style.display = "none";
    document.getElementById("overlay").style.display = "none";
  }

  document.getElementById("btnMedicalHistory").onclick = function () {
    window.location.href = "admin_medicalhistory.php?user_id=" + selectedUserId;
  };

  document.getElementById("btnPersonalMedical").onclick = function () {
    window.location.href = "admin_personalhistory.php?user_id=" + selectedUserId;
  };

  document.getElementById("btnMedications").onclick = function () {
    window.location.href = "admin_usermedications.php?user_id=" + selectedUserId;
  };

  document.getElementById("btnScreeningCheckup").onclick = function () {
    window.location.href = "admin_screening_summary.php?user_id=" + selectedUserId;
  };
</script>

</body>
</html>

<?php $conn->close(); ?>
