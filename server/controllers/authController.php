<?php
// server/controllers/authController.php

require_once __DIR__ . '/../services/authService.php';

// Controller for handling authentication API requests
class AuthController
{
    private $service;

    // Create an instance of AuthService for business logic
    public function __construct()
    {
        $this->service = new AuthService();
    }

    // Handle user login API request
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->login(
            $data['userName'] ?? null,
            $data['password'] ?? null,
            $data['expectedRole'] ?? null,
            $data['captchaToken'] ?? null
        );
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Handle user/admin signup API request
    public function signup()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->signup($data);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Send verification email for user signup or reset
    public function sendVerificationEmail()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'] ?? null;

        $result = $this->service->sendVerificationEmail($email);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Verify 6-digit code for user (student/admin) signup/login
    public function verifyCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyCode($data['pin'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Verify admin-specific code for admin operations
    public function verifyAdminCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyAdminCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Send unlock code by email for locked accounts
    public function sendLockedCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->sendLockedCode($data['email'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Verify unlock code for locked account reactivation
    public function verifyLockedCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyLockedCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Issue a new admin verification code (for admin management)
    public function issueAdminCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->issueAdminCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Handle change password request for any user/admin
    public function changePassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->changePassword(
            $data['email'] ?? null,
            $data['oldPassword'] ?? null,
            $data['newPassword'] ?? null
        );

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Send password reset code to user's email
    public function resetPassword()
    {
        error_log("AuthController::resetPassword called with input: " . file_get_contents("php://input"));
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->resetPassword($data['email'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // Confirm password reset using code and set the new password
    public function confirmResetPassword()
    {
        error_log("AuthController::confirmResetPassword called with input: " . file_get_contents("php://input"));
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->confirmResetPassword(
            $data['email'] ?? null,
            $data['code'] ?? null,
            $data['newPassword'] ?? null
        );

        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
