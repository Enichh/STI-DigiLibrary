<?php
// app/controllers/userController.php
require_once __DIR__ . '/../services/userService.php';

/**
 * Controller for handling user-related API requests.
 *
 * Manages operations such as fetching, updating, and deleting user data.
 */
class UserController
{
    private $service;

    /**
     * Initialize UserController with a service instance.
     */
    public function __construct()
    {
        $this->service = new UserService();
    }

    /**
     * Handles GET /users
     * Fetches all users with pagination (?page=1&limit=10)
     */
    public function getUsers(): void
    {
        try {
            $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

            $result = $this->service->getUsers($page, $limit);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'success',
                'message' => 'User list fetched successfully',
                'data'    => $result
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid request: ' . $e->getMessage()
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles GET /users/{id}
     * Fetches a specific user by ID.
     */
    public function getUserById(int $id): void
    {
        try {
            $user = $this->service->getUserById($id);

            if (!$user) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User fetched successfully',
                'data'    => $user
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles GET /users/profile
     * Fetches the currently authenticated user's profile.
     */
    public function getCurrentUserProfile(): void
    {
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if (!$token) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                return;
            }

            $user = $this->service->getCurrentUserProfile($token);

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User profile fetched successfully',
                'data'    => $user
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles PUT /users/{id}
     * Updates an existing user by ID.
     */
    public function updateUser(int $id): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
                return;
            }

            $updatedUser = $this->service->updateUser($id, $data);

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User updated successfully',
                'data'    => $updatedUser
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handles DELETE /users/{id}
     * Deletes a user by ID.
     */
    public function deleteUser(int $id): void
    {
        try {
            $success = $this->service->deleteUser($id);

            if (!$success) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User deleted successfully'
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function getBorrowerCount(): void
    {
        $count = $this->service->getBorrowerCount();

        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "count"   => $count
        ]);
    }
}
