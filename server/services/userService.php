<?php
require_once __DIR__ . '/../models/userModel.php';

/**
 * Service for handling user-related business logic.
 *
 * This class provides methods for fetching user data and acts as an intermediary
 * between the UserController and the UserModel.
 */
class UserService
{
    private $userModel;

    /**
     * Creates an instance of UserService and initializes the UserModel.
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
     * @return array An array containing the users and the total count.
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
        } catch (Exception $e) {
            return ["error" => "Failed to load users: " . $e->getMessage()];
        }
    }
}
