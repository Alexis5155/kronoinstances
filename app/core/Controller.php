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
   
    /**
     * Vérifie que l'utilisateur est connecté ET actif.
     * Redirige vers login si non connecté.
     * Redirige vers une page d'attente si compte en cours de validation.
     * À appeler en première ligne de chaque méthode protégée.
     */
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $return = urlencode($_SERVER['REQUEST_URI']);
            $this->redirect('login?return=' . $return);
        }

        $status = $_SESSION['user_status'] ?? 'active';

        if ($status === 'pending_email') {
            $this->redirect('register/pending-email');
        }

        if ($status === 'pending_approval') {
            $this->redirect('register/pending-approval');
        }

        if ($status === 'banned') {
            session_destroy();
            $this->redirect('login?error=banned');
        }
    }
}
