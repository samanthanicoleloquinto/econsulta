<?php
session_name('EConsultaDoctor');
session_start();
$conn = new mysqli("localhost","root","","econsulta");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM doctors WHERE email=? AND status='active'");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows === 1){
        $doctor = $result->fetch_assoc();
        if(password_verify($password, $doctor['password'])){
            $_SESSION['doctor_id'] = $doctor['id'];
            header("Location: doctor_dashboard.php");
            exit;
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "Email not found or inactive.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Login</title>
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
<h2>Doctor Login</h2>
<?php if($message) echo "<div class='message'>$message</div>"; ?>
<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="doctor_signup.php">Don't have an account? Sign Up</a></p>
</div>

</body>
</html>
