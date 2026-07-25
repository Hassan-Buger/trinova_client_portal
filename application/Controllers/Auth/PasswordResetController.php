<?php

namespace Application\Controllers\Auth;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\User;
use Application\Services\AuditService;
use Application\Services\NotificationService;

class PasswordResetController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showRequestForm(Request $request, Response $response): void
    {
        $this->render('auth/password-reset', [
            'pageTitle' => 'Reset Password — TriNova Portal',
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ], 'main');
    }

    public function sendResetLink(Request $request, Response $response): void
    {
        $email = strtolower(trim($request->input('email') ?? ''));

        if (empty($email)) {
            Session::setFlash('error', 'Please enter your email address.');
            $response->redirect('/password/reset');
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $code = (string) random_int(100000, 999999);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $this->userModel->storeVerificationCode($email, $code, $expiresAt);
            AuditService::log('password_reset_code_sent', 'users', $user['id'], $user['id']);

            NotificationService::sendVerificationCodeEmail($email, $user['name'], $code);
        }

        Session::setFlash('success', 'A 6-digit verification code has been dispatched to your email address.');
        $response->redirect('/password/verify?email=' . urlencode($email));
    }

    public function showVerifyForm(Request $request, Response $response): void
    {
        $email = strtolower(trim($request->input('email') ?? ''));

        $this->render('auth/verify-code', [
            'pageTitle' => 'Enter Verification Code — TriNova Portal',
            'email'     => $email,
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ], 'main');
    }

    public function processCodeVerify(Request $request, Response $response): void
    {
        $email           = strtolower(trim($request->input('email') ?? ''));
        $code            = trim($request->input('code') ?? '');
        $password        = $request->input('password') ?? '';
        $passwordConfirm = $request->input('password_confirm') ?? '';

        if (empty($email) || empty($code)) {
            Session::setFlash('error', 'Email address and 6-digit verification code are required.');
            $response->redirect('/password/verify?email=' . urlencode($email));
            return;
        }

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $response->redirect('/password/verify?email=' . urlencode($email));
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setFlash('error', 'Passwords do not match.');
            $response->redirect('/password/verify?email=' . urlencode($email));
            return;
        }

        $user = $this->userModel->findByVerificationCode($email, $code);
        if (!$user) {
            Session::setFlash('error', 'Invalid or expired 6-digit verification code. Please request a new code.');
            $response->redirect('/password/verify?email=' . urlencode($email));
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->userModel->updatePassword((int)$user['id'], $hash);
        $this->userModel->clearVerificationCode((int)$user['id']);
        $this->userModel->resetLoginAttempts((int)$user['id']);

        AuditService::log('password_reset_complete', 'users', $user['id'], $user['id']);
        NotificationService::sendPasswordChangedAlert($user['email'], $user['name']);

        Session::setFlash('success', 'Your password has been reset successfully. Please sign in with your new password.');
        $response->redirect('/login');
    }
}
