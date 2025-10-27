<?php
// app/controllers/authController.php

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
    /**
     * Verifies a user-submitted verification code.
     *
     * @return void
     */
    public function verifyCode()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->verifyCode($data['pin'] ?? null);

        // Ensure consistent response format
        $response = [
            'success' => !isset($result['error']),
            'message' => $result['message'] ?? ($result['error'] ?? 'An error occurred')
        ];

        if (isset($result['error'])) {
            $response['error'] = $result['error'];
        }

        // Add any additional data from the result
        foreach ($result as $key => $value) {
            if (!in_array($key, ['success', 'message', 'error'])) {
                $response[$key] = $value;
            }
        }

        header('Content-Type: application/json');
        http_response_code($response['success'] ? 200 : 400);
        echo json_encode($response);
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
