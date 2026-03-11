<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Log;
use app\core\Mailer;
use app\core\Captcha;
use app\core\Database;

class Password extends Controller {

    /**
     * Endpoint POST uniquement — traite la demande de reset
     */
    public function forgot() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('login');
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        if ($this->isSpamming($ip)) {
            $_SESSION['forgot_error'] = "Trop de tentatives. Veuillez réessayer dans une heure.";
            $this->redirect('login');
        }

        if (!checkCsrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['forgot_error'] = "Session expirée. Veuillez réessayer.";
            $this->redirect('login');
        }

        // ── Vérification captcha via la classe universelle ──────────────
        if (!Captcha::verify($_POST['captcha_input'] ?? '', 'captcha_code')) {
            $_SESSION['forgot_error'] = "Le code de sécurité est incorrect.";
            $this->redirect('login');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (empty($email)) {
            $_SESSION['forgot_error'] = "Veuillez saisir une adresse e-mail valide.";
            $this->redirect('login');
        }

        $userModel = new User();
        $user      = $userModel->findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            if ($userModel->setResetToken($user['id'], $token)) {
                if ($this->sendResetEmail($user['email'], $token)) {
                    Log::add('AUTH_RESET_REQ', "Demande de réinitialisation pour : " . $email);
                    $_SESSION['forgot_success'] = "Un lien de réinitialisation vous a été envoyé par e-mail.";
                } else {
                    $_SESSION['forgot_error'] = "Erreur lors de l'envoi de l'e-mail. Contactez un administrateur.";
                }
            }
        } else {
            $_SESSION['forgot_success'] = "Si cet e-mail correspond à un compte, un lien de réinitialisation a été envoyé.";
        }

        $this->redirect('login');
    }

    /**
     * Génère l'image captcha — clé selon le type demandé
     * /password/captcha           → captcha_code    (forgot password)
     * /password/captcha?type=register → captcha_register (inscription)
     */
    public function captcha() {
        $type = $_GET['type'] ?? 'forgot';
        $key  = $type === 'register' ? 'captcha_register' : 'captcha_code';
        Captcha::generate($key);
    }

    /**
     * Formulaire de saisie du nouveau mot de passe
     */
    public function reset($token = null) {
        if (!$token) {
            $this->redirect('login');
        }

        $userModel = new User();
        $user      = $userModel->getUserByToken($token);

        if (!$user) {
            $_SESSION['forgot_error'] = "Ce lien de réinitialisation est invalide ou a expiré.";
            $this->redirect('login');
        }

        $data = [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password']         ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 8) {
                $data['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($password !== $confirm) {
                $data['error'] = "Les mots de passe ne correspondent pas.";
            } else {
                if ($userModel->resetPassword($user['id'], $password)) {
                    Log::add('AUTH_RESET_SUCCESS', "Mot de passe modifié via jeton", $user['id'], 'user');
                    $_SESSION['flash_success'] = "Votre mot de passe a été mis à jour. Vous pouvez vous connecter.";
                    $this->redirect('login');
                }
            }
        }

        $this->render('auth/reset', $data);
    }

    /**
     * Envoi de l'email de réinitialisation
     */
    private function sendResetEmail(string $to, string $token): bool {
        $resetLink = URLROOT . "/password/reset/" . $token;

        $subject = "Réinitialisation de votre mot de passe — KronoInstances";
        $body = "
            <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px;'>
                <h3 style='color:#0d6efd;margin-bottom:8px;'>Réinitialisation de mot de passe</h3>
                <p>Vous avez demandé la réinitialisation de votre mot de passe pour <strong>KronoInstances</strong>.</p>
                <p style='margin:28px 0;text-align:center;'>
                    <a href='{$resetLink}'
                       style='padding:13px 28px;color:#fff;background:#0d6efd;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;'>
                        Réinitialiser mon mot de passe
                    </a>
                </p>
                <p style='font-size:0.8rem;color:#888;border-top:1px solid #eee;padding-top:14px;'>
                    Ce lien expire dans <strong>1 heure</strong>. Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.
                </p>
            </div>
        ";

        return Mailer::send($to, $subject, $body);
    }

    /**
     * Rate limiting : max 3 demandes par heure par IP
     */
    private function isSpamming(string $ip): bool {
        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM logs
            WHERE action = 'AUTH_RESET_REQ'
            AND ip_address = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() >= 3;
    }
}