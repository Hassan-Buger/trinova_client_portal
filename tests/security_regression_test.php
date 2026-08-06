<?php

declare(strict_types=1);

function securityExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$root = dirname(__DIR__);
$session = file_get_contents($root . '/application/Core/Session.php');
$authController = file_get_contents($root . '/application/Controllers/Auth/AuthController.php');
$authMiddleware = file_get_contents($root . '/application/Middleware/AuthMiddleware.php');
$userController = file_get_contents($root . '/application/Controllers/Staff/UserAdminController.php');
$clientUpload = file_get_contents($root . '/application/Controllers/Client/DocumentController.php');
$staffUpload = file_get_contents($root . '/application/Controllers/Staff/DocumentController.php');
$storage = file_get_contents($root . '/application/Services/FileStorageService.php');
$notifications = file_get_contents($root . '/application/Services/NotificationService.php');
$routes = file_get_contents($root . '/public/index.php');
$schema = file_get_contents($root . '/config/database.sql');

securityExpect(str_contains($session, 'session_regenerate_id(true)'), 'Login session rotation support is missing.');
securityExpect(str_contains($authController, 'Session::regenerate()'), 'Successful login does not rotate the session.');
securityExpect(str_contains($authController, "\$_SESSION['auth_hash']"), 'Login does not bind the session to the password hash.');
securityExpect(str_contains($authMiddleware, "\$user['status'] !== 'active'"), 'Protected routes do not revoke suspended accounts.');
securityExpect(str_contains($authMiddleware, 'hash_equals($sessionAuthHash, $authHash)'), 'Password changes do not revoke existing sessions.');
securityExpect(str_contains($routes, "router->post('/logout'") && !str_contains($routes, "router->get('/logout'"), 'Logout is not POST-only.');
securityExpect(str_contains($userController, 'beginTransaction()') && str_contains($userController, "'email_sent' => \$emailSent"), 'User provisioning is not transactional and AJAX-safe.');
securityExpect(str_contains($userController, 'function resendActivation') && str_contains($routes, "'/users/resend-activation'"), 'Pending client activation cannot be retried from User Administration.');
securityExpect(str_contains($clientUpload, 'FileStorageService::store') && str_contains($staffUpload, 'FileStorageService::store'), 'A document upload path bypasses secure file validation.');
securityExpect(str_contains($storage, 'finfo_file') && str_contains($storage, '$allowedTypes[$extension]'), 'Upload MIME and extension validation are not coupled.');
securityExpect(str_contains($notifications, 'LOCAL MAIL CAPTURE - NOT SENT') && str_contains($notifications, "['local', 'testing']"), 'Local account activation cannot be completed without an external email provider.');
foreach (['users', 'clients', 'client_entities', 'entity_directors', 'documents', 'document_requests', 'messages', 'deadlines', 'meetings'] as $table) {
    $matched = preg_match('/CREATE TABLE IF NOT EXISTS `'.preg_quote($table, '/').'`\s*\(([\s\S]*?)\) ENGINE=InnoDB/', $schema, $definition);
    securityExpect($matched === 1 && str_contains($definition[1], '`deleted_at` DATETIME NULL'), "Canonical schema is missing {$table}.deleted_at.");
}
foreach (['original_full_name', 'director_utr', 'address', 'id_number', 'ch_verification_number', 'last_director_import_id'] as $column) {
    securityExpect(str_contains($schema, "`{$column}`"), "Canonical schema is missing director field {$column}.");
}

echo "Security and provisioning regression checks passed\n";
