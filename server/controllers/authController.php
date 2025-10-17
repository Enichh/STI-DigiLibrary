<?php
// server/controllers/authController.php

require_once __DIR__ . '/../services/authService.php';

/**
 * Controller for handling authentication API requests.
 *
 * This class manages user authentication, including login, signup, and password management.
 * It interacts with the AuthService to perform business logic and returns JSON responses.
 */
class AuthController
{
    private $service;

    /**
     * Creates an instance of AuthService.
     */
    public function __construct()
    {
        $this->service = new AuthService();
    }

    /**
     * Handles user login API request.
     *
     * @return void
     */
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

    /**
     * Handles user/admin signup API request.
     *
     * @return void
     */
    public function signup()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->signup($data);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Sends a verification email for user signup or reset.
     *
     * @return void
     */
    public function sendVerificationEmail()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'] ?? null;

        $result = $this->service->sendVerificationEmail($email);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Verifies a 6-digit code for user (student/admin) signup/login.
     *
     * @return void
     */
    public function verifyCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyCode($data['pin'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Verifies an admin-specific code for admin operations.
     *
     * @return void
     */
    public function verifyAdminCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyAdminCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Sends an unlock code by email for locked accounts.
     *
     * @return void
     */
    public function sendLockedCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->sendLockedCode($data['email'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Verifies an unlock code for locked account reactivation.
     *
     * @return void
     */
    public function verifyLockedCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyLockedCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Issues a new admin verification code for admin management.
     *
     * @return void
     */
    public function issueAdminCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->issueAdminCode($data['code'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Handles a change password request for any user/admin.
     *
     * @return void
     */
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

    /**
     * Sends a password reset code to the user's email.
     *
     * @return void
     */
    public function resetPassword()
    {
        error_log("AuthController::resetPassword called with input: " . file_get_contents("php://input"));
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->resetPassword($data['email'] ?? null);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Confirms a password reset using a code and sets the new password.
     *
     * @return void
     */
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
