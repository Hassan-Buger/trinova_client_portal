<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\AuditLog;
use Application\Models\User;

class DashboardController extends Controller
{
    private AuditLog $auditModel;
    private User $userModel;

    public function __construct()
    {
        $this->auditModel = new AuditLog();
        $this->userModel = new User();
    }

    public function index(Request $request, Response $response): void
    {
        $staffMembers = $this->userModel->getAllStaff();
        $recentActivity = $this->auditModel->getRecentActivity(10);

        $this->render('staff/dashboard', [
            'pageTitle'      => 'Staff Dashboard',
            'userName'       => Session::get('user_name', 'Staff Member'),
            'staffMembers'   => $staffMembers,
            'recentActivity' => $recentActivity,
        ], 'main');
    }

    public function switchIdentity(Request $request, Response $response, int $id): void
    {
        $staffUser = $this->userModel->findById($id);
        if ($staffUser && $staffUser['role'] === 'staff') {
            Session::set('user_id', (int) $staffUser['id']);
            Session::set('user_name', $staffUser['name']);
            Session::set('user_email', $staffUser['email']);
            Session::setFlash('success', "Switched identity to {$staffUser['name']}.");
        }

        $response->redirect('/staff/dashboard');
    }
}
