<?php
// Use a dedicated session namespace for ADMIN so it doesn't clash with doctor/user
session_name('EConsultaAdmin');
session_start();
include 'index.php';

$message = "";
$show_modal = "";
$modal_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // NOTE: Your table stores cleartext passwords; keeping your original check.
        if ($password === $admin['password']) {
            // Primary admin key
            $_SESSION['admin_username'] = $username;

            // Compatibility key for older pages that still check 'email'
            $_SESSION['email'] = $username;

            // Make sure no doctor session bleeds into admin
            unset($_SESSION['doctor_id']);

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $show_modal = "error";
            $modal_message = "Incorrect password!";
        }
    } else {
        $show_modal = "error";
        $modal_message = "Admin not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Login - Barangay San Miguel</title>
<link href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&family=DM+Serif+Display&display=swap" rel="stylesheet" />
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        height: 100vh; font-family: 'DM Serif Display', serif;
        background: linear-gradient(to bottom, #0118D8, #4E71FF, #A8F1FF, #FBFBFB);
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .container { width: 100%; height: 100vh; display: flex; align-items: center;
        justify-content: space-between; padding: 0 80px; }
    .login-box {
        background: rgba(0, 0, 108, 0.85); border-radius: 20px; padding: 60px;
        width: 60%; max-width: 750px; color: white; display: flex; flex-direction: column;
        justify-content: center; position: relative;
    }
    .header { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 30px; }
    .header .logo { position: absolute; left: 40px; top: 40px; width: 100px; height: auto; }
    .header h2 { font-family: 'Alfa Slab One', serif; font-size: 48px; letter-spacing: 8px;
        -webkit-text-stroke: 0.5px black; text-align: center; flex: 1; }
    .login-box p { font-size: 16px; margin-bottom: 30px; text-align: center; }
    .login-box label { font-weight: bold; margin-top: 15px; display: block; letter-spacing: 5px; }
    .login-box input[type="text"], .login-box input[type="password"] {
        width: 100%; padding: 12px; margin-top: 8px; border-radius: 10px; border: none;
        font-size: 16px; margin-bottom: 20px; background-color: #ffffff; color: #000;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .login-box button {
        background-color: #FFD700; color: black; font-weight: bold; padding: 12px; width: 100%;
        border: none; border-radius: 10px; font-size: 18px; cursor: pointer; transition: background 0.3s ease;
        margin-top: 10px;
    }
    .login-box button:hover { background-color: #e6c200; }
    .signup-container { display: flex; justify-content: center; margin-top: 20px; font-size: 14px; }
    .signup-container a { color: #FFD700; font-weight: bold; margin-left: 5px; text-decoration: none; }
    .right-section { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
    .right-section img { position: absolute; right: 0; width: 80%; height: 100%; object-fit: cover; }
    .modal {
        display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background-color: #1e3d73; color: white; padding: 40px; border-radius: 15px; width: 400px;
        text-align: center; font-size: 18px; z-index: 9999;
    }
    .modal.error { background-color: #F44336; }
    .modal button {
        padding: 10px; background-color: #FFD700; border: none; color: black; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px;
    }
    .modal button:hover { background-color: #e6c200; }
</style>
</head>
<body>

<div class="right-section">
    <img src="Avatar.svg" alt="Doctors Image" />
</div>

<div class="container">
    <div class="login-box">
        <div class="header">
            <img src="logo.png" alt="Barangay Logo" class="logo" />
            <h2>ADMIN</h2>
        </div>
        <p>ENTER YOUR ADMIN CREDENTIALS</p>
        <form method="POST" action="">
            <label for="username">USERNAME</label>
            <input type="text" id="username" name="username" required />

            <label for="password">PASSWORD</label>
            <input type="password" id="password" name="password" required />

            <button type="submit">LOGIN</button>

            <div class="signup-container">
                <span>DON'T HAVE AN ACCOUNT?</span>
                <a href="admin_signup.php">SIGN UP</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($show_modal)): ?>
    <div class="modal <?= $show_modal ?>">
        <p><?= $modal_message ?></p>
        <button onclick="closeModal()">OK</button>
    </div>
<?php endif; ?>

<script>
function closeModal(){ document.querySelector('.modal').style.display='none'; }
<?php if (!empty($show_modal)): ?>document.querySelector('.modal').style.display='block';<?php endif; ?>
</script>

</body>
</html>
