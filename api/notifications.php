<?php
/**
 * API Endpoint: Get Notifications
 */

header('Content-Type: application/json');

require_once('../config/database.php');
require_once('../config/security.php');
require_once('../includes/NotificationService.php');

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$notification_service = new NotificationService($conn, $_SESSION['user_id']);

try {
    if ($action === 'get_unread') {
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $notifications = $notification_service->getUnread($limit);
        $unread_count = $notification_service->getUnreadCount();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);

    } elseif ($action === 'get_count') {
        $unread_count = $notification_service->getUnreadCount();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count
        ]);

    } elseif ($action === 'mark_as_read') {
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if ($notification_id <= 0) {
            throw new Exception('Invalid notification ID');
        }

        $result = $notification_service->markAsRead($notification_id);
        http_response_code(200);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Notification marked as read' : 'Failed to mark as read'
        ]);

    } elseif ($action === 'mark_all_as_read') {
        $result = $notification_service->markAllAsRead();
        http_response_code(200);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'All notifications marked as read' : 'Failed to update notifications'
        ]);

    } elseif ($action === 'delete') {
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if ($notification_id <= 0) {
            throw new Exception('Invalid notification ID');
        }

        $result = $notification_service->delete($notification_id);
        http_response_code(200);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
        ]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>