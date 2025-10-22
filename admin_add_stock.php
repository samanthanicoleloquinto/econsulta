<?php
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $medicine_id = $_POST['medicine_id'];
    $add_quantity = $_POST['add_quantity'];

    $stmt = $conn->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE medicine_id = ?");
    $stmt->bind_param("ii", $add_quantity, $medicine_id);
    if ($stmt->execute()) {
        // log history
        $log = $conn->prepare("INSERT INTO stock_history (medicine_id, change_type, quantity, remarks) VALUES (?, 'IN', ?, 'Stock added by Admin')");
        $log->bind_param("ii", $medicine_id, $add_quantity);
        $log->execute();

        $message = "<div class='alert success'>✅ Stock updated successfully!</div>";
    } else {
        $message = "<div class='alert error'>❌ Failed to update stock.</div>";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_new'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stock_quantity = $_POST['stock_quantity'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO medicines (name, description, stock_quantity, status, category) VALUES (?, ?, ?, 'available', ?)");
    $stmt->bind_param("ssis", $name, $description, $stock_quantity, $category);
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;

        // log history
        $log = $conn->prepare("INSERT INTO stock_history (medicine_id, change_type, quantity, remarks) VALUES (?, 'IN', ?, 'New item added')");
        $log->bind_param("ii", $new_id, $stock_quantity);
        $log->execute();

        $message = "<div class='alert success'>✅ New $category added successfully!</div>";
    } else {
        $message = "<div class='alert error'>❌ Failed to add new item.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Free Medicine Management</title>
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
      margin-top: 20px;
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

    form.inline-form {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    input[type="number"], input[type="text"], textarea, select {
      padding: 6px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    .submit-btn {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 4px;
      cursor: pointer;
    }

    .submit-btn:hover {
      background-color: #219150;
    }

    .add-btn {
      background-color: #f39c12;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      margin-top: 10px;
      cursor: pointer;
      font-weight: bold;
    }

    .alert {
      padding: 10px;
      border-radius: 6px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .alert.success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert.error {
      background-color: #f8d7da;
      color: #721c24;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 10;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 8% auto;
      padding: 20px;
      border-radius: 8px;
      width: 400px;
      box-shadow: 0 0 12px rgba(0,0,0,0.3);
    }

    .close {
      float: right;
      font-size: 20px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: red;
    }

    .popup-form label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
    }

    .popup-form input,
    .popup-form textarea,
    .popup-form select {
      width: 100%;
      margin-bottom: 10px;
    }

    .popup-form .submit-btn {
      width: 100%;
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
    <a href="admin_inventory.php">Medicine Inventory</a>
    <a href="admin_tbdots.php">TB DOTS</a>
    <a href="admin_toothextraction.php">Free Tooth Extraction</a>
    <a href="admin_calendar.php">Schedule Calendar</a>
    <a href="admin_login.php">Logout</a>
  </div>

<div class="main-content">
  <h2>📦 Free Medicine & Vitamin Stock</h2>
  <?= $message ?>

  <div class="tabs">
    <button class="tab-button active" onclick="showTab('medicine')">Medicines</button>
    <button class="tab-button" onclick="showTab('vitamin')">Vitamins</button>
  </div>

  <div id="medicine" class="tab-content active">
    <h3>🧪 Medicines</h3>
    <button class="add-btn" onclick="openModal('medicine')">➕ Add Medicine</button>
    <table>
      <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Stock</th>
        <th>Add Stock</th>
      </tr>
      <?php
      $result = $conn->query("SELECT * FROM medicines WHERE category = 'medicine'");
      while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['name']}</td>
            <td>{$row['description']}</td>
            <td>{$row['stock_quantity']}</td>
            <td>
                <form method='POST' class='inline-form'>
                    <input type='hidden' name='medicine_id' value='{$row['medicine_id']}'>
                    <input type='number' name='add_quantity' min='1' required>
                    <input type='submit' name='update_stock' class='submit-btn' value='➕ Add'>
                </form>
            </td>
        </tr>";
      }
      ?>
    </table>
  </div>

  <div id="vitamin" class="tab-content">
    <h3>💊 Vitamins</h3>
    <button class="add-btn" onclick="openModal('vitamin')">➕ Add Vitamin</button>
    <table>
      <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Stock</th>
        <th>Add Stock</th>
      </tr>
      <?php
      $result = $conn->query("SELECT * FROM medicines WHERE category = 'vitamin'");
      while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['name']}</td>
            <td>{$row['description']}</td>
            <td>{$row['stock_quantity']}</td>
            <td>
                <form method='POST' class='inline-form'>
                    <input type='hidden' name='medicine_id' value='{$row['medicine_id']}'>
                    <input type='number' name='add_quantity' min='1' required>
                    <input type='submit' name='update_stock' class='submit-btn' value='➕ Add'>
                </form>
            </td>
        </tr>";
      }
      ?>
    </table>
  </div>
</div>

<!-- ADD MEDICINE/VITAMIN MODAL -->
<div id="addModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div class="modal-content" style="background:#fff; padding:20px; margin:100px auto; width:400px; border-radius:8px; position:relative;">
    <span class="close" onclick="closeModal()" style="position:absolute; right:10px; top:10px; cursor:pointer; font-size:20px;">&times;</span>
    <h2 id="modal-title">Add New Item</h2>
    <form method="POST">
      <input type="hidden" name="category" id="modal-category">
      <label for="name">Name:</label><br>
      <input type="text" name="name" required><br><br>
      
      <label for="description">Description:</label><br>
      <textarea name="description" required></textarea><br><br>
      
      <label for="stock_quantity">Initial Stock:</label><br>
      <input type="number" name="stock_quantity" min="1" required><br><br>
      
      <button type="submit" name="add_medicine">Save</button>
    </form>
  </div>
</div>

<script>
function showTab(category) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.getElementById(category).classList.add('active');
    event.target.classList.add('active');
}

function openModal(category) {
    document.getElementById("modal-category").value = category;
    document.getElementById("modal-title").innerText = "Add New " + (category === 'vitamin' ? "Vitamin" : "Medicine");
    document.getElementById("addModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addModal").style.display = "none";
}
</script>

</body>
</html>
