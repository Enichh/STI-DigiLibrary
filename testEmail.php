<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/server/services/EmailService.php';

// Set SMTP credentials
putenv('SMTP_USER=services.stidigilibrary@gmail.com');
putenv('SMTP_PASS=hwew gjxp mala ljhc');

// Init EmailService
$emailService = new EmailService();

// Email(s) for testing
$recipientEmails = [
    'pompompuerin@gmail.com',
    'asheserein1@gmail.com',
    'jamesviray6969@gmail.com',
    'enocjastor@gmail.com'
];

// Choose function via ?method= in URL (default is borrow confirmation)
$method = isset($_GET['method']) ? $_GET['method'] : 'sendBorrowRequestConfirmation';

foreach ($recipientEmails as $email) {
    try {
        switch ($method) {
            case 'sendBorrowRequestConfirmation':
                $emailService->sendBorrowRequestConfirmation($email, 'Walang Pasok SANA', date('F d, Y'));
                echo "[OK] Borrow confirmation sent to $email<br>";
                break;

            case 'sendBorrowCancellationNotice':
                $emailService->sendBorrowCancellationNotice(
                    $email,
                    'Walang Pasok SANA',
                    date('F d, Y'),
                    'Did not arrive by deadline'
                );
                echo "[OK] Borrow cancellation notice sent to $email<br>";
                break;

            case 'sendVerificationEmail':
                $code = $emailService->sendVerificationEmail($email);
                echo "[OK] Verification ($code) sent to $email<br>";
                break;

            case 'sendLockedPasscode':
                $emailService->sendLockedPasscode($email, '123456', 'tempPass123!');
                echo "[OK] Locked account code and temp password sent to $email<br>";
                break;

            case 'sendPasswordResetEmail':
                $emailService->sendPasswordResetEmail($email, '789654');
                echo "[OK] Password reset code sent to $email<br>";
                break;

            case 'sendDueDateReminder':
                $emailService->sendDueDateReminder(
                    $email,
                    'Fifty Shades of Grey',
                    date('F d, Y', strtotime('+2 days'))
                );
                echo "[OK] Due date reminder sent to $email<br>";
                break;

            case 'sendDueDateTodayAlert':
                $emailService->sendDueDateTodayAlert(
                    $email,
                    'Fifty Shades of Grey',
                    date('F d, Y')
                );
                echo "[OK] Due date today alert sent to $email<br>";
                break;

            case 'sendOverdueNotification':
                $emailService->sendOverdueNotification(
                    $email,
                    'Fifty Shades of Grey',
                    date('F d, Y', strtotime('-5 days')), // 5 days ago as due date
                    5, // days overdue
                    10 // fine per day
                );
                echo "[OK] Overdue notification sent to $email<br>";
                break;

            case 'sendFineNotice':
                $emailService->sendFineNotice(
                    $email,
                    'Fifty Shades of Grey',
                    20000 // Example total fine
                );
                echo "[OK] Fine notice sent to $email<br>";
                break;

            default:
                echo "[ERR] Unknown test method '$method'<br>";
        }
    } catch (Exception $e) {
        echo "[ERR] {$e->getMessage()} to $email<br>";
    }
}
