<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Deadline;
use Application\Services\AuditService;

class DeadlineController extends Controller
{
    private Deadline $deadlineModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->deadlineModel = new Deadline();
        $this->clientModel   = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $deadlines = $this->deadlineModel->getAllWithDetails();
        $clients   = $this->clientModel->getAllWithUsers();

        $this->render('staff/deadlines/index', [
            'pageTitle' => 'Practice Compliance Deadlines',
            'deadlines' => $deadlines,
            'clients'   => $clients,
        ], 'main');
    }

    public function create(Request $request, Response $response): void
    {
        $body     = $request->getBody();
        $clientId = (int)($body['client_id'] ?? 0);
        $type     = trim($body['type'] ?? '');
        $dueDate  = trim($body['due_date'] ?? '');

        if ($clientId > 0 && !empty($type) && !empty($dueDate)) {
            $id = $this->deadlineModel->create([
                'client_id' => $clientId,
                'type'      => $type,
                'due_date'  => $dueDate,
                'status'    => 'Pending',
            ]);

            AuditService::log('deadline_created', 'deadlines', $id);
            Session::setFlash('success', 'Compliance deadline added.');
        }

        $response->redirect('/staff/deadlines');
    }

    public function updateStatus(Request $request, Response $response): void
    {
        $body       = $request->getBody();
        $deadlineId = (int)($body['deadline_id'] ?? 0);
        $status     = trim($body['status'] ?? '');

        if ($deadlineId > 0 && in_array($status, ['Pending', 'Overdue', 'Completed'], true)) {
            $this->deadlineModel->updateStatus($deadlineId, $status);
            AuditService::log('deadline_status_updated', 'deadlines', $deadlineId);
            Session::setFlash('success', 'Deadline status updated.');
        }

        $response->redirect('/staff/deadlines');
    }
}
