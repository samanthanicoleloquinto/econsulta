<?php
// Start the session
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "econsulta"); // Replace with your DB credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$feedback_sent = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture the form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    // Insert the data into the database
    $stmt = $conn->prepare("INSERT INTO feedback (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    // Execute the query and check if the insert was successful
    if ($stmt->execute()) {
        $feedback_sent = true;
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – Submit Feedback</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&family=Allan&display=swap" rel="stylesheet">
    <style>
        * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(to bottom, #0118D8, #4E71FF, #A8F1FF, #FBFBFB);
      font-family: 'Allan', cursive;
      height: auto;
      overflow-x: hidden;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 50px;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo-container img {
      height: 100px;
      margin-left: -20px;
      margin-top: 10px;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 40px;
    }

    .navbar img {
      width: 40px;
      height: auto;
      border-radius: 50%;
      transition: transform 0.3s ease;
      cursor: pointer;
    }

    .navbar img:hover {
      transform: scale(1.1);
      box-shadow: 0 0 15px rgb(255, 225, 0);
    }

    .nav-links {
      display: flex;
      gap: 40px;
      align-items: center;
    }

    .nav-links a {
      text-decoration: none;
      color: white;
      font-weight: bold;
      font-size: 18px;
      letter-spacing: 1px;
      padding: 8px 14px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .nav-links a:hover {
      transform: scale(1.1);
      text-shadow: 0 0 5px rgb(255, 230, 0);
      color: white;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo-container img {
      height: 70px;
      margin-top: 10px;
    }

    .logo-container h1 {
      font-family: Verdana, Geneva, Tahoma, sans-serif;
      font-size: 36px;
      color: #000;
      -webkit-text-stroke: 1px #fff;
      letter-spacing: 2px;
    }

    .navbar {
      margin-top: 10px;
    }

    .nav-links {
      display: flex;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }

    .nav-links a {
      text-decoration: none;
      color: #fff;
      font-weight: bold;
      font-size: 16px;
      padding: 6px 12px;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .nav-links a:hover {
      transform: scale(1.1);
      text-shadow: 0 0 5px rgb(255, 230, 0);
    }

    .nav-links img {
      width: 35px;
      border-radius: 50%;
      transition: transform 0.3s ease;
      cursor: pointer;
    }

    .nav-links img:hover {
      transform: scale(1.1);
      box-shadow: 0 0 12px rgb(255, 225, 0);
    }

        .contact-form-container {
            width: 100%;
            max-width: 700px;
            margin: 50px auto;
            padding: 50px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }

        .contact-form-container:hover {
            box-shadow: 0 12px 50px rgba(0, 0, 0, 0.2);
        }

        .contact-form-container h2 {
            font-size: 36px;
            color: #003d99;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .contact-form-container input,
        .contact-form-container textarea {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .contact-form-container input:focus,
        .contact-form-container textarea:focus {
            border-color: #003d99;
        }

        .contact-form-container button {
            background-color: #003d99;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .contact-form-container button:hover {
            background-color: #002c6d;
        }

        .thankyou-message {
            text-align: center;
            padding: 50px;
            font-size: 22px;
            color: #333;
            background-color: #c4ccf0ff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            margin: 50px auto;
            width: 80%;
        }

        .thankyou-message h2 {
            color: #003d99;
            font-weight: 600;
        }

        .thankyou-message p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .thankyou-message a {
            color: #eeff00ff;
            font-weight: 600;
            text-decoration: none;
            font-size: 18px;
        }

        .thankyou-message a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .contact-form-container {
                padding: 30px;
            }

            .contact-form-container h2 {
                font-size: 28px;
            }

            .thankyou-message {
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="logo.png" alt="Barangay Logo">
            <h1>SAN MIGUEL</h1>
        </div>
    <div class="navbar">
      <div class="nav-links">
        <a href="homepage.html">HOME</a>
        <a href="about_us.html">ABOUT US</a>
        <a href="contact.php">CONTACT</a>
        <a href="update_password.php"><img src="user-icon.png" alt="User Icon"></a>
      </div>
    </div>
  </header>

    <!-- Contact Form Section -->
    <?php if (!$feedback_sent): ?>
        <div class="contact-form-container">
            <h2>Submit Your Feedback</h2>

            <form method="POST">
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="email" name="email" placeholder="Your Email" required>
                <input type="text" name="subject" placeholder="Subject" required>
                <textarea name="message" placeholder="Your Message" required></textarea>
                <button type="submit">Submit Feedback</button>
                <button onclick="window.history.back()" style="background-color: #ffd500ff; color: #000; margin-top: 10px;">Back</button>
            </form>
        </div>
    <?php else: ?>
        <div class="thankyou-message">
            <h2>Thank You for Your Feedback!</h2>
            <p>We appreciate your time and effort in providing feedback. Our team will review it and respond if necessary.</p>
            <a href="index.php">Return to Homepage</a>
        </div>
    <?php endif; ?>

</body>

</html>
