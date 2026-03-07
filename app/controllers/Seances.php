<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Seance;
use app\models\Instance;
use app\models\PointOdj;
use app\models\Log;
use app\core\Mailer;

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

        // Récupération des filtres
        $search_instance = $_GET['search_instance'] ?? '';
        $search_date = $_GET['search_date'] ?? '';

        $toutes_seances = [];
        foreach ($instances as $inst) {
            // Filtrage par instance
            if (!empty($search_instance) && $inst['id'] != $search_instance) {
                continue;
            }

            $instSeances = $seanceModel->getByInstance($inst['id']);
            foreach ($instSeances as $s) {
                // Filtrage par date
                if (!empty($search_date) && $s['date_seance'] != $search_date) {
                    continue;
                }

                $s['instance_nom'] = $inst['nom'];
                $toutes_seances[] = $s;
            }
        }

        // Séparation en deux listes : Futures (y compris aujourd'hui) et Passées
        $seances_futures = [];
        $seances_passees = [];
        $today = date('Y-m-d');

        foreach ($toutes_seances as $s) {
            // "Terminée" force la séance dans les passées, sinon on regarde la date
            if ($s['statut'] === 'terminee' || $s['date_seance'] < $today) {
                $seances_passees[] = $s;
            } else {
                $seances_futures[] = $s;
            }
        }

        // Tri : Futures = les plus proches en premier (croissant)
        usort($seances_futures, function($a, $b) {
            return strtotime($a['date_seance']) - strtotime($b['date_seance']);
        });

        // Tri : Passées = les plus récentes en premier (décroissant)
        usort($seances_passees, function($a, $b) {
            return strtotime($b['date_seance']) - strtotime($a['date_seance']);
        });

        $this->render('seances/index', [
            'title' => 'Gestion des Séances',
            'instances' => $instances,
            'seances_futures' => $seances_futures,
            'seances_passees' => $seances_passees,
            'search_instance' => $search_instance,
            'search_date' => $search_date
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
     * Changer le statut d'une séance
     * Gère les nouveaux statuts et l'envoi manuel/automatique des convocations
     */
    public function changeStatut($seanceId) {
        $statut = $_GET['statut'] ?? null;
        $statutsValides = ['ajournee', 'brouillon', 'date_fixee', 'odj_valide', 'dossier_disponible', 'en_cours', 'finalisation', 'terminee'];

        if ($statut && in_array($statut, $statutsValides)) {
            $seanceModel = new Seance();
            $seanceAcienne = $seanceModel->getById($seanceId);
            
            // Sécurité : On ne peut terminer que si le PV est uploadé
            if ($statut === 'terminee' && empty($seanceAcienne['proces_verbal_path'])) {
                setToast("Impossible de terminer la séance : le procès-verbal signé n'a pas été déposé.", "danger");
                $this->redirect('seances/edit/' . $seanceId);
                return;
            }

            $seanceModel->updateStatut($seanceId, $statut);
            Log::add('UPDATE_SEANCE_STATUT', "Séance ID $seanceId passée au statut : $statut");
            setToast("Le statut de la séance a été mis à jour.");

            // Si on valide l'ODJ avec envoi de convocations
            if ($statut === 'odj_valide' && isset($_GET['send_convocs']) && $_GET['send_convocs'] == '1') {
                $this->sendConvocationsManual($seanceId);
            }

            // Si on clôture la séance (Terminée), on envoie et dépose automatiquement le PV
            if ($statut === 'terminee' && (!isset($seanceAcienne['pv_envoye']) || $seanceAcienne['pv_envoye'] == 0)) {
                $this->envoyerPvEtDeposer($seanceId);
            }

            // Ajournement automatique (votre logique existante)
            if ($statut === 'ajournee') {
                $etapesAvancees = ['odj_valide', 'dossier_disponible', 'en_cours'];
                if (in_array($seanceAcienne['statut'], $etapesAvancees) && \app\models\User::can('manage_convocations')) {
                    $this->notifierAjournement($seanceId);
                }
            }

            if ($statut === 'en_cours') {
                $this->redirect('seances/live/' . $seanceId);
                return;
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }


    /**
     * Notifie les membres que la séance est ajournée
     */
    private function notifierAjournement($seanceId) {
        $seanceModel   = new Seance();
        $seance  = $seanceModel->getById($seanceId);
        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);

        if (empty($membres)) return;

        $dateObj = new \DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        $dateFormatee = $dateObj->format('d/m/Y à H\hi');
        $subject = "REPORT – " . $seance['instance_nom'] . " du " . $dateObj->format('d/m/Y');

        $nbEnvoyes = 0;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $nomComplet = strtoupper($membre['nom']) . ' ' . $membre['prenom'];
            $body = '
            <!DOCTYPE html>
            <html lang="fr">
            <body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;">
                <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; border-top: 4px solid #dc3545;">
                    <h2 style="color: #dc3545; margin-top:0;">SÉANCE AJOURNÉE</h2>
                    <p>Madame, Monsieur <strong>' . $nomComplet . '</strong>,</p>
                    <p>Veuillez noter que la séance de <strong>' . htmlspecialchars($seance['instance_nom']) . '</strong> 
                    initialement prévue le <strong>' . $dateFormatee . '</strong> a été <strong>ajournée</strong>.</p>
                    <p>Une nouvelle convocation vous sera adressée ultérieurement dès qu\'une nouvelle date sera fixée.</p>
                </div>
            </body>
            </html>';

            if (Mailer::send($membre['email'], $subject, $body)) {
                $nbEnvoyes++;
            }
        }

        if ($nbEnvoyes > 0) {
            setToast("✅ Les membres ont été notifiés de l'ajournement par e-mail.");
        }
    }

    /**
     * Envoie les convocations à tous les membres titulaires de l'instance
     */
    private function envoyerConvocations($seanceId) {
        $seanceModel   = new Seance();
        $pointModel    = new PointOdj();
        $instanceModel = new Instance();

        $seance  = $seanceModel->getById($seanceId);
        $points  = $pointModel->getBySeance($seanceId);
        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);

        if (empty($membres)) return;

        $dateObj = new \DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        $dateFormatee = $dateObj->format('d/m/Y à H\hi');
        $lienConsultation = URLROOT . '/seances/view/' . $seanceId;

        // Construction de l'ODJ en HTML pour le corps du mail
        $listeOdj = '';
        foreach ($points as $i => $pt) {
            $typeCfg = [
                'information'  => '#17a2b8',
                'deliberation' => '#0d6efd',
                'vote'         => '#dc3545',
                'divers'       => '#6c757d',
            ];
            $couleur = $typeCfg[$pt['type_point']] ?? '#6c757d';
            $listeOdj .= '
                <tr>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;">
                        <strong style="color: #0d6efd;">' . ($i + 1) . '.</strong> 
                        ' . htmlspecialchars($pt['titre']) . '
                    </td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f0f0f0; text-align: right;">
                        <span style="background-color:' . $couleur . '; color:#fff; font-size:11px; padding: 2px 8px; border-radius: 20px;">
                            ' . ucfirst($pt['type_point']) . '
                        </span>
                    </td>
                </tr>';
        }

        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $nomComplet = strtoupper($membre['nom']) . ' ' . $membre['prenom'];
            $subject    = "Convocation – " . $seance['instance_nom'] . " – " . $dateObj->format('d/m/Y');

            // Template HTML de la convocation
            $body = '
            <!DOCTYPE html>
            <html lang="fr">
            <head><meta charset="UTF-8"></head>
            <body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding: 30px 0;">
                    <tr><td align="center">
                        <table width="620" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius: 8px; overflow:hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            
                            <!-- EN-TÊTE BLEU -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0d6efd, #0a58ca); padding: 30px 40px; text-align: center;">
                                    <h1 style="color: #333; margin:0; font-size: 22px; font-weight: bold; letter-spacing: 1px;">CONVOCATION</h1>
                                    <p style="color: #333; margin: 8px 0 0 0; font-size: 14px;">' . htmlspecialchars($seance['instance_nom']) . '</p>
                                </td>
                            </tr>

                            <!-- CORPS -->
                            <tr>
                                <td style="padding: 35px 40px;">
                                    <p style="color: #333; font-size: 15px; margin-top:0;">Madame, Monsieur <strong>' . $nomComplet . '</strong>,</p>
                                    <p style="color: #555; font-size: 14px; line-height: 1.7;">
                                        Vous êtes convoqué(e) à la réunion de <strong>' . htmlspecialchars($seance['instance_nom']) . '</strong> 
                                        qui se tiendra le :
                                    </p>

                                    <!-- ENCART DATE -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                        <tr>
                                            <td style="background-color: #f0f5ff; border-left: 4px solid #0d6efd; border-radius: 4px; padding: 15px 20px;">
                                                <p style="margin:0; font-size: 18px; font-weight: bold; color: #0d6efd;">📅 ' . $dateFormatee . '</p>
                                                ' . (!empty($seance['lieu']) ? '<p style="margin: 5px 0 0 0; font-size: 13px; color: #555;">📍 ' . htmlspecialchars($seance['lieu']) . '</p>' : '') . '
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- ORDRE DU JOUR -->
                                    <p style="color: #333; font-size: 14px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Ordre du Jour</p>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden;">
                                        ' . ($listeOdj ?: '<tr><td style="padding: 12px; color: #999; font-size: 13px; font-style: italic;">L\'ordre du jour complet sera communiqué prochainement.</td></tr>') . '
                                    </table>

                                    <!-- LIEN DE CONSULTATION -->
                                    <p style="color: #555; font-size: 14px; line-height: 1.7; margin-top: 25px;">
                                        Vous pouvez consulter le dossier de séance en ligne en cliquant sur le bouton ci-dessous, 
                                        une fois celui-ci mis à disposition.
                                    </p>
                                    <table cellpadding="0" cellspacing="0" style="margin: 10px 0 20px 0;">
                                        <tr>
                                            <td style="background-color:#0d6efd; border-radius: 6px; padding: 12px 28px;">
                                                <a href="' . $lienConsultation . '" style="color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">
                                                    Accéder au dossier de séance →
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>

                            <!-- PIED DE PAGE -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 18px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                                    <p style="color: #aaa; font-size: 12px; margin: 0;">
                                        Cet e-mail a été envoyé automatiquement par KronoInstances. Merci de ne pas y répondre directement.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td></tr>
                </table>
            </body>
            </html>';

            if (Mailer::send($membre['email'], $subject, $body)) {
                $nbEnvoyes++;
            } else {
                $nbEchecs++;
            }
        }

        // Toast de résumé
        if ($nbEchecs === 0) {
            setToast("✅ Convocations envoyées à $nbEnvoyes membre(s) avec succès.");
        } else {
            setToast("⚠️ $nbEnvoyes convocation(s) envoyée(s), $nbEchecs échec(s). Vérifiez les adresses e-mail.", "warning");
        }

        Log::add('CONVOCATIONS_ENVOYEES', "Séance ID $seanceId : $nbEnvoyes envoi(s), $nbEchecs échec(s).");
    }

    /**
     * Mettre à jour l'état du Quorum via AJAX
     */
    public function quorum($seanceId) {
        $attained = isset($_GET['attained']) ? (int)$_GET['attained'] : 0;
        
        $seanceModel = new Seance();
        $seanceModel->updateQuorum($seanceId, $attained);
        
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
            $remplacePar = $data['remplace_par'] ?? null;

            $presenceModel = new \app\models\Presence();
            $presenceModel->update($seanceId, $membreId, $estPresent, $remplacePar);

            $seanceModel = new Seance();
            $seance = $seanceModel->getById($seanceId);
            $presences = $presenceModel->getBySeance($seanceId);
            
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

    /**
     * VUE CONSULTATION (Pour les membres de l'instance)
     */
    public function view($id) {
        $seanceModel = new Seance();
        $pointModel  = new PointOdj();
        $instanceModel = new Instance();
        $docModel = new \app\models\Document();

        $seance = $seanceModel->getById($id);
        if (!$seance) {
            setToast("Séance introuvable.", "danger");
            $this->redirect('seances'); return;
        }

        $this->render('seances/view', [
            'title'  => 'Consultation de la séance',
            'seance' => $seance,
            'points' => $pointModel->getBySeance($id),
            'membres' => $instanceModel->getMembres($seance['instance_id']),
            'documents' => $docModel->getBySeance($id)
        ]);
    }

    /**
     * VUE GESTION (Pour les RH / Admins)
     */
    public function edit($id) {
        $seanceModel = new Seance();
        $pointModel  = new PointOdj();
        $instanceModel = new Instance();
        $docModel = new \app\models\Document();

        $seance = $seanceModel->getById($id);
        if (!$seance) {
            $this->redirect('seances'); return;
        }

        $points = $pointModel->getBySeance($id);
        
        // Récupération des votes pour chaque point
        $votes = [];
        foreach($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }

        $this->render('seances/edit', [
            'title'     => 'Gestion de la séance',
            'seance'    => $seance,
            'points'    => $points,
            'membres'   => $instanceModel->getMembres($seance['instance_id']),
            'documents' => $docModel->getBySeance($id),
            'votes'     => $votes // On transmet les votes à la vue
        ]);
    }

    /**
     * UPLOAD DE DOCUMENT (Annexe, Convocation...)
     */
    public function uploadDoc($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
            $nom = trim($_POST['nom'] ?? '');
            $pointId = !empty($_POST['point_odj_id']) ? $_POST['point_odj_id'] : null;
            $typeDoc = $_POST['type_doc'] ?? 'annexe';
            
            $file = $_FILES['fichier'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (empty($nom)) { $nom = pathinfo($file['name'], PATHINFO_FILENAME); }
                
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom) . '.' . $ext;
                
                $uploadDir = 'uploads/seances/' . $seanceId . '/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                
                $destPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $docModel = new \app\models\Document();
                    $docModel->create($seanceId, $pointId, $nom, $destPath, $typeDoc);
                    setToast("Document ajouté avec succès.");
                } else {
                    setToast("Erreur lors de l'upload du fichier.", "danger");
                }
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

    public function deleteDoc($docId) {
        $docModel = new \app\models\Document();
        $doc = $docModel->getById($docId);
        if ($doc) {
            if (file_exists($doc['chemin_fichier'])) { unlink($doc['chemin_fichier']); }
            $docModel->delete($docId);
            setToast("Document supprimé.");
            $this->redirect('seances/edit/' . $doc['seance_id']);
        } else {
            $this->redirect('seances');
        }
    }

    /**
     * AJAX : Mettre à jour l'ordre des points (Drag & Drop)
     */
    public function updateOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['order']) && is_array($data['order'])) {
                $pointModel = new PointOdj();
                foreach ($data['order'] as $index => $id) {
                    $pointModel->updateOrdre($id, $index + 1); // +1 car l'index JS commence à 0
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }

    /**
     * AJAX : Mettre à jour l'exposé des motifs (Texte enrichi)
     */
    public function updateDescription($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $description = $data['description'] ?? '';
            
            $pointModel = new PointOdj();
            $pointModel->updateDescription($pointId, $description);
            
            echo json_encode(['success' => true]);
            exit;
        }
    }

    /**
     * AJAX : Mettre à jour le titre et le type d'un point
     */
    public function updatePointMeta($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data      = json_decode(file_get_contents('php://input'), true);
            $titre     = trim($data['titre'] ?? '');
            $typePoint = trim($data['type_point'] ?? 'information');

            $allowed = ['information', 'deliberation', 'vote', 'divers'];
            if (empty($titre) || !in_array($typePoint, $allowed)) {
                http_response_code(422);
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            // Vérification que la séance est encore éditable
            $db   = \app\core\Database::getConnection();
            $stmt = $db->prepare("
                SELECT s.statut FROM points_odj p
                INNER JOIN seances s ON s.id = p.seance_id
                WHERE p.id = ?
            ");
            $stmt->execute([$pointId]);
            $row = $stmt->fetch();

            if (!$row || !in_array($row['statut'], ['brouillon', 'date_fixee', 'odj_valide'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé']);
                exit;
            }

            $pointModel = new PointOdj();
            $pointModel->updateMeta($pointId, $titre, $typePoint);

            echo json_encode(['success' => true]);
            exit;
        }
    }

    /**
     * AJAX : Mettre à jour la note interne d'un point
     */
    public function updateNoteInterne($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $note = $data['note_interne'] ?? '';

            $db   = \app\core\Database::getConnection();
            $stmt = $db->prepare("
                SELECT s.statut FROM points_odj p
                INNER JOIN seances s ON s.id = p.seance_id
                WHERE p.id = ?
            ");
            $stmt->execute([$pointId]);
            $row = $stmt->fetch();

            if (!$row || !in_array($row['statut'], ['brouillon', 'date_fixee', 'odj_valide'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé']);
                exit;
            }

            $db->prepare("UPDATE points_odj SET note_interne = ? WHERE id = ?")
            ->execute([$note, $pointId]);

            echo json_encode(['success' => true]);
            exit;
        }
    }

    /**
     * AJAX : Poser un verrou collaboratif sur un point
     */
    public function lockPoint($pointId) {
        $userId = $_SESSION['user_id'];
        $db     = \app\core\Database::getConnection();

        // Récupération du nom de l'utilisateur connecté
        $stmt = $db->prepare("SELECT prenom, nom FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user     = $stmt->fetch();
        $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))
                    ?: 'Utilisateur #' . $userId;

        // Nettoyage des verrous expirés (> 60 secondes)
        $db->prepare("DELETE FROM point_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)")
        ->execute();

        // Pose ou rafraîchit le verrou
        $db->prepare("
            INSERT INTO point_locks (point_odj_id, user_id, user_name, locked_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                user_id   = ?,
                user_name = ?,
                locked_at = NOW()
        ")->execute([$pointId, $userId, $userName, $userId, $userName]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * AJAX : Libérer le verrou d'un point
     */
    public function unlockPoint($pointId) {
        $userId = $_SESSION['user_id'];
        $db     = \app\core\Database::getConnection();

        $db->prepare("DELETE FROM point_locks WHERE point_odj_id = ? AND user_id = ?")
        ->execute([$pointId, $userId]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * AJAX : Retourner les verrous actifs d'une séance (polling frontend)
     */
    public function checkLocks($seanceId) {
        $db = \app\core\Database::getConnection();

        // Nettoyage des verrous expirés au passage
        $db->prepare("DELETE FROM point_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)")
        ->execute();

        $stmt = $db->prepare("
            SELECT pl.point_odj_id, pl.user_id, pl.user_name
            FROM point_locks pl
            INNER JOIN points_odj po ON po.id = pl.point_odj_id
            WHERE po.seance_id = ?
        ");
        $stmt->execute([$seanceId]);

        $locks = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $locks[$row['point_odj_id']] = [
                'user_id'   => $row['user_id'],
                'user_name' => $row['user_name'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($locks);
        exit;
    }

    /**
     * Génère la convocation pré-remplie au format ODT de façon sécurisée
     */
    public function generateConvocation($seanceId) {
        // 1. Instanciation des modèles et récupération des données
        $seanceModel = new \app\models\Seance(); 
        $seance = $seanceModel->getById($seanceId);
        
        if (!$seance) {
            $this->redirect('seances'); 
            return;
        }

        // 2. Vérification de l'existence du modèle physique
        $modelePath = dirname(dirname(__DIR__)) . '/uploads/modeles/modele_instance_' . $seance['instance_id'] . '.odt';
        if (!file_exists($modelePath)) {
            setToast("Le modèle de convocation est introuvable sur le serveur.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // 3. Création sécurisée d'un fichier temporaire de travail
        $tempFile = tempnam(sys_get_temp_dir(), 'KRONO_');
        if (!copy($modelePath, $tempFile)) {
            setToast("Impossible de copier le modèle temporairement.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // 4. Ouverture et extraction du XML de l'archive ODT
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) !== true) {
            setToast("Le fichier modèle est corrompu.", "danger");
            unlink($tempFile);
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $content = $zip->getFromName('content.xml');
        if ($content === false) {
            setToast("Le contenu du fichier ODT est invalide.", "danger");
            $zip->close();
            unlink($tempFile);
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // 5. Préparation des variables à injecter
        $dateStr = date('d/m/Y', strtotime($seance['date_seance']));
        $heureStr = date('H\hi', strtotime($seance['heure_debut']));
        
        // Construction de l'ordre du jour avec la syntaxe de saut de ligne propre à LibreOffice
        $pointModel = new \app\models\PointOdj();
        $points = $pointModel->getBySeance($seanceId);
        
        $listeOdj = "";
        if (!empty($points)) {
            foreach ($points as $index => $pt) {
                if ($index > 0) {
                    $listeOdj .= '<text:tab/><text:line-break/>'; 
                }
                // On échappe le titre pour éviter de casser le document XML s'il contient des & ou <
                $listeOdj .= ($index + 1) . '. ' . htmlspecialchars($pt['titre'], ENT_QUOTES, 'UTF-8');
            }
        } else {
            $listeOdj = "Aucun point inscrit à l'ordre du jour.";
        }

        // Dictionnaire des tags à remplacer avec leurs valeurs échappées
        $replacements = [
            '{INSTANCE}' => htmlspecialchars($seance['instance_nom'] ?? '', ENT_QUOTES, 'UTF-8'),
            '{DATE}'     => htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'),
            '{HEURE}'    => htmlspecialchars($heureStr, ENT_QUOTES, 'UTF-8'),
            '{LIEU}'     => htmlspecialchars($seance['lieu'] ?? '', ENT_QUOTES, 'UTF-8'),
            '{ODJ}'      => $listeOdj // L'ODJ contient déjà du XML valide, on ne le ré-échappe pas
        ];

        // 6. Remplacement des tags (Parade contre la fragmentation XML de LibreOffice)
        // Construit une règle regex stricte : cherche "{" + XML optionnel + "I" + XML optionnel + ... + "}"
        foreach ($replacements as $tag => $value) {
            $regex = '/\{(?:\s*<[^>]+>\s*)*';
            foreach (str_split($tag) as $char) {
                $regex .= preg_quote($char, '/');
                $regex .= '(?:\s*<[^>]+>\s*)*';
            }
            $regex .= '\}/u';
            
            $content = preg_replace($regex, $value, $content);
        }

        // 7. Sauvegarde dans l'archive
        $zip->addFromString('content.xml', $content);
        $zip->close(); // Écrit réellement sur le disque

        // 8. Forcer le téléchargement proprement
        // On purge la mémoire tampon de PHP de toute ligne blanche générée par d'autres fichiers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // On force PHP à relire la vraie taille du fichier (sinon il croit toujours qu'il fait 0 octet)
        clearstatcache(true, $tempFile);

        // Nom de fichier propre et headers HTTP stricts
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $seance['instance_nom']);
        $safeDate = str_replace('/', '-', $dateStr);
        $filename = "Convocation_{$safeName}_{$safeDate}.odt";

        header('Content-Type: application/vnd.oasis.opendocument.text');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tempFile)); // Maintenant PHP lira la vraie taille
        
        // Envoi au navigateur puis nettoyage du serveur
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    /**
     * GÉNÉRATION DU PROCÈS-VERBAL (ODT)
     */
    public function generatePv($seanceId) {
        $seanceModel = new Seance();
        $pointModel = new PointOdj();
        $instanceModel = new Instance();
        $presenceModel = new \app\models\Presence();
        
        $seance = $seanceModel->getById($seanceId);
        if (!$seance) {
            $this->redirect('seances'); return;
        }

        $points = $pointModel->getBySeance($seanceId);
        $membres = $instanceModel->getMembres($seance['instance_id']);
        $presences = $presenceModel->getBySeance($seanceId);

        // --- 1. PRÉPARATION DU CONTENU DYNAMIQUE ---
        $xmlContent = "";
        
        // Variables de style
        $pStyle = '<text:p text:style-name="P1">';
        $h1Style = '<text:p text:style-name="H1">';
        $h2Style = '<text:p text:style-name="H2">';
        $boldStyleStart = '<text:span text:style-name="T1">';
        $boldStyleEnd = '</text:span>';
        $endP = '</text:p>';
        
        // Titre & En-tête
        $dateObj = new \DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        $xmlContent .= $h1Style . 'PROCÈS-VERBAL' . $endP;
        $xmlContent .= $pStyle . $boldStyleStart . htmlspecialchars($seance['instance_nom'], ENT_XML1, 'UTF-8') . $boldStyleEnd . $endP;
        $xmlContent .= $pStyle . 'Séance du ' . htmlspecialchars($dateObj->format('d/m/Y à H\hi'), ENT_XML1, 'UTF-8') . $endP;
        if (!empty($seance['lieu'])) {
            $xmlContent .= $pStyle . 'Lieu : ' . htmlspecialchars($seance['lieu'], ENT_XML1, 'UTF-8') . $endP;
        }
        $xmlContent .= '<text:p text:style-name="Standard"/>';

        // Présences
        $xmlContent .= $h2Style . 'MEMBRES PRÉSENTS' . $endP;
        
        $presents = []; $excuses = []; $remplaces = [];
        
        foreach ($membres as $m) {
            $presenceInfo = null;
            foreach ($presences as $p) {
                if ($p['membre_id'] == $m['id']) {
                    $presenceInfo = $p; break;
                }
            }
            
            $nomComplet = strtoupper($m['nom']) . ' ' . $m['prenom'];
            $college = ucfirst($m['college']); // Renverra Administration ou Personnel
            
            if ($presenceInfo && $presenceInfo['est_present']) {
                $presents[] = "• $nomComplet ($college)";
            } elseif ($presenceInfo && !empty($presenceInfo['remplace_par_id'])) {
                $suppleantNom = '';
                foreach ($membres as $sup) {
                    if ($sup['id'] == $presenceInfo['remplace_par_id']) {
                        $suppleantNom = strtoupper($sup['nom']) . ' ' . $sup['prenom'];
                        break;
                    }
                }
                $remplaces[] = "• $nomComplet ($college) – représenté(e) par $suppleantNom";
            } else {
                $excuses[] = "• $nomComplet ($college)";
            }
        }
        
        foreach ($presents as $p) { $xmlContent .= $pStyle . htmlspecialchars($p, ENT_XML1, 'UTF-8') . $endP; }
        
        if (!empty($remplaces)) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . 'MEMBRES REPRÉSENTÉS' . $endP;
            foreach ($remplaces as $r) { $xmlContent .= $pStyle . htmlspecialchars($r, ENT_XML1, 'UTF-8') . $endP; }
        }
        
        if (!empty($excuses)) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . 'MEMBRES EXCUSÉS' . $endP;
            foreach ($excuses as $e) { $xmlContent .= $pStyle . htmlspecialchars($e, ENT_XML1, 'UTF-8') . $endP; }
        }

        // Saut de page
        $xmlContent .= '<text:p text:style-name="SautDePage"/>';
        
        // Ordre du jour et débats
        $xmlContent .= $h1Style . 'ORDRE DU JOUR ET DÉBATS' . $endP;
        
        foreach ($points as $index => $pt) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . ($index + 1) . '. ' . htmlspecialchars($pt['titre'], ENT_XML1, 'UTF-8') . $endP;
            
            if (!empty($pt['debats'])) {
                $debatsLines = explode("\n", $pt['debats']);
                foreach ($debatsLines as $line) {
                    if (trim($line)) {
                        $xmlContent .= $pStyle . htmlspecialchars(trim($line), ENT_XML1, 'UTF-8') . $endP;
                    }
                }
            } else {
                $xmlContent .= $pStyle . '<text:span text:style-name="T2">Aucun débat enregistré pour ce point.</text:span>' . $endP;
            }

            // Gestion des votes
            if (in_array($pt['type_point'], ['deliberation', 'vote'])) {
                $xmlContent .= '<text:p text:style-name="Standard"/>';
                $xmlContent .= $pStyle . $boldStyleStart . 'Les membres de l\'instance sont invités à émettre un avis :' . $boldStyleEnd . $endP;
                
                $votes = $pointModel->getVotes($pt['id']);
                
                // Analyse par collège (Administration / Personnel)
                foreach (['administration', 'personnel'] as $college) {
                    $v = array_filter($votes, fn($vote) => $vote['college'] === $college);
                    $v = reset($v);
                    
                    if ($v) {
                        $unanimite = ($v['pour'] > 0 && $v['contre'] == 0 && $v['abstention'] == 0);
                        $nomCollege = ucfirst($college);
                        
                        if ($unanimite) {
                            $xmlContent .= $pStyle . "• Collège $nomCollege : Avis favorable à l'unanimité" . $endP;
                        } else {
                            $details = [];
                            if ($v['pour'] > 0) $details[] = $v['pour'] . ' favorable(s)';
                            if ($v['contre'] > 0) $details[] = $v['contre'] . ' défavorable(s)';
                            if ($v['abstention'] > 0) $details[] = $v['abstention'] . ' abstention(s)';
                            if ($v['refus'] > 0) $details[] = $v['refus'] . ' refus de vote';
                            
                            $txtVote = "• Collège $nomCollege : " . implode(', ', $details);
                            $xmlContent .= $pStyle . htmlspecialchars($txtVote, ENT_XML1, 'UTF-8') . $endP;
                        }
                    }
                }
            }
            $xmlContent .= $pStyle . '________________________________________________________' . $endP;
        }

        // --- 2. CRÉATION DES FICHIERS XML DE L'ODT ---
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:rpt="http://openoffice.org/2005/report" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:grddl="http://www.w3.org/2003/g/data-view#" xmlns:tableooo="http://openoffice.org/2009/table" xmlns:textooo="http://openoffice.org/2013/office" xmlns:field="urn:openoffice:names:experimental:ooo-ms-interop:ext:field:1.0" office:version="1.2">
            <office:automatic-styles>
                <style:style style:name="SautDePage" style:family="paragraph" style:parent-style-name="Standard">
                    <style:paragraph-properties fo:break-before="page"/>
                </style:style>
                <style:style style:name="P1" style:family="paragraph" style:parent-style-name="Standard">
                    <style:text-properties fo:font-size="11pt" style:font-size-asian="11pt" style:font-size-complex="11pt" style:font-name="Arial"/>
                    <style:paragraph-properties fo:margin-bottom="0.2cm"/>
                </style:style>
                <style:style style:name="H1" style:family="paragraph" style:parent-style-name="Standard">
                    <style:text-properties fo:font-size="14pt" fo:font-weight="bold" style:font-name="Arial"/>
                    <style:paragraph-properties fo:text-align="center" fo:margin-bottom="0.4cm"/>
                </style:style>
                <style:style style:name="H2" style:family="paragraph" style:parent-style-name="Standard">
                    <style:text-properties fo:font-size="12pt" fo:font-weight="bold" style:text-underline-style="solid" style:font-name="Arial"/>
                    <style:paragraph-properties fo:margin-bottom="0.3cm"/>
                </style:style>
                <style:style style:name="T1" style:family="text">
                    <style:text-properties fo:font-weight="bold"/>
                </style:style>
                <style:style style:name="T2" style:family="text">
                    <style:text-properties fo:font-style="italic" fo:color="#666666"/>
                </style:style>
            </office:automatic-styles>
            <office:body>
                <office:text>
                    ' . $xmlContent . '
                </office:text>
            </office:body>
        </office:document-content>';

        $manifestXml = '<?xml version="1.0" encoding="UTF-8"?>
        <manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">
            <manifest:file-entry manifest:full-path="/" manifest:version="1.2" manifest:media-type="application/vnd.oasis.opendocument.text"/>
            <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
        </manifest:manifest>';

        // --- 3. GÉNÉRATION DE L'ARCHIVE ZIP (ODT) ---
        $tempFile = tempnam(sys_get_temp_dir(), 'PV_') . '.odt';
        $zip = new \ZipArchive();
        
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            setToast("Impossible de créer le fichier ODT.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // Ajout des fichiers requis
        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString('content.xml', $contentXml);
        $zip->addEmptyDir('META-INF');
        $zip->addFromString('META-INF/manifest.xml', $manifestXml);
        $zip->close();

        // --- 4. TÉLÉCHARGEMENT ---
        while (ob_get_level()) { ob_end_clean(); }
        clearstatcache(true, $tempFile);

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $seance['instance_nom']);
        $safeDate = str_replace('/', '', $dateObj->format('Ymd'));
        $filename = "PV_{$safeName}_{$safeDate}.odt";

        header('Content-Type: application/vnd.oasis.opendocument.text');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    /**
     * UPLOAD DU PV SIGNÉ
     */
    public function uploadPv($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pv_signe'])) {
            $file = $_FILES['pv_signe'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExt = ['pdf', 'odt', 'docx'];
                
                if (!in_array($ext, $allowedExt)) {
                    setToast("Format de fichier non autorisé. Utilisez PDF, ODT ou DOCX.", "danger");
                    $this->redirect('seances/edit/' . $seanceId);
                    return;
                }
                
                $safeName = 'PV_signe_' . $seanceId . '_' . uniqid() . '.' . $ext;
                $uploadDir = 'uploads/seances/' . $seanceId . '/';
                
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                
                $destPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $seanceModel = new Seance();
                    $seanceModel->updatePvPath($seanceId, $destPath);
                    Log::add('UPLOAD_PV', "PV signé uploadé pour la séance ID: $seanceId");
                    setToast("✅ Procès-verbal signé uploadé avec succès.");
                } else {
                    setToast("Erreur lors de l'upload du fichier.", "danger");
                }
            } else {
                setToast("Erreur lors de l'upload : code " . $file['error'], "danger");
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

    /**
     * SUPPRESSION DU PV SIGNÉ
     */
    public function deletePv($seanceId) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);
        
        if ($seance && !empty($seance['proces_verbal_path'])) {
            if (file_exists($seance['proces_verbal_path'])) {
                unlink($seance['proces_verbal_path']);
            }
            $seanceModel->updatePvPath($seanceId, null);
            Log::add('DELETE_PV', "PV signé supprimé pour la séance ID: $seanceId");
            setToast("Procès-verbal signé supprimé.");
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

        /**
     * NOUVEAU : Retirer ou Rétablir un point
     */
    public function toggleRetirePoint($pointId) {
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT retire, seance_id FROM points_odj WHERE id = ?");
        $stmt->execute([$pointId]);
        $pt = $stmt->fetch();
        if ($pt) {
            $newStatus = $pt['retire'] ? 0 : 1;
            $db->prepare("UPDATE points_odj SET retire = ? WHERE id = ?")->execute([$newStatus, $pointId]);
            setToast($newStatus ? "Le point a été rayé de l'ordre du jour." : "Le point a été rétabli.");
            $this->redirect('seances/edit/' . $pt['seance_id']);
        }
    }

    /**
     * NOUVEAU : Envoi manuel des convocations depuis l'encart (Étape 3)
     */
    public function sendConvocationsManual($seanceId) {
        if (!\app\models\User::can('manage_convocations')) {
            setToast("Vous n'avez pas la permission d'envoyer des convocations.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $db = \app\core\Database::getConnection();
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);

        if (isset($seance['convocations_envoyees']) && $seance['convocations_envoyees'] == 1) {
            setToast("Les convocations ont déjà été envoyées.", "warning");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $stmt = $db->prepare("SELECT chemin_fichier FROM documents WHERE seance_id = ? AND type_doc = 'convocation' LIMIT 1");
        $stmt->execute([$seanceId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            setToast("Impossible d'envoyer : aucun fichier PDF de convocation n'a été déposé.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);
        $userModel = new \app\models\User();
        $nbEnvoyes = 0;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $userMatch = $userModel->findByEmail($membre['email']);
            if ($userMatch) {
                // Dépose le document et déclenche la notification applicative !
                \app\models\UserDocument::deposer(
                    $userMatch['id'], 
                    "Convocation - " . $seance['instance_nom'], 
                    $doc['chemin_fichier'], 
                    "KronoInstances", 
                    true 
                );
            }

            // Envoi de l'e-mail
            $dateObj = new \DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
            $subject = "Convocation – " . $seance['instance_nom'];
            $lien = URLROOT . '/seances/view/' . $seanceId;
            $body = "<p>Madame, Monsieur,</p><p>Vous êtes convoqué(e) à la séance du <strong>".$dateObj->format('d/m/Y à H\hi')."</strong>.</p><p>Vous pouvez consulter l'ordre du jour et la convocation officielle sur votre espace : <br><a href='$lien'>Accéder à mon espace</a></p>";
            
            if (\app\core\Mailer::send($membre['email'], $subject, $body)) {
                $nbEnvoyes++;
            }
        }

        $db->prepare("UPDATE seances SET convocations_envoyees = 1 WHERE id = ?")->execute([$seanceId]);
        setToast("✅ Convocations envoyées par e-mail et déposées sur l'espace de $nbEnvoyes membre(s).");
        $this->redirect('seances/edit/' . $seanceId);
    }

    /**
     * NOUVEAU : Envoi du PV
     */
    private function envoyerPvEtDeposer($seanceId) {
        $db = \app\core\Database::getConnection();
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);

        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);
        $userModel = new \app\models\User();
        $nbEnvoyes = 0;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $userMatch = $userModel->findByEmail($membre['email']);
            if ($userMatch) {
                \app\models\UserDocument::deposer(
                    $userMatch['id'], 
                    "Procès-Verbal - " . $seance['instance_nom'], 
                    $seance['proces_verbal_path'], 
                    "KronoInstances", 
                    true
                );
            }

            $subject = "Procès-Verbal disponible – " . $seance['instance_nom'];
            $lien = URLROOT . '/seances/view/' . $seanceId;
            $body = "<p>Madame, Monsieur,</p><p>Le procès-verbal définitif de la séance du <strong>" . date('d/m/Y', strtotime($seance['date_seance'])) . "</strong> est désormais consultable.</p><p><a href='$lien'>Accéder à mon espace</a></p>";
            
            if (\app\core\Mailer::send($membre['email'], $subject, $body)) {
                $nbEnvoyes++;
            }
        }
        $db->prepare("UPDATE seances SET pv_envoye = 1 WHERE id = ?")->execute([$seanceId]);
    }

    /**
     * Modification manuelle des votes (Étape 6)
     */
    public function saveVotesManual($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pointModel = new PointOdj();
            $db = \app\core\Database::getConnection();
            $stmt = $db->prepare("SELECT seance_id FROM points_odj WHERE id = ?");
            $stmt->execute([$pointId]);
            $pt = $stmt->fetch();
            
            if ($pt) {
                $pointModel->saveVotes($pointId, 'administration', $_POST['admin_pour']??0, $_POST['admin_contre']??0, $_POST['admin_abstention']??0, $_POST['admin_refus']??0);
                $pointModel->saveVotes($pointId, 'personnel', $_POST['pers_pour']??0, $_POST['pers_contre']??0, $_POST['pers_abstention']??0, $_POST['pers_refus']??0);
                setToast("Les votes ont été mis à jour.");
                $this->redirect('seances/edit/' . $pt['seance_id']);
                return;
            }
        }
        $this->redirect('seances');
    }
}