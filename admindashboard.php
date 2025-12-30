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

<?php include 'sidebar.php'; ?>

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
