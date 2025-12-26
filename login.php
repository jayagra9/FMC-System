<?php
require_once 'config.php';

// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Log login activity
        try {
            $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
            $login_time = $sri_lanka_time->format('Y-m-d H:i:s');
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, timestamp, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'login', $login_time, $ip_address]);
        } catch (Exception $e) {
            // Silent fail - still login even if logging fails
            error_log("Login logging error: " . $e->getMessage());
        }
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admindashboard.php");
            exit();
        } else {
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FMC Fisheries</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Login</h1>
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="login.php" method="POST" class="user-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
</body>
</html>