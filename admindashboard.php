<?php
session_start();

// Check if user is admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FMC Admin Dashboard</title>

    <style>
        body {
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
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #a00c1a;
        }

        .main {
            margin-left: 260px;
            padding: 20px;
        }

        .card-container {
            display: flex;
            gap: 20px;
        }

        .card {
            width: 220px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            margin: 0;
            font-size: 28px;
        }

        .card p {
            margin: 5px 0 0;
            color: #666;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background: white;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        table th {
            background: #1a2b47;
            color: white;
        }
    </style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-content">
        <h2 style="text-align:center;">FMC Admin</h2>

        <a href="admindashboard.php">Dashboard</a>
        <a href="register_user.php">Register User</a>
        <a href="vessels.php">Vessels</a>
        <a href="admin_border_crossings.php">Border Crossing Alerts</a>
        <a href="admin_silent_vessels.php">Silent Vessel Alerts</a>
        <a href="admin_distress_vessels.php">Distress Alerts</a>
        <a href="owners.php">Vessel Owners</a>
        <a href="reports.php">Reports</a>
        <a href="usermanagement.php">User Management</a>
        <a href="activitylogs.php">Activity Logs</a>
        <a href="admin_profile.php">Settings</a>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="main">
    <h1>Admin Dashboard</h1>

    <div class="card-container">

        <div class="card">
            <h2>4722</h2>
            <p>Total Vessels</p>
        </div>

        <div class="card">
            <h2>38</h2>
            <p>Silent Vessels</p>
        </div>

        <div class="card">
            <h2>12</h2>
            <p>Border Crossing</p>
        </div>

        <div class="card">
            <h2>3</h2>
            <p>Distress Alerts</p>
        </div>
    </div>

    <h2>Recent Alerts</h2>

    <table>
        <tr>
            <th>Vessel</th>
            <th>Alert Type</th>
            <th>Time</th>
            <th>Status</th>
        </tr>

        <tr>
            <td>IMUL-1234</td>
            <td>Border Crossing</td>
            <td>2025-03-10 10:22</td>
            <td>Pending</td>
        </tr>

        <tr>
            <td>IMUL-0912</td>
            <td>Silent Vessel</td>
            <td>2025-03-10 09:41</td>
            <td>Notified</td>
        </tr>

        <tr>
            <td>IMUL-7788</td>
            <td>Distress</td>
            <td>2025-03-10 07:10</td>
            <td>Escalated</td>
        </tr>
    </table>
</div>

</body>
</html>
