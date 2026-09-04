<?php
/**
 * Notification Service
 * Handles creation and management of alerts
 */

class NotificationService {
    private $conn;
    private $user_id;

    public function __construct($connection, $user_id = null) {
        $this->conn = $connection;
        $this->user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
    }

    /**
     * Create notification
     */
    public function create($type, $title, $message, $reference_type = null, $reference_id = null, $priority = 'medium') {
        if (!$this->conn) return false;

        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (user_id, type, title, message, reference_type, reference_id, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) return false;

        $stmt->bind_param(
            'issssss',
            $this->user_id,
            $type,
            $title,
            $message,
            $reference_type,
            $reference_id,
            $priority
        );

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get unread notifications for user
     */
    public function getUnread($limit = 10) {
        if (!$this->conn || !$this->user_id) return [];

        $stmt = $this->conn->prepare(
            "SELECT * FROM notifications
             WHERE user_id = ? AND is_read = 0
             ORDER BY priority DESC, created_at DESC
             LIMIT ?"
        );

        if (!$stmt) return [];

        $stmt->bind_param('ii', $this->user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $notifications;
    }

    /**
     * Get unread count
     */
    public function getUnreadCount() {
        if (!$this->conn || !$this->user_id) return 0;

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM notifications
             WHERE user_id = ? AND is_read = 0"
        );

        if (!$stmt) return 0;

        $stmt->bind_param('i', $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['count'] ?? 0);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        if (!$this->conn) return false;

        $read_at = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1, read_at = ?
             WHERE notification_id = ? AND user_id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param('sii', $read_at, $notification_id, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead() {
        if (!$this->conn || !$this->user_id) return false;

        $read_at = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1, read_at = ?
             WHERE user_id = ? AND is_read = 0"
        );

        if (!$stmt) return false;

        $stmt->bind_param('si', $read_at, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Delete notification
     */
    public function delete($notification_id) {
        if (!$this->conn) return false;

        $stmt = $this->conn->prepare(
            "DELETE FROM notifications
             WHERE notification_id = ? AND user_id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param('ii', $notification_id, $this->user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

/**
 * Alert Engine - Check inventory conditions and create notifications
 */
class AlertEngine {
    private $conn;
    private $notification_service;

    public function __construct($connection) {
        $this->conn = $connection;
        $this->notification_service = new NotificationService($connection);
    }

    /**
     * Check all inventory alerts
     */
    public function checkAllAlerts() {
        $this->checkOutOfStock();
        $this->checkLowStock();
    }

    /**
     * Check for out of stock products
     */
    public function checkOutOfStock() {
        if (!$this->conn) return;

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM product_list
             WHERE stock <= 0 AND delete_flag = 0 AND status = 1"
        );

        if (!$stmt) return;

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int)($row['count'] ?? 0);
        $stmt->close();

        if ($count > 0) {
            // Check if notification already exists today
            $stmt = $this->conn->prepare(
                "SELECT notification_id FROM notifications
                 WHERE type = 'OUT_OF_STOCK' AND DATE(created_at) = CURDATE()
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if (!$exists) {
                    $this->notification_service->create(
                        'OUT_OF_STOCK',
                        'Out of Stock Alert',
                        "$count products are out of stock",
                        'products',
                        null,
                        'critical'
                    );
                }
            }
        }
    }

    /**
     * Check for low stock products
     */
    public function checkLowStock() {
        if (!$this->conn) return;

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM product_list
             WHERE stock > 0 AND stock <= alert_restock
             AND delete_flag = 0 AND status = 1"
        );

        if (!$stmt) return;

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int)($row['count'] ?? 0);
        $stmt->close();

        if ($count > 0) {
            $stmt = $this->conn->prepare(
                "SELECT notification_id FROM notifications
                 WHERE type = 'LOW_STOCK' AND DATE(created_at) = CURDATE()
                 LIMIT 1"
            );

            if ($stmt) {
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if (!$exists) {
                    $this->notification_service->create(
                        'LOW_STOCK',
                        'Low Stock Alert',
                        "$count products have low stock levels",
                        'products',
                        null,
                        'high'
                    );
                }
            }
        }
    }

    /**
     * Record stock movement and create notification if needed
     */
    public function recordStockMovement($product_id, $movement_type, $quantity, $reference_type, $reference_id, $previous_stock, $new_stock, $user_id, $notes = '') {
        if (!$this->conn) return false;

        $stmt = $this->conn->prepare(
            "INSERT INTO stock_movements
             (product_id, movement_type, quantity, reference_type, reference_id, previous_stock, new_stock, user_id, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) return false;

        $stmt->bind_param(
            'issiiidiis',
            $product_id,
            $movement_type,
            $quantity,
            $reference_type,
            $reference_id,
            $previous_stock,
            $new_stock,
            $user_id,
            $notes
        );

        $result = $stmt->execute();
        $stmt->close();

        // Check alerts after movement
        if ($result) {
            $this->checkProductAlerts($product_id);
        }

        return $result;
    }

    /**
     * Check alerts for specific product
     */
    private function checkProductAlerts($product_id) {
        if (!$this->conn) return;

        $stmt = $this->conn->prepare(
            "SELECT product_id, name, stock, alert_restock
             FROM product_list
             WHERE product_id = ?"
        );

        if (!$stmt) return;

        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if ($product) {
            $stock = (float)$product['stock'];
            $reorder = (float)$product['alert_restock'];

            if ($stock <= 0) {
                $this->notification_service->create(
                    'OUT_OF_STOCK',
                    'Out of Stock',
                    $product['name'] . ' is now out of stock',
                    'product',
                    $product_id,
                    'critical'
                );
            } elseif ($stock <= $reorder) {
                $this->notification_service->create(
                    'LOW_STOCK',
                    'Low Stock',
                    $product['name'] . ' stock is below reorder level',
                    'product',
                    $product_id,
                    'high'
                );
            }
        }
    }
}
?>