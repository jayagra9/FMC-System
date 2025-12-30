<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access_denied.php");
    exit();
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'user';

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "Full name, username, email, and password are required.";
    } elseif (!$email) {
        $error = "Please provide a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email address is already registered.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $hashed_password, $role]);
                    $success = "Successfully registered a new " . ($role === 'admin' ? 'admin' : 'user') . "!";
                    $full_name = $username = $email = $password = '';
                }
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
    <title>Register New User - FMC Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: Arial;
            background: #f5f7fa;
        }

        .main {
            margin-left: 250px;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }

        .card h1 {
            margin-top: 0;
            margin-bottom: 30px;
            color: #1a2b47;
            text-align: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input, 
        .form-group select {
            padding: 12px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: #0f1c30;
            box-shadow: 0 0 0 3px rgba(15, 28, 48, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            background-color: #1a2b47;
            color: #FFFFFF;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #0f1c30;
        }

        .error-message {
            color: #c1121f;
            background: #fce4e4;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c1121f;
        }

        .success-message {
            color: #27ae60;
            background: #d5f4e6;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="card">
        <h1><i class="fas fa-user-plus"></i> Register New User</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="register_user.php">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" name="register" class="btn">
                <i class="fas fa-user-plus"></i> Register User
            </button>
        </form>
    </div>
</div>

</body>
</html>
