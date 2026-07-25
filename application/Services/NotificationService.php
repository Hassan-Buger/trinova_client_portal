<?php

namespace Application\Services;

class NotificationService
{
    /**
     * Dispatch an automated prompt email without confidential contents.
     */
    public static function sendPromptEmail(string $toEmail, string $subject, string $messageBody): bool
    {
        // Non-confidential email template footer
        $footer = "\n\n---\nTriNova Accounting Client Portal Notification\nLog in at https://portal.trinova.co.uk to view details securely.";
        $fullBody = $messageBody . $footer;

        // In local/prototype environment, log the email dispatch to storage/logs/mail.log
        $logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';
        $logDir  = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] MAIL DISPATCH -> To: %s | Subject: %s\nBody:\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $subject,
            $fullBody
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND);
        return true;
    }
}
