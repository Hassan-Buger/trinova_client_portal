<?php

namespace Application\Services;

use Application\Config\App;

class NotificationService
{
    /**
     * Dispatch email using Resend API (HTTP POST https://api.resend.com/emails) with fallback to mail log.
     */
    public static function sendResendEmail(string $toEmail, string $subject, string $htmlContent): bool
    {
        $apiKey = trim((string) (App::get('resend_api_key') ?: getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '')));
        $from   = trim((string) App::get('email_from', 'TriNova Accounting <onboarding@resend.dev>'));

        // Log locally first
        self::logEmail($toEmail, $subject, '[Email body omitted from logs]');

        if (empty($apiKey)) {
            self::logEmail($toEmail, $subject, "[RESEND SKIPPED: RESEND_API_KEY is empty in .env]");
            return false;
        }

        $payload = json_encode([
            'from'    => $from,
            'to'      => [$toEmail],
            'subject' => $subject,
            'html'    => $htmlContent,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            self::logEmail($toEmail, $subject, '[RESEND ERROR: Unable to encode email payload]');
            return false;
        }

        $response = false;
        $httpCode = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response === false) {
                self::logEmail($toEmail, $subject, '[RESEND CONNECTION ERROR]: ' . ($curlError ?: 'Unknown cURL error'));
            }
        } else {
            // Fallback to file_get_contents if cURL extension is not enabled
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
            ];
            $context  = stream_context_create($opts);
            $response = @file_get_contents('https://api.resend.com/emails', false, $context);
            if (isset($http_response_header[0])) {
                preg_match('{HTTP\/\S+\s+(\d+)}', $http_response_header[0], $m);
                $httpCode = (int) ($m[1] ?? 0);
            }
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $safeResponse = self::safeApiError($response);
            self::logEmail($toEmail, $subject, "[RESEND API ERROR HTTP {$httpCode}]: {$safeResponse}");
        }

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Send 6-digit Verification Code for Password Reset
     */
    public static function sendVerificationCodeEmail(string $toEmail, string $userName, string $code, string $purpose = 'password reset', string $verificationLink = ''): bool
    {
        $subject = "TriNova Portal — Password Reset Code: {$code}";
        $html = "
            <div style='font-family:Helvetica,Arial,sans-serif;max-width:540px;margin:0 auto;padding:28px;background:#ffffff;border:1.5px solid #e0e9e5;border-radius:20px'>
                <h2 style='color:#0d9488;margin-top:0'>TriNova Accounting Portal</h2>
                <p style='color:#213330;font-size:15px'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p style='color:#5f726c;font-size:14px;line-height:1.5'>You requested a password reset for your TriNova Portal account. Here is your 6-digit verification code:</p>
                <div style='background:#f0f5f3;border-radius:14px;padding:18px;text-align:center;margin:20px 0'>
                    <span style='font-size:32px;font-weight:800;letter-spacing:8px;color:#0d9488'>" . htmlspecialchars($code) . "</span>
                </div>
                " . ($verificationLink !== '' ? "<p style='text-align:center'><a href='" . htmlspecialchars($verificationLink) . "' style='color:#0d9488;font-weight:700'>Continue verification</a></p>" : '') . "
                <p style='color:#8a9a94;font-size:13px'>This single-use code expires in 10 minutes. Never share it. If you did not request this " . htmlspecialchars($purpose) . ", please contact TriNova support.</p>
            </div>
        ";

        return self::sendResendEmail($toEmail, $subject, $html);
    }

    /**
     * Send Welcome & Account Activation Link for New Clients
     */
    public static function sendWelcomeActivationEmail(string $toEmail, string $userName, string $activationLink, string $code): bool
    {
        $subject = "Welcome to TriNova Client Portal — Activate Your Account";
        $html = "
            <div style='font-family:Helvetica,Arial,sans-serif;max-width:540px;margin:0 auto;padding:28px;background:#ffffff;border:1.5px solid #e0e9e5;border-radius:20px'>
                <h2 style='color:#0d9488;margin-top:0'>Welcome to TriNova Accounting</h2>
                <p style='color:#213330;font-size:15px'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p style='color:#5f726c;font-size:14px;line-height:1.5'>An account has been created for you on the TriNova Client Portal. Please click the button below to verify your email address and create your portal password:</p>
                <div style='background:#f0f5f3;border-radius:14px;padding:18px;text-align:center;margin:20px 0'><span style='font-size:32px;font-weight:800;letter-spacing:8px;color:#0d9488'>" . htmlspecialchars($code) . "</span></div>
                <p style='color:#8a9a94;font-size:13px'>This single-use code expires in 10 minutes. Never share it. Request a new code from the verification page if needed.</p>
                <div style='text-align:center;margin:26px 0'>
                    <a href='" . htmlspecialchars($activationLink) . "' style='background:#0d9488;color:#ffffff;padding:14px 28px;border-radius:14px;font-weight:700;font-size:15px;text-decoration:none;display:inline-block;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)'>Activate My Portal Account &rarr;</a>
                </div>
                <p style='color:#8a9a94;font-size:13px'>Your account will remain pending activation until you set your custom password. If the button above doesn't work, copy and paste this URL into your browser:</p>
                <p style='color:#0d9488;font-size:12.5px;word-break:break-all'>" . htmlspecialchars($activationLink) . "</p>
            </div>
        ";

        return self::sendResendEmail($toEmail, $subject, $html);
    }

    /**
     * Send Security Alert Email Notification on Password Change
     */
    public static function sendPasswordChangedAlert(string $toEmail, string $userName): bool
    {
        $subject = "Security Alert: Your TriNova Portal Password Was Updated";
        $dateTime = date('d M Y \a\t H:i T');
        $html = "
            <div style='font-family:Helvetica,Arial,sans-serif;max-width:540px;margin:0 auto;padding:28px;background:#ffffff;border:1.5px solid #e0e9e5;border-radius:20px'>
                <h2 style='color:#e07d24;margin-top:0'>🔒 Security Notification</h2>
                <p style='color:#213330;font-size:15px'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p style='color:#5f726c;font-size:14px;line-height:1.5'>This is a security confirmation that your TriNova Client Portal password was updated on <strong>{$dateTime}</strong>.</p>
                <p style='color:#8a9a94;font-size:13px'>If you performed this action, no further steps are required. If you did NOT change your password, please contact TriNova Accounting immediately to secure your account.</p>
            </div>
        ";

        return self::sendResendEmail($toEmail, $subject, $html);
    }

    public static function sendPromptEmail(string $toEmail, string $subject, string $messageBody): bool
    {
        return self::sendResendEmail($toEmail, $subject, "<p>" . nl2br(htmlspecialchars($messageBody)) . "</p>");
    }

    private static function logEmail(string $toEmail, string $subject, string $textBody): void
    {
        try {
            $logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';
            $logDir  = dirname($logFile);

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $logEntry = sprintf(
                "[%s] MAIL DISPATCH -> To: %s | Subject: %s\nBody:\n%s\n----------------------------------------\n",
                date('Y-m-d H:i:s'),
                $toEmail,
                $subject,
                $textBody
            );

            @file_put_contents($logFile, $logEntry, FILE_APPEND);
        } catch (\Throwable $e) {
            // Ignore logging errors silently
        }
    }

    private static function safeApiError(string|false $response): string
    {
        if (!$response) {
            return ' No response';
        }

        $decoded = json_decode($response, true);
        $message = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['name'] ?? 'Request rejected') : 'Request rejected';
        return ' ' . substr(preg_replace('/[\r\n]+/', ' ', $message), 0, 500);
    }
}
