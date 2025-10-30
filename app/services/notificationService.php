<?php
// app/services/notificationService.php

require_once __DIR__ . '/../models/notificationModel.php';

/**
 * Service for handling notification-related business logic.
 * Intermediary between NotificationController and NotificationModel.
 */
class NotificationService
{
    private NotificationModel $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new NotificationModel($pdo);
    }

    /**
     * Create a new notification.
     *
     * @param int|null $userId
     * @param string $type
     * @param string $message
     * @return int|null
     */
    public function createNotification(?int $userId, string $type, string $message): ?int
    {
        return $this->model->createNotification($userId, $type, $message);
    }

    /**
     * Get recent notifications (global or per user).
     *
     * @param int $limit
     * @param int|null $userId
     * @return array
     */
    public function getRecentNotifications(int $limit = 5, ?int $userId = null): array
    {
        return $this->model->getRecentNotifications($limit, $userId);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->model->markAsRead($notificationId);
    }

    /**
     * Delete a notification.
     *
     * @param int $notificationId
     * @return bool
     */
    public function deleteNotification(int $notificationId): bool
    {
        return $this->model->deleteNotification($notificationId);
    }

    /**
     * Count unread notifications (global or per user).
     *
     * @param int|null $userId
     * @return int
     */
    public function countUnread(?int $userId = null): int
    {
        return $this->model->countUnread($userId);
    }
}
