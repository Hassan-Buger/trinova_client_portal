<?php

namespace Application\Services;

use Application\Core\Database;
use PDO;

class NotificationService
{
    public static function sendPromptEmail(int $userId, string $type, string $subject, string $messageBody): bool
    {
        // Insert notification record in database
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, related_entity, sent_at, created_at)
            VALUES (:user_id, :type, :related_entity, NOW(), NOW())
        ");
        $stmt->execute([
            'user_id'        => $userId,
            'type'           => $type,
            'related_entity' => $subject
        ]);

        // Email dispatch stub (In production, uses Mailer SDK with confidential-free login prompts)
        return true;
    }
}
