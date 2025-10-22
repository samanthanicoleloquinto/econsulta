<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Validate POST
if (!isset($_POST['consult_id'])) {
    die("Invalid request.");
}

$consult_id = (int) $_POST['consult_id'];
$status = ($_POST['action'] === "complete") ? "completed" : "in_progress";

// Fetch existing consultation status (to prevent overwriting completed records)
$check = $conn->prepare("SELECT consultation_status FROM screening_data WHERE id=?");
$check->bind_param("i", $consult_id);
$check->execute();
$check_res = $check->get_result();
if (!$check_res || $check_res->num_rows === 0) die("Consultation not found.");

$current = $check_res->fetch_assoc();
if ($current['consultation_status'] === "completed") {
    die("This consultation is already completed and cannot be edited.");
}

// Sanitize fields
$height = !empty($_POST['height']) ? (float) $_POST['height'] : null;
$weight = !empty($_POST['weight']) ? (float) $_POST['weight'] : null;
$bmi = !empty($_POST['bmi']) ? (float) $_POST['bmi'] : null;
$temp = !empty($_POST['temperature']) ? trim($_POST['temperature']) : null;
$bp = !empty($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : null;
$findings = !empty($_POST['findings']) ? trim($_POST['findings']) : null;
$prescription = !empty($_POST['prescription']) ? trim($_POST['prescription']) : null;

// Update record
$sql = "UPDATE screening_data 
        SET height=?, weight=?, bmi=?, temperature=?, 
            blood_pressure=?, findings=?, prescription=?, consultation_status=?
        WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "dddsdsssi",
    $height, $weight, $bmi, $temp, $bp,
    $findings, $prescription, $status, $consult_id
);

if ($stmt->execute()) {
    header("Location: doctor_dashboard.php?success=1");
    exit;
} else {
    die("Error saving consultation: " . $conn->error);
}
?>