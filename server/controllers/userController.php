<?php
require_once __DIR__ . '/../services/userService.php';

class UserController
{
    private $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function getUsers()
    {
        $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $result = $this->service->getUsers($page, $limit);

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
