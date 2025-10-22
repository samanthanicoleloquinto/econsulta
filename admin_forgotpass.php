<?php
include 'connection_db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];

  $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    header("Location: reset_password.php?email=" . urlencode($email));
    exit();
  } else {
    $message = "No admin found with that email address.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Barangay San Miguel</title>
  <link href="https://fonts.googleapis.com/css2?family=Moul&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Moul', serif;
      background: linear-gradient(to bottom, #0118D8 30%, #4E71FF, #ABF1FF, #FBFBFB);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      padding-left: 100px;
    }

    .wrapper {
      position: relative;
      width: fit-content;
    }

    .forgotpass-container {
      display: flex;
      width: 888px;
      height: 700px;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      justify-content: left;
      z-index: 1;
    }

    .forgotpass-form {
      background: #00006CC4;
      padding: 40px;
      color: white;
      flex: 1;
      text-align: center;
    }

    .forgotpass-form img.logo {
      margin: 20px;
      width: 150px;
      display: flex;
    }

    .forgotpass-form h1 {
      font-size: 80px;
      font-weight: bold;
      text-align: center;
      margin-top: -160px;
      margin-bottom: 100px;
    }

    .forgotpass-form label {
      display: block;
      margin-top: 20px;
      margin-bottom: 5px;
      font-weight: bold;
      font-size: 20px;
    }

    .forgotpass-form input {
      width: 80%;
      margin: 0 auto 10px auto;
      display: block;
      padding: 10px;
      border-radius: 8px;
      border: none;
      font-size: 14px;
    }

    .btn-forgotpass {
      display: block;
      margin: 30px auto 10px auto;
      padding: 12px 24px;
      width: 40%;
      background-color: #ffd700;
      border: none;
      border-radius: 15px;
      font-weight: bold;
      font-size: 20px;
      cursor: pointer;
      transition: 0.3s ease;
      text-align: center;
    }

    .btn-forgotpass:hover {
      background-color: #ffcc00;
    }

    .back-forgotpass {
      margin-top: 20px;
      font-size: 15px;
    }

    .back-forgotpass a {
      text-decoration: underline;
      color: white;
      font-weight: bold;
    }

    .error-message {
      color: #cc0000;
      padding: 5px 5px;
      margin: 10px auto 0 auto;
      border-radius: 5px;
      font-weight: bold;
      text-align: center;
      width: 80%;
      background-color: #ffe5e5;
    }

    .image {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
    }

    .image img {
      position: absolute;
      right: 0;
      width: 80%;
      height: 100%;
      object-fit: over;
    }

    @media (max-width: 768px) {
      .forgotpass-container {
        flex-direction: column;
        width: 95%;
        height: auto;
      }

      .forgotpass-form {
        padding: 30px;
      }

      .image {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="forgotpass-container">
      <div class="forgotpass-form">
        <img src="logo.png" alt="Barangay Logo" class="logo">
        <h1>FORGOT PASSWORD</h1>

        <?php if (!empty($message)): ?>
          <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <label for="email">ENTER YOUR EMAIL/PHONE NUMBER</label>
          <input type="email" name="email" id="email" required>

          <button type="submit" class="btn-forgotpass">SUBMIT</button>

          <p class="back-forgotpass">
            <a href="login.php">GO BACK TO THE LOGIN PAGE</a>
          </p>
        </form>
      </div>
    </div>

    <div class="image">
      <img src="Avatar.svg" alt="doctors">
    </div>
  </div>
</body>
</html>
