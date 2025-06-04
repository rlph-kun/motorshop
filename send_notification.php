<?php
session_start();
include_once '../../includes/database.php';
require_once '../../includes/NotificationHandler.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../backend/login.php');
    exit();
}

$notificationHandler = new NotificationHandler($conn);
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'system';
    $recipients = $_POST['recipients'] ?? [];

    if (empty($title) || empty($message) || empty($type) || empty($recipients)) {
        $error = 'Please fill in all fields and select at least one recipient.';
    } else {
        if (in_array('all', $recipients)) {
            // Send to all customers
            $result = $conn->query("SELECT user_id FROM users");
            while ($row = $result->fetch_assoc()) {
                $notificationHandler->createNotification($row['user_id'], $title, $message, $type);
            }
            $success = 'Notification sent to all customers!';
        } else {
            // Send to selected customers
            foreach ($recipients as $user_id) {
                $notificationHandler->createNotification($user_id, $title, $message, $type);
            }
            $success = 'Notification sent to selected customers!';
        }
    }
}

// Fetch all customers for the select list
$customers = [];
$result = $conn->query("SELECT user_id, firstname, lastname, email FROM users ORDER BY firstname, lastname");
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Fetch notification history (last 20)
$history = $conn->query("SELECT n.*, u.firstname, u.lastname FROM notifications n JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification - Admin Panel</title>
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
        .form-label {
            font-weight: 500;
            color: #555;
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
        .notifications-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .notifications-table th {
            background: #f8f9fa;
            color: #28a745;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        .notifications-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            vertical-align: middle;
        }
        .notifications-table tr:last-child td {
            border-bottom: none;
        }
        .notifications-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
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
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .notifications-table {
                display: block;
                overflow-x: auto;
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
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content w-100">
        <div class="settings-card">
            <h2 class="mb-4 text-primary"><i class="fas fa-bell me-2"></i>Send Notification to Customers</h2>
            
            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php elseif ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="settings-section">
                <form method="POST">
                    <div class="mb-3">
                        <label for="recipients" class="form-label">Recipients</label>
                        <select name="recipients[]" id="recipients" class="form-select" multiple required>
                            <option value="all">All Customers</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['user_id']; ?>"><?php echo htmlspecialchars($c['firstname'] . ' ' . $c['lastname'] . ' (' . $c['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple customers, or choose "All Customers".</small>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea name="message" id="message" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="system">System</option>
                            <option value="reminder">Reminder</option>
                            <option value="service">Service</option>
                            <option value="appointment">Appointment</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Send Notification</button>
                </form>
            </div>

            <div class="settings-section">
                <h4 class="mb-3">Recent Notification History</h4>
                <div class="table-responsive">
                    <table class="notifications-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Date Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($history && $history->num_rows > 0): ?>
                            <?php while ($n = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($n['firstname'] . ' ' . $n['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($n['title']); ?></td>
                                    <td><?php echo htmlspecialchars($n['message']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst($n['TYPE'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($n['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No notifications sent yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
