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

// --- Handle Appointment Update --- (NEW)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    $new_mechanic_id = !empty($_POST['mechanic_id']) ? $_POST['mechanic_id'] : NULL; // Handle empty selection for mechanic

    // Validate status (optional, but good practice)
    $allowed_statuses = ['Pending', 'Confirmed', 'Assigned', 'In Progress', 'Completed', 'Cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $message = "Invalid status selected.";
        $message_type = 'error';
    } else {
        $update_sql = "UPDATE appointments SET status = ?, mechanic_id = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("sii", $new_status, $new_mechanic_id, $appointment_id);
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
    }
}

// --- Handle Appointment Delete ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    $delete_sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $appointment_id);
        if ($stmt->execute()) {
            $message = "Appointment #{$appointment_id} deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "Error deleting appointment: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Database error (prepare delete): " . $conn->error;
        $message_type = 'error';
    }
}
// --- End Handle Appointment Update ---

// Fetch all mechanics for dropdown (NEW)
$mechanics_list = [];
$mechanics_sql = "SELECT id, firstname, lastname, mech_username FROM mechanics ORDER BY firstname ASC, lastname ASC";
$mechanics_result = $conn->query($mechanics_sql);
if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($mech_row = $mechanics_result->fetch_assoc()) {
        $mechanics_list[] = $mech_row;
    }
}

// Define available statuses (NEW)
$available_statuses = ['Pending', 'Completed', 'Cancelled'];

// --- FILTERS: Status and Date ---
$status_filter = isset($_GET['status']) && in_array(strtolower($_GET['status']), ['pending','completed','cancelled']) ? ucfirst(strtolower($_GET['status'])) : '';
$date_filter = isset($_GET['date']) ? strtolower($_GET['date']) : '';
$where_clauses = [];
$params = [];
$types = '';

if ($status_filter) {
    $where_clauses[] = 'a.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_filter === 'today') {
    $where_clauses[] = 'a.preferred_date = CURDATE()';
} elseif ($date_filter === 'week') {
    $where_clauses[] = 'YEARWEEK(a.preferred_date, 1) = YEARWEEK(CURDATE(), 1)';
} elseif ($date_filter === 'month') {
    $where_clauses[] = 'YEAR(a.preferred_date) = YEAR(CURDATE()) AND MONTH(a.preferred_date) = MONTH(CURDATE())';
}
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$sql = "SELECT 
            a.id, a.user_id, 
            COALESCE(u.firstname, 'N/A') as customer_firstname, 
            COALESCE(u.lastname, '') as customer_lastname, 
            a.vehicle_model, a.service_description, 
            a.preferred_date, a.preferred_time, a.status, 
            a.mechanic_id, COALESCE(m.mech_username, 'Not Assigned') as mechanic_username, 
            a.admin_notes, a.customer_notes, a.created_at
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.user_id
        LEFT JOIN mechanics m ON a.mechanic_id = m.id
        $where_sql
        ORDER BY 
            CASE 
                WHEN a.status = 'Pending' THEN 1
                WHEN a.status = 'Completed' THEN 2
                WHEN a.status = 'Cancelled' THEN 3
                ELSE 4
            END,
            a.preferred_date DESC, a.created_at DESC";

$appointments = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $appointments_result = $stmt->get_result();
    if ($appointments_result && $appointments_result->num_rows > 0) {
        while ($row = $appointments_result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    $stmt->close();
} else {
    $appointments_result = $conn->query($sql);
    if ($appointments_result && $appointments_result->num_rows > 0) {
        while ($row = $appointments_result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Admin Panel</title>
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
        .appointments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .appointments-table th {
            background: #f8f9fa;
            color: #28a745;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        .appointments-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            vertical-align: middle;
        }
        .appointments-table tr:last-child td {
            border-bottom: none;
        }
        .appointments-table tr:hover {
            background: #f8f9fa;
        }
        select, .appointments-table button {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 1rem;
            transition: all 0.2s;
        }
        select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            outline: none;
        }
        .appointments-table button {
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }
        .appointments-table button:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .appointments-table .btn-danger {
            background: #dc3545;
        }
        .appointments-table .btn-danger:hover {
            background: #c82333;
        }
        .action-links {
            display: flex;
            gap: 0.5rem;
        }
        .action-links a {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .edit-link {
            background: #ffc107;
            color: #000;
        }
        .edit-link:hover {
            background: #e0a800;
            color: #000;
            transform: translateY(-1px);
        }
        .status-pending { color: #ff8c00; font-weight: 600; }
        .status-confirmed { color: #007bff; font-weight: 600; }
        .status-assigned { color: #17a2b8; font-weight: 600; }
        .status-inprogress { color: #fd7e14; font-weight: 600; }
        .status-completed { color: #28a745; font-weight: 600; }
        .status-cancelled { color: #dc3545; font-weight: 600; }
        .no-appointments {
            text-align: center;
            color: #6c757d;
            font-size: 1.1rem;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
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
        .filter-section {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-section select {
            min-width: 200px;
        }
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .appointments-table {
                display: block;
                overflow-x: auto;
            }
            .action-links {
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
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section select {
                width: 100%;
            }
        }
        .appointments-table .btn-view-blue {
            background: #2563eb;
            color: #fff;
            border: none;
            transition: background 0.2s, transform 0.2s;
        }
        .appointments-table .btn-view-blue:hover {
            background: #1d4ed8;
            color: #fff;
            transform: translateY(-1px) scale(1.04);
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-calendar-check me-2"></i>Manage Appointments</h2>
            
            <div class="settings-section">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="filter-section">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="dateFilter" class="form-select">
                        <option value="" 
                            <?php echo $date_filter === '' ? 'selected' : ''; ?>>All Dates</option>
                        <option value="today" 
                            <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" 
                            <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" 
                            <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="no-appointments">
                        <i class="fas fa-calendar-times me-2"></i>No appointments found.
                    </div>
                <?php else: ?>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Mechanic</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($appointment['preferred_date'] . ' ' . $appointment['preferred_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['customer_firstname'] . ' ' . $appointment['customer_lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['vehicle_model']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['service_description']); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($appointment['status']); ?>">
                                            <?php echo htmlspecialchars($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['mechanic_username']); ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Appointment #<?php echo $appointment['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this appointment?</p>
                                                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($appointment['customer_firstname'] . ' ' . $appointment['customer_lastname']); ?></p>
                                                        <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($appointment['preferred_date'] . ' ' . $appointment['preferred_time'])); ?></p>
                                                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_description']); ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete_appointment" class="btn btn-danger">Delete Appointment</button>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(appointmentId, newStatus) {
            if (confirm('Are you sure you want to update this appointment\'s status?')) {
                window.location.href = `update_status.php?id=${appointmentId}&status=${newStatus}`;
            }
        }

        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFilter').addEventListener('change', applyFilters);

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const date = document.getElementById('dateFilter').value;
            window.location.href = `?status=${status}&date=${date}`;
        }
    </script>
</body>
</html>
