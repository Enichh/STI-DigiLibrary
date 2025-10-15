<?php
// server/services/authService.php
use App\Utils\Logger;

require_once __DIR__ . '/../models/userModel.php';
require_once __DIR__ . '/emailService.php';
require_once __DIR__ . '/../utils/generatePassword.php';

class AuthService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login($userName, $password, $expectedRole, $captchaToken)
    {
        if (!$userName || !$password || !$captchaToken) {
            return ["error" => "Username, password, and CAPTCHA are required"];
        }

        $secret = $_ENV['RECAPTCHA_SECRET'] ?? null;
        if (!$secret) {
            return ["error" => "Server misconfigured: missing reCAPTCHA secret"];
        }

        // Debug logging for troubleshooting
        error_log("DEBUG: reCAPTCHA verification starting");
        error_log("DEBUG: captchaToken length: " . strlen($captchaToken));
        error_log("DEBUG: captchaToken prefix: " . substr($captchaToken, 0, 20) . "...");
        error_log("DEBUG: secret key length: " . strlen($secret));
        error_log("DEBUG: secret key prefix: " . substr($secret, 0, 10) . "...");

        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'secret'   => $secret,
                    'response' => $captchaToken
                ])
            ]
        ]));

        error_log("DEBUG: Google API response received, length: " . strlen($response));

        $captchaResult = json_decode($response, true);

        // Detailed error logging
        if (!$captchaResult) {
            error_log("ERROR: Failed to decode Google API response as JSON");
            error_log("ERROR: Raw response: " . $response);
            return ["error" => "CAPTCHA verification failed - invalid response from Google"];
        }

        error_log("DEBUG: Google API response decoded successfully");
        error_log("DEBUG: captchaResult['success']: " . ($captchaResult['success'] ? 'true' : 'false'));

        if (isset($captchaResult['error-codes'])) {
            error_log("DEBUG: Google error codes: " . implode(', ', $captchaResult['error-codes']));
        }

        if (!$captchaResult['success']) {
            $errorMsg = "CAPTCHA verification failed";
            if (isset($captchaResult['error-codes'])) {
                $errorMsg .= " - Google errors: " . implode(', ', $captchaResult['error-codes']);
            }
            error_log("ERROR: " . $errorMsg);
            return ["error" => $errorMsg];
        }

        error_log("DEBUG: reCAPTCHA verification successful");

        $user = $this->userModel->findByUserName($userName);
        if (!$user) {
            return ["error" => "Invalid credentials"];
        }

        if ($user['accountStatus'] === 'Locked') {
            return [
                "error"        => "Account is locked",
                "requiresCode" => true,
                "redirectTo"   => "/locked-code",
                "email"        => $user['email']
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = ($user['failedAttempts'] ?? 0) + 1;
            $lock = $attempts >= 3;
            $this->userModel->updateFailedAttempts($user['user_id'], $attempts, $lock);
            return ["error" => "Invalid credentials"];
        }

        if ($expectedRole && $user['role_id']) {
            $roleName = $this->userModel->getRoleNameById($user['role_id']);
            if ($roleName !== $expectedRole && $roleName !== 'superadmin') {
                return ["error" => "Invalid credentials"];
            }
        }

        $this->userModel->resetFailedAttempts($user['user_id']);

        // Send verification email for login
        $emailResult = $this->sendVerificationEmail($user['email']);
        if (isset($emailResult['error'])) {
            return ["error" => "Failed to send verification email"];
        }

        $roleName = $this->userModel->getRoleNameById($user['role_id']);
        $_SESSION['pending_user_id'] = $user['user_id'];
        $_SESSION['pending_user_role'] = $roleName;

        $response = [
            "message" => "Verification email sent",
            "requiresVerification" => true,
            "role" => $roleName,
            "userName" => $user['userName'],
            "email" => $user['email']
        ];

        error_log("Login response: " . json_encode($response));
        return $response;
    }

    public function signup($data)
    {
        $userName        = $data['userName'] ?? null;
        $email           = $data['email'] ?? null;
        $password        = $data['password'] ?? null;
        $confirmPassword = $data['confirmPassword'] ?? null;
        $roleName        = $data['role'] ?? null;
        $adminCode       = $data['adminCode'] ?? null;


        if (!$userName || !$email || !$password || !$confirmPassword || !$roleName) {
            return ["error" => "userName, email, password, confirmPassword and role are required"];
        }

        if ($password !== $confirmPassword) {
            return ["error" => "Passwords do not match"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Invalid email format"];
        }

        if (strlen($password) < 8) {
            return ["error" => "Password must be at least 8 characters long"];
        }

        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
            return ["error" => "Password must include at least one letter and one special character"];
        }

        if ($this->userModel->findByUserName($userName)) {
            return ["error" => "Username already in use"];
        }
        if ($this->userModel->findByEmail($email)) {
            return ["error" => "Email already in use"];
        }

        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $roleId = $this->userModel->getRoleIdByName($roleName);
        if (!$roleId) {
            return ["error" => "Invalid role"];
        }

        if ($roleName === 'admin') {
            if (!$adminCode) {
                return ["error" => "Superadmin code is required for admin signup"];
            }
            if (!$this->userModel->checkAdminCode($adminCode)) {
                return ["error" => "Invalid or expired superadmin code"];
            }
            $this->userModel->consumeAdminCode($adminCode);
        }

        if ($roleName === 'student') {
            if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== $email) {
                return ["error" => "Email verification required before signup"];
            }
            unset($_SESSION['email_verified']);
        }

        // 6. Insert user
        try {
            $userId = $this->userModel->createUser($userName, $email, $hashedPassword, $roleId);

            return [
                "message" => "Signup successful",
                "user" => [
                    "id"       => $userId,
                    "userName" => $userName,
                    "email"    => $email,
                    "role"     => $roleName
                ]
            ];
        } catch (Exception $e) {
            return ["error" => "Signup failed: " . $e->getMessage()];
        }
    }

    public function sendVerificationEmail($email): array
    {
        if (!$email) {
            Logger::warning("Attempted to send verification email with no email provided");
            return ["error" => "Email is required"];
        }

        try {
            $code = (string)(new EmailService())->sendVerificationEmail($email);

            $_SESSION['verificationCode']    = $code;
            $_SESSION['verificationEmail']   = $email;
            $_SESSION['verificationExpires'] = time() + (5 * 60);

            Logger::info("Verification code generated and stored", [
                "email"     => $email,
                "code"      => '***' . substr($code, -2),
                "expires"   => date('Y-m-d H:i:s', $_SESSION['verificationExpires']),
                "sessionId" => session_id()
            ]);

            return ["message" => "Verification code sent to $email"];
        } catch (\Exception $e) {
            Logger::error("Failed to send verification email", [
                "email" => $email,
                "error" => $e->getMessage()
            ]);
            return ["error" => "Failed to send verification code"];
        }
    }

    public function verifyCode(?string $pin): array
    {
        Logger::debug("Starting verification attempt", [
            "submitted_pin" => $pin ? '***' . substr($pin, -2) : null,
            "session_id"    => session_id()
        ]);

        $storedCode = $_SESSION['verificationCode'] ?? null;
        $expiresAt  = $_SESSION['verificationExpires'] ?? null;
        $email      = $_SESSION['verificationEmail'] ?? null;

        // Debug log to inspect stored code
        Logger::debug("Stored verification code details", [
            "type" => $storedCode !== null ? gettype($storedCode) : 'null',
            "value" => $storedCode,
            "session_keys" => array_keys($_SESSION)
        ]);

        if (!$storedCode) {
            Logger::warning("Verification failed: no code in session", [
                "email" => $email,
                "session_keys" => array_keys($_SESSION)
            ]);
            return ["error" => "Verification code not found"];
        }

        if ($expiresAt && time() > $expiresAt) {
            Logger::warning("Verification failed: code expired", [
                "email"     => $email,
                "expiresAt" => date('Y-m-d H:i:s', $expiresAt),
                "now"       => date('Y-m-d H:i:s')
            ]);
            return ["error" => "Verification code expired"];
        }

        if ($pin !== (string)$storedCode) {
            Logger::warning("Verification failed: code mismatch", [
                "email"    => $email,
                "expected" => '***' . substr($storedCode, -2),
                "got"      => $pin ? '***' . substr($pin, -2) : null
            ]);
            return ["error" => "Invalid verification code"];
        }

        // Mark email as verified before clearing session
        $_SESSION['email_verified'] = $email;

        Logger::info("Verification successful", [
            "email" => $email,
            "session_id" => session_id()
        ]);

        unset(
            $_SESSION['verificationCode'],
            $_SESSION['verificationEmail'],
            $_SESSION['verificationExpires']
        );

        if (isset($_SESSION['pending_user_id'])) {
            $_SESSION['user_id'] = $_SESSION['pending_user_id'];
            $_SESSION['role']    = $_SESSION['pending_user_role'] ?? null;

            Logger::info("Login verification completed", [
                "user_id" => $_SESSION['user_id'],
                "role"    => $_SESSION['role']
            ]);

            unset($_SESSION['pending_user_id'], $_SESSION['pending_user_role']);

            return ["message" => "Login verification successful"];
        }

        return ["message" => "Signup verification successful"];
    }

    public function verifyAdminCode(?string $code): array
    {
        if (!$code) {
            return ["error" => "Verification code is required"];
        }

        if (!$this->userModel->checkAdminCode($code)) {
            return ["error" => "Invalid or expired verification code"];
        }

        $this->userModel->consumeAdminCode($code);

        return ["success" => true];
    }

    public function issueAdminCode(?string $code = null): array
    {
        if (!$code) {
            return ["error" => "Code is required"];
        }

        try {
            $this->userModel->consumeAdminCode($code);
            $this->userModel->issueAdminCode($code);

            return [
                "message" => "Verification code issued",
                "code"    => $code
            ];
        } catch (Exception $e) {
            return ["error" => "Failed to issue code: " . $e->getMessage()];
        }
    }

    public function sendLockedCode(?string $email): array
    {
        if (!$email) {
            return ["error" => "Email is required"];
        }

        try {
            $code = random_int(100000, 999999);
            $tempPassword = generateSecurePassword();

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                return ["error" => "User not found"];
            }

            $hashed = password_hash($tempPassword, PASSWORD_ARGON2ID);
            $this->userModel->updatePassword($user['user_id'], $hashed);

            $emailService = new EmailService();
            $emailService->sendLockedPasscode($email, $code, $tempPassword);

            $_SESSION['lockedCode']    = $code;
            $_SESSION['lockedEmail']   = $email;
            $_SESSION['lockedExpires'] = time() + (5 * 60);

            return ["message" => "Locked account instructions sent to $email"];
        } catch (Exception $e) {
            return ["error" => "Failed to send locked code: " . $e->getMessage()];
        }
    }

    public function verifyLockedCode(?string $code): array
    {
        $storedCode   = $_SESSION['lockedCode'] ?? null;
        $storedEmail  = $_SESSION['lockedEmail'] ?? null;
        $expiresAt    = $_SESSION['lockedExpires'] ?? null;

        if (!$storedCode || !$storedEmail || !$expiresAt) {
            return ["error" => "Invalid or expired verification code"];
        }

        if (time() > $expiresAt) {
            return ["error" => "Verification code expired"];
        }

        if (trim((string)$code) !== trim((string)$storedCode)) {
            return ["error" => "Invalid verification code"];
        }

        try {
            $user = $this->userModel->findByEmail($storedEmail);
            if (!$user) {
                return ["error" => "User not found"];
            }

            // Use model method instead of raw query
            $this->userModel->unlockAccount($user['user_id']);

            // Clean up session
            unset($_SESSION['lockedCode'], $_SESSION['lockedEmail'], $_SESSION['lockedExpires']);

            return ["message" => "Account unlocked successfully"];
        } catch (Exception $e) {
            return ["error" => "Server error during verification: " . $e->getMessage()];
        }
    }

    public function changePassword(?string $email, ?string $oldPassword, ?string $newPassword): array
    {
        if (!$email || !$oldPassword || !$newPassword) {
            return ["error" => "Email, old password, and new password are required"];
        }

        $normalizedEmail = strtolower(trim($email));

        // Password strength check
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
            return ["error" => "Password must be at least 8 characters and include uppercase, lowercase, number, and special character"];
        }

        try {
            $user = $this->userModel->findByEmail($normalizedEmail);
            if (!$user) {
                return ["error" => "Invalid credentials"];
            }

            if (!isset($user['password_hash'])) {
                return ["error" => "Account data is invalid"];
            }

            // Verify old password
            if (!password_verify($oldPassword, $user['password_hash'])) {
                return ["success" => false, "error" => "Incorrect current password"];
            }

            // Prevent reusing the same password
            if ($oldPassword === $newPassword || password_verify($newPassword, $user['password_hash'])) {
                return ["error" => "New password must be different from the old password"];
            }

            // Hash and update
            $hashedNewPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            $this->userModel->updatePassword($user['user_id'], $hashedNewPassword);

            return ["success" => true, "message" => "Password updated successfully"];
        } catch (Exception $e) {
            return ["error" => "Password change failed: " . $e->getMessage()];
        }
    }

    public function resetPassword(?string $email): array
    {
        if (!$email) {
            return ["error" => "Email is required"];
        }

        $normalizedEmail = strtolower(trim($email));

        if (isset($_SESSION['resetExpires']) && time() < $_SESSION['resetExpires']) {
            return ["error" => "Please wait before requesting another reset"];
        }

        try {
            $user = $this->userModel->findByEmail($normalizedEmail);
            if (!$user) {
                return ["error" => "Email not registered"];
            }

            $code = random_int(100000, 999999);

            $emailService = new EmailService();
            $emailService->sendPasswordResetEmail($normalizedEmail, $code);

            $_SESSION['resetCode']   = $code;
            $_SESSION['resetEmail']  = $normalizedEmail;
            $_SESSION['resetExpires'] = time() + (5 * 60); // 5 minutes

            return ["message" => "Reset code sent"];
        } catch (Exception $e) {
            return ["error" => "Internal server error: " . $e->getMessage()];
        }
    }

    public function confirmResetPassword(?string $email, ?string $code, ?string $newPassword): array
    {
        $normalizedEmail = $email ? strtolower(trim($email)) : null;

        if (!$normalizedEmail || !$code || !$newPassword) {
            return ["error" => "Email, code, and new password are required"];
        }

        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $newPassword)) {
            return ["error" => "Password must include at least one letter and one special character"];
        }

        if (
            !isset($_SESSION['resetCode'], $_SESSION['resetEmail'], $_SESSION['resetExpires']) ||
            $_SESSION['resetEmail'] !== $normalizedEmail ||
            (string)$_SESSION['resetCode'] !== (string)$code ||
            time() > $_SESSION['resetExpires']
        ) {
            return ["error" => "Invalid or expired reset code"];
        }

        try {
            $user = $this->userModel->findByEmail($normalizedEmail);
            if (!$user) {
                return ["error" => "User not found"];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);

            // Delegate DB update to model
            $this->userModel->updatePassword($user['user_id'], $hashedPassword);

            // Clear reset session
            unset($_SESSION['resetCode'], $_SESSION['resetEmail'], $_SESSION['resetExpires']);

            return ["message" => "Password reset successful"];
        } catch (Exception $e) {
            return ["error" => "Password reset failed: " . $e->getMessage()];
        }
    }
}
