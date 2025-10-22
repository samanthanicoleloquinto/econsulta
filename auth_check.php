<?php
session_start();
$conn = new mysqli("localhost", "root", "", "econsulta");

if (!isset($_COOKIE['session_token'])) {
    header("Location: login.php");
    exit();
}

$token = $_COOKIE['session_token'];

$stmt = $conn->prepare("
    SELECT users.* FROM users
    JOIN session_tokens ON session_tokens.user_id = users.id
    WHERE session_tokens.token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: login.php");
    exit();
}

// Store into session for convenience (optional)
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['middle_initial'] = $user['middle_initial'];

$stmt->close();
?>
