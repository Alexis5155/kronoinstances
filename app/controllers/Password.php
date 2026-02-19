<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Log;
use app\core\Mailer;
use app\core\Captcha;
use app\core\Database;

/**
 * Contrôleur gérant la récupération de mot de passe
 */
class Password extends Controller {

    /**
     * Affiche le formulaire "Mot de passe oublié" et traite l'envoi
     */
    public function forgot() {
        $data = [
            'title' => 'Réinitialisation du mot de passe',
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // 1. Vérification du Rate Limit (Max 3 tentatives par heure par IP)
            if ($this->isSpamming($ip)) {
                $data['error'] = "Trop de tentatives. Veuillez réessayer dans une heure.";
                $this->render('password/forgot', $data);
                return;
            }

            // 2. Vérification du Captcha
            $user_captcha = strtoupper($_POST['captcha_input'] ?? '');
            $session_captcha = $_SESSION['captcha_code'] ?? '';

            if (empty($user_captcha) || $user_captcha !== $session_captcha) {
                $data['error'] = "Le code de sécurité est incorrect.";
                // On ne vide pas la session ici pour permettre une correction immédiate si l'utilisateur s'est trompé
            } else {
                // Captcha correct : on l'invalide pour la sécurité
                unset($_SESSION['captcha_code']);

                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                
                if (!empty($email)) {
                    $userModel = new User();
                    $user = $userModel->findByEmail($email);

                    if ($user) {
                        // Génération d'un jeton unique et sécurisé
                        $token = bin2hex(random_bytes(32));
                        
                        // Enregistrement en base de données (valide 1h)
                        if ($userModel->setResetToken($user['id'], $token)) {
                            
                            // Envoi de l'email via la classe Mailer
                            if ($this->sendResetEmail($user['email'], $token)) {
                                $data['success'] = "Un lien de réinitialisation vous a été envoyé par email.";
                                // Log de la demande (Sert aussi au Rate Limiting)
                                Log::add('AUTH_RESET_REQ', "Demande de réinitialisation pour : " . $email);
                            } else {
                                $data['error'] = "Erreur lors de l'envoi de l'email. Contactez un administrateur.";
                            }
                        }
                    } else {
                        // Sécurité : Message identique même si l'email n'existe pas
                        $data['success'] = "Si cet email correspond à un compte, un lien de réinitialisation a été envoyé.";
                    }
                } else {
                    $data['error'] = "Veuillez saisir une adresse email valide.";
                }
            }
        }

        $this->render('password/forgot', $data);
    }

    /**
     * Génère l'image du captcha (Appelé par la balise <img> dans la vue)
     */
    public function captcha() {
        Captcha::generate();
    }

    /**
     * Affiche le formulaire de saisie du nouveau mot de passe
     */
    public function reset($token = null) {
        if (!$token) {
            $this->redirect('login');
        }

        $userModel = new User();
        $user = $userModel->getUserByToken($token);

        // Vérification de la validité du token
        if (!$user) {
            $data = [
                'title' => 'Lien expiré',
                'error' => 'Ce lien de réinitialisation est invalide ou a expiré.'
            ];
            $this->render('password/forgot', $data);
            return;
        }

        $data = [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (empty($password) || strlen($password) < 8) {
                $data['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($password !== $confirm) {
                $data['error'] = "Les mots de passe ne correspondent pas.";
            } else {
                // Mise à jour du MDP et suppression du token
                if ($userModel->resetPassword($user['id'], $password)) {
                    Log::add('AUTH_RESET_SUCCESS', "Mot de passe modifié avec succès via jeton", $user['id'], 'user');
                    
                    $_SESSION['flash_success'] = "Votre mot de passe a été mis à jour. Vous pouvez vous connecter.";
                    $this->redirect('login');
                }
            }
        }

        $this->render('password/reset', $data);
    }

    /**
     * Logique d'envoi SMTP
     */
    private function sendResetEmail($to, $token) {
        $resetLink = URLROOT . "/password/reset/" . $token;
        
        $subject = "Réinitialisation de votre mot de passe - KronoActes";
        $body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h3 style='color: #0d6efd;'>Réinitialisation de mot de passe</h3>
                <p>Vous avez demandé la réinitialisation de votre mot de passe pour l'application <strong>KronoActes</strong>.</p>
                <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                <p style='margin: 30px 0; text-align: center;'>
                    <a href='{$resetLink}' style='padding: 12px 25px; color: #fff; background-color: #0d6efd; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Réinitialiser mon mot de passe</a>
                </p>
                <p style='font-size: 0.8rem; color: #666; border-top: 1px solid #eee; padding-top: 15px;'>
                    Ce lien expirera dans 1 heure. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email en toute sécurité.
                </p>
            </div>
        ";

        return Mailer::send($to, $subject, $body);
    }

    /**
     * Vérifie si l'IP a déjà fait trop de demandes (Rate Limiting)
     */
    private function isSpamming($ip) {
        $db = \app\core\Database::getConnection(); 
        
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM logs 
            WHERE action = 'AUTH_RESET_REQ' 
            AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        $count = $stmt->fetchColumn();

        return ($count >= 3);
    }
}