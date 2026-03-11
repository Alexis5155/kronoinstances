<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use app\models\User;
use app\models\Log;
use app\models\Parametre;

class Login extends Controller {

    public function index() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $data = [
            'register_errors' => $_SESSION['register_errors'] ?? [],
            'register_old'    => $_SESSION['register_old']    ?? [],
            'forgot_error'    => $_SESSION['forgot_error']    ?? '',
            'forgot_success'  => $_SESSION['forgot_success']  ?? '',
            'login_error'     => '',
        ];

        unset(
            $_SESSION['register_errors'],
            $_SESSION['register_old'],
            $_SESSION['forgot_error'],
            $_SESSION['forgot_success']
        );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_POST['csrf_token']) || !checkCsrf($_POST['csrf_token'])) {
                $data['login_error'] = "Session expirée ou tentative invalide. Veuillez actualiser la page.";
            } else {
                $userModel  = new User();
                $identifier = trim($_POST['username'] ?? '');
                $password   = $_POST['password'] ?? '';
                $user       = $userModel->authenticate($identifier, $password);

                if ($user) {
                    // 🔴 FIX 3 : vérification du statut avant mise en session
                    if ($user['status'] === 'pending_email') {
                        $data['login_error'] = "Vous devez d'abord vérifier votre adresse e-mail.";
                    } elseif ($user['status'] === 'pending_approval') {
                        $data['login_error'] = "Votre compte est en attente de validation par un administrateur.";
                    } elseif ($user['status'] === 'banned') {
                        $data['login_error'] = "Votre compte a été désactivé. Contactez l'administrateur.";
                    } else {
                        Log::add('LOGIN', "Connexion réussie", $user['id'], 'user');

                        $_SESSION['user_id']     = $user['id'];
                        $_SESSION['username']    = $user['username'];
                        $_SESSION['permissions'] = $user['permissions'];
                        $_SESSION['prenom']      = $user['prenom'];
                        $_SESSION['nom']         = $user['nom'];
                        $_SESSION['user_status'] = $user['status'];

                        $target = $_POST['return'] ?? $_GET['return'] ?? 'dashboard';
                        $this->redirect($target);
                    }
                } else {
                    Log::add('LOGIN_FAIL', "Échec de connexion pour : " . $identifier);
                    $data['login_error'] = "Identifiant ou mot de passe incorrect.";
                }
            }
        }

        // Récupérer allow_register depuis les settings
        $parametre = new Parametre();
        $data['allow_register'] = (bool) $parametre->get('allow_register');

        $data['csrf_token'] = getCsrfToken();

        $this->render('auth/login', $data);
    }

    /**
     * Déconnexion sécurisée
     */
    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        $this->redirect('login');
    }
}
