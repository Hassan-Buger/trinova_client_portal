<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\User;
use Application\Services\AuditService;
use Application\Services\NotificationService;

class UserAdminController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index(Request $request, Response $response): void
    {
        $users = $this->userModel->getAll();

        $this->render('staff/users/index', [
            'pageTitle' => 'User Administration',
            'users'     => $users,
        ], 'main');
    }

    public function createUser(Request $request, Response $response): void
    {
        $body  = $request->getBody();
        $name  = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $role  = trim($body['role'] ?? 'client');
        $pass  = trim($body['password'] ?? 'password123');

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Valid name and email address are required.');
            $response->redirect('/staff/users');
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            Session::setFlash('error', "An account with email '{$email}' already exists.");
            $response->redirect('/staff/users');
            return;
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $userId = $this->userModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
            'role'          => $role,
            'status'        => 'active',
        ]);

        AuditService::log('user_provisioned', 'users', $userId);
        NotificationService::sendPromptEmail($email, 'Welcome to TriNova Accounting Portal', "An account has been provisioned for you as {$role}. Default password: {$pass}");
        Session::setFlash('success', "User account '{$name}' created successfully.");
        $response->redirect('/staff/users');
    }

    public function toggleStatus(Request $request, Response $response): void
    {
        $body   = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);
        $status = trim($body['status'] ?? '');

        if ($userId > 0 && in_array($status, ['active', 'suspended'], true)) {
            $this->userModel->updateStatus($userId, $status);
            AuditService::log('user_status_toggled', 'users', $userId);
            Session::setFlash('success', "User account status updated to {$status}.");
        }

        $response->redirect('/staff/users');
    }
}
