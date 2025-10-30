<?php
// app/controllers/notificationController.php

require_once __DIR__ . '/../services/notificationService.php';

class NotificationController
{
    private NotificationService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new NotificationService($pdo);
    }

    /**
     * GET /notifications/recent?limit=5
     */
    public function getRecent()
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;

        $notifications = $this->service->getRecentNotifications($limit, $userId);

        header('Content-Type: application/json');
        echo json_encode($notifications);
    }

    /**
     * POST /notifications
     * Body: { "userId": 1, "type": "system", "message": "..." }
     */
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $userId  = $data['userId'] ?? null;
        $type    = $data['type'] ?? 'system';
        $message = $data['message'] ?? '';

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message is required']);
            return;
        }

        $id = $this->service->createNotification($userId, $type, $message);

        header('Content-Type: application/json');
        echo json_encode(['notification_id' => $id]);
    }

    /**
     * PATCH /notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $success = $this->service->markAsRead((int)$id);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * DELETE /notifications/{id}
     */
    public function delete($id)
    {
        $success = $this->service->deleteNotification((int)$id);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * GET /notifications/unread-count
     */
    public function countUnread()
    {
        $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
        $count = $this->service->countUnread($userId);

        header('Content-Type: application/json');
        echo json_encode(['unread' => $count]);
    }
}
