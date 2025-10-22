<?php
include 'index.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Validate input
  if (empty($username) || empty($email) || empty($password)) {
    $message = "All fields are required!";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Invalid email format!";
  } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
    $message = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
  } else {
    // Check if username or email exists
    $check = $conn->prepare("SELECT * FROM admin WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
      $message = "Username or Email already exists!";
    } else {
      // Store password as plain text (not recommended)
      $stmt = $conn->prepare("INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $username, $email, $password);
      if ($stmt->execute()) {
        header("Location: admin_login.php");
        exit();
      } else {
        $message = "Error during signup. Try again.";
      }
    }
  }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - Barangay San Miguel</title>
  <link href="https://fonts.googleapis.com/css2?family=Moul&display=swap" rel="stylesheet">
</head>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; overflow: hidden; }

  body {
    height: 150vh;
    font-family: 'DM Serif Display', serif;
    background: linear-gradient(to bottom, #0118D8, #4E71FF, #A8F1FF, #FBFBFB);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .wrapper {
    width: 100%;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-top: -300px;
  }

  .signup-container {
    display: flex;
    width: 700px;
    height: 600px;
    background: transparent;
    border-radius: 10px;
    overflow: hidden;
    z-index: 1;
    left: 750px;
    position: relative;
  }

  .signup-form {
    background: rgba(0, 0, 108, 0.85);
    padding: 40px;
    color: white;
    flex: 1;
    position: relative;
    text-align: center;
  }

  .signup-form img.logo {
    position: absolute;
    left: 40px;
    top: 20px;
    width: 100px;
    height: auto;
  }

  .signup-form h1 {
    font-family: 'Alfa Slab One', serif;
    font-size: 48px;
    letter-spacing: 8px;
    -webkit-text-stroke: 0.5px black;
    text-align: center;
    margin-bottom: 50px;
  }

  .signup-form label {
    display: block;
    margin-top: 18px;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 18px;
    letter-spacing: 2px;
    text-align: start;
    margin-left: 20px
  }

  .signup-form input {
    width: 95%;
    margin: 0 auto 30px auto;
    display: block;
    padding: 5px;
    border-radius: 8px;
    border: none;
    font-size: 16px;
  }

  .btn-signup {
    display: block;
    margin: 30px auto 0 auto;
    padding: 10px 20px;
    width: 20%;
    background-color: #ffd700;
    border: none;
    border-radius: 15px;
    font-weight: bold;
    font-size: 20px;
    cursor: pointer;
    transition: 0.3s ease;
    text-align: center;
  }

  .btn-signup:hover {
    background-color: #ffcc00;
  }

  .signup {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    font-size: 14px;
  }

  .signup a {
    color: #FFD700;
    font-weight: bold;
    margin-left: 5px;
    text-decoration: none;
  }

  .error-message {
    background-color: #ffcccc;
    color: #660000;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 8px;
  }

  .left-section {
    position: fixed;
    top: 0;
    left: -500px;
    width: 100%;
    height: 100%;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
  }

  .left-section img {
    position: absolute;
    left: 0;
    width: 80%;
    height: 100%;
    object-fit: cover;
  }

  @media (max-width: 768px) {
    .signup-container {
      flex-direction: column;
      width: 95%;
      height: auto;
    }

    .signup-form {
      padding: 30px;
    }

    .image {
      display: none;
    }
  }
</style>

<body>
  <div class="left-section">
    <img src="Avatar.svg" alt="Doctors Image" />
  </div>

  <div class="wrapper">
    <div class="signup-container">
      <div class="signup-form">
        <img src="logo.png" alt="Barangay Logo" class="logo">
        <h1>SIGN UP</h1>

        <?php if (!empty($message)): ?>
          <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <label for="username">USERNAME</label>
          <input type="text" id="username" name="username" required>

          <label for="email">EMAIL</label>
          <input type="email" id="email" name="email" required>

          <label for="password">PASSWORD</label>
          <input type="password" id="password" name="password" required>

          <button type="submit" class="btn-signup">SIGN UP</button>
        </form>

        <div class="signup">
          ALREADY HAVE AN ACCOUNT? <a href="admin_login.php">LOG IN</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
