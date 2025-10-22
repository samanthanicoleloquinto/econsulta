<?php
session_start();
$conn = new mysqli("localhost","root","","econsulta");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $specialization = trim($_POST['specialization']);

    if($password !== $confirm_password){
        $message = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO doctors (first_name, last_name, email, password, specialization) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss",$first_name,$last_name,$email,$hashed,$specialization);
        if($stmt->execute()){
            $message = "Sign up successful! <a href='doctor_login.php'>Login here</a>.";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Sign Up</title>
<style>
body { font-family: Arial; background: #f4f6fa; display:flex; justify-content:center; align-items:center; height:100vh; }
.form-container { background:white; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width: 350px; }
h2 { text-align:center; color:#003d99; }
input { width:100%; padding:10px; margin:8px 0; border-radius:6px; border:1px solid #ccc; }
button { width:100%; padding:10px; background:#003d99; color:white; border:none; border-radius:6px; cursor:pointer; }
button:hover { background:#002c6d; }
.message { color:red; text-align:center; margin-bottom:10px; }
</style>
</head>
<body>

<div class="form-container">
<h2>Doctor Sign Up</h2>
<?php if($message) echo "<div class='message'>$message</div>"; ?>
<form method="POST">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="text" name="specialization" placeholder="Specialization">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Sign Up</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="doctor_login.php">Already have an account? Login</a></p>
</div>

</body>
</html>
