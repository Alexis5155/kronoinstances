<?php
namespace app\core;

class Controller {
    protected function render($view, $data = []) {
        extract($data);
        
        $root = URLROOT . '/'; 
        
        if (file_exists(__DIR__ . '/../views/' . $view . '.php')) {
            require_once __DIR__ . '/../views/' . $view . '.php';
        } else {
            die("La vue $view n'existe pas.");
        }
    }

    protected function redirect($url) {
        header('Location: ' . URLROOT . '/' . ltrim($url, '/'));
        exit();
    }
}