<?php

namespace Application\Middleware;

use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request, Response $response, array $args = []): bool
    {
        if (!Session::get('user_id')) {
            Session::setFlash('error', 'Please sign in to access your portal.');
            $response->redirect('/login');
            return false;
        }
        return true;
    }
}
