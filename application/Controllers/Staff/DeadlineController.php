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
        $query = $request->getQueryParams();
        $types = ['VAT', 'Payroll', 'Accounts', 'Corporation Tax', 'Self Assessment', 'Confirmation Statement'];
        $statuses = ['Pending', 'Overdue', 'Completed'];
        $filters = [
            'search' => trim((string)($query['q'] ?? '')),
            'client_id' => max(0, (int)($query['client_id'] ?? 0)),
            'type' => in_array(($query['type'] ?? ''), $types, true) ? $query['type'] : '',
            'status' => in_array(($query['status'] ?? ''), $statuses, true) ? $query['status'] : '',
            'due_from' => $this->validDate($query['due_from'] ?? '') ? $query['due_from'] : '',
            'due_to' => $this->validDate($query['due_to'] ?? '') ? $query['due_to'] : '',
            'timing' => in_array(($query['timing'] ?? ''), ['overdue', 'upcoming'], true) ? $query['timing'] : '',
            'sort' => in_array(($query['sort'] ?? ''), ['due_asc', 'due_desc', 'newest', 'client_asc'], true) ? $query['sort'] : 'due_asc',
        ];
        $requestedPerPage = (int)($query['per_page'] ?? 20);
        $perPage = in_array($requestedPerPage, [10, 20, 50], true) ? $requestedPerPage : 20;
        $pagination = $this->deadlineModel->paginateWithDetails($filters, max(1, (int)($query['page'] ?? 1)), $perPage);
        $clients   = $this->clientModel->getAllWithUsers();

        $this->render('staff/deadlines/index', [
            'pageTitle' => 'Practice Compliance Deadlines',
            'deadlines' => $pagination['items'],
            'clients'   => $clients,
            'types' => $types,
            'statuses' => $statuses,
            'filters' => $filters,
            'pagination' => $pagination,
        ], 'main');
    }

    private function validDate(mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
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
