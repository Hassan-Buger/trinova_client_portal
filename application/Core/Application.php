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
        try {
            $this->router->resolve($this->request, $this->response);
        } catch (\Throwable $e) {
            $isDebug = (($_ENV['APP_DEBUG'] ?? 'false') === 'true') || isset($_GET['debug']);
            if ($isDebug) {
                $this->response->setStatusCode(500);
                echo "<div style='padding:30px;background:#fff5f5;color:#991b1b;font-family:sans-serif;border:2px solid #f87171;border-radius:16px;margin:24px;box-shadow:0 10px 30px rgba(0,0,0,0.1);'>";
                echo "<h2 style='margin-top:0;font-size:20px;font-weight:800'>Application Exception Detected</h2>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line <strong>" . $e->getLine() . "</strong></p>";
                echo "<pre style='background:#fef2f2;padding:16px;border-radius:10px;overflow-x:auto;font-size:13px;line-height:1.5'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            } else {
                $this->response->setStatusCode(500);
                echo "<div style='padding:40px;text-align:center;font-family:sans-serif;color:#374151;'>";
                echo "<h2 style='font-size:24px;font-weight:800;color:#dc2626'>500 - Internal Server Error</h2>";
                echo "<p style='color:#6b7280;font-size:14px'>An unexpected error occurred. Append <code>?debug=1</code> to the URL to view diagnostic details.</p>";
                echo "</div>";
            }
        }
    }
}
