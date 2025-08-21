<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateVerificationCode(): string
{
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function registerEmail($email): bool
{
    $file = __DIR__ . '/registered_emails.txt';
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (!in_array($email, $emails)) {
        file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    return true;
}

function unsubscribeEmail($email): bool
{
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) return false;

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filtered = array_filter($emails, fn($e) => trim($e) !== trim($email));
    file_put_contents($file, implode(PHP_EOL, $filtered) . PHP_EOL, LOCK_EX);

    return true;
}

function sendVerificationEmail($email, $code, $type = 'subscribe'): bool
{
    $subject = $type === 'unsubscribe' ? 'Confirm Un-subscription' : 'Your Verification Code';
    $body = $type === 'unsubscribe' ? <<<HTML
<div style="font-family: ui-sans-serif, system-ui, sans-serif; background-color: #ffffff; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; margin: auto;">
    <h2 style="color: #dc2626; font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Unsubscribe Confirmation</h2>
    <p style="font-size: 1rem; color: #374151; margin-bottom: 0.75rem;">
        You requested to unsubscribe from XKCD emails.
    </p>
    <p style="font-size: 1rem; margin-bottom: 0.5rem;">Use the code below to confirm:</p>
    <div style="font-size: 1.5rem; font-weight: bold; color: #b91c1c; background-color: #fee2e2; padding: 0.75rem 1rem; border-radius: 8px; text-align: center;">
        $code
    </div>
</div>
HTML
        :
        <<<HTML
<div style="font-family: ui-sans-serif, system-ui, sans-serif; background-color: #ffffff; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; margin: auto;">
    <h2 style="color: #16a34a; font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Email Verification</h2>
    <p style="font-size: 1rem; color: #374151; margin-bottom: 0.75rem;">
        Thank you for subscribing to XKCD Comics!
    </p>
    <p style="font-size: 1rem; margin-bottom: 0.5rem;">Use the code below to verify your email address:</p>
    <div style="font-size: 1.5rem; font-weight: bold; color: #15803d; background-color: #dcfce7; padding: 0.75rem 1rem; border-radius: 8px; text-align: center;">
        $code
    </div>
</div>
HTML;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();

        $_SESSION["verification_code_{$type}_{$email}"] = $code;

        return true;
    } catch (Exception $e) {
        error_log("Mail error: {$mail->ErrorInfo}");
        return false;
    }
}

function verifyCode($email, $code, $type = 'subscribe'): bool
{
    $stored = $_SESSION["verification_code_{$type}_{$email}"] ?? null;
    return $stored && $stored === $code;
}

function fetchAndFormatXKCDData(): string
{
    $latest = json_decode(file_get_contents('https://xkcd.com/info.0.json'), true);
    if (!$latest || !isset($latest['num'])) return '';

    $randomId = random_int(1, $latest['num']);
    $comic = json_decode(file_get_contents("https://xkcd.com/{$randomId}/info.0.json"), true);
    if (!$comic) return '';

    $img = htmlspecialchars($comic['img']);
    $title = htmlspecialchars($comic['title']);

    return <<<HTML
<table style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; text-align: center;">
  <tr>
    <td>
      <h2 style="color: #333;">XKCD Comic</h2>
      <img src="{$img}" alt="{$title}" style="max-width: 100%; border-radius: 10px;">
      <p style="margin-top: 20px;">
        <a href="https://email-verification-fkfa.onrender.com/unsubscribe.php" style="padding: 12px 20px; background-color: #ff4b5c; color: white; text-decoration: none; border-radius: 5px;">
          Unsubscribe
        </a>
      </p>
    </td>
  </tr>
</table>
HTML;
}

function sendXKCDUpdatesToSubscribers(): void
{
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) return;

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $body = fetchAndFormatXKCDData();
    if (!$body) return;

    foreach ($emails as $email) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USERNAME'];
            $mail->Password   = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your XKCD Comic';
            $mail->Body    = $body;

            $mail->send();
        } catch (Exception $e) {
            error_log("Failed to send XKCD to $email: {$mail->ErrorInfo}");
        }
    }
}
