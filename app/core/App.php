<?php
namespace app\core;

class App {
    protected $controller = 'Dashboard';
    protected $method     = 'index';
    protected $params     = [];

    public function __construct() {
        $url = $this->parseUrl();

        // Résolution du contrôleur
        $controllerName = isset($url[0]) ? ucfirst(strtolower($url[0])) : 'Dashboard';

        if (file_exists(__DIR__ . '/../controllers/' . $controllerName . '.php')) {
            $this->controller = $controllerName;
            unset($url[0]);
        }

        $controllerClass = "\\app\\controllers\\" . $this->controller;

        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass;
        } else {
            $_SESSION['toasts'][] = ['message' => "La page demandée n'existe pas.", 'type' => 'danger'];
            header('Location: ' . URLROOT . '/dashboard');
            exit;
        }

        // Résolution de la méthode
        // Convertit "pending-email" → "pendingEmail" (kebab-case → camelCase)
        if (isset($url[1])) {
            $methodName = $this->kebabToCamel($url[1]);
            if (method_exists($this->controller, $methodName)) {
                $this->method = $methodName;
                unset($url[1]);
            }
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Convertit kebab-case en camelCase
     * Ex : "pending-email" → "pendingEmail"
     */
    private function kebabToCamel(string $str): string {
        return lcfirst(str_replace('-', '', ucwords($str, '-')));
    }

    private function parseUrl(): array {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
