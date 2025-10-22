<?php
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Stock History</title>
  <style>
    body { font-family: Arial; margin: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    th { background: #0047b3; color: white; }
    .in { color: green; font-weight: bold; }
    .out { color: red; font-weight: bold; }
  </style>
</head>
<body>
  <h2>📜 Stock Movement History</h2>
  <table>
    <tr>
      <th>Date</th>
      <th>Medicine</th>
      <th>Type</th>
      <th>Quantity</th>
      <th>Remarks</th>
    </tr>
    <?php
    $res = $conn->query("SELECT s.*, m.name FROM stock_history s 
                         JOIN medicines m ON s.medicine_id = m.medicine_id 
                         ORDER BY s.created_at DESC");
    while ($row = $res->fetch_assoc()) {
        $typeClass = ($row['change_type'] == 'IN') ? "in" : "out";
        echo "<tr>
                <td>{$row['created_at']}</td>
                <td>{$row['name']}</td>
                <td class='$typeClass'>{$row['change_type']}</td>
                <td>{$row['quantity']}</td>
                <td>{$row['remarks']}</td>
              </tr>";
    }
    ?>
  </table>
</body>
</html>
