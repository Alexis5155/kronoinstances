<?php
session_start();

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$rootPath = rtrim($scriptName, '/');
define('URLROOT', $protocol . "://" . $host . $rootPath);

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

if (file_exists('app/config/config.php')) {
    require_once 'app/config/config.php';
} else {
    header('Location: ' . URLROOT . '/install/index.php');
    exit;
}


require_once 'app/core/Autoloader.php';
require_once 'app/core/Functions.php';

use app\core\App;

$app = new App();