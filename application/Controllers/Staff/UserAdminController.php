<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Database;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\User;
use Application\Services\AuditService;
use Application\Services\NotificationService;
use Application\Services\ErrorHandler;

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

        if ($user['role'] === 'client') {
            $otpModel = new \Application\Models\OtpChallenge();
            $otp = $otpModel->issue($userId, $user['email'], \Application\Models\OtpChallenge::PASSWORD_RESET);
            $resetToken=bin2hex(random_bytes(32));$this->userModel->createResetToken($user['email'],$resetToken,date('Y-m-d H:i:s',time()+600));
            $resetLink=rtrim(\Application\Config\App::get('url'),'/').'/password/verify?token='.urlencode($resetToken);
            if (!$otp['ok'] || !NotificationService::sendVerificationCodeEmail($user['email'], $user['name'], $otp['code'], 'password reset', $resetLink)) {
                if ($otp['ok']) $otpModel->invalidate($userId, \Application\Models\OtpChallenge::PASSWORD_RESET);
                $message='We could not send the client password-reset verification code.';
                if($request->isAjax()){ $response->json(['success'=>false,'message'=>$message],422); return; }
                Session::setFlash('error',$message); $response->redirect('/staff/users'); return;
            }
            $message="A password-reset verification code was sent to '{$user['name']}'.";
            if($request->isAjax()){ $response->json(['success'=>true,'message'=>$message]); return; }
            Session::setFlash('success',$message); $response->redirect('/staff/users'); return;
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
        $email = strtolower(trim($body['email'] ?? ''));
        $role  = trim($body['role'] ?? 'client');
        $pass  = trim($body['password'] ?? 'password123');

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['client', 'staff'], true) || ($role === 'staff' && strlen($pass) < 8)) {
            $message = 'Enter a valid name, email, account role, and password of at least 8 characters.';
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => $message], 422);
            }
            Session::setFlash('error', $message);
            $response->redirect('/staff/users');
            return;
        }

        $existing = $this->userModel->findByEmailIncludingDeleted($email);
        if ($existing) {
            $message = !empty($existing['deleted_at'])
                ? "An account with email '{$email}' is in Trash. Restore that account instead of creating a duplicate."
                : "An account with email '{$email}' already exists.";
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => $message], 409);
            }
            Session::setFlash('error', $message);
            $response->redirect('/staff/users');
            return;
        }

        $db = Database::getInstance();
        $activationToken = null;
        $activationCode = null;
        try {
            $db->beginTransaction();
            $hash = password_hash($pass !== '' ? $pass : 'password123', PASSWORD_BCRYPT);
            $userId = $this->userModel->create([
                'name'          => $name,
                'email'         => $email,
                'password_hash' => $hash,
                'role'          => $role,
                'status'        => ($role === 'client' && $pass === '') ? 'pending_activation' : 'active',
            ]);

            if ($role === 'client') {
                (new Client())->create([
                    'user_id' => $userId,
                    'aml_status' => 'Action Required',
                ]);
                $activationToken = bin2hex(random_bytes(32));
                $this->userModel->storeActivationToken($userId, $activationToken);
                $otp = (new \Application\Models\OtpChallenge())->issue($userId, $email, \Application\Models\OtpChallenge::ACTIVATION);
                if (!$otp['ok']) throw new \RuntimeException('The activation challenge could not be created.');
                $activationCode = $otp['code'];
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            ErrorHandler::report(new \RuntimeException('User provisioning transaction failed.', 0, $e), $request);
            $message = 'The account could not be created. No user or client record was saved.';
            if ($request->isAjax()) $response->json(['success' => false, 'message' => $message], 500);
            Session::setFlash('error', $message);
            $response->redirect('/staff/users');
            return;
        }

        AuditService::log('user_provisioned', 'users', $userId);
        $message = "User account '{$name}' created successfully.";
        $emailSent = true;
        if ($role === 'client') {
            $link = rtrim(\Application\Config\App::get('url'), '/') . '/activate?token=' . urlencode((string)$activationToken);
            $emailSent = NotificationService::sendWelcomeActivationEmail($email, $name, $link, (string)$activationCode);
            $localCapture = in_array((string)\Application\Config\App::get('env', 'local'), ['local', 'testing'], true)
                && (string)\Application\Config\App::get('resend_api_key', '') === '';
            if ($localCapture) $message .= ' Local activation details were captured in storage/logs/mail.log.';
            elseif (!$emailSent) $message .= ' The account is saved, but the activation email was not sent. Configure RESEND_API_KEY and resend activation before the client signs in.';
        } else {
            NotificationService::sendPromptEmail($email, 'Welcome to TriNova Accounting Portal', 'A staff account has been provisioned for you.');
        }
        if ($request->isAjax()) {
            $response->json(['success' => true, 'message' => $message, 'redirect' => '/staff/users', 'email_sent' => $emailSent], 201);
        }
        Session::setFlash('success', $message);
        $response->redirect('/staff/users');
    }

    public function toggleStatus(Request $request, Response $response): void
    {
        $body   = $request->getBody();
        $userId = (int)($body['user_id'] ?? 0);
        $status = trim($body['status'] ?? '');

        if ($userId === (int)Session::get('user_id') && $status === 'suspended') {
            Session::setFlash('error', 'You cannot suspend your own staff account.');
            $response->redirect('/staff/users');
            return;
        }

        if ($userId > 0 && in_array($status, ['active', 'suspended'], true)) {
            $this->userModel->updateStatus($userId, $status);
            AuditService::log('user_status_toggled', 'users', $userId);
            Session::setFlash('success', "User account status updated to {$status}.");
        }

        $response->redirect('/staff/users');
    }

    public function resendActivation(Request $request, Response $response): void
    {
        $userId = (int)$request->input('user_id', 0);
        $user = $userId > 0 ? $this->userModel->findById($userId) : null;
        if (!$user || $user['role'] !== 'client' || $user['status'] !== 'pending_activation') {
            $message = 'Choose a client account that is awaiting activation.';
            if ($request->isAjax()) $response->json(['success' => false, 'message' => $message], 422);
            Session::setFlash('error', $message);
            $response->redirect('/staff/users');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->userModel->storeActivationToken($userId, $token);
        $otp = (new \Application\Models\OtpChallenge())->issue($userId, $user['email'], \Application\Models\OtpChallenge::ACTIVATION, true);
        $link = rtrim(\Application\Config\App::get('url'), '/') . '/activate?token=' . urlencode($token);
        $delivered = $otp['ok'] && NotificationService::sendWelcomeActivationEmail($user['email'], $user['name'], $link, $otp['code']);
        $localCapture = in_array((string)\Application\Config\App::get('env', 'local'), ['local', 'testing'], true)
            && (string)\Application\Config\App::get('resend_api_key', '') === '';
        $message = $delivered
            ? ($localCapture ? 'New activation details were captured in storage/logs/mail.log.' : 'A new activation email was sent successfully.')
            : 'The activation email could not be sent. Check the email configuration and try again.';
        if ($request->isAjax()) $response->json(['success' => $delivered, 'message' => $message], $delivered ? 200 : 422);
        Session::setFlash($delivered ? 'success' : 'error', $message);
        $response->redirect('/staff/users');
    }

    public function delete(Request $request, Response $response): void
    {
        $userId = (int)($request->input('user_id', 0) ?: $request->input('id', 0));
        if ($userId === (int)Session::get('user_id')) {
            $msg = 'You cannot delete your own staff account.';
            if ($request->isAjax()) $response->json(['success' => false, 'message' => $msg], 422);
            Session::setFlash('error', $msg);
            $response->redirect('/staff/users');
            return;
        }
        if ($userId > 0 && $this->softDeleteAccount($userId)) {
            AuditService::log('user_deleted', 'users', $userId);
            $msg = 'User account deleted.';
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Failed to delete user account.'], 422);
                return;
            }
            Session::setFlash('error', 'Failed to delete user account.');
        }
        $response->redirect('/staff/users');
    }

    public function bulkDelete(Request $request, Response $response): void
    {
        $rawIds = $request->input('ids', []);
        $ids = is_array($rawIds) ? array_values(array_unique(array_map('intval', array_filter($rawIds)))) : [];
        $ids = array_values(array_filter($ids, fn(int $id): bool => $id !== (int)Session::get('user_id')));

        if (empty($ids)) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'No users selected for deletion.'], 422);
                return;
            }
            Session::setFlash('error', 'No users selected for deletion.');
            $response->redirect('/staff/users');
            return;
        }

        $count = 0;
        foreach ($ids as $id) if ($this->softDeleteAccount($id)) $count++;
        if ($count > 0) {
            AuditService::log('user_bulk_deleted', 'users', null, null, ['count' => $count, 'ids' => $ids]);
            $msg = "{$count} user account(s) deleted.";
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        }
        $response->redirect('/staff/users');
    }

    private function softDeleteAccount(int $userId): bool
    {
        $user = $this->userModel->findById($userId);
        if (!$user) return false;
        if ($user['role'] === 'client') {
            $client = (new Client())->findByUserId($userId);
            return $client ? (new Client())->softDelete((int)$client['id']) : $this->userModel->softDelete($userId);
        }
        return $this->userModel->softDelete($userId);
    }
}
