<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Allow local dev: simple CORS for same machine (adjust in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No request body']);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$required = ['to', 'subject', 'message'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing field: $f"]);
        exit;
    }
}

// Load SMTP config
require_once __DIR__ . '/config.php';

// Load PHPMailer
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    // Use the string value from config ('' | 'ssl' | 'tls')
    if (!empty(SMTP_SECURE)) {
        $mail->SMTPSecure = SMTP_SECURE;
    }
    $mail->Port = SMTP_PORT;
    // From address from config
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    if (!empty($data['fromEmail'])) {
        $mail->addReplyTo(filter_var($data['fromEmail'], FILTER_SANITIZE_EMAIL), $data['fromName'] ?? '');
    }

    // Recipient (force to mdrrm@arthurnerri.tech)
    $mail->addAddress('mdrrm@arthurnerri.tech');

    // Content
    $mail->isHTML(!empty($data['isHtml']));
    $mail->Subject = $data['subject'];
    if (!empty($data['isHtml'])) {
        $mail->Body = $data['message'];
        $mail->AltBody = strip_tags($data['message']);
    } else {
        $mail->Body = null;
        $mail->AltBody = $data['message'];
    }

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()]);
}
