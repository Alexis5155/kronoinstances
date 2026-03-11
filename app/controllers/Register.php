<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use app\core\Mailer;
use app\models\Log;
use app\models\Parametre;

class Register extends Controller {

    // ── Page d'inscription ──────────────────────────────────────────────
    public function index() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $parametre = new Parametre();
        if (!$parametre->get('allow_register')) {
            $this->redirect('login');
        }

        $data = [
            'csrf_token'      => getCsrfToken(),
            'allow_register'  => true,
            'register_errors' => $_SESSION['register_errors'] ?? [],
            'register_old'    => $_SESSION['register_old']    ?? [],
        ];
        unset($_SESSION['register_errors'], $_SESSION['register_old']);

        $this->render('auth/login', $data);
    }

    // ── Traitement POST /register/submit ────────────────────────────────
    public function submit() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $parametre = new Parametre();
        if (!$parametre->get('allow_register')) {
            $this->redirect('login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('login');
        }

        if (!checkCsrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['register_errors'] = ["Session expirée. Veuillez réessayer."];
            $this->redirect('login');
        }

        $this->handleRegister($parametre);
    }

    // ── Traitement du formulaire ────────────────────────────────────────
    private function handleRegister(Parametre $parametre): void {
        $db     = Database::getConnection();
        $nom    = trim($_POST['nom']      ?? '');
        $prenom = trim($_POST['prenom']   ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $user   = trim($_POST['username'] ?? '');
        $pass   = $_POST['password']      ?? '';
        $pass2  = $_POST['password2']     ?? '';

        $errors = [];

        // ── Captcha en premier (usage unique, doit être vérifié avant tout redirect) ──
        if (!\app\core\Captcha::verify($_POST['captcha_input'] ?? '', 'captcha_register')) {
            $errors[] = "Code de sécurité incorrect.";
        }

        if (!$nom || !$prenom)                          $errors[] = "Nom et prénom obligatoires.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Adresse e-mail invalide.";
        if (strlen($user) < 3)                          $errors[] = "Nom d'utilisateur trop court (3 caractères min).";
        if (strlen($pass) < 8)                          $errors[] = "Mot de passe trop court (8 caractères min).";
        if ($pass !== $pass2)                           $errors[] = "Les mots de passe ne correspondent pas.";

        if (empty($errors)) {
            $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $check->execute([$email, $user]);
            if ($check->fetch()) {
                $errors[] = "Cet e-mail ou ce nom d'utilisateur est déjà utilisé.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old']    = compact('nom', 'prenom', 'email') + ['username' => $user];
            $this->redirect('login');
        }

        // ── Insertion ──────────────────────────────────────────────────
        $requireApproval = (bool) $parametre->get('require_approval');
        $token           = bin2hex(random_bytes(32));
        $hash            = password_hash($pass, PASSWORD_BCRYPT);
        $defaultRoleId   = 2;

        $insert = $db->prepare("
            INSERT INTO users (username, password, email, nom, prenom, role_id, status, email_verify_token, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending_email', ?, NOW())
        ");
        $insert->execute([$user, $hash, $email, $nom, $prenom, $defaultRoleId, $token]);
        $userId = $db->lastInsertId();

        Log::add('REGISTER', "Nouvelle inscription : $email", $userId, 'user');

        // ── E-mail de vérification ──────────────────────────────────────
        $verifyUrl = URLROOT . '/register/verify?token=' . $token;
        Mailer::send(
            $email,
            "Vérifiez votre adresse e-mail — KronoInstances",
            "
            <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px;'>
                <h3 style='color:#0d6efd;'>Vérification de votre e-mail</h3>
                <p>Bonjour <strong>$prenom</strong>,</p>
                <p>Cliquez sur le bouton ci-dessous pour valider votre adresse e-mail :</p>
                <p style='margin:28px 0;text-align:center;'>
                    <a href='$verifyUrl' style='background:#0d6efd;color:white;padding:13px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>
                        Vérifier mon e-mail
                    </a>
                </p>
                <p style='font-size:0.8rem;color:#888;border-top:1px solid #eee;padding-top:14px;'>Ce lien est valable 24h.</p>
            </div>
            ",
            true
        );

        // ── Affichage panel confirmation ────────────────────────────────
        $this->render('auth/login', [
            'csrf_token'             => getCsrfToken(),
            'allow_register'         => true,
            'register_success'       => true,
            'register_success_email' => $email,
        ]);
    }

    // ── Vérification du token email ─────────────────────────────────────
    public function verify() {
        $token = trim($_GET['token'] ?? '');
        $db    = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT id, prenom, email, status FROM users
             WHERE email_verify_token = ? AND status = 'pending_email' LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['forgot_error'] = "Ce lien de vérification est invalide ou a expiré.";
            $this->redirect('login');
        }

        $parametre       = new Parametre();
        $requireApproval = (bool) $parametre->get('require_approval');
        $newStatus       = $requireApproval ? 'pending_approval' : 'active';

        $db->prepare(
            "UPDATE users SET status = ?, email_verified_at = NOW(), email_verify_token = NULL WHERE id = ?"
        )->execute([$newStatus, $user['id']]);

        $this->linkToInstances($db, $user['id'], $user['email']);
        Log::add('EMAIL_VERIFIED', "E-mail vérifié", $user['id'], 'user');

        if ($requireApproval) {
            $this->notifyAdmins($db, $user);
            $this->render('auth/login', [
                'csrf_token'      => getCsrfToken(),
                'allow_register'  => true,
                'pending_approval'=> true,
            ]);
        } else {
            $this->redirect('login?verified=1');
        }
    }

    // ── Pages d'état (redirections pour compatibilité) ───────────────────
    public function pendingEmail() {
        $this->redirect('login');
    }

    public function pendingApproval() {
        $this->redirect('login');
    }

    // ── Liaison aux instances par email ─────────────────────────────────
    private function linkToInstances($db, int $userId, string $email): void {
        $stmt = $db->prepare("SELECT id FROM membres WHERE email = ? AND user_id IS NULL");
        $stmt->execute([$email]);
        $membres = $stmt->fetchAll();

        foreach ($membres as $membre) {
            $db->prepare("UPDATE membres SET user_id = ? WHERE id = ?")
               ->execute([$userId, $membre['id']]);
        }

        if (!empty($membres)) {
            Log::add('AUTO_LINK', count($membres) . " instance(s) liée(s) automatiquement", $userId, 'user');
        }
    }

    // ── Notification aux admins ─────────────────────────────────────────
    private function notifyAdmins($db, array $user): void {
        $stmt = $db->query(
            "SELECT DISTINCT u.email, u.prenom
             FROM users u
             JOIN user_permissions up ON u.id = up.user_id
             WHERE up.permission_slug IN ('manage_users', 'manage_system')
               AND u.status = 'active'"
        );
        $admins     = $stmt->fetchAll();
        $approveUrl = URLROOT . '/admin/users';

        foreach ($admins as $admin) {
            Mailer::send(
                $admin['email'],
                "Nouveau compte en attente d'approbation — KronoInstances",
                "
                <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px;'>
                    <h3 style='color:#0d6efd;'>Nouveau compte à approuver</h3>
                    <p>Bonjour <strong>{$admin['prenom']}</strong>,</p>
                    <p><strong>{$user['prenom']}</strong> ({$user['email']}) vient de créer un compte et attend votre validation.</p>
                    <p style='margin:28px 0;text-align:center;'>
                        <a href='$approveUrl' style='background:#0d6efd;color:white;padding:13px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>
                            Gérer les comptes
                        </a>
                    </p>
                </div>
                ",
                true
            );
        }
    }
}
