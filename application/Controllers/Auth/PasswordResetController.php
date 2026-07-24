<?php

namespace Application\Controllers\Auth;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\User;
use Application\Services\AuditService;

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
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->userModel->createResetToken($email, $token, $expiresAt);
            AuditService::log('password_reset_request', 'users', $user['id'], $user['id']);
        }

        // Always show success message to prevent user enumeration
        Session::setFlash('success', 'If an account exists for that email, a secure reset link has been generated.');
        $response->redirect('/password/reset');
    }

    public function showResetConfirm(Request $request, Response $response, string $token): void
    {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            Session::setFlash('error', 'Invalid or expired password reset link.');
            $response->redirect('/login');
            return;
        }

        $this->render('auth/reset-confirm', [
            'pageTitle' => 'Set New Password — TriNova Portal',
            'token'     => $token,
            'error'     => Session::getFlash('error'),
        ], 'main');
    }

    public function processReset(Request $request, Response $response): void
    {
        $token = $request->input('token') ?? '';
        $password = $request->input('password') ?? '';
        $passwordConfirm = $request->input('password_confirm') ?? '';

        if (empty($password) || strlen($password) < 8) {
            Session::setFlash('error', 'Password must be at least 8 characters long.');
            $response->redirect("/password/reset/{$token}");
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setFlash('error', 'Passwords do not match.');
            $response->redirect("/password/reset/{$token}");
            return;
        }

        $user = $this->userModel->findByResetToken($token);
        if (!$user) {
            Session::setFlash('error', 'Invalid or expired password reset token.');
            $response->redirect('/login');
            return;
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $this->userModel->updatePassword($user['id'], $hash);

        AuditService::log('password_reset_complete', 'users', $user['id'], $user['id']);

        Session::setFlash('success', 'Your password has been reset successfully. Please sign in with your new password.');
        $response->redirect('/login');
    }
}
