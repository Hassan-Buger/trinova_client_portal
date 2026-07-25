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
        $queryParams = method_exists($request, 'getQueryParams') ? $request->getQueryParams() : $_GET;
        $actionFilter = trim($queryParams['action'] ?? $request->input('action', ''));
        $limit        = (int)($queryParams['limit'] ?? $request->input('limit', 50));
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
