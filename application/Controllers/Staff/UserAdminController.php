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
        $query = $request->getQueryParams();
        $filters = [
            'search' => trim((string)($query['q'] ?? '')),
            'role' => in_array(($query['role'] ?? ''), ['client', 'staff'], true) ? $query['role'] : '',
            'status' => in_array(($query['status'] ?? ''), ['active', 'suspended', 'pending_activation'], true) ? $query['status'] : '',
            'login' => in_array(($query['login'] ?? ''), ['never', 'logged_in', 'recent_30'], true) ? $query['login'] : '',
            'sort' => in_array(($query['sort'] ?? ''), ['name_asc', 'newest', 'last_login', 'role'], true) ? $query['sort'] : 'name_asc',
        ];
        $requestedPerPage = (int)($query['per_page'] ?? 20);
        $perPage = in_array($requestedPerPage, [10, 20, 50], true) ? $requestedPerPage : 20;
        $pagination = $this->userModel->paginate($filters, max(1, (int)($query['page'] ?? 1)), $perPage);

        $this->render('staff/users/index', [
            'pageTitle' => 'User Administration',
            'users'     => $pagination['items'],
            'filters' => $filters,
            'pagination' => $pagination,
        ], 'main');
    }

    public function resetPassword(Request $request, Response $response): void
    {
        $userId = (int)($request->getBody()['user_id'] ?? 0);
        $newPassword = trim((string)($request->getBody()['new_password'] ?? ''));
        $user = $userId > 0 ? $this->userModel->findById($userId) : null;

        if (!$user || $user['status'] === 'pending_activation' || strlen($newPassword) < 8) {
            if ($request->isAjax()) $response->json(['success' => false, 'message' => 'Choose a valid user and enter a password of at least 8 characters.'], 422);
            Session::setFlash('error', 'Choose an activated user and enter a password of at least 8 characters.');
            $response->redirect('/staff/users');
        }

        $this->userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_ARGON2ID));
        $this->userModel->resetLoginAttempts($userId);
        AuditService::log('staff_reset_user_password', 'users', $userId);
        NotificationService::sendPromptEmail($user['email'], 'Your TriNova Portal password was reset', 'A staff member reset your portal password. Contact TriNova immediately if you did not expect this change.');
        $message = "Password for '{$user['name']}' updated successfully.";
        if ($request->isAjax()) $response->json(['success' => true, 'message' => $message]);
        Session::setFlash('success', $message);
        $response->redirect('/staff/users');
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
