<?php

/**
 * TriNova Client Portal - Front Controller
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Application\Core\Application;
use Application\Middleware\AuthMiddleware;
use Application\Middleware\CsrfMiddleware;
use Application\Middleware\RoleMiddleware;
use Application\Middleware\SessionTimeoutMiddleware;
use Application\Controllers\Auth\AuthController;
use Application\Controllers\Auth\PasswordResetController;
use Application\Controllers\Client\DashboardController as ClientDashboardController;
use Application\Controllers\Client\DocumentController as ClientDocumentController;
use Application\Controllers\Client\MessageController as ClientMessageController;
use Application\Controllers\Client\RequestController as ClientRequestController;
use Application\Controllers\Client\DeadlineController as ClientDeadlineController;
use Application\Controllers\Client\MeetingController as ClientMeetingController;
use Application\Controllers\Client\ProfileController as ClientProfileController;
use Application\Controllers\Staff\DashboardController as StaffDashboardController;
use Application\Controllers\Staff\ClientController as StaffClientController;
use Application\Controllers\Staff\DocumentController as StaffDocumentController;
use Application\Controllers\Staff\RequestController as StaffRequestController;
use Application\Controllers\Staff\MessageController as StaffMessageController;
use Application\Controllers\Staff\DeadlineController as StaffDeadlineController;
use Application\Controllers\Staff\UserAdminController as StaffUserAdminController;

// Simple dotenv loader fallback for environment variables
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim(trim($val), '"\'');
        }
    }
}

$app = new Application(dirname(__DIR__));

// --- PUBLIC AUTH ROUTES ---
$app->router->get('/', function($req, $res) {
    $res->redirect('/login');
});
$app->router->get('/login', [AuthController::class, 'showLogin']);
$app->router->post('/login', [AuthController::class, 'login'])->middleware([CsrfMiddleware::class]);
$app->router->get('/logout', [AuthController::class, 'logout']);

$app->router->get('/password/reset', [PasswordResetController::class, 'showRequestForm']);
$app->router->post('/password/reset', [PasswordResetController::class, 'sendResetLink'])->middleware([CsrfMiddleware::class]);
$app->router->get('/password/reset/{token}', [PasswordResetController::class, 'showResetConfirm']);
$app->router->post('/password/reset/confirm', [PasswordResetController::class, 'processReset'])->middleware([CsrfMiddleware::class]);

// --- SHARED SECURED FILE STREAMING ROUTE ---
$app->router->get('/documents/download/{id}', [ClientDocumentController::class, 'download'])->middleware([AuthMiddleware::class, SessionTimeoutMiddleware::class]);

// --- SECURED CLIENT ROUTES ---
$app->router->group([
    'prefix' => '/client',
    'middleware' => [AuthMiddleware::class, SessionTimeoutMiddleware::class, RoleMiddleware::class . ':client']
], function($r) {
    $r->get('/dashboard', [ClientDashboardController::class, 'index']);
    $r->get('/documents/upload', [ClientDocumentController::class, 'showUpload']);
    $r->post('/documents/upload', [ClientDocumentController::class, 'processUpload'])->middleware([CsrfMiddleware::class]);
    $r->get('/documents/my-uploads', [ClientDocumentController::class, 'myUploads']);
    $r->get('/documents/trinova', [ClientDocumentController::class, 'trinovaDocs']);
    $r->get('/documents/download/{id}', [ClientDocumentController::class, 'download']);
    $r->get('/messages', [ClientMessageController::class, 'index']);
    $r->post('/messages/send', [ClientMessageController::class, 'send'])->middleware([CsrfMiddleware::class]);
    $r->get('/requests', [ClientRequestController::class, 'index']);
    $r->get('/deadlines', [ClientDeadlineController::class, 'index']);
    $r->get('/meetings/book', [ClientMeetingController::class, 'index']);
    $r->post('/meetings/book', [ClientMeetingController::class, 'book'])->middleware([CsrfMiddleware::class]);
    $r->get('/aml', [ClientProfileController::class, 'aml']);
    $r->get('/profile/details', [ClientProfileController::class, 'details']);
    $r->post('/profile/request-update', [ClientProfileController::class, 'requestUpdate'])->middleware([CsrfMiddleware::class]);
});

// --- SECURED STAFF ROUTES ---
$app->router->group([
    'prefix' => '/staff',
    'middleware' => [AuthMiddleware::class, SessionTimeoutMiddleware::class, RoleMiddleware::class . ':staff']
], function($r) {
    $r->get('/dashboard', [StaffDashboardController::class, 'index']);
    $r->get('/switch/{id}', [StaffDashboardController::class, 'switchIdentity']);
    $r->get('/clients', [StaffClientController::class, 'index']);
    $r->post('/clients/create', [StaffClientController::class, 'create'])->middleware([CsrfMiddleware::class]);
    $r->get('/clients/{id}', [StaffClientController::class, 'show']);
    $r->post('/clients/add-entity', [StaffClientController::class, 'addEntity'])->middleware([CsrfMiddleware::class]);
    $r->get('/documents', [StaffDocumentController::class, 'index']);
    $r->post('/documents/upload', [StaffDocumentController::class, 'upload'])->middleware([CsrfMiddleware::class]);
    $r->get('/documents/download/{id}', [StaffDocumentController::class, 'download']);
    $r->get('/requests', [StaffRequestController::class, 'index']);
    $r->post('/requests/create', [StaffRequestController::class, 'create'])->middleware([CsrfMiddleware::class]);
    $r->post('/requests/update-status', [StaffRequestController::class, 'updateStatus'])->middleware([CsrfMiddleware::class]);
    $r->get('/messages', [StaffMessageController::class, 'index']);
    $r->post('/messages/send', [StaffMessageController::class, 'send'])->middleware([CsrfMiddleware::class]);
    $r->get('/deadlines', [StaffDeadlineController::class, 'index']);
    $r->post('/deadlines/create', [StaffDeadlineController::class, 'create'])->middleware([CsrfMiddleware::class]);
    $r->post('/deadlines/update-status', [StaffDeadlineController::class, 'updateStatus'])->middleware([CsrfMiddleware::class]);
    $r->get('/audit', [\Application\Controllers\Staff\AuditController::class, 'index']);
    $r->get('/users', [StaffUserAdminController::class, 'index']);
    $r->post('/users/create', [StaffUserAdminController::class, 'createUser'])->middleware([CsrfMiddleware::class]);
    $r->post('/users/toggle-status', [StaffUserAdminController::class, 'toggleStatus'])->middleware([CsrfMiddleware::class]);
});

// Security response headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-XSS-Protection: 1; mode=block');

// Boot application
$app->run();
