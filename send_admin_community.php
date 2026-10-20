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

// Read raw body (could be JSON) but also accept regular form POST (multipart/form-data)
$raw = @file_get_contents('php://input');
$data = null;
if ($raw !== false && strlen(trim((string)$raw)) > 0) {
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $decoded;
    }
}

// If no JSON payload, fall back to $_POST (form submissions)
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// If still empty, return an informative error
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No request body (expected JSON or form-data)']);
    exit;
}

// Validate required fields - message may be in 'message' or 'msg'
$required = ['to', 'subject'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing field: $f"]);
        exit;
    }
}

// Prefer 'message' key, fall back to 'msg' or form field
$messageField = $data['message'] ?? $data['msg'] ?? '';
if (trim((string)$messageField) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing field: message']);
    exit;
}
// Normalize data
$data['message'] = $messageField;

// Load SMTP config
require_once __DIR__ . '/config1.php';

// Load PHPMailer (paths relative to this file)
require_once __DIR__ . '/email/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/email/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/email/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
    $mail->SMTPAuth = true;
    $mail->Username = defined('SMTP_USER') ? SMTP_USER : '';
    $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
    // Allow config1.php to specify PHPMailer constant or plain string
    if (defined('SMTP_SECURE') && strpos(SMTP_SECURE, 'ENCRYPTION') !== false) {
        // config used the PHPMailer constant name, evaluate if available
        if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS')) {
            // noop - constant exists
        }
    }
    // If config provided the literal string for secure, map to PHPMailer constants
    if (defined('SMTP_SECURE') && in_array(SMTP_SECURE, ['ssl', 'tls'])) {
        $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    } elseif (defined('SMTP_SECURE') && strpos(SMTP_SECURE, 'ENCRYPTION') !== false) {
        // If config1.php left the PHPMailer::ENCRYPTION_SMTPS string, try to use PHPMailer constant
        $mail->SMTPSecure = defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS') ? PHPMailer::ENCRYPTION_SMTPS : '';
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }
    $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $mail->setFrom(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : (defined('SMTP_USER') ? SMTP_USER : 'no-reply@example.com'),
        defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Contact');
    if (!empty($data['fromEmail'])) {
        $mail->addReplyTo(filter_var($data['fromEmail'], FILTER_SANITIZE_EMAIL), $data['fromName'] ?? '');
    }

    // Recipient
    $mail->addAddress(filter_var($data['to'], FILTER_SANITIZE_EMAIL));

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