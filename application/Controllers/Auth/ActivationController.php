<?php

namespace Application\Controllers\Auth;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\User;
use Application\Services\AuditService;
use Application\Services\NotificationService;

class ActivationController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showActivationForm(Request $request, Response $response): void
    {
        $token = trim($request->input('token') ?? $_GET['token'] ?? '');

        if (empty($token)) {
            Session::setFlash('error', 'Invalid activation link.');
            $response->redirect('/login');
            return;
        }

        $user = $this->userModel->findByActivationToken($token);

        if (!$user) {
            Session::setFlash('error', 'Invalid or expired activation link.');
            $response->redirect('/login');
            return;
        }

        $this->render('auth/activate', [
            'pageTitle' => 'Activate Your Account — TriNova Portal',
            'token'     => $token,
            'userName'  => $user['name'],
            'email'     => $user['email'],
            'error'     => Session::getFlash('error'),
        ], 'main');
    }

    public function processActivation(Request $request, Response $response): void
    {
        $token           = trim($request->input('token') ?? $_POST['token'] ?? $_GET['token'] ?? '');
        $password        = $request->input('password') ?? '';
        $passwordConfirm = $request->input('password_confirm') ?? '';

        if (empty($token)) {
            Session::setFlash('error', 'Invalid activation session.');
            $response->redirect('/login');
            return;
        }

        $user = $this->userModel->findByActivationToken($token);
        if (!$user) {
            Session::setFlash('error', 'Invalid or expired activation token.');
            $response->redirect('/login');
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $response->redirect('/activate?token=' . urlencode($token));
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setFlash('error', 'Passwords do not match.');
            $response->redirect('/activate?token=' . urlencode($token));
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->userModel->activateAccount((int)$user['id'], $hash);

        AuditService::log('account_activated', 'users', $user['id'], $user['id']);
        NotificationService::sendPasswordChangedAlert($user['email'], $user['name']);

        Session::setFlash('success', 'Your password has been created successfully. You can now log in.');
        $response->redirect('/login');
    }
}
