<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Log;

class Compte extends Controller {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $currentPath = $_GET['url'] ?? ''; 
            $this->redirect('login?return=' . urlencode($currentPath));
            exit;
        }

        $userModel = new User();
        $userId = $_SESSION['user_id'];
        
        // Remplacement de getInfoWithService (obsolète) par getById
        $user = $userModel->getById($userId);

        if (!$user) { 
            $this->redirect('logout'); 
            exit;
        }

        // --- TRAITEMENT DU FORMULAIRE ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            // Vérification CSRF standardisée
            if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
                setToast("Erreur de sécurité (CSRF).", "danger");
            } else {
                $db = \app\core\Database::getConnection();
                
                // On capture l'état avant modification pour l'audit
                $oldUser = $user;
                $newData = [];
                $passwordChanged = false;

                // 1. Mise à jour Email
                $newEmail = trim($_POST['email']);
                if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    // Vérifier si l'email n'est pas déjà pris par qqn d'autre
                    $existing = $userModel->findByEmail($newEmail);
                    if ($existing && $existing['id'] != $userId) {
                        setToast("Cette adresse email est déjà utilisée.", "danger");
                    } else {
                        $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $stmt->execute([$newEmail, $userId]);
                        $newData['email'] = $newEmail;
                    }
                } else {
                    setToast("Format d'email invalide.", "warning");
                }

                // 2. Mise à jour Mot de passe (optionnel)
                if (!empty($_POST['password'])) {
                    $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newPass, $userId]);
                    $passwordChanged = true;
                }

                // --- LOG AUDIT TRAIL ---
                if (!empty($newData) || $passwordChanged) {
                    $details = "Mise à jour du profil personnel";
                    if ($passwordChanged) $details .= " (Mot de passe modifié)";

                    Log::add(
                        'UPDATE_PROFILE', 
                        $details, 
                        $userId, 
                        'user', 
                        ['email' => $oldUser['email']], 
                        $newData
                    );
                    setToast("Profil mis à jour avec succès.");
                }

                // On recharge les infos fraîches
                $user = $userModel->getById($userId);
            }
        }

                // Génération d'un token CSRF s'il n'existe pas
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        // On récupère les slugs de permissions de l'utilisateur
        $permissionsSlugs = $userModel->getPermissions($userId);
        $isAdmin = in_array('manage_system', $permissionsSlugs) || in_array('manage_users', $permissionsSlugs);

        // On traduit les slugs en libellés compréhensibles grâce au catalogue
        $catalog = \app\config\Permissions::LIST;
        $userPermissionsNames = [];
        foreach ($permissionsSlugs as $slug) {
            if (isset($catalog[$slug])) {
                $userPermissionsNames[] = $catalog[$slug];
            }
        }

        $this->render('user/compte', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'userPermissionsNames' => $userPermissionsNames, // Ajout de la liste traduite
            'csrf_token' => $_SESSION['csrf']
        ]);
    }
}