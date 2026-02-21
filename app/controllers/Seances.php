<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Seance;
use app\models\Instance;
use app\models\PointOdj;
use app\models\Log;

class Seances extends Controller {

    public function __construct() {
        // Redirection si non connecté
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
    }

    /**
     * Liste de toutes les séances
     */
    public function index() {
        $seanceModel = new Seance();
        $instanceModel = new Instance();

        $instances = $instanceModel->getAll();

        $seances = [];
        foreach ($instances as $inst) {
            $instSeances = $seanceModel->getByInstance($inst['id']);
            foreach ($instSeances as $s) {
                $s['instance_nom'] = $inst['nom'];
                $seances[] = $s;
            }
        }

        // Trier toutes les séances par date (les plus récentes / futures d'abord)
        usort($seances, function($a, $b) {
            return strtotime($b['date_seance']) - strtotime($a['date_seance']);
        });

        $this->render('seances/index', [
            'title' => 'Gestion des Séances',
            'instances' => $instances,
            'seances' => $seances
        ]);
    }

    /**
     * Page de détail d'une séance (ODJ, Présences, Statut)
     */
    public function view($id) {
        $seanceModel = new Seance();
        $pointModel  = new PointOdj();
        $instanceModel = new Instance();

        $seance = $seanceModel->getById($id);
        if (!$seance) {
            setToast("Séance introuvable.", "danger");
            $this->redirect('seances');
            return;
        }

        $points  = $pointModel->getBySeance($id);
        $membres = $instanceModel->getMembres($seance['instance_id']);

        $this->render('seances/view', [
            'title'  => 'Détail de la séance',
            'seance' => $seance,
            'points' => $points,
            'membres' => $membres,
        ]);
    }

    /**
     * Planifier une nouvelle séance (Action POST)
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $instanceId = $_POST['instance_id'] ?? null;
            $date  = $_POST['date_seance'] ?? null;
            $heure = $_POST['heure_debut'] ?? null;
            $lieu  = trim($_POST['lieu'] ?? '');

            if ($instanceId && $date && $heure) {
                $seanceModel = new Seance();
                $newId = $seanceModel->create($instanceId, $date, $heure, $lieu);
                if ($newId) {
                    Log::add('CREATE_SEANCE', "Planification d'une séance le $date pour l'instance ID: $instanceId");
                    setToast("La séance a été planifiée avec succès.");
                    $this->redirect('seances/view/' . $newId);
                    return;
                } else {
                    setToast("Erreur lors de la création de la séance.", "danger");
                }
            } else {
                setToast("Veuillez remplir tous les champs obligatoires.", "danger");
            }
        }
        $this->redirect('seances');
    }

    /**
     * Ajouter un point à l'ordre du jour (Action POST)
     */
    public function addPoint($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $titre     = trim($_POST['titre'] ?? '');
            $desc      = trim($_POST['description'] ?? '');
            $type      = $_POST['type_point'] ?? 'information';
            $direction = trim($_POST['direction_origine'] ?? '');

            if (!empty($titre)) {
                $pointModel = new PointOdj();
                $pointModel->create($seanceId, $titre, $desc, $type, $direction);
                setToast("Point ajouté à l'ordre du jour.");
            } else {
                setToast("Le titre du point est obligatoire.", "warning");
            }
        }
        $this->redirect('seances/view/' . $seanceId);
    }

    /**
     * Supprimer un point de l'ordre du jour
     */
    public function deletePoint($pointId) {
        // On récupère la séance associée pour pouvoir rediriger au bon endroit
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT seance_id FROM points_odj WHERE id = ?");
        $stmt->execute([$pointId]);
        $row = $stmt->fetch();
        $seanceId = $row['seance_id'] ?? null;

        $pointModel = new PointOdj();
        $pointModel->delete($pointId);
        setToast("Point supprimé de l'ordre du jour.");

        if ($seanceId) {
            $this->redirect('seances/view/' . $seanceId);
        } else {
            $this->redirect('seances');
        }
    }

    /**
     * Changer le statut d'une séance (planifiee -> en_cours -> terminee)
     */
    /**
     * Changer le statut d'une séance (planifiee -> en_cours -> terminee)
     */
    public function changeStatut($seanceId) {
        $statut = $_GET['statut'] ?? null;
        $statutsValides = ['planifiee', 'en_cours', 'terminee'];

        if ($statut && in_array($statut, $statutsValides)) {
            $seanceModel = new Seance();
            $seanceModel->updateStatut($seanceId, $statut);
            Log::add('UPDATE_SEANCE_STATUT', "Séance ID $seanceId passée au statut : $statut");
            setToast("Le statut de la séance a été mis à jour.");
            
            // Si on vient de démarrer la séance, on redirige directement sur le Live !
            if ($statut === 'en_cours') {
                $this->redirect('seances/live/' . $seanceId);
                return;
            }
        }
        $this->redirect('seances/view/' . $seanceId);
    }


    /**
     * Mettre à jour l'état du Quorum via AJAX
     */
    public function quorum($seanceId) {
        // Cette méthode est appelée via Javascript (fetch) donc on ne redirige pas
        $attained = isset($_GET['attained']) ? (int)$_GET['attained'] : 0;
        
        $seanceModel = new Seance();
        $seanceModel->updateQuorum($seanceId, $attained);
        
        // On renvoie un header HTTP 200 OK pour que JS sache que c'est bon
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Supprimer une séance (entière)
     */
    public function delete($id) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($id);
        if ($seance) {
            $seanceModel->delete($id);
            Log::add('DELETE_SEANCE', "Suppression de la séance ID: $id");
            setToast("Séance supprimée avec succès.");
        }
        $this->redirect('seances');
    }

        /**
     * VUE LIVE : Le bureau de la séance en direct
     */
    public function live($id) {
        $seanceModel = new Seance();
        $pointModel  = new PointOdj();
        $instanceModel = new Instance();
        $presenceModel = new \app\models\Presence();

        $seance = $seanceModel->getById($id);
        if (!$seance || $seance['statut'] !== 'en_cours') {
            setToast("La séance doit être démarrée pour accéder au mode Live.", "warning");
            $this->redirect('seances/view/' . $id);
            return;
        }

        $points  = $pointModel->getBySeance($id);
        $membres = $instanceModel->getMembres($seance['instance_id']);
        $presences = $presenceModel->getBySeance($id);

        // Récupérer les votes déjà existants pour préparer l'affichage
        $votes = [];
        foreach($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }

        $this->render('seances/live', [
            'title'  => 'Séance en direct',
            'seance' => $seance,
            'points' => $points,
            'membres' => $membres,
            'presences' => $presences,
            'votes' => $votes
        ]);
    }

    /**
     * AJAX : Sauvegarde automatique des débats
     */
    public function autoSaveDebats($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $texte = $data['debats'] ?? '';
            
            $pointModel = new PointOdj();
            $pointModel->updateDebats($pointId, $texte);
            
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    /**
     * AJAX : Mise à jour de la présence / suppléant
     */
    public function togglePresence() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $seanceId = $data['seance_id'] ?? 0;
            $membreId = $data['membre_id'] ?? 0;
            $estPresent = $data['est_present'] ?? false;
            $remplacePar = $data['remplace_par'] ?? null; // ID du suppléant

            $presenceModel = new \app\models\Presence();
            $presenceModel->update($seanceId, $membreId, $estPresent, $remplacePar);

            // Vérification simple du quorum après mise à jour
            $seanceModel = new Seance();
            $seance = $seanceModel->getById($seanceId);
            $presences = $presenceModel->getBySeance($seanceId);
            
            // Compter les présents titulaires (ou suppléants remplaçants)
            $nbPresents = 0;
            foreach($presences as $p) {
                if ($p['est_present'] || !empty($p['remplace_par_id'])) {
                    $nbPresents++;
                }
            }

            $quorumAtteint = ($nbPresents >= $seance['quorum_requis']);
            $seanceModel->updateQuorum($seanceId, $quorumAtteint);

            http_response_code(200);
            echo json_encode(['success' => true, 'quorum_atteint' => $quorumAtteint, 'presents' => $nbPresents]);
            exit;
        }
    }

    /**
     * AJAX : Sauvegarder les votes par collège
     */
    public function saveVote() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $pointId = $data['point_id'] ?? 0;
            $college = $data['college'] ?? 'administration';
            $pour = $data['pour'] ?? 0;
            $contre = $data['contre'] ?? 0;
            $abstention = $data['abstention'] ?? 0;
            $refus = $data['refus'] ?? 0;

            $pointModel = new PointOdj();
            $pointModel->saveVotes($pointId, $college, $pour, $contre, $abstention, $refus);

            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    /**
     * AJAX : Récupérer l'état complet de la séance pour l'actualisation en direct
     */
    public function getLiveState($seanceId) {
        $pointModel = new PointOdj();
        $presenceModel = new \app\models\Presence();
        
        $points = $pointModel->getBySeance($seanceId);
        $presences = $presenceModel->getBySeance($seanceId);
        
        $votes = [];
        foreach($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'points' => $points, 
            'presences' => $presences, 
            'votes' => $votes
        ]);
        exit;
    }


}
