<?php
session_start();
include 'index.php'; // DB connection

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
    exit();
}

// Define variables and initialize with empty values
$old_password = $new_password = $confirm_password = "";
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['admin_username'];  // Get the current logged-in admin username

    // Get the old, new and confirm passwords
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the admin details from the database
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    // Check if the old password matches
    if (password_verify($old_password, $admin['password'])) {
        // Check if the new password and confirm password match
        if ($new_password == $confirm_password) {
            // Update the password in the database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $hashed_password, $username);
            if ($update_stmt->execute()) {
                $message = "Password updated successfully!";
            } else {
                $message = "Something went wrong. Please try again!";
            }
            $update_stmt->close();
        } else {
            $message = "New passwords do not match!";
        }
    } else {
        $message = "Old password is incorrect!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Password - Barangay San Miguel</title>
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
      justify-content: center;
      align-items: center;
    }

    .wrapper {
      width: 800px;
      height: 800px;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      display: flex;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      margin-left: 50%; /* Adjust to move the entire container to the right */
    }

    .form-section {
      padding: 40px;
      flex: 1;
      background: #00006CC4;
      color: white;
      position: relative; /* Make sure logo is not overlapping */
    }

    .logo {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 150px;
    }

    .form-section h1 {
      margin-left: 20%;
      font-size: 50px;
      margin-bottom: 30px;
      text-align: center;
    }

    label {
      display: block;
      margin: 50px 0 3px; /* Increased margin-top for the old password field */
      font-size: 20px;
    }

    .password-container {
      position: relative;
      width: 100%;
    }

    input {
      width: 100%;
      padding: 10px 10px 10px 40px;  /* Add padding to the left for the icon */
      border-radius: 8px;
      border: none;
      margin-bottom: 3px;
      font-size: 16px;
    }

    .password-container img {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      width: 20px;
      height: 20px;
    }

    .btn-submit {
      background-color: #ffd700;
      color: black;
      font-weight: bold;
      padding: 12px 24px;
      border: none;
      border-radius: 15px;
      font-size: 18px;
      cursor: pointer;
      width: 50%;
      margin: 20px auto 0;
      display: block;
    }

    .btn-submit:hover {
      background-color: #ffcc00;
    }

    .info-box {
      font-size: 14px;
      margin-top: 10px;
      text-align: left;
      color: #ffdf00;
    }

    .error-message {
      background: #ffe5e5;
      color: #cc0000;
      font-weight: bold;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      text-align: center;
    }

    .success-message {
      background: #e5ffe5;
      color: #007f00;
      font-weight: bold;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      text-align: center;
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
      left: -650px;
      width: 85%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body>

  <div class="wrapper">
    <div class="form-section">
      <img src="logo.png" alt="Barangay Logo" class="logo">
      <h1>UPDATE PASSWORD</h1>

      <?php if (!empty($message)): ?>
        <?php if (strpos($message, 'success') !== false): ?>
          <div class="success-message"><?php echo $message; ?></div>
        <?php else: ?>
          <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>
      <?php endif; ?>

      <form method="POST" action="">

        <!-- Old Password -->
        <label for="old_password">Old Password</label>
        <div class="password-container">
          <input type="password" id="old_password" name="old_password" required>
          <img src="eye.png" id="eye-image-old" onclick="togglePasswordVisibility('old_password', 'eye-image-old')" alt="Toggle visibility">
        </div>

        <!-- New Password -->
        <label for="new_password">New Password</label>
        <div class="password-container">
          <input type="password" id="new_password" name="new_password" required>
          <img src="eye.png" id="eye-image-new" onclick="togglePasswordVisibility('new_password', 'eye-image-new')" alt="Toggle visibility">
        </div>

        <!-- Confirm Password -->
        <label for="confirm_password">Confirm New Password</label>
        <div class="password-container">
          <input type="password" id="confirm_password" name="confirm_password" required>
          <img src="eye.png" id="eye-image-confirm" onclick="togglePasswordVisibility('confirm_password', 'eye-image-confirm')" alt="Toggle visibility">
        </div>

        <div class="info-box">
          <p>YOUR PASSWORD MUST CONTAIN:</p>
          <ul>
            <li>A minimum of 8 characters</li>
            <li>At least one number</li>
            <li>At least 1 special character</li>
            <li>At least one uppercase letter</li>
          </ul>
        </div>

        <button type="submit" class="btn-submit">Update Password</button>
      </form>
    </div>
  </div>

  <div class="image">
    <img src="Avatar.svg" alt="doctors">
  </div>

  <!-- JavaScript to toggle password visibility -->
  <script>
    function togglePasswordVisibility(inputId, iconId) {
      var passwordField = document.getElementById(inputId);
      var eyeImage = document.getElementById(iconId);

      if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeImage.src = "hidden.png";  // icon when password is visible
      } else {
        passwordField.type = "password";
        eyeImage.src = "eye.png";  // icon when password is hidden
      }
    }
  </script>

</body>
</html>
