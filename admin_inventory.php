<?php
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Inventory Management</title>
  <style>
    body { font-family: Arial; margin: 20px; background: #f4f6fa; }
    h2 { color: #0047b3; }
    .tabs { margin-bottom: 20px; }
    .tab-button {
      background-color: #0047b3;
      border: none;
      padding: 10px 20px;
      color: white;
      cursor: pointer;
      border-radius: 6px;
      margin-right: 10px;
    }
    .tab-button.active { background-color: #2a80b9; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    th { background: #0047b3; color: white; }
    .low { background: #f8d7da; color: #721c24; font-weight: bold; }
  </style>
</head>
<body>
  <h2>📦 Inventory Management</h2>

  <div class="tabs">
    <button class="tab-button active" onclick="showTab('overview')">Inventory Overview</button>
    <button class="tab-button" onclick="showTab('history')">Stock History</button>
  </div>

  <!-- Inventory Overview -->
  <div id="overview" class="tab-content active">
    <h3>📋 Current Stock</h3>
    <table>
      <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Description</th>
        <th>Stock</th>
        <th>Status</th>
      </tr>
      <?php
      $res = $conn->query("SELECT * FROM medicines");
      while ($row = $res->fetch_assoc()) {
          $status = ($row['stock_quantity'] <= 10) ? "<span class='low'>LOW STOCK</span>" : "OK";
          echo "<tr>
                  <td>{$row['name']}</td>
                  <td>{$row['category']}</td>
                  <td>{$row['description']}</td>
                  <td>{$row['stock_quantity']}</td>
                  <td>$status</td>
                </tr>";
      }
      ?>
    </table>
  </div>

  <!-- Stock History -->
  <div id="history" class="tab-content">
    <h3>📜 Stock History</h3>
    <table>
      <tr>
        <th>ID</th>
        <th>Medicine ID</th>
        <th>Action</th>
        <th>Quantity</th>
        <th>Admin</th>
        <th>Date</th>
      </tr>
      <?php
      $res = $conn->query("SELECT * FROM stock_history ORDER BY created_at DESC");
      while ($row = $res->fetch_assoc()) {
          echo "<tr>
                  <td>{$row['id']}</td>
                  <td>{$row['medicine_id']}</td>
                  <td>{$row['action']}</td>
                  <td>{$row['quantity']}</td>
                  <td>{$row['admin_name']}</td>
                  <td>{$row['created_at']}</td>
                </tr>";
      }
      ?>
    </table>
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
