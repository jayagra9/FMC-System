<!-- nav.php -->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$display_name = $user['full_name'] ?? $_SESSION['username'] ?? 'User';
$avatar_letter = strtoupper(substr($display_name, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f8fafc; }

        .navbar {
            height: 70px;
            background: linear-gradient(135deg, #0f1c30 0%, #1a2b47 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            color: #2dd4bf;
            font-size: 24px;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-center {
             display: flex;
            gap: 25px;
            align-items: center;
            list-style: none;
            flex: 1;
            justify-content: center;
        }

        .navbar-center a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar-center a:hover {
            color: #2dd4bf;
            transform: translateY(-2px);
        }

        .user-menu {
            position: relative;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #2dd4bf, #0d9488);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(45,212,191,0.4);
        }

        .user-dropdown {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            display: none;
        }

        .user-dropdown.show { display: block; animation: fadeIn 0.3s; }

        .user-dropdown a {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #334155;
            text-decoration: none;
            font-size: 15px;
            transition: background 0.2s;
        }

        .user-dropdown a:hover { background: #f1f5f9; }
        .user-dropdown a.logout { color: #ef4444; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            .navbar-center { display: none; }
            .navbar { padding: 0 20px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fas fa-ship"></i> FMC Fisheries
        </a>

        <div class="navbar-center">
            <a href="border_crossing.php"><i class="fas fa-exclamation-circle"></i> Border Crossing</a>
            <a href="silent_vessel.php"><i class="fas fa-radio"></i> Silent Vessels</a>
            <a href="distress_vessel.php"><i class="fas fa-sos"></i> Distress Vessels</a>
            <a href="service.php"><i class="fas fa-tools"></i> Service</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        </div>

        <div class="navbar-right">
            <div class="user-menu">
                <div class="user-avatar" onclick="toggleDropdown()">
                    <?php echo strtoupper(substr($user['full_name'] ?? $_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="logout.php" class="logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

<script>
    function toggleDropdown() {
        document.getElementById('userDropdown').classList.toggle('show');
    }
    window.onclick = function(e) {
        if (!e.target.matches('.user-avatar')) {
            document.getElementById('userDropdown')?.classList.remove('show');
        }
    }
</script>