<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Arrete;
use app\models\Signataire;

class Dashboard extends Controller {

    public function index() {
        // 1. Sécurité : redirection si non connecté
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }

        $arreteModel = new Arrete();
        $userModel = new User();
        
        $myId = $_SESSION['user_id'];
        
        // Récupération des infos étendues de l'utilisateur
        $userInfo = $userModel->getInfoWithService($myId);
        $myServiceId = $userInfo['service_id'] ?? null;

        // --- SUPPRESSION DU TRAITEMENT POST DE CRÉATION ---
        // L'ancien bloc if ($_SERVER['REQUEST_METHOD'] == 'POST' ...) est supprimé.

        // --- RÉCUPÉRATION DES DONNÉES ---
        
        // Statistiques
        $stats = $arreteModel->getStats($myId);

        // Mes dossiers en cours (Brouillons + En validation)
        // On adapte le filtre pour inclure les statuts workflow
        $pendingList = $arreteModel->getFiltered([
            'scope'   => 'me',
            'user_id' => $myId,
            'statut_workflow'  => ['brouillon', 'en_validation', 'rejete'] // Nouveaux statuts
        ], 5, 0);

        // Dossiers du service (Collègues) - En cours de validation
        $serviceList = [];
        if (User::can('view_service_actes') && $myServiceId) {
            $serviceList = $arreteModel->getFiltered([
                'scope'           => 'service',
                'user_id'         => $myId,
                'user_service_id' => $myServiceId,
                'statut_workflow' => ['en_validation'], // Seuls ceux en validation intéressent les collègues
                'exclude_me'      => true
            ], 5, 0);
        }

        // Rendu de la vue
        $this->render('dashboard', [
            'title'           => 'Tableau de bord',
            'username'        => $_SESSION['username'],
            'my_service_id'   => $myServiceId,
            'my_service_nom'  => $userInfo['service_nom'] ?? 'Sans service',
            'stats'           => $stats,

        ]);
    }
}
