<?php

namespace Application\Middleware;

use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;

class RoleMiddleware
{
    public function handle(Request $request, Response $response, array $args = []): bool
    {
        $requiredRole = $args[0] ?? null;
        $userRole = Session::get('role');

        if ($requiredRole && $userRole !== $requiredRole) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'You do not have permission to perform this operation.'], 403);
                return false;
            }
            $response->setStatusCode(403);
            die('Access Denied: You do not have permission to view this resource.');
        }
        return true;
    }
}
