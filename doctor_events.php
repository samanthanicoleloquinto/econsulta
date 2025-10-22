<?php
// doctor_events.php — JSON feed for FullCalendar
session_name('EConsultaDoctor');
session_start();
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) {
  echo json_encode([]); exit;
}
$conn->set_charset('utf8mb4');

if (empty($_SESSION['doctor_id'])) { echo json_encode([]); exit; }
$doctor_id = (int)$_SESSION['doctor_id'];

// Optional FullCalendar range (start/end). We’ll ignore and just return next/prev 2 months.
$events = [];

$stmt = $conn->prepare("
  SELECT id, schedule_time, CONCAT(first_name,' ',COALESCE(middle_initial,''),' ',last_name) AS name
  FROM screening_data
  WHERE doctor_id = ?
    AND status='approved'
    AND schedule_time IS NOT NULL
  ORDER BY schedule_time ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
  $sid = (int)$row['id'];
  $startIso = date('c', strtotime($row['schedule_time']));
  $title = $row['name'] ? $row['name'] : 'Consultation';
  $events[] = [
    'id'    => $sid,
    'sid'   => $sid,
    'title' => $title,
    'start' => $startIso,
    'allDay'=> false,
    // Yellow highlight
    'backgroundColor' => '#ffc61a',
    'borderColor'     => '#f1b300',
    'textColor'       => '#1a2b6d'
  ];
}
$stmt->close();

echo json_encode($events);
