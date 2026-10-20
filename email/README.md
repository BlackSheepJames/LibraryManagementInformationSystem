# Simple Emailing Site

This small project provides a minimal web UI and API to send email using PHPMailer and your cPanel SMTP account.

Files added
- `index.php` — simple form UI (AJAX POST to `send.php`).
- `send.php` — JSON API endpoint that sends email using PHPMailer.
- `config.php` — SMTP configuration template (fill with your cPanel SMTP credentials).

Setup
1. Edit `config.php` and fill in your SMTP host, user, password, secure type, port, and from address.
2. Ensure PHP can connect to your SMTP server (some hosting or local firewalls block outbound SMTP ports).
3. Access `http://localhost/email/` in your browser and send a test email.

Troubleshooting
- If `send.php` returns an error, check `send.php` response JSON for `error` details.
- For authentication issues, double-check SMTP credentials and port/secure values.
- If using Gmail or other providers with 2FA, you may need an app-specific password or OAuth (not included here).

Security notes
- This is a minimal demo. Do not expose the site publicly without adding authentication, rate-limiting, and CSRF protections.
