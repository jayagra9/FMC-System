<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$dashboard_time = $sri_lanka_time->format('Y-m-d H:i:s');

// Fetch user details
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
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

        .navbar-brand i {
            font-size: 22px;
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
            color: #2DD4BF;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2DD4BF 0%, #0D9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: white;
            cursor: pointer;
        }

        .user-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            z-index: 200;
            display: none;
            overflow: hidden;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .user-dropdown a:hover {
            background: #f5f7fa;
        }

        .user-dropdown a:first-child {
            border-bottom: 1px solid #f0f0f0;
        }

        .user-dropdown a.logout {
            color: #c1121f;
        }

        .user-dropdown a i {
            width: 16px;
            text-align: center;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #1a2b47 0%, #0f1c30 100%);
            color: white;
            padding: 50px 45px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .welcome-card h1 {
            font-size: 36px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .welcome-card p {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 16px;
        }

        .dashboard-time {
            display: inline-block;
            background: rgba(45, 212, 191, 0.2);
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .card-item {
            background: white;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border: none;
        }

        .card-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card-icon {
            width: 65px;
            height: 65px;
            background: rgba(45, 212, 191, 0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #2DD4BF;
            margin-bottom: 18px;
        }

        .card-item h3 {
            font-size: 19px;
            color: #1a2b47;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .card-item p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            flex: 1;
            margin-bottom: 16px;
        }

        .card-arrow {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2DD4BF;
            font-weight: 700;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0 15px;
                height: 55px;
            }

            .navbar-center {
                display: none;
            }

            .navbar-brand {
                font-size: 16px;
            }

            .container {
                margin: 30px auto;
                padding: 0 15px;
            }

            .welcome-card {
                padding: 30px;
                margin-bottom: 25px;
            }

            .welcome-card h1 {
                font-size: 28px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .card-item {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
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

    <!-- Dashboard Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-card">
            <h1><i class="fas fa-chart-line"></i> Welcome, <?php echo htmlspecialchars($user['full_name'] ?? $_SESSION['username']); ?>!</h1>
            <p>You are successfully logged into the FMC Fisheries Management System</p>
            <div class="dashboard-time">
                <i class="fas fa-clock"></i> Last accessed: <?php echo $dashboard_time; ?>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="cards-grid">
            <a href="border_crossing.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-map"></i>
                </div>
                <h3>Border Crossing</h3>
                <p>Monitor and manage vessel border crossing alerts and reports.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="silent_vessel.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-ship"></i>
                </div>
                <h3>Silent Vessels</h3>
                <p>Track and manage silent vessel alerts and notifications.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="distress_vessel.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Distress Vessels</h3>
                <p>Monitor distress vessel reports and emergency situations.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="service.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>Service</h3>
                <p>Access services and support for the fisheries system.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="payments.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Payments</h3>
                <p>Manage vessel payments and financial transactions.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="profile.php" class="card-item">
                <div class="card-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3>My Profile</h3>
                <p>View and manage your profile information and settings.</p>
                <div class="card-arrow">
                    View Details <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(event.target)) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>