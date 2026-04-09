<?php
/**
 * Contact Form Handler — Gmail SMTP
 * Sends form submissions to saqibcena99@gmail.com via Gmail SMTP.
 * Works without any external library (uses PHP sockets directly).
 */

header('Content-Type: text/plain; charset=utf-8');

// ─── Configuration ────────────────────────────────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'saqibcena99@gmail.com');
define('SMTP_PASSWORD', 'yekw egod ingz znny');   // Gmail App Password
define('MAIL_TO',       'saqibcena99@gmail.com');
define('MAIL_FROM',     'saqibcena99@gmail.com');
// ──────────────────────────────────────────────────────────────────────────────

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// ─── Sanitize & Validate Input ────────────────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    die('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die('Invalid email address.');
}

// ─── Send via Gmail SMTP ──────────────────────────────────────────────────────
function smtp_send($name, $email, $subject, $message) {
    $socket = fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 30);

    if (!$socket) {
        return "Connection failed: $errstr ($errno)";
    }

    $read = fgets($socket, 512);
    if (substr($read, 0, 3) !== '220') {
        fclose($socket);
        return "SMTP greeting error: $read";
    }

    $domain = gethostname() ?: 'localhost';

    $commands = [
        ["EHLO $domain\r\n",                         '250'],
        ["AUTH LOGIN\r\n",                            '334'],
        [base64_encode(SMTP_USERNAME) . "\r\n",       '334'],
        [base64_encode(SMTP_PASSWORD) . "\r\n",       '235'],
        ["MAIL FROM:<" . MAIL_FROM . ">\r\n",         '250'],
        ["RCPT TO:<" . MAIL_TO . ">\r\n",             '250'],
        ["DATA\r\n",                                  '354'],
    ];

    foreach ($commands as [$cmd, $expected]) {
        fwrite($socket, $cmd);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== $expected) {
            fclose($socket);
            return "SMTP error ($expected expected): $response";
        }
    }

    // Build the email body
    $date    = date('r');
    $boundary = md5(uniqid());
    $headers  = "Date: $date\r\n"
              . "From: \"Portfolio Contact\" <" . MAIL_FROM . ">\r\n"
              . "To: " . MAIL_TO . "\r\n"
              . "Reply-To: $name <$email>\r\n"
              . "Subject: =?UTF-8?B?" . base64_encode("Portfolio: $subject") . "?=\r\n"
              . "MIME-Version: 1.0\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n";

    $body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;'>
  <div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
    <div style='background:#149ddd;padding:24px 30px;'>
      <h2 style='color:#fff;margin:0;font-size:22px;'>📬 New Contact Form Message</h2>
    </div>
    <div style='padding:30px;'>
      <table style='width:100%;border-collapse:collapse;'>
        <tr><td style='padding:10px 0;color:#555;font-weight:bold;width:100px;'>From:</td>
            <td style='padding:10px 0;color:#333;'>$name</td></tr>
        <tr><td style='padding:10px 0;color:#555;font-weight:bold;'>Email:</td>
            <td style='padding:10px 0;'><a href='mailto:$email' style='color:#149ddd;'>$email</a></td></tr>
        <tr><td style='padding:10px 0;color:#555;font-weight:bold;'>Subject:</td>
            <td style='padding:10px 0;color:#333;'>$subject</td></tr>
      </table>
      <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
      <h3 style='color:#149ddd;margin-top:0;'>Message:</h3>
      <p style='color:#444;line-height:1.7;white-space:pre-wrap;'>$message</p>
    </div>
    <div style='background:#f9f9f9;padding:15px 30px;text-align:center;'>
      <small style='color:#aaa;'>Sent from your portfolio website — Saqib Raza</small>
    </div>
  </div>
</body>
</html>
";

    fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        return "Failed to send message body: $response";
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}

$result = smtp_send($name, $email, $subject, $message);

if ($result === true) {
    echo 'OK';
} else {
    http_response_code(500);
    echo 'Could not send your message. Please try again later. Error: ' . $result;
}
?>
