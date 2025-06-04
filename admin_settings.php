<?php
session_start();
include_once '../../includes/database.php'; // Adjusted path

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$current_admin_username = $_SESSION['username'];

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['new_username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Fetch current admin details for password verification
    $stmt = $conn->prepare("SELECT username, password FROM admin WHERE id = ?");
    if (!$stmt) {
        $message = "Database error (prepare failed): " . $conn->error;
        $message_type = 'error';
    } else {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin_data = $result->fetch_assoc();
        $stmt->close();

        if (!$admin_data) {
            $message = "Error fetching admin data.";
            $message_type = 'error';
        } elseif (!password_verify($current_password, $admin_data['password'])) {
            $message = "Incorrect current password.";
            $message_type = 'error';
        } else {
            // Current password verified, proceed with updates
            $update_fields = [];
            $update_params_types = '';
            $update_params_values = [];

            // Check if username needs to be updated
            if (!empty($new_username) && $new_username !== $current_admin_username) {
                // Optional: Check if new username is already taken by another admin (if system allows multiple admins)
                $check_user_stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
                $check_user_stmt->bind_param("si", $new_username, $admin_id);
                $check_user_stmt->execute();
                $check_user_result = $check_user_stmt->get_result();
                if ($check_user_result->num_rows > 0) {
                    $message = "New username is already taken.";
                    $message_type = 'error';
                }
                $check_user_stmt->close();
                
                if ($message_type !== 'error') {
                    $update_fields[] = "username = ?";
                    $update_params_types .= 's';
                    $update_params_values[] = $new_username;
                }
            }

            // Check if password needs to be updated
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $message = "New password must be at least 6 characters long.";
                    $message_type = 'error';
                } elseif ($new_password !== $confirm_new_password) {
                    $message = "New passwords do not match.";
                    $message_type = 'error';
                } else {
                    if ($message_type !== 'error') {
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_fields[] = "password = ?";
                        $update_params_types .= 's';
                        $update_params_values[] = $hashed_new_password;
                    }
                }
            }

            if ($message_type !== 'error' && !empty($update_fields)) {
                $update_params_values[] = $admin_id; // For the WHERE clause
                $update_params_types .= 'i';

                $sql_update = "UPDATE admin SET " . implode(", ", $update_fields) . " WHERE id = ?";
                $update_stmt = $conn->prepare($sql_update);
                
                if ($update_stmt) {
                    // Dynamically bind parameters
                    $update_stmt->bind_param($update_params_types, ...$update_params_values);

                    if ($update_stmt->execute()) {
                        $message = "Settings updated successfully!";
                        $message_type = 'success';
                        if (!empty($new_username) && $new_username !== $current_admin_username) {
                            $_SESSION['username'] = $new_username; // Update session username
                            $current_admin_username = $new_username; // Update for display on page
                        }
                    } else {
                        $message = "Error updating settings: " . $update_stmt->error;
                        $message_type = 'error';
                    }
                    $update_stmt->close();
                } else {
                    $message = "Database error (update prepare failed): " . $conn->error;
                    $message_type = 'error';
                }
            } elseif (empty($update_fields) && $message_type !== 'error') {
                $message = "No changes were submitted or new username is the same as current.";
                $message_type = 'info'; // Or 'error' if you prefer
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Motorshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f4f6fa;
        }
        .main-content {
            margin-left: 20px;
            min-height: 100vh;
            padding: 40px;
        }
        .settings-card {
            width: 100%;
            max-width: 100%;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .settings-card h2 {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .settings-section {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .settings-section h4 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .btn-primary {
            padding: 0.75rem;
            font-weight: 600;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .current-username {
            background-color: #e9ecef;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 0;
            color: #495057;
            font-weight: 500;
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 0;
            }
        }
        @media (max-width: 600px) {
            .settings-card {
                padding: 1.2rem 0.5rem;
            }
            .settings-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-cog me-2"></i>Admin Account Settings</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : ($message_type === 'error' ? 'danger' : 'info'); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="admin_settings.php" method="POST">
                <!-- Profile Information -->
                <div class="settings-section">
                    <h4><i class="fas fa-user me-2"></i>Profile Information</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Current Username</label>
                            <div class="current-username"><?php echo htmlspecialchars($current_admin_username); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_username" class="form-label">New Username (Optional)</label>
                            <input type="text" class="form-control" id="new_username" name="new_username" placeholder="Enter new username if changing">
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="settings-section">
                    <h4><i class="fas fa-lock me-2"></i>Change Password</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Current Password (Required)</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <span class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">New Password (Optional)</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password if changing">
                                <span class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <small class="text-muted">Must be at least 6 characters long.</small>
                        </div>
                        <div class="col-md-4">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm new password">
                                <span class="password-toggle" onclick="togglePassword('confirm_new_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
