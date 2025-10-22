<?php
session_start();
include 'index.php'; // Database connection

// Handle fetch POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    parse_str(file_get_contents("php://input"), $_POST);

    $action = $_POST['action'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($action === 'update' && $user_id && in_array($status, ['approved', 'rejected'])) {
        // Fetch the user's email based on the user_id
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $email = $user['email'];

        // Update the status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $user_id);
        if ($stmt->execute()) {
            // Echo a message with the user's email instead of the ID
            echo "$email has been $status."; // Use email in message
        } else {
            echo "Failed to update user. " . $stmt->error;
        }
        exit();
    } else {
        echo "Invalid request.";
        exit();
    }
}

$stmt = $conn->prepare("SELECT id, email, first_name, last_name, contact_number, years_of_residency, age, proof_of_identity, status FROM users");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Barangay San Miguel</title>
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
      margin-left: 240px;
      padding: 40px;
      font-size: 22px;
    }

    .main-content h2 {
      font-size: 30px;
      margin-bottom: 25px;
      margin-left: -50px;
      margin-top: -10px;
      color: #1a1a1a;
      font-weight: bolder;
    }

    /* Table Container */
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
      margin-left: -50px;
      background-color: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Table Styles */
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
  }

th, td {
  padding: 12px 14px;
  text-align: center;
  font-size: 14px;
  border: 1px solid #e0e0e0;
}

thead th {
  background-color: #0047b3;
  color: white;
  font-weight: 600;
  font-size: 14px;
  text-transform: uppercase;
}

tbody td {
  background-color: #fafafa;
  transition: background-color 0.3s ease;
}

tbody tr:hover td {
  background-color: #eef4ff;
}

/* Buttons */
button {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.btn-approve {
  background-color: #28a745;
  color: white;
}

.btn-approve:hover {
  background-color: #218838;
  transform: translateY(-1px);
}

.btn-reject {
  background-color: #dc3545;
  color: white;
}

.btn-reject:hover {
  background-color: #c82333;
  transform: translateY(-1px);
}

/* Modal Styles */
#custom-alert {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  padding: 25px 30px;
  border-radius: 12px;
  text-align: center;
  font-size: 16px;
  max-width: 400px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.modal-content p {
  margin-bottom: 20px;
  line-height: 1.5;
}

.modal-content button {
  margin-top: 15px;
  padding: 10px 24px;
  background-color: #0033a0;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s ease;
}

.modal-content button:hover {
  background-color: #001f70;
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


  <div class="topbar">
    <h1>Barangay Pineda Admin</h1>
    <div class="user-icon">👤 Admin</div>
  </div>

<div class="main-content">
  <h2>ACCOUNT MANAGEMENT</h2>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Email</th><th>First Name</th><th>Last Name</th>
          <th>Contact</th><th>Residency (Years)</th><th>Age</th>
          <th>Proof</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr id="row-<?php echo $row['id']; ?>">
          <td><?php echo $row['id']; ?></td>
          <td><?php echo $row['email']; ?></td>
          <td><?php echo $row['first_name']; ?></td>
          <td><?php echo $row['last_name']; ?></td>
          <td><?php echo $row['contact_number']; ?></td>
          <td><?php echo $row['years_of_residency']; ?></td>
          <td><?php echo $row['age']; ?></td>
          <td><a href="<?php echo $row['proof_of_identity']; ?>" target="_blank">View</a></td>
          <td id="status-<?php echo $row['id']; ?>"><?php echo $row['status']; ?></td>
          <td>
            <button onclick="updateStatus(<?php echo $row['id']; ?>, 'approved')" class="btn-approve">Approve</button>
            <button onclick="updateStatus(<?php echo $row['id']; ?>, 'rejected')" class="btn-reject">Reject</button>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div id="custom-alert">
  <div class="modal-content">
    <p id="alert-text">Message</p>
    <button onclick="document.getElementById('custom-alert').style.display = 'none'">OK</button>
  </div>
</div>

<script>
function updateStatus(userId, status) {
  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update&user_id=${encodeURIComponent(userId)}&status=${encodeURIComponent(status)}`
  })
  .then(response => response.text())
  .then(message => {
    document.getElementById('status-' + userId).textContent = status;
    document.getElementById('alert-text').textContent = message;
    document.getElementById('custom-alert').style.display = 'flex';
  })
  .catch(error => alert("Error: " + error));
}
</script>

</body>
</html>

<?php $conn->close(); ?>
