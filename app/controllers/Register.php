<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use app\core\Mailer;
use app\models\Log;

class Register extends Controller {

    // ── Page d'inscription ──────────────────────────────────────────────
    public function index() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $db   = Database::getConnection();
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'allow_register' LIMIT 1");
        if (!$stmt || !$stmt->fetchColumn()) {
            $this->redirect('login');
        }

        $data = ['errors' => [], 'csrf_token' => getCsrfToken()];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!checkCsrf($_POST['csrf_token'] ?? '')) {
                $data['errors'][] = "Session expirée. Veuillez réessayer.";
            } else {
                $data = array_merge($data, $this->handleRegister($db));
            }
        }

        $this->render('auth/register', $data);
    }

    // ── Traitement du formulaire ────────────────────────────────────────
    private function handleRegister($db): array {
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $user   = trim($_POST['username'] ?? '');
        $pass   = $_POST['password']  ?? '';
        $pass2  = $_POST['password2'] ?? '';

        $errors = [];

        if (!$nom || !$prenom)          $errors[] = "Nom et prénom obligatoires.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Adresse e-mail invalide.";
        if (strlen($user) < 3)          $errors[] = "Nom d'utilisateur trop court (3 caractères min).";
        if (strlen($pass) < 8)          $errors[] = "Mot de passe trop court (8 caractères min).";
        if ($pass !== $pass2)           $errors[] = "Les mots de passe ne correspondent pas.";

        if (empty($errors)) {
            // Vérifier unicité email + username
            $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $check->execute([$email, $user]);
            if ($check->fetch()) {
                $errors[] = "Cet e-mail ou ce nom d'utilisateur est déjà utilisé.";
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors, 'csrf_token' => getCsrfToken(),
                    'old' => compact('nom', 'prenom', 'email', 'user')];
        }

        // Récupérer les settings
        $stmtSettings = $db->query(
            "SELECT setting_key, setting_value FROM settings 
             WHERE setting_key IN ('require_approval')"
        );
        $settings = [];
        foreach ($stmtSettings->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $requireApproval = !empty($settings['require_approval']);
        $token           = bin2hex(random_bytes(32));
        $hash            = password_hash($pass, PASSWORD_BCRYPT);
        $defaultRoleId   = 2; // rôle "utilisateur" par défaut

        // Insertion
        $insert = $db->prepare("
            INSERT INTO users (username, password, email, nom, prenom, role_id, status, email_verify_token, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending_email', ?, NOW())
        ");
        $insert->execute([$user, $hash, $email, $nom, $prenom, $defaultRoleId, $token]);
        $userId = $db->lastInsertId();

        Log::add('REGISTER', "Nouvelle inscription : $email", $userId, 'user');

        // Envoi de l'email de vérification
        $verifyUrl = URLROOT . '/register/verify?token=' . $token;
        Mailer::send(
            $email,
            "Vérifiez votre adresse e-mail",
            "
            <p>Bonjour $prenom,</p>
            <p>Cliquez sur le lien ci-dessous pour valider votre adresse e-mail :</p>
            <p><a href='$verifyUrl' style='background:#0d6efd;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>
                Vérifier mon e-mail
            </a></p>
            <p style='color:#888;font-size:0.85rem;'>Ce lien est valable 24h.</p>
            ",
            true
        );

        // Stocker en session pour afficher la bonne page d'attente
        $_SESSION['pending_email'] = $email;
        $_SESSION['require_approval_after'] = $requireApproval;

        $this->redirect('register/pending-email');
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
            $this->render('auth/verify_invalid', []);
            return;
        }

        // Récupérer require_approval
        $stmtSetting = $db->query(
            "SELECT setting_value FROM settings WHERE setting_key = 'require_approval' LIMIT 1"
        );
        $requireApproval = $stmtSetting ? (bool)$stmtSetting->fetchColumn() : false;

        $newStatus = $requireApproval ? 'pending_approval' : 'active';

        $db->prepare(
            "UPDATE users SET status = ?, email_verified_at = NOW(), email_verify_token = NULL WHERE id = ?"
        )->execute([$newStatus, $user['id']]);

        // Liaison automatique aux instances
        $this->linkToInstances($db, $user['id'], $user['email']);

        Log::add('EMAIL_VERIFIED', "E-mail vérifié", $user['id'], 'user');

        if ($requireApproval) {
            // Notifier les admins
            $this->notifyAdmins($db, $user);
            $this->redirect('register/pending-approval');
        } else {
            $this->redirect('login?verified=1');
        }
    }

    // ── Pages d'état ────────────────────────────────────────────────────
    public function pendingEmail() {
        $email = $_SESSION['pending_email'] ?? null;
        $this->render('auth/pending_email', ['email' => $email]);
    }

    public function pendingApproval() {
        $this->render('auth/pending_approval', []);
    }

    // ── Liaison aux instances par email ─────────────────────────────────
    private function linkToInstances($db, $userId, $email) {
        $stmt = $db->prepare(
            "SELECT id FROM membres WHERE email = ? AND user_id IS NULL"
        );
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
    private function notifyAdmins($db, $user) {
        $stmt = $db->query(
            "SELECT u.email, u.prenom FROM users u
             JOIN roles r ON u.role_id = r.id
             JOIN role_permissions rp ON r.id = rp.role_id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE p.name = 'manage_users' AND u.status = 'active'"
        );
        $admins = $stmt->fetchAll();

        $approveUrl = URLROOT . '/admin/users';
        foreach ($admins as $admin) {
            Mailer::send(
                $admin['email'],
                "Nouveau compte en attente d'approbation",
                "
                <p>Bonjour {$admin['prenom']},</p>
                <p><strong>{$user['prenom']}</strong> ({$user['email']}) vient de créer un compte et attend votre validation.</p>
                <p><a href='$approveUrl' style='background:#0d6efd;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>
                    Gérer les comptes
                </a></p>
                ",
                true
            );
        }
    }
}
