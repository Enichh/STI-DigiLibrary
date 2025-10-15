<?php
// server/services/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.gmail.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['SMTP_USER'] ?? '';
        $this->mailer->Password   = $_ENV['SMTP_PASS'] ?? '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = 587;

        $this->mailer->setFrom('noreply@digilib.sti.edu.ph', 'STI DigiLibrary');
    }

    public function sendVerificationEmail(string $to): int
    {
        $code = random_int(100000, 999999);

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verification Code';
            $this->mailer->Body    = "
                <div style='text-align: center; margin-top: 2rem;'>
                  <p style='font-size: 1.5rem; margin-bottom: 0.5rem;'>Your 6-digit code is:</p>
                  <p style='font-size: 3rem; font-weight: bold; color: #2c3e50;'>$code</p>
                </div>
            ";

            $this->mailer->send();
            return $code;
        } catch (Exception $e) {
            throw new Exception("Failed to send verification email: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendLockedPasscode(string $to, string $code, string $tempPassword): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Account Unlock Instructions';
            $this->mailer->Body    = "
            <div style='font-family: DM Sans, sans-serif; padding: 1.5rem; background-color: #f9f9f9; border-radius: 8px;'>
                <h2 style='color: #00315f;'>Your STI DigiLibrary Account Is Locked</h2>
                <p style='font-size: 1rem;'>To unlock your account, please enter the verification code below:</p>
                <p style='font-size: 2rem; font-weight: bold; color: #2c3e50; margin: 1rem 0;'>$code</p>
                <hr style='margin: 1.5rem 0;' />
                <p style='font-size: 1rem;'>Once unlocked, use this temporary password to log in:</p>
                <p style='font-size: 1.5rem; font-weight: bold; color: #c0392b;'>$tempPassword</p>
                <p style='margin-top: 1rem; font-size: 0.9rem; color: #555;'>
                    Please change your password immediately after logging in to ensure account security.
                </p>
            </div>
        ";

            $this->mailer->send();
        } catch (Exception $e) {
            throw new Exception("Failed to send locked account email: {$this->mailer->ErrorInfo}");
        }
    }

    public function sendPasswordResetEmail(string $to, string $code): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset Request';
            $this->mailer->Body    = "
        <div style='font-family: DM Sans, sans-serif; padding: 1.5rem; background-color: #f9f9f9; border-radius: 8px;'>
            <h2 style='color: #00315f;'>Password Reset Request</h2>
            <p style='font-size: 1rem;'>We received a request to reset your STI DigiLibrary account password.</p>
            <p style='font-size: 1rem;'>Use the verification code below to proceed:</p>
            <p style='font-size: 2rem; font-weight: bold; color: #2c3e50; margin: 1rem 0;'>$code</p>
            <p style='margin-top: 1rem; font-size: 0.9rem; color: #555;'>
                If you did not request this reset, you can safely ignore this email.
            </p>
        </div>
        ";

            $this->mailer->send();
        } catch (Exception $e) {
            throw new Exception("Failed to send password reset email: {$this->mailer->ErrorInfo}");
        }
    }
}
