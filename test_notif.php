<?php
/**
 * TEST NOTIFICATIONS - KronoActes
 */

// 1. Initialisation
session_start();

// AJOUTE CETTE LIGNE : Elle contient tes identifiants de base de données
// Si ton fichier de config est ailleurs, adapte le chemin
require_once 'app/config/config.php'; 

require_once 'app/core/Database.php';

use app\core\Database;

if (!isset($_SESSION['user_id'])) {
    die("Erreur : Vous devez être connecté à KronoActes pour tester ce script.");
}

$db = Database::getConnection();
$message_status = "";

// 2. Traitement de l'envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $type = $_POST['type'];
    $msg = $_POST['message'];
    $link = $_POST['link'] ?? 'dashboard';
    $uid = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    
    if ($stmt->execute([$uid, $type, $msg, $link])) {
        $message_status = "<div class='alert alert-success'>Notification envoyée ! Va voir ta cloche. 🔔</div>";
    } else {
        $message_status = "<div class='alert alert-danger'>Erreur lors de l'envoi.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Testeur de Notifications - KronoActes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white p-4">
                        <h4 class="mb-0 fw-bold">🚀 Testeur de Notifications</h4>
                        <p class="small opacity-75 mb-0">Envoyez une alerte à l'utilisateur : <strong><?= $_SESSION['username'] ?></strong></p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?= $message_status ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Type d'alerte</label>
                                <select name="type" class="form-select">
                                    <option value="info">🔵 Info (Bleu)</option>
                                    <option value="success">🟢 Succès (Vert)</option>
                                    <option value="warning">🟡 Warning (Orange)</option>
                                    <option value="danger">🔴 Danger (Rouge)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <input type="text" name="message" class="form-control" placeholder="Ex: Un nouvel arrêté a été créé !" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Lien de redirection (optionnel)</label>
                                <input type="text" name="link" class="form-control" placeholder="Ex: liste ou dashboard">
                            </div>

                            <button type="submit" name="send_test" class="btn btn-dark w-100 fw-bold py-2">
                                <i class="bi bi-send me-2"></i> Envoyer la notification
                            </button>
                        </form>
                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <a href="dashboard" class="text-decoration-none small fw-bold">← Retour au site</a>
                    </div>
                </div>
                <div class="alert alert-info mt-4 small border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Note :</strong> Pour voir la notification apparaître, tu devras peut-être actualiser la page de KronoActes (ou cliquer sur un lien) car nous n'avons pas encore activé le rafraîchissement AJAX.
                </div>
            </div>
        </div>
    </div>
</body>
</html>