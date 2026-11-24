<?php
/**
 * Food Bridge - Professional Email Service Configuration
 * Developed by Team EtherX
 *
 * All sensitive credentials are kept secure in .env (ignored by Git).
 */

// Simple lightweight .env loader
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1], " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

return [
    // Driver: 'smtp' sends live emails directly through Google SMTP servers
    'driver'       => getenv('MAIL_DRIVER') ?: 'smtp',

    // SMTP Host & Port
    'host'         => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'         => (int)(getenv('MAIL_PORT') ?: 587),
    'encryption'   => getenv('MAIL_ENCRYPTION') ?: 'tls',

    // SMTP Authentication Credentials (Private, loaded from .env)
    'username'     => getenv('MAIL_USERNAME') ?: 'db2672329@gmail.com',
    'password'     => getenv('MAIL_PASSWORD') ?: '',

    // Official Public Sender Identity (Recipients See This)
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'db2672329@gmail.com',
    'from_name'    => getenv('MAIL_FROM_NAME') ?: 'Food Bridge Official Team',
    'reply_to'     => getenv('MAIL_REPLY_TO') ?: 'db2672329@gmail.com',
];
