<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/NotificationHandler.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notificationId = intval($_POST['notification_id']);
$userId = intval($_SESSION['customer_id']);

$notificationHandler = new NotificationHandler($conn);
$success = $notificationHandler->deleteNotification($notificationId, $userId);

if (!$success) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete notification. notification_id: ' . $notificationId . ', user_id: ' . $userId . ', Error: ' . $conn->error
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Notification deleted successfully'
]);
?> 