<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\AuditLog;
use Application\Models\Client;
use Application\Models\Document;
use Application\Models\DocumentRequest;
use Application\Models\Message;
use Application\Models\User;

class DashboardController extends Controller
{
    private AuditLog $auditModel;
    private User $userModel;
    private Document $documentModel;
    private Message $messageModel;
    private DocumentRequest $requestModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->auditModel = new AuditLog();
        $this->userModel = new User();
        $this->documentModel = new Document();
        $this->messageModel = new Message();
        $this->requestModel = new DocumentRequest();
        $this->clientModel = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $staffMembers = $this->userModel->getAllStaff();
        $recentActivity = $this->auditModel->getRecentActivity(10);

        $recentUploadsCount  = $this->documentModel->getRecentCount();
        $unreadMessagesCount = $this->messageModel->getTotalUnreadCountForStaff();
        $overdueRequestsCount= $this->requestModel->getOverdueCount();
        $amlActionCount      = $this->clientModel->getAmlActionRequiredCount();

        $this->render('staff/dashboard', [
            'pageTitle'            => 'Staff Dashboard',
            'userName'             => Session::get('user_name', 'Staff Member'),
            'staffMembers'         => $staffMembers,
            'recentActivity'       => $recentActivity,
            'recentUploadsCount'   => $recentUploadsCount,
            'unreadMessagesCount'  => $unreadMessagesCount,
            'overdueRequestsCount' => $overdueRequestsCount,
            'amlActionCount'       => $amlActionCount,
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
