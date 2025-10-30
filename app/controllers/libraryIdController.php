<?php
// libraryIdController.php

class LibraryIdController
{
    private $libraryIdService;

    // Dependency Injection of the service
    public function __construct(LibraryIdService $libraryIdService)
    {
        $this->libraryIdService = $libraryIdService;
    }

    // Endpoint: POST /library-ids/request (user requests a library/digital ID)
    public function requestLibraryId()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $remarks = $input['remarks'] ?? null;

        if (!$userId) {
            http_response_code(400);
            return ['error' => 'Missing user_id'];
        }
        try {
            $this->libraryIdService->requestLibraryId($userId, $remarks);
            return ['success' => true, 'message' => 'Library ID request created'];
        } catch (Exception $e) {
            http_response_code(409);
            return ['error' => $e->getMessage()];
        }
    }

    // Endpoint: GET /library-ids/user/{userId} (fetch current user's ID for card display)
    public function getUserLibraryId($userId)
    {
        $id = $this->libraryIdService->getLatestLibraryId($userId);
        if ($id) {
            return ['success' => true, 'libraryId' => $id];
        }
        http_response_code(404);
        return ['error' => 'No library ID found'];
    }

    // Endpoint: POST /library-ids/{id}/approve (admin approves a pending request)
    public function approveLibraryId($libraryIdId, $adminUserId)
    {
        try {
            $this->libraryIdService->approveLibraryId($libraryIdId, $adminUserId);
            return ['success' => true, 'message' => 'Library ID approved'];
        } catch (Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    // Endpoint: POST /library-ids/{id}/status (admin updates status: expired/revoked)
    public function updateStatus($libraryIdId)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? null;
        if (!$status) {
            http_response_code(400);
            return ['error' => 'Missing status value'];
        }
        try {
            $this->libraryIdService->updateStatus($libraryIdId, $status);
            return ['success' => true];
        } catch (Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    // Endpoint: GET /library-ids/{id} (fetch any library ID by pk)
    public function getLibraryIdById($libraryIdId)
    {
        $id = $this->libraryIdService->getLibraryIdById($libraryIdId);
        if ($id) {
            return ['success' => true, 'libraryId' => $id];
        }
        http_response_code(404);
        return ['error' => 'Not found'];
    }

    // Endpoint: GET /library-ids (admin: list all IDs, or only by status)
    public function listLibraryIds($status = null)
    {
        $result = $this->libraryIdService->listLibraryIds($status);
        return ['success' => true, 'items' => $result];
    }

    public function getLibraryIdCount()
    {
        $count = $this->libraryIdService->getLibraryIdCount();
        http_response_code(200);
        return ['success' => true, 'count' => $count];
    }

    // Endpoint: DELETE /library-ids/{id} (admin: delete library ID record)
    public function deleteLibraryId($libraryIdId)
    {
        $this->libraryIdService->deleteLibraryId($libraryIdId);
        return ['success' => true];
    }
}
