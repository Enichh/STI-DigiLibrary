<?php
require_once __DIR__ . '/../services/userService.php';

/**
 * Controller for handling user-related API requests.
 *
 * This class manages fetching user data.
 * It interacts with the UserService to perform business logic and returns JSON responses.
 */
class UserController
{
    private $service;

    /**
     * Creates an instance of UserService.
     */
    public function __construct()
    {
        $this->service = new UserService();
    }

    /**
     * Handles GET requests for users.
     *
     * Fetches a paginated list of users.
     *
     * @return void
     */
    public function getUsers()
    {
        $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $result = $this->service->getUsers($page, $limit);

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
