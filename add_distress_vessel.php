<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [
        'date' => filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING),
        'vessel_name' => filter_input(INPUT_POST, 'vessel_name', FILTER_SANITIZE_STRING),
        'owner_name' => filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_STRING),
        'contact_number' => filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING),
        'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
        'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING),
        'speed' => filter_input(INPUT_POST, 'speed', FILTER_SANITIZE_STRING),
        'position' => filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING),
        'date_time_detection' => filter_input(INPUT_POST, 'date_time_detection', FILTER_SANITIZE_STRING),
        'distance_last_position' => filter_input(INPUT_POST, 'distance_last_position', FILTER_SANITIZE_STRING),
        'notes' => filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING),
        'remark' => filter_input(INPUT_POST, 'remark', FILTER_SANITIZE_STRING),
        'username' => $_SESSION['username']
    ];

    // Append 00:00:00 to date fields
    $data['date'] = $data['date'] ? $data['date'] . ' 00:00:00' : null;
    $data['date_time_detection'] = $data['date_time_detection'] ? $data['date_time_detection'] . ':00' : null; // Assuming datetime picker inputs 'Y-m-d H:i'

    // current user and timestamp (Asia/Colombo)
    $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    $today_date = $sri_lanka_time->format('Y-m-d H:i:s');

    // Handle file uploads for departure_form and voyage (JPEG only)
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $data['departure_form'] = '';
    if (isset($_FILES['departure_form']) && $_FILES['departure_form']['error'] == 0) {
        $file_tmp = $_FILES['departure_form']['tmp_name'];
        $file_name = basename($_FILES['departure_form']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg'])) {
            $dest_path = $upload_dir . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $data['departure_form'] = $dest_path;
            } else {
                echo "<script>alert('Unsuccessful: Failed to upload Departure Form.'); window.location.href = 'add_distress_vessel.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Unsuccessful: Departure Form must be JPEG.'); window.location.href = 'add_distress_vessel.php';</script>";
            exit();
        }
    }

    $data['voyage'] = '';
    if (isset($_FILES['voyage']) && $_FILES['voyage']['error'] == 0) {
        $file_tmp = $_FILES['voyage']['tmp_name'];
        $file_name = basename($_FILES['voyage']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg'])) {
            $dest_path = $upload_dir . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $data['voyage'] = $dest_path;
            } else {
                echo "<script>alert('Unsuccessful: Failed to upload Voyage.'); window.location.href = 'add_distress_vessel.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Unsuccessful: Voyage must be JPEG.'); window.location.href = 'add_distress_vessel.php';</script>";
            exit();
        }
    }

    if (empty($data['vessel_name'])) {
        echo "<script>alert('Unsuccessful: Vessel Name is required.'); window.location.href = 'add_distress_vessel.php';</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO distress_vessels (date, vessel_name, owner_name, contact_number, address, status, speed, position, date_time_detection, distance_last_position, notes, remark, departure_form, voyage, username, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['date'],
            $data['vessel_name'],
            $data['owner_name'],
            $data['contact_number'],
            $data['address'],
            $data['status'],
            $data['speed'],
            $data['position'],
            $data['date_time_detection'],
            $data['distance_last_position'],
            $data['notes'],
            $data['remark'],
            $data['departure_form'],
            $data['voyage'],
            $data['username'],
            $_SESSION['user_id'],
            $today_date
        ]);
        // record centralized audit (non-fatal)
        try {
            require_once __DIR__ . '/audit_helpers.php';
            $newId = $pdo->lastInsertId();
            record_audit($pdo, 'distress_vessels', $newId, 'create', $data);
        } catch (Exception $ae) {
            error_log('audit record failed (distress create): ' . $ae->getMessage());
        }
        echo "<script>alert('Successfully added!'); window.location.href = 'distress_vessel.php';</script>";
        exit();
    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Unsuccessful: $msg'); window.location.href = 'add_distress_vessel.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Distress Vessel - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #2DD4BF;
            color: #FFFFFF;
        }
        .btn-primary:hover {
            background-color: #0D9488;
        }
        .error-message {
            color: red;
            margin-top: 10px;
            display: none;
        }
        fieldset {
            border: 1px solid #D1D5DB;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        legend {
            font-weight: 600;
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <?php require_once 'nav.php'; ?>
    <div class="container">
        <div class="card">
            <h1>Add New Distress Vessel</h1>
            <form method="POST" action="add_distress_vessel.php" enctype="multipart/form-data" class="form-grid" id="distressVesselForm">
                <fieldset>
                    <legend>Vessel Details</legend>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date">
                    </div>
                    <div class="form-group">
                        <label for="vessel_name">Name *</label>
                        <input type="text" name="vessel_name" id="vessel_name" required>
                    </div>
                    <div class="form-group">
                        <label for="owner_name">Owner's Name</label>
                        <input type="text" name="owner_name" id="owner_name">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" name="address" id="address">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Vessel Status</legend>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <input type="text" name="status" id="status">
                    </div>
                    <div class="form-group">
                        <label for="speed">Speed</label>
                        <input type="text" name="speed" id="speed">
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" name="position" id="position">
                    </div>
                    <div class="form-group">
                        <label for="date_time_detection">Date and Time of Detection</label>
                        <input type="datetime-local" name="date_time_detection" id="date_time_detection">
                    </div>
                    <div class="form-group">
                        <label for="distance_last_position">Distance from Last Position (to harbour)</label>
                        <input type="text" name="distance_last_position" id="distance_last_position">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Comments</legend>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <input type="text" name="notes" id="notes">
                    </div>
                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <input type="text" name="remark" id="remark">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Departure Form and Voyage</legend>
                    <div class="form-group">
                        <label for="departure_form">Departure Form (JPEG)</label>
                        <input type="file" name="departure_form" id="departure_form" accept="image/jpeg">
                    </div>
                    <div class="form-group">
                        <label for="voyage">Voyage (JPEG)</label>
                        <input type="file" name="voyage" id="voyage" accept="image/jpeg">
                    </div>
                </fieldset>

                <div class="form-group full-width">
                    <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
            <div class="error-message" id="phoneError"></div>
        </div>
    </div>
    <script>
        document.getElementById('distressVesselForm').addEventListener('submit', function(event) {
            const contactPhone = document.getElementById('contact_number').value;
            const phoneError = document.getElementById('phoneError');

            if (contactPhone && !/^\d{10}$/.test(contactPhone)) {
                event.preventDefault();
                phoneError.textContent = 'Contact Number must be exactly 10 digits.';
                phoneError.style.display = 'block';
                return;
            }
            phoneError.style.display = 'none';
        });
    </script>
</body>
</html>