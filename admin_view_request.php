<?php
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve'])) {
    $request_id = $_POST['request_id'];
    $pickup_schedule = $_POST['pickup_schedule'];

    // Get requested quantity and medicine ID
    $stmt = $conn->prepare("SELECT medicine_id, quantity FROM medicine_requests WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($medicine_id, $quantity);
    $stmt->fetch();
    $stmt->close();

    
    $update_stock = $conn->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");
    $update_stock->bind_param("ii", $quantity, $medicine_id);
    $update_stock->execute();

    
    $stmt = $conn->prepare("UPDATE medicine_requests SET status = 'approved', pickup_schedule = ? WHERE request_id = ?");
    $stmt->bind_param("si", $pickup_schedule, $request_id);
    $stmt->execute();

    echo "<p style='color: green;'>✅ Request approved and stock updated!</p>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject'])) {
    $request_id = $_POST['request_id'];
    $stmt = $conn->prepare("UPDATE medicine_requests SET status = 'rejected' WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    echo "<p style='color: red;'>❌ Request rejected.</p>";
}

// Get all pending requests
$pending_result = $conn->query("
    SELECT mr.request_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.contact_number, m.name AS medicine_name, mr.quantity, mr.request_date
    FROM medicine_requests mr
    JOIN users u ON mr.patient_id = u.id
    JOIN medicines m ON mr.medicine_id = m.medicine_id
    WHERE mr.status = 'pending'
    ORDER BY mr.request_date DESC
");

$approved_result = $conn->query("
    SELECT mr.request_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, u.contact_number, m.name AS medicine_name, mr.quantity, mr.request_date, mr.pickup_schedule
    FROM medicine_requests mr
    JOIN users u ON mr.patient_id = u.id
    JOIN medicines m ON mr.medicine_id = m.medicine_id
    WHERE mr.status = 'approved'
    ORDER BY mr.request_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Free Medicine Requests</title>
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

    .main-content {
      margin-left: 220px;
      padding: 30px;
    }

    h2 {
      margin-bottom: 15px;
      color: #002c6d;
    }

    .tabs {
      margin-bottom: 20px;
    }

    .tab-button {
      background-color: #0047b3;
      border: none;
      padding: 10px 20px;
      color: white;
      cursor: pointer;
      border-radius: 6px;
      margin-right: 10px;
    }

    .tab-button.active {
      background-color: #2a80b9;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }


    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }

    th {
      background-color: #0047b3;
      color: white;
    }

    .action-btn {
      padding: 6px 12px;
      margin: 4px 2px;
      font-size: 13px;
      font-weight: bold;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .approve-btn {
      background-color: #38b000;
      color: white;
    }

    .reject-btn {
      background-color: #d90429;
      color: white;
    }

    .approve-btn:hover {
      background-color: #2f8700;
    }

    .reject-btn:hover {
      background-color: #b2031d;
    }

    input[type="datetime-local"] {
      padding: 5px;
      font-size: 13px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    .alert {
      margin-bottom: 20px;
      padding: 12px 20px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
    }

    .alert.success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert.error {
      background-color: #f8d7da;
      color: #721c24;
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

  <div class="main-content">
    <h2>📬 Pending Free Medicine Requests</h2>

    <?= isset($alert) ? $alert : '' ?>

    <div class="tabs">
    <button class="tab-button active" onclick="showTab('pending')">⏳ Pending</button>
    <button class="tab-button" onclick="showTab('approved')">✅ Approved</button>
  </div>

  <div id="pending" class="tab-content active">
    <?php if ($pending_result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Contact</th>
            <th>Medicine</th>
            <th>Quantity</th>
            <th>Request Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $pending_result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['full_name'] ?></td>
            <td><?= $row['contact_number'] ?></td>
            <td><?= $row['medicine_name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['request_date'] ?></td>
            <td>
              <form method="POST" style="display:inline-block;">
                <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                <input type="datetime-local" name="pickup_schedule" required>
                <button type="submit" name="approve" class="action-btn approve-btn">✅ Approve</button>
              </form>
              <form method="POST" style="display:inline-block;">
                <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                <button type="submit" name="reject" class="action-btn reject-btn" onclick="return confirm('Reject this request?')">❌ Reject</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No pending requests at the moment.</p>
    <?php endif; ?>
  </div>

  <div id="approved" class="tab-content">
    <?php if ($approved_result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Contact</th>
            <th>Medicine</th>
            <th>Quantity</th>
            <th>Request Date</th>
            <th>Pickup Schedule</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $approved_result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['full_name'] ?></td>
            <td><?= $row['contact_number'] ?></td>
            <td><?= $row['medicine_name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['request_date'] ?></td>
            <td><?= $row['pickup_schedule'] ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No approved requests yet.</p>
    <?php endif; ?>
  </div>
</div>

<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
