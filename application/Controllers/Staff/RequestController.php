<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\DocumentRequest;
use Application\Models\Notification;
use Application\Models\ClientEntity;
use Application\Models\EntityAccess;
use Application\Services\AuditService;

class RequestController extends Controller
{
    private DocumentRequest $requestModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->requestModel = new DocumentRequest();
        $this->clientModel  = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $query = $request->getQueryParams();
        $statuses = ['Awaiting Client', 'Uploaded', 'Under Review', 'Completed'];
        $filters = [
            'search' => trim((string)($query['q'] ?? '')),
            'client_id' => max(0, (int)($query['client_id'] ?? 0)),
            'created_by' => max(0, (int)($query['created_by'] ?? 0)),
            'status' => in_array(($query['status'] ?? ''), $statuses, true) ? $query['status'] : '',
            'due_from' => $this->validDate($query['due_from'] ?? '') ? $query['due_from'] : '',
            'due_to' => $this->validDate($query['due_to'] ?? '') ? $query['due_to'] : '',
            'timing' => in_array(($query['timing'] ?? ''), ['overdue', 'upcoming'], true) ? $query['timing'] : '',
            'sort' => in_array(($query['sort'] ?? ''), ['due_asc', 'due_desc', 'newest', 'client_asc'], true) ? $query['sort'] : 'due_asc',
        ];
        $requestedPerPage = (int)($query['per_page'] ?? 20);
        $perPage = in_array($requestedPerPage, [10, 20, 50], true) ? $requestedPerPage : 20;
        $pagination = $this->requestModel->paginateWithDetails($filters, max(1, (int)($query['page'] ?? 1)), $perPage);
        $clients  = $this->clientModel->getAllWithUsers();
        $staff = (new \Application\Models\User())->getAllStaff();
        $entities = (new ClientEntity())->getAllWithClient();

        $this->render('staff/requests/index', [
            'pageTitle' => 'Document Request Management',
            'requests'  => $pagination['items'],
            'clients'   => $clients,
            'staff' => $staff,
            'entities' => $entities,
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
        $staffUserId = Session::get('user_id');
        $body        = $request->getBody();
        $entityId    = (int)($body['entity_id'] ?? 0);
        $entity      = $entityId > 0 ? (new ClientEntity())->findById($entityId) : null;
        $clientId    = (int)($entity['client_id'] ?? 0);
        $title       = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $dueDate     = trim($body['due_date'] ?? '');

        $today = new \DateTimeImmutable('today');
        $parsedDueDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        if ($clientId <= 0 || empty($title) || !$parsedDueDate || $parsedDueDate->format('Y-m-d') !== $dueDate || $parsedDueDate < $today) {
            Session::setFlash('error', 'Client, request title, and a due date of today or later are required.');
            $response->redirect('/staff/requests');
            return;
        }

        $reqId = $this->requestModel->create([
            'client_id'          => $clientId,
            'entity_id'          => $entityId,
            'scope'              => $entity['entity_scope'],
            'created_by_user_id' => $staffUserId,
            'title'              => $title,
            'description'        => $description,
            'due_date'           => $dueDate,
            'status'             => 'Awaiting Client',
        ]);

        AuditService::log('request_created', 'document_requests', $reqId);
        try {
            foreach ((new EntityAccess())->recipients($entityId) as $client) {
                (new Notification())->create(
                    (int)$client['id'],
                    'document_request',
                    'request:' . $reqId,
                    'New Document Request',
                    "Staff requested: {$title}",
                    '/client/requests'
                );
            }
        } catch (\Throwable $e) {
            // A notification failure must not roll back the document request.
        }
        Session::setFlash('success', "Document request '{$title}' issued successfully.");
        $response->redirect('/staff/requests');
    }

    public function updateStatus(Request $request, Response $response): void
    {
        $body      = $request->getBody();
        $requestId = (int)($body['request_id'] ?? 0);
        $status    = trim($body['status'] ?? '');

        $validStatuses = ['Awaiting Client', 'Uploaded', 'Under Review', 'Completed'];
        if ($requestId > 0 && in_array($status, $validStatuses, true)) {
            $this->requestModel->updateStatus($requestId, $status);
            AuditService::log('request_status_updated', 'document_requests', $requestId);
            Session::setFlash('success', 'Request status updated.');
        }

        $response->redirect('/staff/requests');
    }

    public function delete(Request $request, Response $response): void
    {
        $reqId = (int)($request->input('request_id', 0) ?: $request->input('id', 0));
        if ($reqId > 0 && $this->requestModel->softDelete($reqId)) {
            AuditService::log('request_deleted', 'document_requests', $reqId);
            $msg = 'Document request deleted.';
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Failed to delete request.'], 422);
                return;
            }
            Session::setFlash('error', 'Failed to delete request.');
        }
        $response->redirect('/staff/requests');
    }

    public function bulkDelete(Request $request, Response $response): void
    {
        $rawIds = $request->input('ids', []);
        $ids = is_array($rawIds) ? array_map('intval', array_filter($rawIds)) : [];

        if (empty($ids)) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'No requests selected for deletion.'], 422);
                return;
            }
            Session::setFlash('error', 'No requests selected for deletion.');
            $response->redirect('/staff/requests');
            return;
        }

        $count = $this->requestModel->bulkSoftDelete($ids);
        if ($count > 0) {
            AuditService::log('request_bulk_deleted', 'document_requests', null, null, ['count' => $count, 'ids' => $ids]);
            $msg = "{$count} document request(s) deleted.";
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        }
        $response->redirect('/staff/requests');
    }
}
