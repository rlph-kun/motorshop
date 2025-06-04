<?php
session_start();
include_once '../../includes/database.php'; // Adjusted path to database.php

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

$message = '';
$message_type = ''; // 'success' or 'error'

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

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
    if (empty($firstname) || empty($lastname) || empty($mech_username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields marked with * are required.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = 'error';
    } else {
        // Check if mech_username or email already exists
        $stmt = $conn->prepare("SELECT id FROM mechanics WHERE mech_username = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $mech_username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "Mechanic username or email already taken.";
                $message_type = 'error';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new mechanic
                $insert_stmt = $conn->prepare("INSERT INTO mechanics (firstname, lastname, mech_username, email, phone, specialization, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssssss", $firstname, $lastname, $mech_username, $email, $phone, $specialization, $hashed_password);
                    if ($insert_stmt->execute()) {
                        $_SESSION['message'] = "Mechanic account created successfully!";
                        $_SESSION['message_type'] = 'success';
                        header("Location: add_mechanic.php");
                        exit();
                    } else {
                        $message = "Error creating mechanic account: " . $insert_stmt->error;
                        $message_type = 'error';
                    }
                    $insert_stmt->close();
                } else {
                     $message = "Database error (insert preparation): " . $conn->error;
                     $message_type = 'error';
                }
            }
            $stmt->close();
        } else {
            $message = "Database error (select preparation): " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Fetch all mechanics for display
$mechanics_list = [];
$mechanics_sql = "SELECT id, firstname, lastname, mech_username, email, phone, specialization FROM mechanics ORDER BY firstname ASC, lastname ASC";
$mechanics_result = $conn->query($mechanics_sql);
if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($mech_row = $mechanics_result->fetch_assoc()) {
        $mechanics_list[] = $mech_row;
    }
}

// Handle mechanic deletion
if (isset($_POST['delete_mechanic'])) {
    $mechanic_id = $_POST['mechanic_id'];
    $delete_stmt = $conn->prepare("DELETE FROM mechanics WHERE id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $mechanic_id);
        if ($delete_stmt->execute()) {
            $message = "Mechanic deleted successfully!";
            $message_type = 'success';
            // Refresh the page to show updated list
            header("Location: add_mechanic.php");
            exit();
        } else {
            $message = "Error deleting mechanic: " . $delete_stmt->error;
            $message_type = 'error';
        }
        $delete_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Mechanic - Admin Panel</title>
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
        .mechanics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .mechanics-table th {
            background: #f8f9fa;
            color: #28a745;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        .mechanics-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            vertical-align: middle;
        }
        .mechanics-table tr:last-child td {
            border-bottom: none;
        }
        .mechanics-table tr:hover {
            background: #f8f9fa;
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
        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-danger:hover {
            background: #c82333;
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
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .mechanics-table {
                display: block;
                overflow-x: auto;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
        @media (max-width: 600px) {
            .settings-card {
                padding: 1.2rem;
            }
            .settings-section {
                padding: 1rem;
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
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-user-plus me-2"></i>Add New Mechanic</h2>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-section">
                <form action="add_mechanic.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mech_username" class="form-label">Mechanic Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mech_username" name="mech_username" value="<?php echo isset($_POST['mech_username']) ? htmlspecialchars($_POST['mech_username']) : ''; ?>" required>
                            <small class="text-muted">This will be used for login</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="password-wrapper position-relative">
                                <input type="password" class="form-control pr-5" id="password" name="password" required style="padding-right: 2.5rem;">
                                <button class="toggle-password" type="button" tabindex="-1" data-target="password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; height: 1.5rem; width: 2rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="password-wrapper position-relative">
                                <input type="password" class="form-control pr-5" id="confirm_password" name="confirm_password" required style="padding-right: 2.5rem;">
                                <button class="toggle-password" type="button" tabindex="-1" data-target="confirm_password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; height: 1.5rem; width: 2rem; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Add Mechanic
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <h3 class="mb-4"><i class="fas fa-users me-2"></i>Manage Mechanics</h3>
                <?php if (!empty($mechanics_list)): ?>
                    <div class="table-responsive">
                        <table class="mechanics-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Specialization</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mechanics_list as $mechanic): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mechanic['firstname'] . ' ' . $mechanic['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($mechanic['mech_username']); ?></td>
                                        <td><?php echo htmlspecialchars($mechanic['email']); ?></td>
                                        <td><?php echo htmlspecialchars($mechanic['phone'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($mechanic['specialization'] ?: 'N/A'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_mechanic.php?id=<?php echo $mechanic['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $mechanic['id']; ?>">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $mechanic['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Mechanic</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this mechanic?</p>
                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($mechanic['firstname'] . ' ' . $mechanic['lastname']); ?></p>
                                                            <p><strong>Username:</strong> <?php echo htmlspecialchars($mechanic['mech_username']); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form action="add_mechanic.php" method="POST" style="display: inline;">
                                                                <input type="hidden" name="mechanic_id" value="<?php echo $mechanic['id']; ?>">
                                                                <button type="submit" name="delete_mechanic" class="btn btn-danger">Delete Mechanic</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No mechanics found. Use the form above to add a new mechanic.</p>
                    </div>
                <?php endif; ?>
            </div>
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
