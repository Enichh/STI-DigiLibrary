<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(__DIR__ . '/server');
$dotenv->load();

$width = 3.375 * 72;
$height = 2.125 * 72;
//http://localhost/STI-DigiLibrary/libraryIDTest.php to test this tas open niyo folder ng STI-DigiLibrary don lalabas yung pdf
$html = '
<html>2ooo
<head>
  <style>
    body {
      margin: 0;
      padding: 0;
    }
    .card {
      width: 250px;   
      height: 100px;  
      background: #003366;
      color: #fff;
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      border: 2px solid #fff;
      border-radius: 12px;
      box-sizing: border-box;
    }
    .name {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 4px;
      text-align: center;
    }
    .idnum {
      font-size: 10px;
      letter-spacing: 1px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="name">Enoch Gabriel Astor</div>
    <div class="idnum">Library ID: 2025-001</div>
  </div>
</body>
</html>';

$dompdf = new Dompdf();
$dompdf->setPaper([0, 0, $width, $height], 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();
$pdfPath = __DIR__ . '/idCard.pdf';
// Output PDF
file_put_contents($pdfPath, $dompdf->output());

// Emailing PDF as attachment
/*
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['SMTP_PORT'];

    $mail->setFrom($_ENV['SMTP_USER'], 'Library Services');
    $mail->addAddress('enocjastor@gmail.com'); // Change for recipient
    $mail->Subject = 'Your Library ID Card';
    $mail->Body    = 'Please find your attached ID card PDF.';
    $mail->addAttachment($pdfPath);

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
*/
