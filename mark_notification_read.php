<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/NotificationHandler.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notificationHandler = new NotificationHandler($conn);
$success = $notificationHandler->markAsRead($_POST['notification_id']);

echo json_encode(['success' => $success]);
?> 