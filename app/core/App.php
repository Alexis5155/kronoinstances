<?php
namespace app\core;

class App {
    protected $controller = 'Dashboard';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        if (isset($url[0]) && file_exists(__DIR__ . '/../controllers/' . ucfirst($url[0]) . '.php')) {
            $this->controller = ucfirst($url[0]);
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

        if (isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    private function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}