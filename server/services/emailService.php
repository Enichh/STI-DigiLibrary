<?php
// server/services/EmailService.php

// Load Composer's autoloader (relative to project root)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Try to find autoload.php in the vendor directory
    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new RuntimeException('Composer autoloader not found. Please run `composer install`');
    }
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service for sending emails.
 *
 * This class uses PHPMailer to send various types of emails, such as verification codes,
 * locked account notifications, and password reset instructions.
 */
class EmailService
{
    private $mailer;

    /**
     * Creates an instance of EmailService and configures PHPMailer with SMTP settings.
     */
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.gmail.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?? '';
        $this->mailer->Password   = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?? '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = 587;
        $this->mailer->SMTPDebug = 0;  // Disable debug output

        // Set default from address
        $this->mailer->setFrom('noreply@digilib.sti.edu.ph', 'STI DigiLibrary');
    }

    /**
     * Sends a 6-digit verification email.
     *
     * @param string $to The recipient's email address.
     * @return int The verification code.
     * @throws Exception If the email fails to send.
     */
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

    /**
     * Sends an email with a passcode and temporary password to unlock a locked account.
     *
     * @param string $to The recipient's email address.
     * @param string $code The verification code.
     * @param string $tempPassword The temporary password.
     * @return void
     * @throws Exception If the email fails to send.
     */
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

    /**
     * Sends a password reset email with a verification code.
     *
     * @param string $to The recipient's email address.
     * @param string $code The verification code.
     * @return void
     * @throws Exception If the email fails to send.
     */
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



    /**
     * Sends a borrow request confirmation email to the user.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The title of the requested book.
     * @param string $pickupDate The required pickup date (e.g., "October 20, 2025").
     * @return void
     * @throws Exception If the email fails to send.
     */
    public function sendBorrowRequestConfirmation(string $to, string $bookTitle, string $pickupDate): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Borrow Request Confirmation';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #00315f;'>Borrow Request Received</h2>
                <p>
                    Thank you for requesting <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span> from STI DigiLibrary.
                </p>
                <p>
                    Your request has been logged. Please arrive at the library in person <span style='font-weight: bold;'>"
                . htmlspecialchars($pickupDate, ENT_QUOTES, 'UTF-8')
                . "</span>, between <span style='font-weight: bold;'>9:00 AM</span> and <span style='font-weight: bold;'>4:00 PM</span>, to claim your book.
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p style='color: #555;'>
                    If you do not arrive by <b>4:00 PM</b>, your request may be cancelled and the book made available to the next requester under our first-come, first-served policy.
                </p>
                <p>
                    For questions or concerns, contact us at <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";

            $this->mailer->send();

            // Log successful send for auditing/debugging
            error_log("Email sent successfully to: $to");
        } catch (Exception $e) {
            $error = "Failed to send borrow request confirmation: {$this->mailer->ErrorInfo}";
            error_log($error);
            throw new Exception($error);
        }
    }


    /**
     * Sends a borrow cancellation notice to the user.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The title of the requested book.
     * @param string $pickupDate The intended pickup date.
     * @param string $reason The reason for cancellation (e.g., "Did not arrive on time").
     * @return void
     * @throws Exception If the email fails to send.
     */
    public function sendBorrowCancellationNotice(string $to, string $bookTitle, string $pickupDate, string $reason): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Borrow Request Cancelled';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #c0392b;'>Borrow Request Cancelled</h2>
                <p>
                    Unfortunately, your request for <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8') .
                "</span> scheduled for pickup on <span style='font-weight: bold;'>"
                . htmlspecialchars($pickupDate, ENT_QUOTES, 'UTF-8') .
                "</span> has been cancelled.
                </p>
                <p style='color: #555;'>
                    Reason: " . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p>
                    The book is now available for other patrons. If you still wish to borrow it, please submit a new request through STI DigiLibrary.
                </p>
                <p>
                    For assistance, contact us at <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";

            $this->mailer->send();

            // Log the cancellation for auditing/debugging
            error_log("Borrow cancellation notice sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $error = "Failed to send borrow cancellation notice: {$this->mailer->ErrorInfo}";
            error_log($error);
            throw new Exception($error);
        }
    }


    /**
     * Sends a due date reminder several days before the book is due.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The borrowed book's title.
     * @param string $dueDate Due date in "Month DD, YYYY" format.
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendDueDateReminder(string $to, string $bookTitle, string $dueDate): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Due Date Reminder';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #00315f;'>Book Due Date Approaching</h2>
                <p>You borrowed <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span> from STI DigiLibrary. </p>
                <p>The due date is <span style='font-weight: bold;'>"
                . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8')
                . "</span>.</p>
                <hr style='margin: 1.5rem 0;' />
                <p style='color: #555;'>
                    Please return the item on or before this date to avoid incurring late fees.
                </p>
                <p>
                    According to library policy, a ₱10 fine is charged each day after the third overdue weekday, excluding holidays and weekends.
                </p>
                <p>Contact us: <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a></p>
            </div>";
            $this->mailer->send();
            error_log("Due date reminder sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send due date reminder: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }

    /**
     * Sends an alert when today is the due date for a borrowed item.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The borrowed book's title.
     * @param string $dueDate Today's due date.
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendDueDateTodayAlert(string $to, string $bookTitle, string $dueDate): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Book Due Today';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #e67e22;'>Book Due Date Is Today</h2>
                <p>This is a friendly reminder: <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span> you borrowed from STI DigiLibrary is due <span style='font-weight: bold;'>today</span>, "
                . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8')
                . ".</p>
                <hr style='margin: 1.5rem 0;' />
                <p style='color: #555;'>
                    Please return the book to avoid fines. Starting tomorrow (excluding weekends and holidays), you will be charged ₱10 per additional overdue day.
                </p>
                <p>Contact: <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a></p>
            </div>";
            $this->mailer->send();
            error_log("Due date today alert sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send due date today alert: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }


    /**
     * Sends an overdue notification to the user.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The overdue book's title.
     * @param string $dueDate The original due date.
     * @param int $daysOverdue Number of overdue library days.
     * @param int $dailyFine Amount fined per overdue day (e.g., 10).
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendOverdueNotification(string $to, string $bookTitle, string $dueDate, int $daysOverdue, int $dailyFine): void
    {
        $totalFine = $daysOverdue * $dailyFine;
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Overdue Book Notice';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #c0392b;'>Book Overdue Notice</h2>
                <p>
                    Our records show that you have not returned <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span> by the due date of <span style='font-weight: bold;'>"
                . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8')
                . "</span>.
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p>
                    As of today, your overdue period is <span style='font-weight: bold;'>$daysOverdue</span> library days. According to library policy,<br>
                    you have accrued a fine of <span style='font-weight: bold;'>₱$totalFine</span> (₱$dailyFine per overdue day, excluding weekends and holidays).
                </p>
                <p>
                    Please return the book as soon as possible to minimize further charges and restore your borrowing privileges.
                </p>
                <p>
                    For questions about fines or payment, contact us at <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";
            $this->mailer->send();
            error_log("Overdue notification sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send overdue notification: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }

    /**
     * Sends a fine notice with payment instructions.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The fined book's title.
     * @param int $fineAmount Total fine amount.
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendFineNotice(string $to, string $bookTitle, int $fineAmount): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Fine Notice';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #c0392b;'>Library Fine Notification</h2>
                <p>
                    You have accrued a fine of <span style='font-weight: bold;'>₱$fineAmount</span> for <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span>.
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p>
                    Please visit the library front desk between 9am and 4pm to receive your fine receipt from the librarian.<br>
                    Payment processing will be completed at the cashier after you collect your receipt.
                </p>
                <p>
                    For questions, contact us at <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";
            $this->mailer->send();
            error_log("Fine notice sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send fine notice: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }

    /**
     * Informs user when a borrowed book has been successfully returned and checked in.
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The returned book's title.
     * @param string $returnDate The return date (e.g., "October 20, 2025").
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendReturnConfirmation(string $to, string $bookTitle, string $returnDate): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Book Return Confirmation';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #27ae60;'>Book Return Confirmed</h2>
                <p>
                    Your return of <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span> has been processed on <span style='font-weight: bold;'>"
                . htmlspecialchars($returnDate, ENT_QUOTES, 'UTF-8')
                . "</span>.
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p>
                    Thank you for returning your book. You may now borrow again from STI DigiLibrary.
                </p>
                <p>
                    Need help? Contact <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";
            $this->mailer->send();
            error_log("Return confirmation sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send return confirmation: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }

    /**
     * Alerts user if a borrowed book is marked as lost (e.g., after 15+ days overdue).
     *
     * @param string $to The recipient's email address.
     * @param string $bookTitle The lost book's title.
     * @param string $dueDate The original due date.
     * @param int $totalFine Fine due (if any) for the lost book.
     * @param string $lostPolicy Message on next steps (e.g., "Please visit the library to discuss your responsibilities.").
     * @return void
     * @throws Exception If sending fails.
     */
    public function sendLostItemNotification(string $to, string $bookTitle, string $dueDate, int $totalFine, string $lostPolicy): void
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'STI DigiLibrary - Lost Book Notification';
            $this->mailer->Body =
                "<div style='font-family: DM Sans, sans-serif; padding: 1.5rem;'>
                <h2 style='color: #c0392b;'>Important: Book Marked as Lost</h2>
                <p>
                    The item <span style='font-weight: bold;'>"
                . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8')
                . "</span>, due on <span style='font-weight: bold;'>"
                . htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8')
                . "</span>, has not been returned after multiple reminders.
                </p>
                <p style='color: #c0392b;'>
                    According to library policy, this book is now considered lost.
                </p>
                <p>
                    Total fine due (if any): <span style='font-weight: bold;'>₱$totalFine</span>
                </p>
                <hr style='margin: 1.5rem 0;' />
                <p>
                    $lostPolicy
                </p>
                <p>
                    Please address this as soon as possible to restore your library borrowing privileges.
                </p>
                <p>
                    Questions? Contact <a href='mailto:services.stidigilibrary@gmail.com'>services.stidigilibrary@gmail.com</a>.
                </p>
            </div>";
            $this->mailer->send();
            error_log("Lost item notification sent to: $to for $bookTitle");
        } catch (Exception $e) {
            $msg = "Failed to send lost item notification: {$this->mailer->ErrorInfo}";
            error_log($msg);
            throw new Exception($msg);
        }
    }
}
