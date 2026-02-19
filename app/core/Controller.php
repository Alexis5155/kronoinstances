<?php
namespace app\core;

class Controller {
    protected function render($view, $data = []) {
        extract($data);
        
        // On utilise la constante pour les inclusions si besoin
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