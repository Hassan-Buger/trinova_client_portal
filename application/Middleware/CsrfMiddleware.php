<?php

namespace Application\Middleware;

use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;

class CsrfMiddleware
{
    public function handle(Request $request, Response $response, array $args = []): bool
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $token = $request->input('csrf_token') ?? '';
            if (!Session::verifyCsrf($token)) {
                $response->setStatusCode(403);
                die('CSRF Validation Failed: Security token invalid or expired.');
            }
        }
        return true;
    }
}
