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
        ini_set('display_errors','0');
        ini_set('display_startup_errors','0');
        ini_set('log_errors','1');
        Session::start();
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
    }

    public function run(): void
    {
        try {
            $this->router->resolve($this->request, $this->response);
        } catch (\Throwable $e) {
            \Application\Services\ErrorHandler::handle($e,$this->request,$this->response);
        }
    }
}
