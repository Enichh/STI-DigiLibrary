<?php
require_once __DIR__ . '/../models/userModel.php';

class UserService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

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
