<?php
require_once 'config.php';

$setup_complete = false;
if (file_exists('setup_complete.flag')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        try {
            // Check if table is empty or no admin exists
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $admin_count = $stmt->fetchColumn();
            if ($admin_count > 0) {
                $error = "An admin already exists. Use login.php.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashed_password, 'admin']);
                file_put_contents('setup_complete.flag', '1'); // Mark setup as complete
                echo "<script>alert('First admin registered successfully! Please log in.'); window.location.href = 'login.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 500px;
            margin: 20px auto;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input {
            padding: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            background-color: #2DD4BF;
            color: #FFFFFF;
        }
        .btn:hover {
            background-color: #0D9488;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Initial Setup</h1>
            <p>Register the first admin user to set up the system.</p>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="initial_setup.php" class="form-grid">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" name="setup" class="btn"><i class="fas fa-user-shield"></i> Create First Admin</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>