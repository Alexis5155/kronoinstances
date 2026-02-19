<?php
spl_autoload_register(function ($class) {
    // On remplace les namespaces par des slashs pour le chemin des fichiers
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/../../' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});