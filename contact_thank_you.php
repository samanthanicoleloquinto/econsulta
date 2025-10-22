<?php
session_start();
unset(
    $_SESSION['personal_info'], 
    $_SESSION['guardian_info'], 
    $_SESSION['personal_medical'], 
    $_SESSION['screening_surgical'], 
    $_SESSION['medications'], 
    $_SESSION['allergies_meds'], 
    $_SESSION['allergies_foods'], 
    $_SESSION['symptoms'], 
    $_SESSION['checkup_info'], 
    $_SESSION['med_doses'], 
    $_SESSION['med_frequencies']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You – Infant Consultation</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to bottom, #c0eaff, #ffffff);
    }

    .thankyou-box {
      background-color: #ffffff;
      padding: 50px 40px;
      border-radius: 14px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 800px;
      width: 100%;
    }

    .thankyou-box h1 {
      color: #070058ff;
      font-weight: normal;
      font-size: 60px;
      margin-bottom: 30px;
      margin-top: 0;
      text-transform: uppercase;
      font-family: Impact, Haettenschweiler, 'Arial Black', sans-serif;
      letter-spacing: 8px;
    }

    .thankyou-box p {
      font-size: 16px;
      color: #444;
      margin-bottom: 50px;
    }

    .btn-home {
      background-color: #001A70;
      color: #ffffff;
      padding: 12px 26px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
      text-transform: uppercase;
    }

    .btn-home:hover {
      background-color: #003199;
    }
  </style>
</head>
<body>

<div class="thankyou-box">
  <h1>Thank You!</h1>
  <p>We appreciate your time and effort in providing feedback. Our team will review it and respond if necessary.</p>
  <a href="consultation.html" class="btn-home">Return to Consultation Form</a>
</div>

</body>
</html>
