<?php
/* ───────── DB CONNECTION ───────── */
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "econsulta";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ───────── PATH TO CSV ───────── */
$csvPath = __DIR__ . "/machine_learning.csv";

/* ───────── FETCH LATEST DATA FROM DATABASE ───────── */
$query = $conn->query("
    SELECT year, month, month_num, weather, disease, cases
    FROM machine_learning
    ORDER BY year, month_num, disease
");

$newData = [];
while ($row = $query->fetch_assoc()) {
    $key = $row['year'] . '|' . $row['month_num'] . '|' . $row['disease'];
    $newData[$key] = $row;
}

/* ───────── CSV UPDATE SECTION ───────── */
$attempts = 0;
$maxAttempts = 5;
$handle = false;

while ($attempts < $maxAttempts && $handle === false) {
    $handle = fopen($csvPath, "c+");  // "c+" → open for read/write; create if not exists
    if ($handle === false) {
        $attempts++;
        usleep(200000); // wait 0.2 sec before retry
    }
}

if ($handle === false) {
    die("⚠️ Unable to open machine_learning.csv — another program may be using it. Close Excel or other viewers and reload.");
}

/* ───────── READ EXISTING CSV CONTENT ───────── */
rewind($handle);
$existingRows = [];
if (filesize($csvPath) > 0) {
    $lines = explode("\n", trim(stream_get_contents($handle)));
    $header = str_getcsv(array_shift($lines)); // remove header row
    foreach ($lines as $line) {
        if ($line === "") continue;
        $cols = str_getcsv($line);
        $key = $cols[0] . '|' . $cols[2] . '|' . $cols[4]; // year|month_num|disease
        $existingRows[$key] = [
            'year' => $cols[0],
            'month' => $cols[1],
            'month_num' => $cols[2],
            'weather' => $cols[3],
            'disease' => $cols[4],
            'cases' => (int)$cols[5],
        ];
    }
}

/* ───────── MERGE (UPDATE EXISTING OR ADD NEW) ───────── */
foreach ($newData as $key => $row) {
    if (isset($existingRows[$key])) {
        $existingRows[$key]['cases']   = $row['cases'];   // update cases
        $existingRows[$key]['weather'] = $row['weather']; // sync weather
    } else {
        $existingRows[$key] = $row; // new record
    }
}

/* ───────── REWRITE UPDATED DATA TO CSV ───────── */
ftruncate($handle, 0);  // clear old content
rewind($handle);
fputcsv($handle, ['year','month','month_num','weather','disease','cases']);
foreach ($existingRows as $row) {
    fputcsv($handle, $row);
}
fclose($handle);

/* ───────── DISPLAY DATA TABLE ───────── */
$query = $conn->query("SELECT * FROM machine_learning ORDER BY year DESC, month_num DESC, disease ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Learning Summary</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 20px;
    }
    h2 {
      text-align: center;
      color: #333;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: center;
    }
    th {
      background: #007bff;
      color: white;
    }
    tr:nth-child(even) {
      background: #f2f2f2;
    }
    .info {
      text-align: right;
      font-size: 14px;
      margin: 10px 0;
      color: #555;
    }
  </style>
</head>
<body>
  <h2>Machine Learning Summary</h2>
  <div class="info">
    ✅ CSV updated successfully (merged without duplicates): <strong><?= date("Y-m-d H:i:s") ?></strong><br>
    📁 File path: <code><?= htmlspecialchars($csvPath) ?></code>
  </div>

  <table>
    <tr>
      <th>Year</th>
      <th>Month</th>
      <th>Weather</th>
      <th>Disease</th>
      <th>Cases</th>
    </tr>
    <?php while($row = $query->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['year']) ?></td>
      <td><?= htmlspecialchars($row['month']) ?></td>
      <td><?= htmlspecialchars($row['weather']) ?></td>
      <td><?= htmlspecialchars($row['disease']) ?></td>
      <td><?= htmlspecialchars($row['cases']) ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
