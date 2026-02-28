<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Seance;
use app\models\Instance;

class Dashboard extends Controller {

    public function index() {
        // 1. Sécurité : redirection si non connecté
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $seanceModel = new Seance();
        $instanceModel = new Instance();
        
        // --- RÉCUPÉRATION DES DONNÉES ---
        
        // Séances à venir (5 max)
        $prochainesSeances = $seanceModel->getProchaines(5);

        // Stats globales (pour les admins/gestionnaires)
        $nbInstances = count($instanceModel->getAll());
        
        // Compter les séances à convoquer via une requête directe
        $db = \app\core\Database::getConnection();
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM seances WHERE statut IN ('brouillon', 'planifiee')");
        $stmtCount->execute();
        $nbSeancesPlanifiees = $stmtCount->fetchColumn();

        // Stats personnelles (pour l'utilisateur)
        // Les instances dont il est membre via un compte "User" lié
        $stmtInstances = $db->prepare("
            SELECT i.nom
            FROM membres m 
            JOIN instances i ON m.instance_id = i.id 
            WHERE m.user_id = ?
        ");
        $stmtInstances->execute([$userId]);
        $userInstances = $stmtInstances->fetchAll();

        // Récupérer les 5 derniers documents ajoutés aux séances
        // Correction de `nom_document` en `nom`
        $stmtDocs = $db->prepare("
            SELECT d.nom, d.type_doc, s.id as seance_id, s.date_seance, i.nom as instance_nom 
            FROM documents d
            JOIN seances s ON d.seance_id = s.id
            JOIN instances i ON s.instance_id = i.id
            ORDER BY d.uploaded_at DESC 
            LIMIT 5
        ");
        $stmtDocs->execute();
        $derniersDocuments = $stmtDocs->fetchAll();

        // Rendu de la vue
        $this->render('dashboard', [
            'title'               => 'Tableau de bord',
            'username'            => $_SESSION['username'],
            'prochainesSeances'   => $prochainesSeances,
            'nbInstances'         => $nbInstances,
            'nbSeancesPlanifiees' => $nbSeancesPlanifiees,
            'userInstances'       => $userInstances,
            'derniersDocuments'   => $derniersDocuments
        ]);
    }
}
