<?php
namespace app\controllers;

use app\core\Controller;
use app\models\UserDocument;
use app\models\User;
use app\models\Log;

class Documents extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
    }

    /**
     * Affiche l'espace personnel de documents
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $docModel = new UserDocument();
        $userModel = new User();

        // Documents de l'utilisateur connecté
        $mesDocuments = $docModel->getForUser($userId);

        // Si l'utilisateur a le droit de déposer, on récupère la liste de tous les utilisateurs 
        // pour populer le select (afin de choisir à qui on envoie le doc)
        $allUsers = [];
        $canDeposit = User::can('depot_document');
        if ($canDeposit) {
            $allUsers = $userModel->getAll();
        }

        $this->render('user/documents', [
            'title' => 'Mon Espace Documents',
            'mesDocuments' => $mesDocuments,
            'canDeposit' => $canDeposit,
            'allUsers' => $allUsers
        ]);
    }

    /**
     * Action pour déposer manuellement un document (Réservé à depot_document)
     */
    public function upload() {
        if (!User::can('depot_document')) {
            setToast("Accès refusé. Vous n'avez pas la permission de déposer des documents.", "danger");
            $this->redirect('documents');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
            $titre = trim($_POST['titre'] ?? '');
            $destinataires = $_POST['users'] ?? []; // Tableau d'ID utilisateurs
            $notify = isset($_POST['notify']) ? true : false;
            $auteur = $_SESSION['prenom'] . ' ' . strtoupper($_SESSION['nom']);

            if (empty($titre) || empty($destinataires)) {
                setToast("Veuillez définir un titre et sélectionner au moins un destinataire.", "warning");
                $this->redirect('documents');
                return;
            }

            $file = $_FILES['fichier'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                
                // Sécurisation et création du dossier
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $titre) . '.' . $ext;
                $uploadDir = 'uploads/documents/'; // Dossier dédié aux dépôts manuels
                
                if (!is_dir($uploadDir)) { 
                    mkdir($uploadDir, 0777, true); 
                }
                
                $destPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Appel à la fameuse fonction globale !
                    UserDocument::deposer($destinataires, $titre, $destPath, $auteur, $notify);
                    
                    Log::add('UPLOAD_USER_DOC', "Dépôt manuel du document '$titre' à " . count($destinataires) . " utilisateur(s).");
                    setToast("Le document a été déposé avec succès à " . count($destinataires) . " collaborateur(s).");
                } else {
                    setToast("Erreur lors de l'enregistrement physique du fichier.", "danger");
                }
            } else {
                setToast("Erreur lors du transfert du fichier (Code: " . $file['error'] . ").", "danger");
            }
        }
        
        $this->redirect('documents');
    }

    /**
     * Action pour télécharger un document
     */
    public function download($docId) {
        $docModel = new UserDocument();
        $userId = $_SESSION['user_id'];
        
        // On récupère les infos
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM user_documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$docId, $userId]);
        $doc = $stmt->fetch();

        if ($doc && file_exists($doc['chemin_fichier'])) {
            $ext = pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION);
            $filename = preg_replace('/[^a-zA-Z0-9_\\-]/', '_', $doc['titre']) . '.' . $ext;

            // Purge le buffer
            while (ob_get_level()) { ob_end_clean(); }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($doc['chemin_fichier']));
            
            readfile($doc['chemin_fichier']);
            exit;
        } else {
            setToast("Document introuvable ou vous n'avez pas les droits.", "danger");
            $this->redirect('documents');
        }
    }

    /**
     * Supprimer un document de son propre espace
     */
    public function delete($docId) {
        // Optionnel: vérifier que le doc appartient bien à l'utilisateur courant pour sécurité
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM user_documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$docId, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $docModel = new UserDocument();
            $docModel->delete($docId);
            setToast("Le document a été retiré de votre espace.");
        }
        $this->redirect('documents');
    }
}
