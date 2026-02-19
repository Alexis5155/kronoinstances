<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Parametre;
use app\core\Database;
use app\models\Log;

class Update extends Controller {

    private $temp_dir = 'backups/temp_update/';

    public function __construct() {
        if (!isset($_SESSION['user_id']) || !\app\models\User::can('manage_system')) {
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' 
                || strpos($_SERVER['REQUEST_URI'], '/download') !== false 
                || strpos($_SERVER['REQUEST_URI'], '/install') !== false 
                || strpos($_SERVER['REQUEST_URI'], '/cleanup') !== false) {
                
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Session expirée ou droits insuffisants.']);
                exit;
            }

            $this->redirect('login');
            exit;
        }
    }

    /**
     * Affiche l'interface de progression avec une vérification initiale
     */
    public function index() {
        // Optionnel : On peut refaire la vérification ici pour empêcher l'accès à la page
        // si aucune mise à jour n'est détectée.
        $this->render('admin/update');
    }

    /**
     * ÉTAPE 1 : Téléchargement et Extraction (AVEC VÉRIFICATION DE VERSION)
     */
    public function download() {
        $paramModel = new Parametre();
        $track = $paramModel->get('update_track') ?: 'main';
        
        $github_user = "Alexis5155";
        $github_repo = "kronoactes";

        $url = ($track === 'beta') 
            ? "https://api.github.com/repos/$github_user/$github_repo/releases" 
            : "https://api.github.com/repos/$github_user/$github_repo/releases/latest";

        $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: KronoActes-App']]];
        $context = stream_context_create($opts);
        $res = json_decode(@file_get_contents($url, false, $context), true);
        $release = ($track === 'beta') ? ($res[0] ?? null) : $res;

        if (!$release) {
            $this->jsonReply(false, "Impossible de joindre GitHub.");
        }

        $new_version = $release['tag_name'];

        // ==========================================
        // SÉCURITÉ : VÉRIFICATION DE VERSION (Comme à l'origine)
        // ==========================================
        if (version_compare($new_version, APP_VERSION, '<=')) {
            $this->jsonReply(false, "Action annulée : Votre système est déjà à jour (v" . APP_VERSION . ").");
        }

        // Si la vérification passe, on procède au téléchargement
        if (!is_dir($this->temp_dir)) mkdir($this->temp_dir, 0777, true);
        $zipPath = $this->temp_dir . 'update.zip';
        
        $zip_content = @file_get_contents($release['zipball_url'], false, $context);
        if (!$zip_content) {
            $this->jsonReply(false, "Échec du téléchargement du paquet ZIP.");
        }
        
        file_put_contents($zipPath, $zip_content);

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($this->temp_dir);
            $zip->close();
            unlink($zipPath);
            
            $_SESSION['target_version'] = $new_version;
            $this->jsonReply(true, "Mise à jour v$new_version téléchargée et prête.");
        }
        
        $this->jsonReply(false, "Échec de l'extraction de l'archive.");
    }

    /**
     * ÉTAPE 2 : Installation (Fichiers existants mis à jour, obsolètes supprimés)
     */
    public function install() {
        // La sécurité repose ici sur la session définie à l'étape précédente
        if (!isset($_SESSION['target_version'])) {
            $this->jsonReply(false, "Session de mise à jour expirée ou invalide.");
        }

        $extracted = glob($this->temp_dir . '*', GLOB_ONLYDIR);
        if (empty($extracted)) $this->jsonReply(false, "Dossier source introuvable.");
        
        $src = $extracted[0] . '/';
        $dst = './'; 

        $this->syncAndClean($src, $dst);

        // Mise à jour du fichier version.php
        $version = $_SESSION['target_version'];
        file_put_contents('version.php', "<?php\ndefine('APP_VERSION', '$version');");

        $this->jsonReply(true, "Fichiers installés et système synchronisé.");
    }

    /**
     * ÉTAPE 3 : Nettoyage final
     */
    public function cleanup() {
        $this->recursiveRmdir($this->temp_dir);
        $v = $_SESSION['target_version'] ?? '?';
        Log::add('SYSTEM_UPDATE', "Mise à jour vers la version " . $v);
        
        unset($_SESSION['target_version']);
        $this->jsonReply(true, "Mise à jour terminée avec succès !");
    }

    // LOGIQUE DE SYNCHRONISATION (La "Cerveau" de l'update)
    private function syncAndClean($src, $dst) {
        // Liste des fichiers et dossiers à NE JAMAIS toucher
        $excluded = ['config.php', 'uploads', 'backups', '.git', '.htaccess', 'version.php'];
        
        // A. COPIE / MISE À JOUR (Source vers Destination)
        /** @var \RecursiveIteratorIterator|\RecursiveDirectoryIterator $items */
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS), 
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $dst . $items->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) @mkdir($target, 0777, true);
            } else {
                // On n'écrase pas les fichiers exclus s'ils existent déjà
                if (!in_array(basename($target), $excluded)) {
                    copy($item->getRealPath(), $target);
                }
            }
        }

        // B. SUPPRESSION DES OBSOLÈTES (Scan du dossier 'app' sur le serveur)
        if (is_dir($dst . 'app')) {
            $appDir = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dst . 'app', \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($appDir as $file) {
                $relativePath = str_replace(realpath($dst) . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $sourcePath = $src . $relativePath;
                
                $fileName = $file->getBasename();

                // --- PROTECTION CRITIQUE ---
                // Si le fichier est dans la liste d'exclusion, on passe au suivant
                if (in_array($fileName, $excluded)) {
                    continue; 
                }

                // Si le fichier existe sur ton serveur mais plus dans le nouveau code source
                if (!file_exists($sourcePath)) {
                    if ($file->isDir()) {
                        // On ne supprime le dossier que s'il est vide
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                    }
                }
            }
        }
    }
    
    private function recursiveRmdir($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function jsonReply($status, $msg) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $msg]);
        exit();
    }
}