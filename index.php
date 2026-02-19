<?php
session_start();

// 1. Définition de l'URLROOT dynamique (Parfait pour les sous-dossiers)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$rootPath = rtrim($scriptName, '/');
define('URLROOT', $protocol . "://" . $host . $rootPath);

// 2. Chargement des dépendances externes
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// 3. Chargement de la configuration
if (file_exists('app/config/config.php')) {
    require_once 'app/config/config.php';
} else {
    // CORRECTION : On utilise URLROOT pour une redirection absolue vers l'installateur
    header('Location: ' . URLROOT . '/install/index.php');
    exit;
}

// 4. Chargement de l'Autoloader Core (Unique désormais)
require_once 'app/core/Autoloader.php';

// 5. Chargement des fonctions globales
require_once 'app/core/Functions.php';

use app\core\App;

// 6. Lancement de l'application
$app = new App();