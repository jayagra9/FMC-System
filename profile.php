<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user = null;
$success_message = '';
$error_message = '';

try {
    // Ensure required columns exist in users table
    $checkColumns = $pdo->query("DESCRIBE users");
    $existingColumns = [];
    while ($col = $checkColumns->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $col['Field'];
    }
    
    // Add missing columns if needed
    if (!in_array('profile_picture', $existingColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255)");
    }
    if (!in_array('created_at', $existingColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
    if (!in_array('updated_at', $existingColumns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error_message = "User data not found.";
    }
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Compatibility helper: try multiple possible column names for a field
function user_field(array $user, array $names, $default = '') {
    foreach ($names as $n) {
        if (isset($user[$n]) && $user[$n] !== null && $user[$n] !== '') {
            return $user[$n];
        }
    }
    return $default;
}

if (!$user || !is_array($user)) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$display_name = user_field($user, ['full_name','fullname','name'], 'User');
$display_username = user_field($user, ['username','user_name','user'],'user');
$display_email = user_field($user, ['email','user_email','email_address'],'');
$display_role = user_field($user, ['role','user_role'], 'user');
$display_created = user_field($user, ['created_at','created','joined_at','registered_at','created_on'], '');

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    // Validate file exists
    if (empty($file['name'])) {
        $error_message = "No file selected.";
    } else {
        // Provide descriptive upload error messages
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "Uploaded file exceeds the server limit (php.ini).";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Missing a temporary folder on the server.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "A PHP extension stopped the file upload.";
                break;
            default:
                $error_message = "Unknown upload error.";
        }

        if ($error_message === '') {
            // Validate file size
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $max_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                // Verify it's an image using getimagesize
                $imgInfo = @getimagesize($file['tmp_name']);
                if ($imgInfo === false) {
                    $error_message = "Uploaded file is not a valid image.";
                } else {
                    $mime = $imgInfo['mime'] ?? '';
                    $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
                    if (!in_array($mime, $allowed_mimes)) {
                        $error_message = "Invalid image type. Allowed: JPG, PNG, GIF, WebP.";
                    }
                }
            }
        }

        if ($error_message === '') {
            try {
                // Ensure uploads directory exists and is writable
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Failed to create upload directory: ' . $uploadDir);
                    }
                }
                if (!is_writable($uploadDir)) {
                    throw new Exception('Upload directory is not writable: ' . $uploadDir);
                }

                // Generate unique filename and path
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
                $filepath = 'uploads/profiles/' . $filename;
                $fullpath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                // Delete old profile picture if exists
                if (!empty($user['profile_picture'])) {
                    $old = __DIR__ . DIRECTORY_SEPARATOR . $user['profile_picture'];
                    if (file_exists($old)) {@unlink($old);}    
                }

                // Move uploaded file (ensure it's an uploaded file and diagnose failures)
                if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                    throw new Exception('Temporary upload file is missing or not an uploaded file.');
                }

                if (move_uploaded_file($file['tmp_name'], $fullpath)) {
                    // Save relative path to DB
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$filepath, $_SESSION['user_id']]);

                    // Update user data
                    $user['profile_picture'] = $filepath;
                    $success_message = "Profile picture updated successfully!";
                    @chmod($fullpath, 0644);
                } else {
                    $error_message = "Failed to move uploaded file. Check permissions.";
                    // write debug with diagnostics
                    $dbg = date('[Y-m-d H:i:s] ') . "move_uploaded_file failed. tmp_name=" . ($file['tmp_name'] ?? '') . " fullpath=" . $fullpath . "\n";
                    $dbg .= "is_uploaded_file(tmp_name)=" . (is_uploaded_file($file['tmp_name']) ? '1' : '0') . "\n";
                    $err = error_get_last();
                    if ($err) { $dbg .= "last_error=" . json_encode($err) . "\n"; }
                    @file_put_contents(__DIR__ . '/uploads/upload_debug.log', $dbg, FILE_APPEND);
                }
            } catch (Exception $e) {
                $error_message = "Error: " . $e->getMessage();
                @file_put_contents(__DIR__ . '/uploads/upload_debug.log', date('[Y-m-d H:i:s] ') . "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        } else {
            // Log validation error
            @file_put_contents(__DIR__ . '/uploads/upload_debug.log', date('[Y-m-d H:i:s] ') . "Validation error: " . $error_message . "\n", FILE_APPEND);
        }
    }
}

$sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$profile_time = $sri_lanka_time->format('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - FMC Fisheries</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-card {
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

        .profile-header {
            background: linear-gradient(135deg, #1a2b47 0%, #0f1c30 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            position: relative;
        }

        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #2DD4BF 0%, #0D9488 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            border: 4px solid white;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .upload-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #2DD4BF;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 3px solid white;
        }

        .upload-badge:hover {
            background: #0D9488;
            transform: scale(1.1);
        }

        .upload-badge i {
            font-size: 18px;
        }

        #profilePictureInput {
            display: none;
        }

        .profile-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .profile-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .profile-body {
            padding: 40px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a2b47;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2DD4BF;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row:hover {
            background-color: #f9f9f9;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label i {
            color: #2DD4BF;
            width: 20px;
            text-align: center;
        }

        .info-value {
            color: #666;
            font-size: 15px;
            font-weight: 500;
        }

        .info-value.badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-value.badge-admin {
            background-color: #c1121f;
            color: white;
        }

        .info-value.badge-user {
            background-color: #2DD4BF;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
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
            text-align: center;
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

        .btn-secondary {
            background: #f5f5f5;
            color: #1a2b47;
            border: 2px solid #1a2b47;
        }

        .btn-secondary:hover {
            background: #1a2b47;
            color: white;
        }

        .error {
            color: #c1121f;
            background: #fce4e4;
            padding: 15px;
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
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #27ae60;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 600px) {
            .profile-header {
                padding: 30px 20px;
            }

            .profile-body {
                padding: 20px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .profile-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'nav.php'; ?>
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar-container">
                        <div class="profile-avatar" id="profileAvatar">
                            <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $user['profile_picture'])): ?>
                                <?php $imgPath = $user['profile_picture']; $imgAbs = __DIR__ . DIRECTORY_SEPARATOR . $imgPath; $version = @filemtime($imgAbs) ?: time(); ?>
                                <img src="<?php echo htmlspecialchars($imgPath . '?v=' . $version); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="upload-badge" onclick="document.getElementById('profilePictureInput').click();" title="Upload Profile Picture">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <form id="profilePictureForm" method="POST" enctype="multipart/form-data">
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                    </form>
                    <h1><?php echo htmlspecialchars($display_name); ?></h1>
                    <p>@<?php echo htmlspecialchars($display_username); ?></p>
                </div>

                <div class="profile-body">
                    <!-- Account Information Section -->
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-info-circle"></i> Account Information
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-user-circle"></i> Username
                            </span>
                                <span class="info-value"><?php echo htmlspecialchars($display_username); ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </span>
                                <span class="info-value"><?php echo htmlspecialchars($display_email ?: 'Not Set'); ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-shield-alt"></i> Role
                            </span>
                                <span class="info-value badge <?php echo (strtolower($display_role) === 'admin') ? 'badge-admin' : 'badge-user'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($display_role)); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Activity Information Section -->
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-history"></i> Activity Information
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-calendar"></i> Member Since
                            </span>
                            <span class="info-value">
                                    <?php 
                                        if (!empty($display_created)) {
                                            echo date('F d, Y', strtotime($display_created));
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">
                                <i class="fas fa-clock"></i> Join Time
                            </span>
                            <span class="info-value">
                                    <?php 
                                        if (!empty($display_created)) {
                                            echo date('h:i A', strtotime($display_created));
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="change_password.php" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="profile-card">
                <div class="profile-header">
                    <h1>Profile Error</h1>
                </div>
                <div class="profile-body">
                    <div class="error">
                        <i class="fas fa-exclamation-triangle"></i>
                        User data not found. Please try logging in again.
                    </div>
                    <a href="login.php" class="btn btn-primary" style="max-width: 300px; margin: 0 auto;">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Handle profile picture selection
        document.getElementById('profilePictureInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit.');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.getElementById('profileAvatar');
                    avatar.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(file);
                
                // Auto-submit form
                document.getElementById('profilePictureForm').submit();
            }
        });

        // Auto-hide success message after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const success = document.querySelector('.success');
            if (success) {
                setTimeout(function() {
                    success.style.transition = 'opacity 0.5s ease';
                    success.style.opacity = '0';
                    setTimeout(function(){ if (success.parentNode) success.parentNode.removeChild(success); }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>