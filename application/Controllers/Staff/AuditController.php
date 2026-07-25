<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\AuditLog;

class AuditController extends Controller
{
    private AuditLog $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditLog();
    }

    public function index(Request $request, Response $response): void
    {
        $actionFilter = trim($request->getQueryParams()['action'] ?? '');
        $limit        = (int)($request->getQueryParams()['limit'] ?? 50);
        if ($limit <= 0) $limit = 50;

        $logs = $this->auditModel->getAllFiltered($actionFilter ?: null, $limit);

        $this->render('staff/audit/index', [
            'pageTitle'    => 'Practice Compliance Audit Log',
            'logs'         => $logs,
            'actionFilter' => $actionFilter,
            'limit'        => $limit,
        ], 'main');
    }
}
