<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Client;
use Application\Models\ClientEntity;
use Application\Models\Deadline;
use Application\Models\DocumentRequest;

class ClientController extends Controller
{
    private Client $clientModel;
    private ClientEntity $entityModel;
    private DocumentRequest $requestModel;
    private Deadline $deadlineModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->entityModel = new ClientEntity();
        $this->requestModel = new DocumentRequest();
        $this->deadlineModel = new Deadline();
    }

    public function index(Request $request, Response $response): void
    {
        $query = $request->getQueryParams();
        $search = trim((string) ($query['q'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = (int) ($query['per_page'] ?? 10);
        if (!in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }
        $pagination = $this->clientModel->paginate($search, $page, $perPage);

        $this->render('staff/clients/index', [
            'pageTitle' => 'Clients Overview',
            'clients'   => $pagination['items'],
            'search' => $search,
            'pagination' => $pagination,
        ], 'main');
    }

    public function show(Request $request, Response $response, int $id): void
    {
        $client = $this->clientModel->findById($id);
        if (!$client) {
            $response->setStatusCode(404);
            die('Client record not found.');
        }

        $entities = $this->entityModel->getByClientId($id);
        $outstanding = $this->requestModel->getOutstandingByClientId($id);
        $deadlineGroups = $this->deadlineModel->getGroupedByClient($id);
        $groupedByEntity = [];
        foreach ($deadlineGroups as $group) $groupedByEntity[(int)$group['entity_id']] = $group;
        $deadlineGroups = array_map(static function(array $entity) use ($groupedByEntity): array {
            return $groupedByEntity[(int)$entity['id']] ?? ['entity_id'=>(int)$entity['id'], 'entity_name'=>$entity['company_name'], 'entity_type'=>$entity['entity_type'], 'deadlines'=>[]];
        }, $entities);

        $auditModel = new \Application\Models\AuditLog();
        $auditLogs = $auditModel->getByUserId((int)$client['user_id'], 20);

        $documentModel = new \Application\Models\Document();
        $documents = $documentModel->getByClientId($id);

        $meetingModel = new \Application\Models\Meeting();
        $meetings = $meetingModel->getByClientId($id);

        $this->render('staff/clients/show', [
            'pageTitle'   => "Client: {$client['name']}",
            'client'      => $client,
            'entities'    => $entities,
            'outstanding' => $outstanding,
            'deadlineGroups' => $deadlineGroups,
            'auditLogs'   => $auditLogs,
            'documents'   => $documents,
            'meetings'    => $meetings,
        ], 'main');
    }

    public function create(Request $request, Response $response): void
    {
        $body    = $request->getBody();
        $name    = trim($body['name'] ?? '');
        $email   = trim($body['email'] ?? '');
        $phone   = trim($body['phone'] ?? '');
        $address = trim($body['address'] ?? '');
        $aml     = trim($body['aml_status'] ?? 'Action Required');

        if (empty($name) || empty($email)) {
            \Application\Core\Session::setFlash('error', 'Client name and email are required.');
            $response->redirect('/staff/clients');
            return;
        }

        $userModel = new \Application\Models\User();
        if ($userModel->findByEmail($email)) {
            \Application\Core\Session::setFlash('error', "User email '{$email}' already registered.");
            $response->redirect('/staff/clients');
            return;
        }

        $dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $userId = $userModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $dummyHash,
            'role'          => 'client',
            'status'        => 'pending_activation',
        ]);

        $activationToken = bin2hex(random_bytes(32));
        $userModel->storeActivationToken($userId, $activationToken);

        $clientId = $this->clientModel->create([
            'user_id'    => $userId,
            'phone'      => $phone,
            'address'    => $address,
            'aml_status' => $aml,
        ]);

        $entityName = trim((string)($body['entity_name'] ?? ''));
        if ($entityName !== '') {
            $entityId = $this->entityModel->create([
                'client_id' => $clientId,
                'company_name' => $entityName,
                'entity_type' => trim((string)($body['entity_type'] ?? 'Other')) ?: 'Other',
                'company_number' => trim((string)($body['company_number'] ?? '')) ?: null,
                'tax_reference' => trim((string)($body['tax_reference'] ?? '')) ?: null,
                'attributes' => $this->entityAttributes($body),
            ]);
            $this->createInlineDeadlines($clientId, $entityId, $body);
        }

        $appUrl = \Application\Config\App::get('url', 'https://white-bison-201906.hostingersite.com');
        $activationLink = rtrim($appUrl, '/') . '/activate?token=' . urlencode($activationToken);

        $otp = (new \Application\Models\OtpChallenge())->issue($userId, $email, \Application\Models\OtpChallenge::ACTIVATION);
        if (!$otp['ok'] || !\Application\Services\NotificationService::sendWelcomeActivationEmail($email, $name, $activationLink, $otp['code'])) {
            (new \Application\Models\OtpChallenge())->invalidate($userId, \Application\Models\OtpChallenge::ACTIVATION);
            \Application\Core\Session::setFlash('error', 'The account was created, but the verification email could not be sent. Use resend from the activation page.');
            $response->redirect('/staff/clients');
            return;
        }

        \Application\Services\AuditService::log('client_created', 'clients', $clientId);

        \Application\Core\Session::setFlash('success', "Client account for '{$name}' created! A welcome activation email has been dispatched.");
        $response->redirect('/staff/clients');
    }

    public function resetPassword(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $newPassword = trim($body['new_password'] ?? '');

        if ($clientId <= 0) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Client ID and new password are required.'], 400);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Client ID and new password are required.');
            $response->redirect('/staff/clients');
            return;
        }

        $client = $this->clientModel->findById($clientId);
        if (!$client) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Client account not found.'], 404);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Client not found.');
            $response->redirect('/staff/clients');
            return;
        }

        $otpModel = new \Application\Models\OtpChallenge();
        $otp = $otpModel->issue((int)$client['user_id'], $client['email'], \Application\Models\OtpChallenge::PASSWORD_RESET);
        $resetToken=bin2hex(random_bytes(32)); (new \Application\Models\User())->createResetToken($client['email'],$resetToken,date('Y-m-d H:i:s',time()+600));
        $resetLink=rtrim(\Application\Config\App::get('url'),'/').'/password/verify?token='.urlencode($resetToken);
        if (!$otp['ok'] || !\Application\Services\NotificationService::sendVerificationCodeEmail($client['email'], $client['name'], $otp['code'], 'password reset', $resetLink)) {
            $otpModel->invalidate((int)$client['user_id'], \Application\Models\OtpChallenge::PASSWORD_RESET);
            $msg = 'We could not send the password-reset verification code. Please try again.';
            if ($request->isAjax()) { $response->json(['success'=>false,'message'=>$msg],422); return; }
            \Application\Core\Session::setFlash('error',$msg); $response->redirect('/staff/clients/'.$clientId); return;
        }

        \Application\Services\AuditService::log('staff_reset_client_password', 'users', $client['user_id']);
        
        $msg = "A password-reset verification code was sent to '{$client['name']}'.";
        if ($request->isAjax()) {
            $response->json(['success' => true, 'message' => $msg]);
            return;
        }

        \Application\Core\Session::setFlash('success', $msg);
        $response->redirect('/staff/clients/' . $clientId);
    }

    public function delete(Request $request, Response $response): void
    {
        $body     = $request->getBody();
        $clientId = (int)($body['client_id'] ?? 0);
        $confirmation = trim((string)($body['confirm_delete'] ?? ''));

        if ($clientId <= 0 || $confirmation !== 'DELETE') {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Type DELETE to confirm permanent client removal.'], 422);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Type DELETE to confirm permanent client removal.');
            $response->redirect('/staff/clients');
            return;
        }

        $client = $this->clientModel->findById($clientId);
        if ($client) {
            $name = $client['name'];
            $this->clientModel->delete($clientId);
            \Application\Services\AuditService::log('client_deleted', 'clients', $clientId);

            $msg = "Client account '{$name}' removed successfully.";
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg, 'redirect' => '/staff/clients']);
                return;
            }
            \Application\Core\Session::setFlash('success', $msg);
        }

        $response->redirect('/staff/clients');
    }

    public function addEntity(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $companyName = trim($body['entity_name'] ?? $body['company_name'] ?? '');
        $companyNum  = trim($body['company_number'] ?? '');
        $taxRef      = trim($body['tax_reference'] ?? '');

        if ($clientId > 0 && !empty($companyName)) {
            $entityId = $this->entityModel->create([
                'client_id'      => $clientId,
                'company_name'   => $companyName,
                'entity_type'    => trim((string)($body['entity_type'] ?? 'Other')) ?: 'Other',
                'company_number' => $companyNum,
                'tax_reference'  => $taxRef,
                'attributes'     => $this->entityAttributes($body),
            ]);
            $this->createInlineDeadlines($clientId, $entityId, $body);

            \Application\Services\AuditService::log('entity_added', 'client_entities', $clientId);
            \Application\Core\Session::setFlash('success', "Business entity '{$companyName}' linked to client.");
        }

        $response->redirect('/staff/clients/' . $clientId);
    }

    private function entityAttributes(array $body): array
    {
        $map = [
            'vat_number' => 'VAT registration number',
            'ct_utr' => 'Corporation Tax UTR',
            'accounting_year_end' => 'Accounting year end',
            'personal_utr' => 'Personal UTR',
            'tax_year' => 'Tax year',
        ];
        $attributes = [];
        foreach ($map as $key => $label) {
            $value = trim((string)($body[$key] ?? ''));
            if ($value !== '') $attributes[$key] = ['label'=>$label, 'value'=>$value];
        }
        $customLabel = trim((string)($body['custom_attribute_label'] ?? ''));
        $customValue = trim((string)($body['custom_attribute_value'] ?? ''));
        if ($customLabel !== '' && $customValue !== '') {
            $attributes['custom_' . substr(hash('sha256', strtolower($customLabel)), 0, 12)] = ['label'=>$customLabel, 'value'=>$customValue];
        }
        return $attributes;
    }

    private function createInlineDeadlines(int $clientId, int $entityId, array $body): void
    {
        $types = is_array($body['deadline_type'] ?? null) ? $body['deadline_type'] : [];
        $dates = is_array($body['deadline_due_date'] ?? null) ? $body['deadline_due_date'] : [];
        foreach ($types as $index => $type) {
            $type = trim((string)$type);
            $date = trim((string)($dates[$index] ?? ''));
            if ($type === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
            $this->deadlineModel->create(['client_id'=>$clientId, 'entity_id'=>$entityId, 'type'=>$type, 'due_date'=>$date, 'status'=>'Pending']);
        }
    }
}
