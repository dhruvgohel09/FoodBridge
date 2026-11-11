<?php
/**
 * Food Bridge - Email Configuration
 * Developed with ❤️ by Team EtherX
 *
 * HOW TO SEND REAL EMAILS:
 * ========================================================
 * Option 1: Gmail SMTP (Direct delivery to actual inboxes)
 * --------------------------------------------------------
 * 1. Log in to your Google Account (e.g. yourname@gmail.com).
 * 2. Enable 2-Step Verification: https://myaccount.google.com/security
 * 3. Generate an App Password: https://myaccount.google.com/apppasswords
 *    - Select App: 'Mail' or custom name 'Food Bridge'
 *    - Copy the 16-character generated password (e.g. "abcd efgh ijkl mnop")
 * 4. Configure below:
 *    - 'driver'     => 'smtp',
 *    - 'host'       => 'smtp.gmail.com',
 *    - 'port'       => 587,
 *    - 'encryption' => 'tls',
 *    - 'username'   => 'your-gmail@gmail.com',
 *    - 'password'   => 'your-16-char-app-password',
 *    - 'from_address' => 'your-gmail@gmail.com',
 *
 * Option 2: Laragon Built-in Mailpit / PHP mail()
 * --------------------------------------------------------
 * - Keep 'driver' => 'mail'.
 * - Laragon captures all outgoing mail in Mailpit (view at http://localhost:8025).
 * - All emails & OTPs are also permanently logged in: logs/mail_log.txt
 * ========================================================
 */

return [
    // Driver: 'smtp' or 'mail'
    'driver'       => getenv('MAIL_DRIVER') ?: 'mail',

    // SMTP Host (e.g. smtp.gmail.com, smtp.brevo.com, 127.0.0.1)
    'host'         => getenv('MAIL_HOST') ?: 'smtp.gmail.com',

    // SMTP Port (587 for TLS, 465 for SSL, 1025 for Mailpit, 25 for standard)
    'port'         => (int)(getenv('MAIL_PORT') ?: 587),

    // Encryption: 'tls', 'ssl', or ''
    'encryption'   => getenv('MAIL_ENCRYPTION') ?: 'tls',

    // SMTP Authentication Username (your email address)
    'username'     => getenv('MAIL_USERNAME') ?: '',

    // SMTP Authentication Password (or Google App Password)
    'password'     => getenv('MAIL_PASSWORD') ?: '',

    // Default Sender Address & Name
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@foodbridge.org',
    'from_name'    => getenv('MAIL_FROM_NAME') ?: 'Food Bridge • Community Food Sharing',
];
