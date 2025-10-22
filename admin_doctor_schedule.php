<?php
session_start();
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle doctor assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $selected_date = $_POST['preferred_schedule'] ?? '';
    $doctor_id = (int) ($_POST['doctor_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'assign' && $user_id && $doctor_id && $selected_date) {
        $day_of_week = date('l', strtotime($selected_date)); // Convert date to day of week

        // Optional: Check if doctor is available on that day
        $check = $conn->prepare("SELECT * FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?");
        $check->bind_param("is", $doctor_id, $day_of_week);
        $check->execute();
        $res = $check->get_result();
        if ($res && $res->num_rows > 0) {
            // Assign doctor and update status
            $stmt = $conn->prepare("UPDATE screening_data SET doctor_id = ?, status = 'approved', preferred_schedule = ? WHERE user_id = ?");
            $stmt->bind_param("isi", $doctor_id, $selected_date, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Doctor assigned successfully.";
        } else {
            $message = "Doctor is not available on this day.";
        }
        $check->close();
    }
}

// Fetch patients awaiting assignment
$sql = "SELECT * FROM screening_data ORDER BY created_at DESC";
$patients = $conn->query($sql);

// Fetch doctors and schedules
$doctors = $conn->query("SELECT id, first_name, last_name FROM doctors WHERE status='active'");

// Fetch schedules for display
$schedules = $conn->query("SELECT ds.*, d.first_name, d.last_name FROM doctor_schedule ds JOIN doctors d ON ds.doctor_id = d.id ORDER BY FIELD(ds.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ds.start_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Doctor Schedule</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6fa; padding: 20px; }
h2 { color: #003d99; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
th { background: #003d99; color: #fff; }
form select, form input { padding: 5px; }
button { padding: 6px 10px; background: #003d99; color: #fff; border: none; cursor: pointer; }
button:hover { background: #002c6d; }
.message { margin: 15px 0; color: green; font-weight: bold; }
</style>
</head>
<body>
<h2>Admin - Doctor Schedule Assignment</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<table>
<thead>
<tr>
    <th>Patient Name</th>
    <th>Preferred Schedule</th>
    <th>Status</th>
    <th>Assign Doctor</th>
</tr>
</thead>
<tbody>
<?php while ($p = $patients->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['middle_initial'] . '. ' . $p['last_name']) ?></td>
    <td>
        <form method="POST">
            <input type="date" name="preferred_schedule" value="<?= htmlspecialchars($p['preferred_schedule'] ?: '') ?>" required>
    </td>
    <td><?= htmlspecialchars(strtoupper($p['status'] ?: 'Pending')) ?></td>
    <td>
            <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
            <select name="doctor_id" required>
                <option value="">Select Doctor</option>
                <?php
                $preferred_date = $p['preferred_schedule'] ?: date('Y-m-d');
                $day_of_week = date('l', strtotime($preferred_date));
                $available_doctors = $conn->query("
                    SELECT ds.*, d.first_name, d.last_name
                    FROM doctor_schedule ds
                    JOIN doctors d ON ds.doctor_id = d.id
                    WHERE ds.day_of_week = '$day_of_week'
                ");
                while ($d = $available_doctors->fetch_assoc()):
                ?>
                    <option value="<?= $d['doctor_id'] ?>"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name'] . " ({$d['start_time']} - {$d['end_time']})") ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="action" value="assign">Assign</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<h2>All Doctor Schedules</h2>
<table>
<thead>
<tr>
    <th>Doctor Name</th>
    <th>Day</th>
    <th>Start Time</th>
    <th>End Time</th>
</tr>
</thead>
<tbody>
<?php while ($s = $schedules->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
    <td><?= htmlspecialchars($s['day_of_week']) ?></td>
    <td><?= htmlspecialchars($s['start_time']) ?></td>
    <td><?= htmlspecialchars($s['end_time']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
