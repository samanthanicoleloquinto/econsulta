<?php
// ADMIN-ONLY: dedicated session namespace
session_name('EConsultaAdmin');
session_start();

// Guard (accept new key OR legacy 'email')
if (empty($_SESSION['admin_username']) && empty($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset('utf8mb4');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resident Schedule Calendar</title>
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
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
      margin-left: 200px;
      margin-top: 50px;;
      padding: 40px;
      font-size: 22px;
    }

  #calendar {
    max-width: 1250px;
    margin: 0 auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* --- Toolbar Styling --- */
  .fc .fc-toolbar-title {
    font-size: 20px;
    font-weight: 600;
    color: #003d99;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  .fc-button {
    background-color: #003d99 !important;
    border: none !important;
    color: white !important;
    font-size: 13px;
    padding: 6px 12px !important;
    border-radius: 6px !important;
    margin: 8px !important;
    text-transform: uppercase !important;
    font-weight: bold;
    transition: none !important;
  }

  .fc-button:hover {
    background-color: #003d99 !important;
    filter: none !important;
    cursor: default !important;
  }

  /* --- Grid View Events --- */
  .fc-event {
    background-color: #ffc107 !important;
    color: #000 !important;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    font-size: 8.5px;
    padding: 4px;
    text-align: center;
    box-shadow: none !important;
    transition: none !important;
  }

  .fc-event:hover {
    background-color: #ffc107 !important;
    box-shadow: none !important;
    filter: none !important;
    cursor: default !important;
  }

  /* --- List View Events --- */
  .fc-list-event {
    background-color: #fff3cd !important;
    color: #000 !important;
    font-weight: bold;
    border-radius: 6px;
    padding: 10px;
    margin: 6px 0;
    border: none !important;
    box-shadow: none !important;
    transition: none !important;
    animation: none !important;
    cursor: default !important;
  }

  .fc-list-event:hover,
  .fc-list-event:focus,
  .fc-list-event:active {
    background-color: #fff3cd !important;
    box-shadow: none !important;
    transform: none !important;
    outline: none !important;
    animation: none !important;
    transition: none !important;
    cursor: default !important;
  }

  /* Disable all effects inside list rows */
  .fc-list-event *,
  .fc-list-event td,
  .fc-list-event th {
    animation: none !important;
    transition: none !important;
    box-shadow: none !important;
    background: none !important;
    cursor: default !important;
  }

  /* --- List View Day Labels and Text --- */
  .fc-list-day,
  .fc-list-day-cushion,
  .fc-list-day-text,
  .fc-list-day-side-text,
  .fc-list-event-time,
  .fc-list-event-title {
    color: #000 !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    margin: 2px 0;
    transition: none !important;
    animation: none !important;
  }

  .fc-list-day {
    margin: 12px 0;
    padding: 10px 0;
    background: transparent !important;
    border: none !important;
  }

  /* --- Global Typography in Table Cells --- */
  .fc td,
  .fc th {
    font-size: 13px;
    color: #000;
    font-weight: 500;
    transition: none !important;
    animation: none !important;
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
    <div id="calendar"></div>
  </div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    events: [
      <?php
      $calendarEvents = $conn->query("SELECT first_name, middle_initial, last_name, preferred_schedule FROM screening_data WHERE preferred_schedule IS NOT NULL AND status = 'approved'");
      while ($row = $calendarEvents->fetch_assoc()):
        $name = htmlspecialchars($row['first_name'] . ' ' . $row['middle_initial'] . '. ' . $row['last_name'], ENT_QUOTES);
        $schedule = $row['preferred_schedule'];
        echo "{ title: '{$name}', start: '{$schedule}' },";
      endwhile;
      ?>
    ],
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,listWeek'
    },
    eventColor: '#0066cc',
    eventTextColor: '#000000'
  });
  calendar.render();
});
</script>

</body>
</html>
