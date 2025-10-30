<?php
// app/models/notificationModel.php

class NotificationModel
{
    private $pdo;

    // Dependency Injection: receive PDO externally
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification.
     *
     * @param int|null $userId
     * @param string $type
     * @param string $message
     * @return int|null Inserted notification_id or null on failure
     */
    public function createNotification(?int $userId, string $type, string $message): ?int
    {
        $sql = "INSERT INTO tbl_notifications (user_id, type, message, created_at, is_read)
                VALUES (:user_id, :type, :message, NOW(), 0)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':type'    => $type,
            ':message' => $message,
        ]);

        return $result ? (int)$this->pdo->lastInsertId() : null;
    }

    /**
     * Fetch recent notifications (global or per user).
     *
     * @param int $limit
     * @param int|null $userId
     * @return array
     */
    public function getRecentNotifications(int $limit = 5, ?int $userId = null): array
    {
        $sql = "SELECT n.notification_id, n.user_id, n.type, n.message, n.created_at, n.is_read,
                       u.userName
                FROM tbl_notifications n
                LEFT JOIN tbl_users u ON n.user_id = u.user_id";
        $params = [];

        if ($userId !== null) {
            $sql .= " WHERE n.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Mark a notification as read.
     *
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        $sql = "UPDATE tbl_notifications SET is_read = 1 WHERE notification_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $notificationId]);
    }

    /**
     * Delete a notification.
     *
     * @param int $notificationId
     * @return bool
     */
    public function deleteNotification(int $notificationId): bool
    {
        $sql = "DELETE FROM tbl_notifications WHERE notification_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $notificationId]);
    }

    /**
     * Count unread notifications (global or per user).
     *
     * @param int|null $userId
     * @return int
     */
    public function countUnread(?int $userId = null): int
    {
        $sql = "SELECT COUNT(*) FROM tbl_notifications WHERE is_read = 0";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
