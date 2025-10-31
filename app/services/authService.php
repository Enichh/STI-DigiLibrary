<?php
// app/services/authService.php
use App\Utils\Logger;

require_once __DIR__ . '/../models/userModel.php';
require_once __DIR__ . '/emailService.php';
require_once __DIR__ . '/../utils/generatePassword.php';

/**
 * Service for handling authentication logic.
 *
 * This class provides methods for user login, signup, password management, and verification.
 * It interacts with the UserModel and EmailService to perform its tasks.
 */
class AuthService
{
    private $userModel;

    /**
     * Creates an instance of AuthService and initializes the UserModel.
     */
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Handles user login.
     *
     * @param string $userName The user's username.
     * @param string $password The user's password.
     * @param string $expectedRole The expected role of the user.
     * @param string $captchaToken The reCAPTCHA token.
     * @return array An array containing the result of the login attempt.
     */
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
            $user = $this->userModel->findByEmail($userName);
        }

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
            if ($roleName !== $expectedRole) {
                return ["error" => "Invalid credentials $roleName $expectedRole"];
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

    /**
     * Handles user signup.
     *
     * @param array $data An associative array of signup data.
     * @return array An array containing the result of the signup attempt.
     */
    public function signup($data)
    {
        Logger::info('Starting signup process', ['data_keys' => array_keys($data)]);
        $email           = $data['email'] ?? null;
        $password        = $data['password'] ?? null;
        $confirmPassword = $data['confirmPassword'] ?? null;
        $roleName = 'student'; // Only allow student signups
        $firstName       = $data['firstName'] ?? null;
        $middleName      = $data['middleName'] ?? null;
        $lastName        = $data['lastName'] ?? null;
        $suffix          = $data['suffix'] ?? null;
        $studentNumber   = $data['studentNumber'] ?? null;

        // Use firstName as userName
        $userName = $firstName;


        if (!$firstName || !$email || !$password || !$confirmPassword || !$roleName || !$lastName) {
            $missing = [];
            if (!$firstName) $missing[] = 'firstName';
            if (!$email) $missing[] = 'email';
            if (!$password) $missing[] = 'password';
            if (!$confirmPassword) $missing[] = 'confirmPassword';
            if (!$lastName) $missing[] = 'lastName';

            $error = "Missing required fields: " . implode(', ', $missing);
            Logger::error('Signup validation failed', ['error' => $error, 'provided_data' => [
                'firstName' => $firstName ? 'provided' : 'missing',
                'email' => $email ? 'provided' : 'missing',
                'password' => $password ? 'provided' : 'missing',
                'confirmPassword' => $confirmPassword ? 'provided' : 'missing',
                'lastName' => $lastName ? 'provided' : 'missing'
            ]]);
            return ["error" => $error];
        }

        if ($roleName === 'student' && !$studentNumber) {
            return ["error" => "studentNumber is required for student signup"];
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

        if ($roleName === 'student' && $studentNumber) {
            if (!preg_match('/^02000\d{6}$/', $studentNumber)) {
                return ["error" => "Student number must be 11 digits starting with 02000"];
            }
        }

        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $roleId = $this->userModel->getRoleIdByName($roleName);
        Logger::debug('Retrieved role ID', ['roleName' => $roleName, 'roleId' => $roleId]);
        if (!$roleId) {
            Logger::error('Invalid role specified', ['roleName' => $roleName]);
            return ["error" => "Invalid role"];
        }

        if ($roleName === 'student') {
            $isVerified = isset($_SESSION['email_verified']) && $_SESSION['email_verified'] === $email;
            Logger::debug('Email verification check', [
                'email' => $email,
                'session_email_verified' => $_SESSION['email_verified'] ?? 'not set',
                'is_verified' => $isVerified
            ]);

            if (!$isVerified) {
                Logger::warning('Email not verified', [
                    'email' => $email,
                    'session_keys' => array_keys($_SESSION)
                ]);
                return ["error" => "Email verification required before signup"];
            }
            unset($_SESSION['email_verified']);
        }

        // 6. Insert user
        try {
            Logger::info('Attempting to create user', [
                'userName' => $userName,
                'email' => $email,
                'roleId' => $roleId,
                'hasPassword' => !empty($hashedPassword)
            ]);

            $userId = $this->userModel->createUser(
                $userName,
                $email,
                $hashedPassword,
                $roleId,
                $firstName,
                $middleName,
                $lastName,
                $suffix,
                $studentNumber
            );

            Logger::info('User created successfully', ['userId' => $userId]);

            // Set email_verified so that verifyCode can auto-login the new user
            $_SESSION['email_verified'] = $email;

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

    /**
     * Sends a verification email.
     *
     * @param string $email The email address to send the verification code to.
     * @return array An array containing the result of the operation.
     */
    public function sendVerificationEmail($email): array
    {
        if (!$email) {
            Logger::warning("Attempted to send verification email with no email provided");
            return ["error" => "Email is required"];
        }

        try {
            // Generate and log the verification code
            $code = (string)(new EmailService())->sendVerificationEmail($email);

            // Log before setting session
            error_log("=== INSERTING VERIFICATION CODE INTO SESSION ===");
            error_log("Email: " . $email);
            error_log("Generated Code: " . $code);
            error_log("Session ID: " . session_id());
            error_log("Current Session Data: " . print_r($_SESSION, true));
            error_log("=============================================");

            // Set session variables
            $_SESSION['verificationCode']    = $code;
            $_SESSION['verificationEmail']   = $email;
            $_SESSION['verificationExpires'] = time() + (5 * 60);

            // Log after setting session
            Logger::info("Verification code generated and stored", [
                "email"     => $email,
                "code"      => '***' . substr($code, -2),
                "expires"   => date('Y-m-d H:i:s', $_SESSION['verificationExpires']),
                "sessionId" => session_id(),
                "sessionData" => [
                    'verificationCodeExists' => isset($_SESSION['verificationCode']),
                    'verificationEmail' => $_SESSION['verificationEmail'] ?? 'not set',
                    'verificationExpires' => $_SESSION['verificationExpires'] ?? 'not set'
                ]
            ]);

            // Verify the code was actually stored in the session
            if (!isset($_SESSION['verificationCode']) || $_SESSION['verificationCode'] !== $code) {
                error_log("ERROR: Verification code was not properly stored in session!");
                error_log("Stored in session: " . ($_SESSION['verificationCode'] ?? 'NOT FOUND'));
                error_log("Expected code: " . $code);
                return ["error" => "Failed to store verification code"];
            }

            return ["message" => "Verification code sent to $email"];
        } catch (\Exception $e) {
            Logger::error("Failed to send verification email", [
                "email" => $email,
                "error" => $e->getMessage()
            ]);
            return ["error" => "Failed to send verification code"];
        }
    }

    /**
     * Verifies a user-submitted code (PIN) for login or signup.
     *
     * @param string|null $pin The 6-digit PIN submitted by the user.
     * @return array Response containing result or error.
     */
    public function verifyCode(?string $pin): array
    {
        // Enhanced session verification logging
        error_log('=== VERIFY CODE REQUEST RECEIVED ===');
        error_log('Timestamp: ' . date('Y-m-d H:i:s'));
        error_log('Session ID: ' . session_id());
        error_log('Session Status: ' . session_status());
        error_log('Submitted PIN: ' . ($pin ? '***' . substr($pin, -2) : 'NULL'));
        error_log('Session Data: ' . print_r($_SESSION, true));
        error_log('===================================');

        // Ensure we have a valid session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $errorMsg = 'No active session during verification';
            error_log('ERROR: ' . $errorMsg);
            Logger::error($errorMsg, [
                'session_status' => session_status(),
                'session_id' => session_id(),
                'headers' => getallheaders()
            ]);
            return [
                'success' => false,
                'error' => 'No active session. Please try again.'
            ];
        }

        // Log verification attempt with detailed session info
        Logger::debug("Starting verification attempt", [
            "submitted_pin" => $pin ? '***' . substr($pin, -2) : null,
            "session_id" => session_id(),
            "session_status" => session_status(),
            "session_cookie_params" => session_get_cookie_params(),
            "http_only_cookies" => headers_list()
        ]);

        // Retrieve and inspect session verification data
        $storedCode = $_SESSION['verificationCode'] ?? null;
        $expiresAt  = $_SESSION['verificationExpires'] ?? null;
        $email      = $_SESSION['verificationEmail'] ?? null;

        // Enhanced logging of stored code details
        $debugInfo = [
            "type" => $storedCode !== null ? gettype($storedCode) : 'null',
            "value" => $storedCode ? '***' . substr($storedCode, -2) : 'NULL',
            "session_keys" => array_keys($_SESSION),
            "session_data_size" => strlen(serialize($_SESSION)) . ' bytes',
            "expires_at" => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'not set',
            "current_time" => date('Y-m-d H:i:s')
        ];

        Logger::debug("Stored verification code details", $debugInfo);
        error_log('VERIFICATION DEBUG: ' . json_encode($debugInfo));

        // Verification code and expiry checks
        if (!$storedCode) {
            Logger::warning("Verification failed: no code in session", [
                "email" => $email,
                "session_keys" => array_keys($_SESSION)
            ]);
            return [
                "success" => false,
                "error" => "Verification code not found"
            ];
        }

        if ($expiresAt && time() > $expiresAt) {
            Logger::warning("Verification failed: code expired", [
                "email"     => $email,
                "expiresAt" => date('Y-m-d H:i:s', $expiresAt),
                "now"       => date('Y-m-d H:i:s')
            ]);
            unset($_SESSION['verificationCode'], $_SESSION['verificationEmail'], $_SESSION['verificationExpires']);
            return [
                "success" => false,
                "error" => "Verification code expired"
            ];
        }

        // Detailed code verification logging
        error_log('=== CODE VERIFICATION ===');
        error_log('Session ID: ' . session_id());
        error_log('Stored Code: ' . ($storedCode ? '***' . substr($storedCode, -2) : 'NULL'));
        error_log('Submitted Code: ' . ($pin ? '***' . substr($pin, -2) : 'NULL'));
        error_log('Code Match: ' . ($pin === (string)$storedCode ? 'YES' : 'NO'));
        error_log('=========================');

        if ($pin !== (string)$storedCode) {
            $mismatchDetails = [
                "email" => $email,
                "expected" => $storedCode ? '***' . substr($storedCode, -2) : 'NULL',
                "got" => $pin ? '***' . substr($pin, -2) : 'NULL',
                "session_id" => session_id(),
                "session_data" => [
                    'verificationCodeExists' => isset($_SESSION['verificationCode']),
                    'verificationEmail' => $_SESSION['verificationEmail'] ?? 'not set',
                    'verificationExpires' => $_SESSION['verificationExpires'] ?? 'not set'
                ]
            ];

            Logger::warning("Verification failed: code mismatch", $mismatchDetails);
            error_log('CODE MISMATCH: ' . json_encode($mismatchDetails));

            return [
                "success" => false,
                "error" => "Invalid verification code"
            ];
        }

        // Log before marking as verified
        error_log('=== VERIFICATION SUCCESSFUL ===');
        error_log('Session ID: ' . session_id());
        error_log('Email being verified: ' . $email);
        error_log('Session Data Before Cleanup: ' . print_r($_SESSION, true));

        // Mark email as verified
        $_SESSION['email_verified'] = $email;

        // Log after setting verification
        error_log('Email verification status set: ' . ($_SESSION['email_verified'] ?? 'NOT SET'));

        // Log the cleanup process
        error_log('Cleaning up verification data from session');
        unset(
            $_SESSION['verificationCode'],
            $_SESSION['verificationEmail'],
            $_SESSION['verificationExpires']
        );

        // Verify cleanup
        error_log('Verification data after cleanup: ' .
            (isset($_SESSION['verificationCode']) ? 'STILL EXISTS' : 'REMOVED'));
        error_log('Session Data After Cleanup: ' . print_r($_SESSION, true));
        error_log('======================================');

        // Log successful verification
        Logger::info("Verification successful", [
            "email" => $email,
            "session_id" => session_id(),
            "session_data" => [
                'email_verified' => $_SESSION['email_verified'] ?? 'not set',
                'verificationCodeExists' => isset($_SESSION['verificationCode']),
                'session_keys' => array_keys($_SESSION)
            ]
        ]);

        // If user is pending login, fetch complete data and set session for login
        if (isset($_SESSION['pending_user_id'])) {
            $user = $this->userModel->getUserById($_SESSION['pending_user_id']);
            if ($user) {
                // Security: Regenerate session ID after verification, before privilege elevation
                session_regenerate_id(true);

                // Set full (sanitized) user session state
                $_SESSION['user_id']    = (int)$user['user_id'];
                $_SESSION['user_name']  = htmlspecialchars($user['userName'] ?? 'User', ENT_QUOTES, 'UTF-8');
                $_SESSION['user_email'] = filter_var($user['email'] ?? '', FILTER_SANITIZE_EMAIL);
                $_SESSION['role']       = $user['role'] ?? $_SESSION['pending_user_role'] ?? null;

                // Fetch and store student profile in session after successful verification
                $studentProfile = $this->userModel->getStudentProfileByUserId($_SESSION['user_id']);
                if ($studentProfile) {
                    $_SESSION['student_number'] = $studentProfile['studentNumber'];
                    $_SESSION['full_name'] = $studentProfile['fullName'];
                }

                Logger::info("Login verification completed", [
                    "user_id"    => $_SESSION['user_id'],
                    "user_name"  => $_SESSION['user_name'],
                    "user_email" => $_SESSION['user_email'],
                    "role"       => $_SESSION['role'],
                    "student_number" => $_SESSION['student_number'] ?? 'not set',
                    "full_name" => $_SESSION['full_name'] ?? 'not set',
                    "session_id" => session_id()
                ]);
            }
            // Cleanup: Remove pending login staging keys
            unset($_SESSION['pending_user_id'], $_SESSION['pending_user_role']);
            return [
                "success" => true,
                "message" => "Login verification successful"
            ];
        }

        // Handle signup verification - auto-login the newly verified user
        if (isset($_SESSION['email_verified'])) {
            // Find the user by the verified email
            $user = $this->userModel->findByEmail($_SESSION['email_verified']);
            if ($user) {
                // Security: Regenerate session ID after verification, before privilege elevation
                session_regenerate_id(true);

                // Set full (sanitized) user session state for the newly verified user
                $_SESSION['user_id']    = (int)$user['user_id'];
                $_SESSION['user_name']  = htmlspecialchars($user['userName'] ?? 'User', ENT_QUOTES, 'UTF-8');
                $_SESSION['user_email'] = filter_var($user['email'] ?? '', FILTER_SANITIZE_EMAIL);
                $_SESSION['role']       = $user['role'] ?? 'student'; // Default to student for new signups

                // Fetch and store student profile in session for new users
                $studentProfile = $this->userModel->getStudentProfileByUserId($_SESSION['user_id']);
                if ($studentProfile) {
                    $_SESSION['student_number'] = $studentProfile['studentNumber'];
                    $_SESSION['full_name'] = $studentProfile['fullName'];
                }

                Logger::info("Signup verification completed - user auto-logged in", [
                    "user_id"    => $_SESSION['user_id'],
                    "user_name"  => $_SESSION['user_name'],
                    "user_email" => $_SESSION['user_email'],
                    "role"       => $_SESSION['role'],
                    "student_number" => $_SESSION['student_number'] ?? 'not set',
                    "full_name" => $_SESSION['full_name'] ?? 'not set',
                    "session_id" => session_id()
                ]);

                // Cleanup: Remove verification data
                unset($_SESSION['email_verified']);
                return [
                    "success" => true,
                    "message" => "Signup verification successful - you are now logged in"
                ];
            }
        }

        // Default: completed signup verification (fallback)
        return [
            "success" => true,
            "message" => "Signup verification successful"
        ];
    }

    // Admin code functionality has been removed

    /**
     * Sends a code to unlock a locked account.
     *
     * @param string|null $email The email address of the locked account.
     * @return array An array containing the result of the operation.
     */
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

    /**
     * Verifies a code to unlock a locked account.
     *
     * @param string|null $code The code to verify.
     * @return array An array containing the result of the verification.
     */
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

    /**
     * Changes a user's password.
     *
     * @param string|null $email The user's email address.
     * @param string|null $oldPassword The user's current password.
     * @param string|null $newPassword The user's new password.
     * @return array An array containing the result of the operation.
     */
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

    /**
     * Sends a password reset code to a user's email.
     *
     * @param string|null $email The user's email address.
     * @return array An array containing the result of the operation.
     */
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

    /**
     * Confirms a password reset using a code and sets a new password.
     *
     * @param string|null $email The user's email address.
     * @param string|null $code The reset code.
     * @param string|null $newPassword The new password.
     * @return array An array containing the result of the operation.
     */
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
