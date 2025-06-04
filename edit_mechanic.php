<?php
session_start();
include_once '../../includes/database.php';

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

$message = '';
$message_type = '';
$mechanic = null;

// Get mechanic ID from URL
$mechanic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($mechanic_id <= 0) {
    header("Location: add_mechanic.php");
    exit();
}

// Fetch mechanic details
$stmt = $conn->prepare("SELECT id, firstname, lastname, mech_username, email, phone, specialization FROM mechanics WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $mechanic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $mechanic = $result->fetch_assoc();
    } else {
        $message = "Mechanic not found.";
        $message_type = 'error';
        header("Location: add_mechanic.php");
        exit();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $mech_username = trim($_POST['mech_username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($firstname) || empty($lastname) || empty($mech_username) || empty($email)) {
        $message = "All fields marked with * are required.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = 'error';
    } else {
        // Check if username or email is taken by another mechanic
        $check_stmt = $conn->prepare("SELECT id FROM mechanics WHERE (mech_username = ? OR email = ?) AND id != ?");
        if ($check_stmt) {
            $check_stmt->bind_param("ssi", $mech_username, $email, $mechanic_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "Username or email already taken by another mechanic.";
                $message_type = 'error';
            } else {
                // If password is provided, validate and update it
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $message = "Password must be at least 6 characters long.";
                        $message_type = 'error';
                    } elseif ($password !== $confirm_password) {
                        $message = "Passwords do not match.";
                        $message_type = 'error';
                    } else {
                        // Update with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE mechanics SET firstname = ?, lastname = ?, mech_username = ?, email = ?, phone = ?, specialization = ?, password = ? WHERE id = ?");
                        $update_stmt->bind_param("sssssssi", $firstname, $lastname, $mech_username, $email, $phone, $specialization, $hashed_password, $mechanic_id);
                    }
                } else {
                    // Update without changing password
                    $update_stmt = $conn->prepare("UPDATE mechanics SET firstname = ?, lastname = ?, mech_username = ?, email = ?, phone = ?, specialization = ? WHERE id = ?");
                    $update_stmt->bind_param("ssssssi", $firstname, $lastname, $mech_username, $email, $phone, $specialization, $mechanic_id);
                }

                if (isset($update_stmt) && $update_stmt->execute()) {
                    $message = "Mechanic details updated successfully!";
                    $message_type = 'success';
                    // Refresh mechanic data
                    $mechanic['firstname'] = $firstname;
                    $mechanic['lastname'] = $lastname;
                    $mechanic['mech_username'] = $mech_username;
                    $mechanic['email'] = $email;
                    $mechanic['phone'] = $phone;
                    $mechanic['specialization'] = $specialization;
                } else {
                    $message = "Error updating mechanic: " . $conn->error;
                    $message_type = 'error';
                }
                if (isset($update_stmt)) {
                    $update_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mechanic - Admin Panel</title>
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
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-primary {
            background: #28a745;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 1.05rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: all 0.2s;
        }
        .back-link:hover {
            color: #218838;
            transform: translateX(-5px);
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }
        @media (max-width: 600px) {
            .settings-card {
                padding: 1.2rem;
            }
        }
        .toggle-password {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            outline: none !important;
            cursor: pointer;
        }
        .password-wrapper {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <a href="add_mechanic.php" class="back-link"><i class="fas fa-arrow-left me-2"></i>Back to Mechanics List</a>
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-user-edit me-2"></i>Edit Mechanic</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <?php if ($mechanic): ?>
                <form action="edit_mechanic.php?id=<?php echo $mechanic_id; ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($mechanic['firstname']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($mechanic['lastname']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mech_username" class="form-label">Mechanic Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mech_username" name="mech_username" value="<?php echo htmlspecialchars($mechanic['mech_username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($mechanic['email']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($mechanic['phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($mechanic['specialization']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <div class="password-wrapper position-relative">
                                <input type="password" class="form-control pr-5" id="password" name="password" style="padding-right: 2.5rem;">
                                <button class="toggle-password" type="button" tabindex="-1" data-target="password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; height: 1.5rem; width: 2rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="password-wrapper position-relative">
                                <input type="password" class="form-control pr-5" id="confirm_password" name="confirm_password" style="padding-right: 2.5rem;">
                                <button class="toggle-password" type="button" tabindex="-1" data-target="confirm_password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; height: 1.5rem; width: 2rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Mechanic
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html> 