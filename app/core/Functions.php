<?php

require_once __DIR__ . '/../../version.php';

function writeLog($pdo, $action, $details) {
    if (!isset($_SESSION['user_id'])) return; // Sécurité si session expirée
    
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $ip]);
}

function setToast($message, $type = 'success') {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    
    $_SESSION['toasts'][] = [
        'message' => $message,
        'type'    => $type
    ];
}

function diagnostiquerFichiers($pdo, $is_admin_path = false) {
    $erreurs = ['bdd_manquants' => [], 'fichiers_fantomes' => []];
    $repertoire = $is_admin_path ? '../uploads/' : 'uploads/';

    if (!is_dir($repertoire)) return $erreurs;

    // 1. Liens brisés (BDD -> Vide)
    $stmt = $pdo->query("SELECT id, num_complet, fichier_path FROM arretes WHERE fichier_path IS NOT NULL AND fichier_path != ''");
    while ($row = $stmt->fetch()) {
        if (!file_exists($repertoire . $row['fichier_path'])) {
            $erreurs['bdd_manquants'][] = $row;
        }
    }

    // 2. Fichiers Orphelins (Disque -> Inconnu)
    $fichiers_disque = array_diff(scandir($repertoire), array('.', '..', '.htaccess', 'index.php'));
    foreach ($fichiers_disque as $fichier) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM arretes WHERE fichier_path = ?");
        $stmt->execute([$fichier]);
        
        if ($stmt->fetchColumn() == 0) {
            $num_extrait = pathinfo($fichier, PATHINFO_FILENAME);
            
            $stmt_match = $pdo->prepare("SELECT id, num_complet, titre FROM arretes WHERE num_complet = ? AND (fichier_path IS NULL OR fichier_path = '')");
            $stmt_match->execute([$num_extrait]);
            $match = $stmt_match->fetch();

            $erreurs['fichiers_fantomes'][] = [
                'nom' => $fichier,
                'match' => $match ? $match : null
            ];
        }
    }
    return $erreurs;
}

/**
 * Protection XSS : Échappe les caractères HTML
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Génère ou récupère un jeton CSRF
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un jeton CSRF
 */
function checkCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>