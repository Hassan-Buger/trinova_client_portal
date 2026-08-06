<?php

namespace Application\Controllers\Auth;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\User;
use Application\Services\AuditService;

class AuthController extends Controller
{
    private User $userModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->clientModel = new Client();
    }

    public function showLogin(Request $request, Response $response): void
    {
        if (Session::get('user_id')) {
            $role = Session::get('role');
            $response->redirect($role === 'staff' ? '/staff/dashboard' : '/client/dashboard');
            return;
        }

        $this->render('auth/login', [
            'pageTitle' => 'Sign In — TriNova Portal',
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ], 'main');
    }

    public function login(Request $request, Response $response): void
    {
        $email = strtolower(trim($request->input('email') ?? ''));
        $password = $request->input('password') ?? '';

        if (empty($email) || empty($password)) {
            Session::setFlash('error', 'Please enter your email and password.');
            $response->redirect('/login');
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user) {
            // Check 15-minute account lockout
            if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                $minsLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                AuditService::log('failed_login_locked', 'users', $user['id']);
                Session::setFlash('error', "Your account is temporarily locked due to multiple failed login attempts. Please try again in {$minsLeft} minutes or reset your password.");
                $response->redirect('/login');
                return;
            }

            // Check if client is pending activation
            if ($user['status'] === 'pending_activation') {
                Session::setFlash('error', 'Please complete your email verification and password setup before logging in.');
                $response->redirect('/login');
                return;
            }
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            if ($user) {
                $attempts = $this->userModel->incrementFailedLogin((int)$user['id']);
                if ($attempts >= 5) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $this->userModel->lockAccount((int)$user['id'], $lockedUntil);
                    AuditService::log('account_locked', 'users', $user['id']);
                    Session::setFlash('error', 'Your account is temporarily locked due to multiple failed login attempts. Please try again in 15 minutes or reset your password.');
                    $response->redirect('/login');
                    return;
                }
            }
            AuditService::log('failed_login', 'users', $user['id'] ?? null, null);
            Session::setFlash('error', 'Invalid email or password. Please try again.');
            $response->redirect('/login');
            return;
        }

        if ($user['status'] !== 'active') {
            AuditService::log('failed_login_suspended', 'users', $user['id'], $user['id']);
            Session::setFlash('error', 'Your account has been suspended. Please contact TriNova support.');
            $response->redirect('/login');
            return;
        }

        // Reset failed login counter on success
        $this->userModel->resetLoginAttempts((int)$user['id']);

        // Initialize Session
        Session::regenerate();
        $_SESSION['user_id']       = (int) $user['id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['user_email']    = $user['email'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['auth_hash']     = hash('sha256', (string)$user['password_hash']);
        $_SESSION['last_activity'] = time();

        // Scope Client ID
        if ($user['role'] === 'client') {
            $client = $this->clientModel->findByUserId($user['id']);
            $_SESSION['client_id'] = $client ? (int) $client['id'] : null;
        } else {
            $_SESSION['client_id'] = null; // Staff context has unrestricted global client access
        }

        $this->userModel->updateLastLogin($user['id']);
        AuditService::log('login', 'users', $user['id'], $user['id']);

        $response->redirect($user['role'] === 'staff' ? '/staff/dashboard' : '/client/dashboard');
    }

    public function logout(Request $request, Response $response): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            AuditService::log('logout', 'users', $userId, $userId);
        }

        Session::destroy();
        Session::start();
        Session::setFlash('success', 'You have been signed out securely.');
        $response->redirect('/login');
    }
}
