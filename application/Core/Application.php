<?php

namespace Application\Core;

class Application
{
    public static string $ROOT_DIR;
    public Router $router;
    public Request $request;
    public Response $response;

    public function __construct(string $rootPath)
    {
        self::$ROOT_DIR = $rootPath;
        Session::start();
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
    }

    public function run(): void
    {
        $this->router->resolve($this->request, $this->response);
    }
}
