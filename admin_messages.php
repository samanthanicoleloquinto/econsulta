<?php
session_start();
$conn = new mysqli("localhost", "root", "", "econsulta");

$admin_id = 1; // admin account ID
$selected_user = $_GET['user_id'] ?? null;

// Fetch all users who messaged admin
$users = $conn->query("SELECT DISTINCT from_user_id 
                       FROM messages WHERE to_user_id = $admin_id 
                       UNION 
                       SELECT DISTINCT to_user_id 
                       FROM messages WHERE from_user_id = $admin_id");

// Fetch messages for selected user
$messages = [];
if ($selected_user) {
    $messages = $conn->query("SELECT * FROM messages 
                              WHERE (from_user_id = $selected_user AND to_user_id = $admin_id) 
                                 OR (from_user_id = $admin_id AND to_user_id = $selected_user) 
                              ORDER BY created_at ASC");
}

// Handle new message
if ($_SERVER["REQUEST_METHOD"] === "POST" && $selected_user) {
    $msg = $conn->real_escape_string($_POST['message']);
    $conn->query("INSERT INTO messages (from_user_id, to_user_id, message) 
                  VALUES ($admin_id, $selected_user, '$msg')");
    header("Location: admin_messages.php?user_id=$selected_user");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Messages</title>
  <style>
    body { font-family: Arial; display: flex; }
    .sidebar { width: 200px; border-right: 1px solid #ccc; padding: 10px; }
    .chat-box { flex: 1; padding: 10px; border-left: 1px solid #ccc; height: 500px; overflow-y: auto; }
    .msg { margin: 5px 0; }
    .from-user { color: blue; }
    .from-admin { color: green; text-align: right; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h3>Users</h3>
    <ul>
      <?php while ($u = $users->fetch_assoc()): 
          $uid = $u['from_user_id'] ?? $u['to_user_id'];
      ?>
        <li><a href="?user_id=<?php echo $uid; ?>">User <?php echo $uid; ?></a></li>
      <?php endwhile; ?>
    </ul>
  </div>
  
  <div class="chat-box">
    <?php if ($selected_user && $messages): ?>
      <h3>Conversation with User <?php echo $selected_user; ?></h3>
      <?php while($m = $messages->fetch_assoc()): ?>
        <div class="msg <?php echo $m['from_user_id'] == $admin_id ? 'from-admin' : 'from-user'; ?>">
          <b><?php echo $m['from_user_id'] == $admin_id ? 'Admin' : 'User'; ?>:</b>
          <?php echo htmlspecialchars($m['message']); ?>
        </div>
      <?php endwhile; ?>
      <form method="POST">
        <input type="text" name="message" required>
        <button type="submit">Send</button>
      </form>
    <?php else: ?>
      <p>Select a user to start chatting.</p>
    <?php endif; ?>
  </div>
</body>
</html>
