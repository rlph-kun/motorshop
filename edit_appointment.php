<?php
session_start();
include_once '../../includes/database.php'; // Adjusted path

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../backend/login.php");
    exit();
}

$message = '';
$message_type = '';
$appointment = null;
$appointment_id = null;

// Check if ID is provided for editing
if (isset($_GET['id'])) {
    $appointment_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$appointment_id) {
        $message = "Invalid Appointment ID.";
        $message_type = 'error';
    }
} else {
    $message = "No Appointment ID provided.";
    $message_type = 'error';
}

// --- Handle Appointment Update --- 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_appointment_details']) && $appointment_id) {
    $vehicle_model = trim($_POST['vehicle_model']);
    $service_description = trim($_POST['service_description']);
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = trim($_POST['preferred_time']);
    $status = $_POST['status'];
    $mechanic_id = !empty($_POST['mechanic_id']) ? $_POST['mechanic_id'] : NULL;
    $admin_notes = trim($_POST['admin_notes']);

    // Simple validation
    $errors = [];
    if (empty($vehicle_model)) {
        $errors[] = "Vehicle Model is required.";
    }
    if (empty($service_description)) {
        $errors[] = "Service Description is required.";
    }
    if (empty($preferred_date)) {
        $errors[] = "Preferred Date is required.";
    }
    if (empty($status)) {
        $errors[] = "Status is required.";
    }

    if (empty($errors)) {
        $update_sql = "UPDATE appointments SET 
                        vehicle_model = ?, 
                        service_description = ?, 
                        preferred_date = ?, 
                        preferred_time = ?, 
                        status = ?, 
                        mechanic_id = ?, 
                        admin_notes = ? 
                      WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sssssisi", 
                $vehicle_model, $service_description, $preferred_date, 
                $preferred_time, $status, $mechanic_id, 
                $admin_notes, $appointment_id
            );
            if ($stmt->execute()) {
                $message = "Appointment #{$appointment_id} updated successfully.";
                $message_type = 'success';
            } else {
                $message = "Error updating appointment: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Database error (prepare update): " . $conn->error;
            $message_type = 'error';
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

// Fetch appointment details if ID is valid and no critical error message set for ID yet
if ($appointment_id && ($message_type !== 'error' || $message === '')) { // Condition to fetch details
    $sql = "SELECT a.*, 
                   COALESCE(u.firstname, 'N/A') as customer_firstname, 
                   COALESCE(u.lastname, '') as customer_lastname, 
                   COALESCE(u.email, 'N/A') as customer_email
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE a.id = ?";
    $stmt_fetch = $conn->prepare($sql);
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $appointment_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows === 1) {
            $appointment = $result->fetch_assoc();
        } else {
            $message = "Appointment not found.";
            $message_type = 'error';
            $appointment = null; // Ensure appointment is null if not found
        }
        $stmt_fetch->close();
    } else {
        $message = "Database error (fetch details): " . $conn->error;
        $message_type = 'error';
        $appointment = null;
    }
}

// Fetch all mechanics for dropdown
$mechanics_list = [];
$mechanics_sql = "SELECT id, firstname, lastname, mech_username FROM mechanics ORDER BY firstname ASC, lastname ASC";
$mechanics_result = $conn->query($mechanics_sql);
if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($mech_row = $mechanics_result->fetch_assoc()) {
        $mechanics_list[] = $mech_row;
    }
}

// Define available statuses
$available_statuses = ['Pending', 'Completed', 'Cancelled'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment - Admin Panel</title>
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
            margin-bottom: 2rem;
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
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
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
        .overview-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.2rem;
        }
        .read-only-field {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 12px;
            color: #495057;
            word-wrap: break-word;
        }
        .notes-display { white-space: pre-wrap; }
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
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <a href="manage_appointments.php" class="back-link"><i class="fas fa-arrow-left me-2"></i>Back to All Appointments</a>
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-edit me-2"></i>Edit Appointment Details</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($appointment): ?>
                <div class="settings-card mb-4">
                    <h4 class="mb-3"><i class="fas fa-info-circle me-2"></i>Appointment Overview</h4>
                    <div class="overview-label">Appointment ID:</div>
                    <div class="read-only-field"><?php echo htmlspecialchars($appointment['id']); ?></div>
                    <div class="overview-label">Customer:</div>
                    <div class="read-only-field"><?php echo htmlspecialchars(trim($appointment['customer_firstname'] . ' ' . $appointment['customer_lastname'])); ?> (User ID: <?php echo htmlspecialchars($appointment['user_id']); ?>, Email: <?php echo htmlspecialchars($appointment['customer_email']); ?>)</div>
                    <div class="overview-label">Customer Notes:</div>
                    <div class="read-only-field notes-display"><?php echo !empty($appointment['customer_notes']) ? nl2br(htmlspecialchars($appointment['customer_notes'])) : 'N/A'; ?></div>
                    <div class="overview-label">Created At:</div>
                    <div class="read-only-field"><?php echo htmlspecialchars($appointment['created_at']); ?></div>
                    <div class="overview-label">Last Updated At:</div>
                    <div class="read-only-field"><?php echo htmlspecialchars($appointment['updated_at']); ?></div>
                </div>
                <div class="settings-card">
                    <h4 class="mb-3"><i class="fas fa-edit me-2"></i>Edit Details</h4>
                    <form action="edit_appointment.php?id=<?php echo $appointment_id; ?>" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vehicle_model" class="form-label">Vehicle Model <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" value="<?php echo htmlspecialchars($appointment['vehicle_model']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="service_description" class="form-label">Service Description <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="service_description" name="service_description" value="<?php echo htmlspecialchars($appointment['service_description']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="preferred_date" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="preferred_date" name="preferred_date" value="<?php echo htmlspecialchars($appointment['preferred_date']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preferred_time" class="form-label">Preferred Time</label>
                                <input type="text" class="form-control" id="preferred_time" name="preferred_time" value="<?php echo htmlspecialchars($appointment['preferred_time']); ?>" placeholder="e.g., 10:00 AM or Morning">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($available_statuses as $status_option): ?>
                                        <option value="<?php echo $status_option; ?>" <?php echo ($appointment['status'] == $status_option) ? 'selected' : ''; ?>>
                                            <?php echo $status_option; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mechanic_id" class="form-label">Assign Mechanic</label>
                                <select class="form-select" id="mechanic_id" name="mechanic_id">
                                    <option value="">- Not Assigned -</option>
                                    <?php foreach ($mechanics_list as $mechanic): ?>
                                        <option value="<?php echo $mechanic['id']; ?>" <?php echo ($appointment['mechanic_id'] == $mechanic['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mechanic['firstname'] . ' ' . $mechanic['lastname'] . ' (' . $mechanic['mech_username'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="admin_notes" class="form-label">Admin Notes</label>
                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"><?php echo htmlspecialchars($appointment['admin_notes']); ?></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" name="save_appointment_details" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php elseif(empty($message)): ?>
                <div class="settings-card">
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Loading appointment details or appointment not specified.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 