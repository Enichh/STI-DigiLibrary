<?php

require_once __DIR__ . '/../index.php';

$authController = new AuthController();
$authController->confirmResetPassword();
