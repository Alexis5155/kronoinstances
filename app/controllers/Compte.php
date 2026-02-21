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
        exit;}

        $userModel = new User();
        $userId = $_SESSION['user_id'];
        $user = $userModel->getInfoWithService($userId);

        if (!$user) { $this->redirect('logout'); }

        // --- TRAITEMENT DU FORMULAIRE ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !checkCsrf($_POST['csrf_token'])) {
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
                    $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$newEmail, $userId]);
                    $newData['email'] = $newEmail;
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
                }

                setToast("Profil mis à jour avec succès.");
                
                // On recharge les infos fraîches
                $user = $userModel->getInfoWithService($userId);
            }
        }

        $this->render('user/compte', [
            'user' => $user,
            'csrf_token' => getCsrfToken()
        ]);
    }
}