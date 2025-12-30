<?php
// sidebar.php
?>

<style>
    .body {
        margin: 0;
        font-family: Arial;
        background: #f5f7fa;
    }

    .sidebar {
        width: 250px;
        background: #1a2b47;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        padding-top: 20px;
        color: white;
        display: flex;
        flex-direction: column;
    }

    .sidebar-content {
        flex: 1;
    }

    .sidebar a {
        display: block;
        padding: 14px;
        color: #d9d9d9;
        text-decoration: none;
        font-size: 16px;
    }

    .sidebar a:hover {
        background: #0f1c30;
        color: white;
    }

    .logout-btn {
        display: block;
        padding: 14px;
        color: white;
        text-decoration: none;
        font-size: 16px;
        background: #c1121f;
        text-align: center;
        margin: 10px;
        margin-top: auto;
        width: calc(100% - 20px);
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-weight: bold;
    }

    .logout-btn:hover {
        background: #a00c1a;
    }
</style>

<div class="sidebar">
    <div class="sidebar-content">
        <h2 style="text-align:center;">FMC Admin</h2>

        <a href="admindashboard.php">Dashboard</a>
        <a href="register_user.php">Register User</a>
        <a href="admin_border_crossings.php">Border Crossing Alerts</a>
        <a href="admin_silent_vessels.php">Silent Vessel Alerts</a>
        <a href="admin_distress_vessels.php">Distress Alerts</a>
        <a href="usermanagement.php">User Management</a>
        <a href="activitylogs.php">Activity Logs</a>
        <a href="admin_profile.php">Settings</a>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>
