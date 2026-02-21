<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Log;

class Login extends Controller {

    public function index() {
        // Si déjà connecté, redirection vers le tableau de bord
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $data = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // 1. Vérification de sécurité CSRF
            if (!isset($_POST['csrf_token']) || !checkCsrf($_POST['csrf_token'])) {
                $data['error'] = "Session expirée ou tentative invalide (CSRF). Veuillez actualiser la page.";
            } else {
                // 2. Authentification Hybride
                $userModel = new User();
                
                // On récupère l'identifiant (Username ou Email) et on nettoie les espaces
                $identifier = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                // Appel de la méthode authenticate (qui doit gérer le OR email = ?)
                $user = $userModel->authenticate($identifier, $password);
                
                if ($user) {
                    Log::add('LOGIN', "Connexion réussie", $user['id'], 'user');
                    
                    // 3. Mise en session des informations utilisateur
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['role_id']     = $user['role_id'];
                    $_SESSION['user_power']  = $user['role_power'];
                    $_SESSION['service_id']  = $user['service_id'];
                    $_SESSION['permissions'] = $user['permissions'];

                    // Redirection : priorité au champ 'return' du POST (si présent dans un champ caché), 
                    // sinon au 'return' du GET, sinon par défaut 'dashboard'
                    $target = $_POST['return'] ?? $_GET['return'] ?? 'dashboard';
    
                    $this->redirect($target);

                } else {
                    // Log de l'échec pour surveillance
                    Log::add('LOGIN_FAIL', "Échec de connexion pour l'identifiant : " . $identifier);
                    $data['error'] = 'Identifiant ou mot de passe incorrect.';
                }
            }
        }

        // Génération d'un nouveau jeton CSRF pour le formulaire
        $data['csrf_token'] = getCsrfToken();
        
        $this->render('auth/login', $data);
    }

    /**
     * Déconnexion sécurisée
     */
    public function logout() {
        // On vide le tableau de session
        $_SESSION = [];
        
        // On détruit le cookie de session si présent
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // On détruit la session côté serveur
        session_destroy();
        
        // Redirection vers la page de login
        $this->redirect('login');
    }
}