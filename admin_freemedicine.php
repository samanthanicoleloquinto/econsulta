<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultation Records</title>
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
      top: 0;
      left: 0;
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

    h2 {
      margin-bottom: 15px;
      color: #002c6d;
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
    <h2>Free Medicine Request - Patient Information</h2>
    <table>
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Age</th>
          <th>Gender</th>
          <th>Address</th>
          <th>Contact No.</th>
          <th>Illness</th>
          <th>Prescribed Medicine</th>
          <th>Medicine Given</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Juan Dela Cruz</td>
          <td>40</td>
          <td>Male</td>
          <td>Brgy. San Miguel</td>
          <td>09123456789</td>
          <td>Hypertension</td>
          <td>Amlodipine 5mg</td>
          <td>Yes</td>
        </tr>
        <tr>
          <td>Maria Santos</td>
          <td>28</td>
          <td>Female</td>
          <td>Brgy. San Roque</td>
          <td>09981234567</td>
          <td>Cough & Cold</td>
          <td>Ambroxol</td>
          <td>No</td>
        </tr>
      </tbody>
    </table>
  </div>

</body>
</html>
