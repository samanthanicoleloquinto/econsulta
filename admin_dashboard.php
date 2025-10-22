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
  <title>Admin Dashboard - Barangay San Miguel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
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

    .topbar {
      margin-left: 200px;
      padding: 20px 30px;
      background-color: #ffffff;
      border-bottom: 1px solid #dce0e7;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topbar h1 {
      font-size: 24px;
      color: #0033a0;
      font-weight: bolder;
      text-transform: uppercase;
    }

    .topbar .user-icon {
      font-size: 15px;
      background-color: #e6ecff;
      padding: 8px 18px;
      border-radius: 30px;
      color: #0033a0;
      font-weight: 500;
    }

    .main-content {
      margin-left: 200px;
      padding: 40px 40px;
    }

    .main-content h2 {
      font-size: 22px;
      margin-bottom: 25px;
      color: #1a1a1a;
      font-weight: bold;
    }

    .service-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    .card {
      background-color: #0047b3;
      color: white;
      padding: 60px 20px;
      border-radius: 14px;
      text-align: center;
      font-weight: 600;
      font-size: 20px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      cursor: pointer;
      position: relative;
    }

    .card:hover {
      background-color: #003399;
      transform: translateY(-6px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .card a {
      text-decoration: none;
      color: white;
      display: block;
      height: 100%;
      width: 100%;
    }

    @media (max-width: 992px) {
      .service-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }

      .main-content {
        padding: 30px 20px;
      }

      .service-grid {
        grid-template-columns: 1fr;
      }
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


  <div class="topbar">
    <h1>Barangay Pineda Admin</h1>
    <div class="user-icon">👤 Admin</div>
  </div>

  <div class="main-content">
    <h2>Health Services</h2>
    <div class="service-grid">
      <div class="card"><a href="#">CONSULTATION</a></div>
      <div class="card"><a href="#">FAMILY PLANNING</a></div>
      <div class="card"><a href="#">FREE MEDICINE</a></div>
      <div class="card"><a href="#">TB DOTS</a></div>
      <div class="card"><a href="#">FREE TOOTH EXTRACTION</a></div>
    </div>
  </div>
</body>
</html>
