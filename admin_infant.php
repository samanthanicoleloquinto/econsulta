<?php  
session_start();  

// DB connection  
$conn = new mysqli("localhost", "root", "", "econsulta");  
if ($conn->connect_error) {  
    die("Connection failed: " . $conn->connect_error);  
}  

// Auto-expire logic for expired infant consultations based on preferred_schedule
$expire_sql = "  
    UPDATE infants_consultation    
    SET status = 'expired'    
    WHERE preferred_schedule IS NOT NULL    
    AND preferred_schedule < NOW()    
    AND status IN ('approved', 'pending')   
";  

// Run the expire query 
if (!$conn->query($expire_sql)) {  
    die("Auto-expire failed: " . $conn->error);  
}  

// Handle form actions (approve/reject/save schedule)  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    $id = $_POST['id'] ?? '';  
    $action = $_POST['action'] ?? '';  
    $preferred_schedule = $_POST['preferred_schedule'] ?? ''; // Using the new preferred_schedule field  
    $new_status = $_POST['new_status'] ?? '';  

    if ($id && in_array($action, ['save', 'reject'])) {  
        if ($action === 'save') {  
            if ($preferred_schedule && strtotime($preferred_schedule) > time()) {  
                $stmt = $conn->prepare("UPDATE infants_consultation SET preferred_schedule = ?, status = 'approved' WHERE id = ?");  
                $stmt->bind_param("si", $preferred_schedule, $id);  
            } else {  
                $stmt = $conn->prepare("UPDATE infants_consultation SET preferred_schedule = ? WHERE id = ?");  
                $stmt->bind_param("si", $preferred_schedule, $id);  
            }  
        } elseif ($action === 'reject') {  
            $stmt = $conn->prepare("UPDATE infants_consultation SET status = ? WHERE id = ?");  
            $stmt->bind_param("si", $new_status, $id);  
        }  
        $stmt->execute();  
        $stmt->close();  
    }  
}  

// Search filter  
$search = trim($_GET['search'] ?? '');  

$query = "SELECT * FROM infants_consultation";  
$params = [];  
$types = '';  

if ($search !== '') {  
    $query .= " WHERE infant_name LIKE ?";  
    $params[] = "%$search%";  
    $types .= 's';  
}  

$query .= " ORDER BY created_at DESC";  

$stmt = $conn->prepare($query);  

if ($types) {  
    $stmt->bind_param($types, ...$params);  
}  
$stmt->execute();  
$result = $stmt->get_result();  
?>  

<!DOCTYPE html>  
<html lang="en">  
<head>  
<meta charset="UTF-8" />  
<title>Admin - Infant Consultation Requests</title>  
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />  
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
  .main-content {  
      margin-left: 220px;  
      padding: 40px 30px;  
      background-color: #f9fafc;  
      min-height: 100vh;  
  }  
  h2 {  
      font-size: 22px;  
      color: #003d99;  
      margin-bottom: 25px;  
  }  
  .search-box {  
      margin-bottom: 25px;  
  }  
  .search-box input[type="text"] {  
      padding: 10px 12px;  
      width: 250px;  
      font-size: 14px;  
      border: 1px solid #ccc;  
      border-radius: 6px;  
  }  
  .search-box button {  
      padding: 10px 14px;  
      font-size: 14px;  
      background-color: #003d99;  
      color: white;  
      border: none;  
      border-radius: 6px;  
      cursor: pointer;  
  }  
  .search-box button:hover {  
      background-color: #002c6d;  
  }  
  table {  
      width: 100%;  
      background-color: white;  
      border-collapse: collapse;  
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);  
  }  
  th, td {  
      padding: 14px 10px;  
      text-align: center;  
      border-bottom: 1px solid #eee;  
      font-size: 13px;  
  }  
  th {  
      background-color: #003d99;  
      color: white;  
  }  
  tr:nth-child(even) {  
      background-color: #f4f6fa;  
  }  
  input[type="datetime-local"] {  
      padding: 8px;  
      font-size: 13px;  
      width: 100%;  
      border-radius: 6px;  
      border: 1px solid #ccc;  
  }  
  .btn-save, .btn-reject {  
      padding: 6px 10px;  
      font-size: 13px;  
      border: none;  
      border-radius: 6px;  
      margin-top: 6px;  
      cursor: pointer;  
      width: 48%;  
  }  
  .btn-save {  
      background-color: #003d99;  
      color: white;  
  }  
  .btn-save:hover {  
      background-color: #002c6d;  
  }  
  .btn-reject {  
      background-color: crimson;  
      color: white;  
  }  
  .btn-reject:hover {  
      background-color: darkred;  
  }  
  .status {  
      display: inline-block;  
      padding: 5px 10px;  
      font-size: 13px;  
      font-weight: bold;  
      border-radius: 12px;  
  }  
  .status.pending {  
      background-color: #ffe08a;  
      color: #000;  
  }  
  .status.approved {  
      background-color: #c4f0c5;  
      color: #000;  
  }  
  .status.rejected {  
      background-color: #f6b6b6;  
      color: #000;  
  }  
  .status.expired {  
      background-color: #dcdcdc;  
      color: #000;  
      font-weight: bold;  
  }  
  .no-results {  
      text-align: center;  
      padding: 30px;  
      font-style: italic;  
      color: #888;  
  }  
  .disabled-btn {  
    background-color: #ccc;  
    cursor: not-allowed;  
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
  <h2>Infant Consultation Requests</h2>

  <form class="search-box" method="GET">
    <input type="text" name="search" placeholder="Search by infant name..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Infant Name</th>
        <th>Age</th>
        <th>Guardian Name</th>
        <th>Consult Type</th>
        <th>Submitted</th>
        <th>Status</th>
        <th>Schedule</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()):
        $age = isset($row['infant_age']) ? (int)$row['infant_age'] : '-';
        $schedule_raw = $row['preferred_schedule'] ?? '';
        $preferred_ts = $schedule_raw ? strtotime($schedule_raw) : 0;
        $now = time();
        $status_raw = strtolower(trim($row['status'] ?? 'pending'));
        $is_expired = $preferred_ts && $preferred_ts < $now && in_array($status_raw, ['pending', 'approved']);
        $status_display = $is_expired ? 'expired' : $status_raw;
        $schedule_value = $schedule_raw ? date('Y-m-d\TH:i', $preferred_ts) : '';
      ?>
      <tr>
        <td><?= htmlspecialchars($row['infant_name']) ?></td>
        <td><?= $age ?></td>
        <td><?= htmlspecialchars($row['guardian_name']) ?></td>
        <td><?= htmlspecialchars($row['consult_type']) ?></td>
        <td><?= date('F j, Y g:i A', strtotime($row['created_at'])) ?></td>
        <td><span class="status <?= $status_display ?>">
            <?php 
                if ($status_display == 'approved') {
                    echo '<span class="status-icon">&#10004;</span>'; // Check mark for approved
                } elseif ($status_display == 'rejected') {
                    echo '<span class="status-icon">&#10006;</span>'; // Cross mark for rejected
                } elseif ($status_display == 'pending') {
                    echo '<span class="status-icon">&#x231B;</span>'; // Hourglass for pending
                } elseif ($status_display == 'expired') {
                    echo '<span class="status-icon">&#128473;</span>'; // Clock for expired
                }
            ?>
            <?= strtoupper($status_display) ?></span></td>
        <td>
          <?php if ($is_expired): ?>
            <div>
              <span style="color: gray; font-weight: bold;">EXPIRED</span><br>
              <small>
                <?= $schedule_value ? date('M d, Y h:i A', strtotime($schedule_value)) : 'No schedule set' ?>
              </small>
            </div>
          <?php else: ?>
            <form method="POST" action="admin_infant.php">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <input type="hidden" name="new_status" value="rejected">
              <input type="datetime-local" name="preferred_schedule" value="<?= $schedule_value ?>" style="margin-bottom: 8px;">
              <div style="display: flex; gap: 6px;">
                <button type="submit" name="action" value="save" class="btn-save">Save</button>
                <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
              </div>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="7" class="no-results">No infant consultation requests found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

</div>
</body>
</html> 
