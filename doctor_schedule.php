<?php
session_start();
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day_of_week'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $action = $_POST['action'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? '';

    if ($action === 'add' && $day && $start && $end) {
        $stmt = $conn->prepare("INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $doctor_id, $day, $start, $end);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete' && $schedule_id) {
        $stmt = $conn->prepare("DELETE FROM doctor_schedule WHERE id=? AND doctor_id=?");
        $stmt->bind_param("ii", $schedule_id, $doctor_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch existing schedules
$schedules = $conn->query("SELECT * FROM doctor_schedule WHERE doctor_id='$doctor_id' ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Doctor Schedule</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6fa; padding: 20px; }
.container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
h2 { text-align: center; color: #003d99; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
button { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; }
.add-btn { background-color: #003d99; color: white; }
.delete-btn { background-color: crimson; color: white; }
</style>
</head>
<body>
<div class="container">
<h2>My Available Schedule</h2>

<form method="POST" style="margin-bottom:20px;">
    <input type="hidden" name="action" value="add">
    <select name="day_of_week" required>
        <option value="">Select Day</option>
        <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
        <option value="<?= $day ?>"><?= $day ?></option>
        <?php endforeach; ?>
    </select>
    <input type="time" name="start_time" required>
    <input type="time" name="end_time" required>
    <button type="submit" class="add-btn">Add Schedule</button>
</form>

<table>
    <tr>
        <th>Day</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Action</th>
    </tr>
    <?php while($row = $schedules->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['day_of_week']) ?></td>
        <td><?= htmlspecialchars($row['start_time']) ?></td>
        <td><?= htmlspecialchars($row['end_time']) ?></td>
        <td>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="schedule_id" value="<?= $row['id'] ?>">
                <button type="submit" class="delete-btn" onclick="return confirm('Delete this schedule?')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>
</body>
</html>
