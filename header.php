<!-- header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Default to user if not logged in (will redirect anyway)
$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FMC Fisheries Monitoring</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <i class="fas fa-ship"></i> FMC Fisheries
    </div>

    <ul class="nav-links">
        <?php if ($role === 'admin'): ?>
            <!-- Admin Menu -->
            <li><a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
            <li><a href="register_user.php"><i class="fas fa-user-plus"></i> Register User</a></li>
            <li><a href="vessels.php"><i class="fas fa-ship"></i> Vessels</a></li>
            <li><a href="border-alerts.php"><i class="fas fa-exclamation-triangle"></i> Border Alerts</a></li>
            <li><a href="silent-alerts.php"><i class="fas fa-volume-mute"></i> Silent Alerts</a></li>
            <li><a href="distress-alerts.php"><i class="fas fa-life-ring"></i> Distress Alerts</a></li>
            <li><a href="owners.php"><i class="fas fa-users"></i> Owners</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="usermanagement.php"><i class="fas fa-users-cog"></i> User Management</a></li>
            <li><a href="activitylogs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
        <?php else: ?>
            <!-- Regular User Menu -->
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="border_crossing.php"><i class="fas fa-map-marker-alt"></i> Border Crossing</a></li>
            <li><a href="silent_vessel.php"><i class="fas fa-wifi-slash"></i> Silent Vessels</a></li>
            <li><a href="distress_vessel.php"><i class="fas fa-exclamation-circle"></i> Distress</a></li>
            <li><a href="service.php"><i class="fas fa-tools"></i> Service Pending</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <?php endif; ?>

        <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>

    <div class="user-info">
        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
        <span class="role-badge <?= $role === 'admin' ? 'admin' : 'user' ?>">
            <?= ucfirst($role) ?>
        </span>
    </div>
</nav>

<div class="page-content">
    <!-- All page content goes here -->