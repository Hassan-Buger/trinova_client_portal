<?php

namespace Application\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        if (!file_exists($viewFile)) {
            die("View file not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // AJAX navigation is an opt-in enhancement. Normal requests still render
        // the complete layout, which keeps every route usable without JavaScript.
        if (($_SERVER['HTTP_X_TRINOVA_PARTIAL'] ?? '') === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'html' => $content,
                'title' => $data['pageTitle'] ?? 'TriNova Client Portal',
                'flash' => [
                    'success' => Session::getFlash('success'),
                    'error' => Session::getFlash('error'),
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return;
        }

        $layoutFile = dirname(__DIR__) . "/Views/layouts/{$layout}.php";
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}
