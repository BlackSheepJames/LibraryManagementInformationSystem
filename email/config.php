<?php
/**
 * SMTP Configuration Template
 * 
 * Edit these constants with your cPanel email account credentials.
 * Use this file in your `send.php` script to configure PHPMailer.
 */

// SMTP host (e.g., mail.yourdomain.com or smtp.yourdomain.com)
define('SMTP_HOST', 'mail.arthurnerri.tech');

// SMTP username (usually your full email address)
define('SMTP_USER', 'mdrrm@arthurnerri.tech');

// SMTP password (⚠️ keep this secret, never commit to public repos)
define('SMTP_PASS', 'arthurtech_2025');

// Encryption type: '', 'ssl', or 'tls'
// Use PHPMailer constants: PHPMailer::ENCRYPTION_SMTPS or PHPMailer::ENCRYPTION_STARTTLS
// Use a simple string here so this config file does not require PHPMailer to be loaded.
// Set to 'ssl' or 'tls' depending on your provider.
define('SMTP_SECURE', 'ssl');

// Port number: 465 (SSL), 587 (TLS), or sometimes 25
define('SMTP_PORT', 465);

// Default "From" email and name
define('SMTP_FROM_EMAIL', 'mdrrm@arthurnerri.tech');
define('SMTP_FROM_NAME', 'Luisiana Disaster Learning Hub');

/**
 * Notes:
 * - Ensure your cPanel email account is allowed to send via SMTP on this server.
 * - If this script runs on the same domain as your email account, 
 *   the above credentials should work without extra configuration.
 */
