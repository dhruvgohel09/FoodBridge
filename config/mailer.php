<?php
/**
 * Food Bridge - Email Service & Validation
 * Developed by Team EtherX
 */

require_once __DIR__ . '/db.php';

/**
 * Strict Email Address Validation
 * Checks RFC compliance, syntax, length, structure, disposable domains, common typos, and DNS records.
 *
 * @param string $rawEmail
 * @return array ['valid' => bool, 'error' => string|null, 'email' => string, 'domain' => string, 'suggestion' => string|null]
 */
function validateEmailAddress(string $rawEmail): array {
    $email = trim(strtolower($rawEmail));

    if (empty($email)) {
        return [
            'valid' => false,
            'error' => 'Email address is required.',
            'email' => '',
            'domain' => '',
            'suggestion' => null
        ];
    }

    // RFC maximum lengths: total <= 254 chars
    if (strlen($email) > 254) {
        return [
            'valid' => false,
            'error' => 'Email address is too long (maximum 254 characters allowed).',
            'email' => $email,
            'domain' => '',
            'suggestion' => null
        ];
    }

    // Basic PHP RFC validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'valid' => false,
            'error' => 'Please enter a valid email format (e.g., yourname@gmail.com).',
            'email' => $email,
            'domain' => '',
            'suggestion' => null
        ];
    }

    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return [
            'valid' => false,
            'error' => 'Invalid email address structure. It must contain exactly one "@" symbol.',
            'email' => $email,
            'domain' => '',
            'suggestion' => null
        ];
    }

    $local = $parts[0];
    $domain = $parts[1];

    // Local part constraints (RFC 5321: <= 64 chars, no consecutive dots, cannot start/end with dot)
    if (strlen($local) > 64) {
        return [
            'valid' => false,
            'error' => 'The portion before the @ is too long (maximum 64 characters allowed).',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    if (str_starts_with($local, '.') || str_ends_with($local, '.') || str_contains($local, '..')) {
        return [
            'valid' => false,
            'error' => 'Email cannot start with, end with, or contain consecutive dots before the @.',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    // Domain part constraints
    if (!str_contains($domain, '.')) {
        return [
            'valid' => false,
            'error' => 'Email domain must contain a valid extension (e.g., .com, .org, .in).',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    if (str_starts_with($domain, '.') || str_ends_with($domain, '.') || str_contains($domain, '..')) {
        return [
            'valid' => false,
            'error' => 'Email domain cannot have leading, trailing, or consecutive dots.',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    // Check valid hostname characters in domain
    if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,24}$/i', $domain)) {
        return [
            'valid' => false,
            'error' => 'Email domain contains invalid characters or an unsupported extension.',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    // Common domain typo correction suggestions
    $knownTypos = [
        'gmial.com'    => 'gmail.com',
        'gamil.com'    => 'gmail.com',
        'gmaill.com'   => 'gmail.com',
        'gnail.com'    => 'gmail.com',
        'gmai.com'     => 'gmail.com',
        'gmaik.com'    => 'gmail.com',
        'yaho.com'     => 'yahoo.com',
        'yahooo.com'   => 'yahoo.com',
        'yhaoo.com'    => 'yahoo.com',
        'hotmial.com'  => 'hotmail.com',
        'hotmaill.com' => 'hotmail.com',
        'outlok.com'   => 'outlook.com',
        'outloo.com'   => 'outlook.com',
        'iclud.com'    => 'icloud.com',
    ];

    if (isset($knownTypos[$domain])) {
        $suggestedDomain = $knownTypos[$domain];
        $suggestedEmail = $local . '@' . $suggestedDomain;
        return [
            'valid' => false,
            'error' => "Did you mean {$suggestedEmail}? Please check your email spelling.",
            'email' => $email,
            'domain' => $domain,
            'suggestion' => $suggestedEmail
        ];
    }

    // Block disposable / throwaway email domains
    $disposableDomains = [
        'tempmail.com', 'throwawaymail.com', '10minutemail.com', 'mailinator.com',
        'guerrillamail.com', 'sharklasers.com', 'dispostable.com', 'yopmail.com',
        'temp-mail.org', 'fakemailgenerator.com', 'trashmail.com', 'nada.ltd',
        'getairmail.com', 'mytemp.email', 'crazymailing.com', 'mohmal.com'
    ];

    if (in_array($domain, $disposableDomains, true)) {
        return [
            'valid' => false,
            'error' => 'Temporary or disposable email addresses are not allowed. Please use your permanent email.',
            'email' => $email,
            'domain' => $domain,
            'suggestion' => null
        ];
    }

    // Check DNS MX records if external DNS resolution is available on the host
    if (function_exists('checkdnsrr')) {
        $dnsReachable = @checkdnsrr('google.com', 'A');
        if ($dnsReachable) {
            $hasMx = @checkdnsrr($domain, 'MX');
            $hasA = @checkdnsrr($domain, 'A');
            if (!$hasMx && !$hasA) {
                $allowedDevDomains = ['localhost', 'example.com', 'test.com', 'foodbridge.org'];
                if (!in_array($domain, $allowedDevDomains, true)) {
                    return [
                        'valid' => false,
                        'error' => "The domain '{$domain}' does not appear to exist or cannot receive email.",
                        'email' => $email,
                        'domain' => $domain,
                        'suggestion' => null
                    ];
                }
            }
        }
    }

    return [
        'valid' => true,
        'error' => null,
        'email' => $email,
        'domain' => $domain,
        'suggestion' => null
    ];
}

/**
 * Mask an email address for privacy display (e.g. "dhruv.gohel@example.com" -> "dh***@example.com")
 */
function maskEmail(string $email): string {
    $parts = explode('@', trim($email));
    if (count($parts) !== 2) {
        return $email;
    }
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);

    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '***';
    } else {
        $maskedName = substr($name, 0, 2) . str_repeat('*', min(4, $len - 2)) . substr($name, -1);
    }
    return $maskedName . '@' . $domain;
}

/**
 * Pure PHP SMTP Client (No Composer / PHPMailer dependencies required)
 * Supports STARTTLS (port 587) and direct SSL (port 465) with AUTH LOGIN.
 */
class SimpleSMTPClient {
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private int $timeout = 12;

    public function __construct(string $host, int $port, string $encryption = '', string $username = '', string $password = '') {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = strtolower($encryption);
        $this->username = $username;
        $this->password = $password;
    }

    public function send(string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        $socketHost = $this->host;
        if ($this->encryption === 'ssl') {
            $socketHost = 'ssl://' . $this->host;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client(
            $socketHost . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("SMTP Connect Failed: {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($socket, $this->timeout);

        $res = $this->readResponse($socket);
        if (substr($res, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }

        $clientHostname = gethostname() ?: 'foodbridge.local';
        $this->writeCommand($socket, "EHLO " . $clientHostname);
        $res = $this->readResponse($socket);

        // Handle STARTTLS
        if ($this->encryption === 'tls' && str_contains($res, 'STARTTLS')) {
            $this->writeCommand($socket, "STARTTLS");
            $tlsRes = $this->readResponse($socket);
            if (substr($tlsRes, 0, 3) === '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return false;
                }
                $this->writeCommand($socket, "EHLO " . $clientHostname);
                $this->readResponse($socket);
            }
        }

        // Authenticate if credentials provided
        if (!empty($this->username) && !empty($this->password)) {
            $this->writeCommand($socket, "AUTH LOGIN");
            $authRes = $this->readResponse($socket);
            if (substr($authRes, 0, 3) === '334') {
                $this->writeCommand($socket, base64_encode($this->username));
                $this->readResponse($socket);
                $this->writeCommand($socket, base64_encode($this->password));
                $passRes = $this->readResponse($socket);
                if (substr($passRes, 0, 3) !== '235') {
                    error_log("SMTP Authentication failed: " . $passRes);
                    fclose($socket);
                    return false;
                }
            }
        }

        // Mail From
        $this->writeCommand($socket, "MAIL FROM:<{$fromEmail}>");
        $mailFromRes = $this->readResponse($socket);
        if (substr($mailFromRes, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        // Recipient
        $this->writeCommand($socket, "RCPT TO:<{$toEmail}>");
        $rcptRes = $this->readResponse($socket);
        if (substr($rcptRes, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        // Data
        $this->writeCommand($socket, "DATA");
        $dataRes = $this->readResponse($socket);
        if (substr($dataRes, 0, 3) !== '354') {
            fclose($socket);
            return false;
        }

        $boundary = '=_fb_' . md5(uniqid((string)time(), true));
        $headers = [
            "MIME-Version: 1.0",
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
            "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>",
            "Date: " . date('r'),
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: FoodBridge PHP Mailer / Team EtherX"
        ];

        $plain = !empty($textBody) ? $textBody : strip_tags($htmlBody);

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plain)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";
        $body .= ".";

        $this->writeCommand($socket, $body);
        $sendRes = $this->readResponse($socket);

        $this->writeCommand($socket, "QUIT");
        fclose($socket);

        return substr($sendRes, 0, 3) === '250';
    }

    private function writeCommand($socket, string $cmd): void {
        fwrite($socket, $cmd . "\r\n");
    }

    private function readResponse($socket): string {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
            if (strlen(trim($line)) === 3) {
                break;
            }
        }
        return trim($response);
    }
}

/**
 * Universal Send Email Function
 * Sends via SMTP if configured, otherwise falls back to PHP mail()
 * Also logs all sent messages to logs/mail_log.txt
 */
function sendFoodBridgeEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
    $config = require __DIR__ . '/mail.php';

    $fromEmail = $config['from_address'] ?? 'no-reply@foodbridge.org';
    $fromName = $config['from_name'] ?? 'Food Bridge • Community Food Sharing';
    $sent = false;
    $method = 'unknown';

    // Try custom SMTP if configured
    if (($config['driver'] ?? '') === 'smtp' || (!empty($config['username']) && !empty($config['password']))) {
        try {
            $smtp = new SimpleSMTPClient(
                $config['host'],
                $config['port'],
                $config['encryption'],
                $config['username'],
                $config['password']
            );
            $sent = $smtp->send($fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody, $textBody);
            $method = 'smtp';
        } catch (\Throwable $e) {
            error_log("SMTP Error: " . $e->getMessage());
            $sent = false;
        }
    }

    // Fallback to PHP native mail()
    if (!$sent) {
        $boundary = '=_fb_' . md5(uniqid((string)time(), true));
        $headers = [
            "MIME-Version: 1.0",
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: FoodBridge PHP Mailer / Team EtherX"
        ];

        $plain = !empty($textBody) ? $textBody : strip_tags($htmlBody);

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($plain)) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= "--{$boundary}--\r\n";

        $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
        $method = 'mail';
    }

    // Always log email dispatch for local testing & audit trail
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/mail_log.txt';
    $logEntry = sprintf(
        "[%s] Method: %s | Status: %s | To: %s <%s> | Subject: %s\n",
        date('Y-m-d H:i:s'),
        $method,
        $sent ? 'SENT' : 'ATTEMPTED',
        $toName,
        $toEmail,
        $subject
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    return $sent;
}

/**
 * Send OTP Verification Email with Branded HTML Template
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp, string $purpose = 'Password Recovery'): bool {
    $subject = "Your Food Bridge Verification Code: {$otp}";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Food Bridge Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #222;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f4; padding: 30px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 580px; background: #ffffff; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e5ebe7;">
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #0d6d43 0%, #1f8c5a 100%); padding: 30px 32px; text-align: left;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="background: rgba(255,255,255,0.2); width: 38px; height: 38px; border-radius: 9px; text-align: center; vertical-align: middle;">
                    <span style="font-size: 22px;">🌱</span>
                  </td>
                  <td style="padding-left: 12px;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">Food Bridge</h1>
                    <p style="margin: 2px 0 0; color: #d8f3e5; font-size: 11px;">Community Food Sharing Network</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding: 34px 32px;">
              <p style="margin: 0 0 12px; font-size: 15px; color: #1e2624;">Hello <strong>{$toName}</strong>,</p>
              <p style="margin: 0 0 20px; font-size: 13px; line-height: 1.6; color: #55635e;">
                We received a request for <strong>{$purpose}</strong> for your Food Bridge account. Use the 6-digit verification code below to proceed:
              </p>

              <!-- OTP Code Display -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 24px 0;">
                <tr>
                  <td align="center" style="background: #f0fdf4; border: 2px dashed #86efac; border-radius: 12px; padding: 22px 10px;">
                    <span style="font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #0d6d43; font-family: 'Courier New', Courier, monospace;">{$otp}</span>
                    <p style="margin: 10px 0 0; font-size: 11px; color: #166534; font-weight: 600;">
                      ⏱️ Valid for 15 minutes only
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin: 0 0 16px; font-size: 12px; line-height: 1.6; color: #697772;">
                If you did not request this verification code, please ignore this email or reach out to support. Your account remains completely safe.
              </p>

              <div style="background: #f9fafb; border-left: 3px solid #1f8c5a; padding: 12px 16px; border-radius: 6px; margin-top: 24px;">
                <p style="margin: 0; font-size: 11px; color: #55635e; line-height: 1.5;">
                  <strong>100% Pure Vegetarian Civic Initiative:</strong> Connecting surplus food with shelters, orphanages, and families in need across Gujarat.
                </p>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background: #fafbfa; border-top: 1px solid #edf0ee; padding: 20px 32px; text-align: center;">
              <p style="margin: 0; font-size: 11px; color: #889490;">
                © 2026 Food Bridge. All rights reserved. • Developed with ❤️ by <strong>Team EtherX</strong>
              </p>
              <p style="margin: 5px 0 0; font-size: 10px; color: #9aa4a0;">
                Gujarat, India • Automated Security Notification
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $text = "Hello {$toName},\n\nYour Food Bridge verification code is: {$otp}\n\nThis code is valid for 15 minutes.\nIf you did not request this code, please ignore this message.\n\nFood Bridge • Developed by Team EtherX";

    return sendFoodBridgeEmail($toEmail, $toName, $subject, $html, $text);
}

/**
 * Send Welcome Email to Newly Registered User
 */
function sendWelcomeEmail(string $toEmail, string $toName): bool {
    $subject = "Welcome to Food Bridge, {$toName}! 🌱";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Welcome to Food Bridge</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #222;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f4; padding: 30px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 580px; background: #ffffff; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e5ebe7;">
          <tr>
            <td style="background: linear-gradient(135deg, #0d6d43 0%, #1f8c5a 100%); padding: 32px 32px; text-align: left;">
              <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700;">🌱 Welcome to Food Bridge!</h1>
              <p style="margin: 4px 0 0; color: #d8f3e5; font-size: 12px;">Community Food Sharing Across Gujarat</p>
            </td>
          </tr>
          <tr>
            <td style="padding: 34px 32px;">
              <p style="margin: 0 0 12px; font-size: 15px; color: #1e2624;">Hello <strong>{$toName}</strong>,</p>
              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #55635e;">
                Thank you for joining Food Bridge. Your account has been successfully created. Together, we can make every meal count by connecting surplus fresh vegetarian food directly with people in need.
              </p>
              <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 18px; margin: 20px 0;">
                <h3 style="margin: 0 0 8px; font-size: 14px; color: #166534;">What you can do on Food Bridge:</h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #14532d; line-height: 1.8;">
                  <li><strong>Donate Food:</strong> Post surplus cooked meals, raw grains, fruits, and packaged food.</li>
                  <li><strong>Need Food:</strong> Request nutritious 100% vegetarian meals for families, communities, and shelters.</li>
                  <li><strong>Track Status:</strong> Monitor real-time status and partner NGO pickups directly from your dashboard.</li>
                </ul>
              </div>
              <p style="margin: 20px 0 0; font-size: 12px; color: #697772; line-height: 1.6;">
                100% Pure Vegetarian &amp; Free Civic Platform • No Commercial Fees.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background: #fafbfa; border-top: 1px solid #edf0ee; padding: 20px 32px; text-align: center;">
              <p style="margin: 0; font-size: 11px; color: #889490;">
                © 2026 Food Bridge. All rights reserved. • Developed with ❤️ by <strong>Team EtherX</strong>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $text = "Hello {$toName},\n\nWelcome to Food Bridge! Your account has been successfully created.\n\nFood Bridge connects surplus fresh vegetarian food directly with families and community shelters across Gujarat.\n\nDeveloped with ❤️ by Team EtherX";

    return sendFoodBridgeEmail($toEmail, $toName, $subject, $html, $text);
}

/**
 * Send Password Changed Security Notification Email
 */
function sendPasswordChangedEmail(string $toEmail, string $toName): bool {
    $subject = "Security Alert: Password Changed for your Food Bridge Account";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Security Alert: Password Changed</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #222;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f4; padding: 30px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 580px; background: #ffffff; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #e5ebe7;">
          <tr>
            <td style="background: linear-gradient(135deg, #0d6d43 0%, #1f8c5a 100%); padding: 30px 32px; text-align: left;">
              <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;">🔒 Password Changed Successfully</h1>
              <p style="margin: 2px 0 0; color: #d8f3e5; font-size: 11px;">Food Bridge Security Alert</p>
            </td>
          </tr>
          <tr>
            <td style="padding: 34px 32px;">
              <p style="margin: 0 0 12px; font-size: 15px; color: #1e2624;">Hello <strong>{$toName}</strong>,</p>
              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #55635e;">
                This email confirms that the password for your Food Bridge account was successfully updated on <strong>
HTML
. date('F j, Y, g:i A') .
<<<HTML
</strong>.
              </p>
              <div style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
                  <strong>Didn't make this change?</strong> If you did not perform this password change, please contact us immediately to protect your account.
                </p>
              </div>
              <p style="margin: 16px 0 0; font-size: 12px; color: #697772;">
                Thank you for being a part of Food Bridge.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background: #fafbfa; border-top: 1px solid #edf0ee; padding: 20px 32px; text-align: center;">
              <p style="margin: 0; font-size: 11px; color: #889490;">
                © 2026 Food Bridge. All rights reserved. • Developed with ❤️ by <strong>Team EtherX</strong>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $text = "Hello {$toName},\n\nThe password for your Food Bridge account was successfully updated.\nIf you did not perform this action, please reach out to support immediately.\n\nFood Bridge • Developed by Team EtherX";

    return sendFoodBridgeEmail($toEmail, $toName, $subject, $html, $text);
}
