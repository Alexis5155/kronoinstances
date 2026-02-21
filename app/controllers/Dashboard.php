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

        $seanceModel = new Seance();
        $instanceModel = new Instance();
        
        // --- RÉCUPÉRATION DES DONNÉES ---
        
        // 1. Les 5 prochaines séances à venir
        $prochainesSeances = $seanceModel->getProchaines(5);

        // 2. Statistiques rapides (Widget)
        $nbInstances = count($instanceModel->getAll());
        
        // Calcul du nombre de séances "Planifiées" (non encore convoquées)
        // On le fait en PHP pour simplifier, ou on pourrait ajouter une méthode count() dans le modèle
        $nbSeancesPlanifiees = 0;
        foreach($prochainesSeances as $s) {
            if($s['statut'] === 'planifiee') $nbSeancesPlanifiees++;
        }

        // Rendu de la vue
        $this->render('dashboard', [
            'title'             => 'Tableau de bord',
            'username'          => $_SESSION['username'],
            'prochainesSeances' => $prochainesSeances,
            'nbInstances'       => $nbInstances,
            'nbSeancesPlanifiees' => $nbSeancesPlanifiees
        ]);
    }
}
