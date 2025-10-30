<?php
require_once __DIR__ . '/../models/userModel.php';

/**
 * Service for handling user-related business logic.
 *
 * Acts as a middle layer between the controller and model,
 * coordinating data transformations and validation rules.
 */
class UserService
{
    private $userModel;

    /**
     * Initialize UserService with the UserModel instance.
     */
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Gets a paginated list of users.
     *
     * @param int $page The current page number.
     * @param int $limit The number of users per page.
     * @return array Returns user data and total count.
     */
    public function getUsers(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        try {
            $users = $this->userModel->getPaginatedUsers($limit, $offset);
            $total = $this->userModel->countUsers();

            return [
                "users" => $users,
                "total" => $total
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to load users: " . $e->getMessage());
        }
    }

    /**
     * Get a specific user by ID.
     *
     * @param int $id User ID.
     * @return array|null User data or null if not found.
     */
    public function getUserById(int $id): ?array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid user ID provided.");
        }

        try {
            return $this->userModel->getUserById($id);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to fetch user: " . $e->getMessage());
        }
    }

    /**
     * Get current user's profile based on token or session data.
     *
     * @param string $token Authorization token.
     * @return array User profile data.
     */
    public function getCurrentUserProfile(string $token): array
    {
        if (empty($token)) {
            throw new InvalidArgumentException("Missing authorization token.");
        }

        try {
            // In a production environment, validate and decode JWT.
            $userId = $this->validateTokenAndGetUserId($token);
            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                throw new RuntimeException("User profile not found.");
            }

            return $user;
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to fetch profile: " . $e->getMessage());
        }
    }

    /**
     * Update user information.
     *
     * @param int $id User ID.
     * @param array $data Updated user information.
     * @return array Updated user data.
     */
    public function updateUser(int $id, array $data): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid user ID.");
        }

        if (empty($data)) {
            throw new InvalidArgumentException("No data provided for update.");
        }

        try {
            $this->userModel->updateUser($id, $data);
            return $this->userModel->getUserById($id);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to update user: " . $e->getMessage());
        }
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id User ID.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function deleteUser(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid user ID.");
        }

        try {
            return $this->userModel->deleteUser($id);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to delete user: " . $e->getMessage());
        }
    }

    /**
     * Token validation helper (mock for future JWT implementation).
     *
     * @param string $token JWT token.
     * @return int User ID extracted from token.
     */
    private function validateTokenAndGetUserId(string $token): int
    {
        // Replace with real JWT validation in production.
        return (int) base64_decode($token);
    }

    /**
     * Gets the detailed student profile for display.
     *
     * @param int $userId
     * @return array|null
     */
    public function getStudentProfile(int $userId): ?array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("Invalid user ID.");
        }
        try {
            return $this->userModel->getStudentProfileByUserId($userId);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to fetch student profile: " . $e->getMessage());
        }
    }

    public function getBorrowerCount(): int
    {
        return $this->userModel->getBorrowerCount();
    }
}
