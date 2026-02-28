<?php
namespace app\models;

use app\core\Database;
use app\models\Notification;

class UserDocument {

    /**
     * FONCTION GLOBALE DE DÉPÔT (Automatique ou Manuel)
     * 
     * @param array|int $userIds Un ID utilisateur unique ou un tableau d'IDs
     * @param string $titre Le titre du document affiché à l'utilisateur
     * @param string $cheminFichier Le chemin relatif depuis la racine (ex: 'uploads/seances/1/convoc.pdf')
     * @param string $auteur 'Système' par défaut, ou le nom de la personne qui fait le dépôt
     * @param bool $notify Faut-il envoyer une notification applicative ?
     */
    public static function deposer($userIds, $titre, $cheminFichier, $auteur = 'Système', $notify = false) {
        $db = Database::getConnection();
        
        // S'assurer qu'on travaille avec un tableau
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $notifModel = new Notification();
        
        // Préparation de la requête d'insertion
        $stmt = $db->prepare("INSERT INTO user_documents (user_id, titre, chemin_fichier, auteur) VALUES (?, ?, ?, ?)");

        foreach ($userIds as $uid) {
            // 1. Insertion en BDD (on stocke juste le lien, on ne duplique pas le fichier)
            $stmt->execute([$uid, $titre, $cheminFichier, $auteur]);

            // 2. Notification si demandé
            if ($notify) {
                $notifModel->add(
                    $uid, 
                    "info",
                    "Un nouveau document a été déposé sur votre espace : " . $titre, 
                    "documents"
                );
            }
        }
        return true;
    }

    /**
     * Récupère tous les documents personnels d'un utilisateur
     */
    public function getForUser($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM user_documents WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Supprime un document
     */
    public function delete($docId) {
        $db = Database::getConnection();
        
        // 1. Récupérer le chemin du fichier avant de supprimer la ligne
        $stmt = $db->prepare("SELECT chemin_fichier FROM user_documents WHERE id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch();

        if ($doc) {
            $cheminFichier = $doc['chemin_fichier'];

            // 2. Suppression de la liaison en BDD pour cet utilisateur précis
            $delStmt = $db->prepare("DELETE FROM user_documents WHERE id = ?");
            $delStmt->execute([$docId]);

            // 3. Vérification s'il s'agit d'un fichier du dossier des dépôts manuels
            if (strpos($cheminFichier, 'uploads/documents/') === 0 && file_exists($cheminFichier)) {
                
                // 4. On vérifie si d'autres utilisateurs possèdent encore un lien vers ce même fichier
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM user_documents WHERE chemin_fichier = ?");
                $checkStmt->execute([$cheminFichier]);
                $countLiaisonsRestantes = $checkStmt->fetchColumn();

                // 5. S'il n'y a plus aucune liaison (0), on supprime le fichier physique pour libérer l'espace
                if ($countLiaisonsRestantes == 0) {
                    unlink($cheminFichier);
                }
            }
        }
    }

}
