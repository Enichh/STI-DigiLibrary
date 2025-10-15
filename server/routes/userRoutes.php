<?php
// server/routes/userRoutes.php

require_once __DIR__ . '/../controllers/userController.php';

function handleUserRoutes($requestPath, $method)
{
    global $matched;

    $controller = new UserController();

    $basePath = '/users.php';

    switch ($requestPath) {
        case $basePath:
            error_log("USER ROUTES: matched users");
            if ($method === 'GET') {
                $matched = true;
                $controller->getUsers();
                exit;
            }
            break;

        default:
            // Don't output here - let main handler decide
            error_log("USER ROUTES: NO MATCH for path '{$requestPath}'");
            break;
    }
}
