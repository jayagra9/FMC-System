<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_STRING);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    // Fetch current password from database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "User not found.";
    } elseif (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        $success = "Password changed successfully!";
        $redirectPage = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'admin_profile.php' : 'profile.php';
        echo '<script>setTimeout(function() { window.location.href = "' . htmlspecialchars($redirectPage) . '"; }, 2000);</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - FMC Fisheries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        /* Sidebar styles are provided by sidebar.php include if admin */

        .main {
            margin-left: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .main.with-sidebar {
            margin-left: 250px;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            max-width: 450px;
            width: 100%;
        }

        .password-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-header {
            background: linear-gradient(135deg, #1a2b47 0%, #0f1c30 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .password-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .password-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .password-body {
            padding: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #2DD4BF;
            width: 18px;
            text-align: center;
        }

        .form-group input {
            padding: 12px;
            border: 2px solid #D1D5DB;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2DD4BF;
            box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.1);
        }

        .password-strength {
            margin-top: 10px;
            font-size: 12px;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: all 0.3s ease;
        }

        .strength-fill.weak {
            width: 33%;
            background: #e74c3c;
        }

        .strength-fill.medium {
            width: 66%;
            background: #f39c12;
        }

        .strength-fill.strong {
            width: 100%;
            background: #27ae60;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2DD4BF 0%, #0D9488 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(45, 212, 191, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 212, 191, 0.4);
        }

        .error {
            color: #c1121f;
            background: #fce4e4;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #c1121f;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success {
            color: #27ae60;
            background: #d5f4e6;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #27ae60;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 6px;
            border-left: 3px solid #2DD4BF;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #2DD4BF;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #0D9488;
        }

        @media (max-width: 600px) {
            .password-header {
                padding: 30px 20px;
            }

            .password-body {
                padding: 20px;
            }

            .password-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php 
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($isAdmin) {
            include 'sidebar.php';
        } else {
            require_once 'nav.php';
        }
    ?>
    <div class="main <?php echo $isAdmin ? 'with-sidebar' : ''; ?>">
        <div class="container">
        <div class="password-card">
            <div class="password-header">
                <h1><i class="fas fa-lock"></i> Change Password</h1>
                <p>Update your account password</p>
            </div>

            <div class="password-body">
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="change_password.php">
                    <div class="form-group">
                        <label for="current_password">
                            <i class="fas fa-lock"></i> Current Password
                        </label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-shield-alt"></i> New Password
                        </label>
                        <input type="password" name="new_password" id="new_password" required onkeyup="checkPasswordStrength(this.value)">
                        <div class="password-strength">
                            <span id="strength-text">Password strength: </span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strength-fill"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-check-circle"></i> Confirm Password
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>

                    <div class="info-text">
                        <i class="fas fa-info-circle"></i> Password must be at least 6 characters long and include uppercase, lowercase, numbers, and special characters for strong security.
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>

                <div class="back-link">
                    <a href="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'admin_profile.php' : 'profile.php'; ?>"><i class="fas fa-arrow-left"></i> Back to Profile</a>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthFill.className = 'strength-fill';
            if (strength < 2) {
                strengthFill.classList.add('weak');
                strengthText.textContent = 'Password strength: Weak';
            } else if (strength < 4) {
                strengthFill.classList.add('medium');
                strengthText.textContent = 'Password strength: Medium';
            } else {
                strengthFill.classList.add('strong');
                strengthText.textContent = 'Password strength: Strong';
            }
        }
    </script>
</body>
</html>