<?php
namespace app\controllers;

use app\core\Controller;
use app\models\Seance;
use app\models\Instance;
use app\models\PointOdj;
use app\models\Presence;
use app\models\Document;
use app\models\UserDocument;
use app\models\Notification;
use app\models\User;
use app\core\Mailer;
use app\models\Log;
use DateTime;

class Seances extends Controller {

    public function __construct() {
        // Accès restreint aux utilisateurs connectés
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
    }

    /**
     * Affiche le tableau de bord principal des séances (filtrage et tri)
     */
    public function index() {
        $seanceModel = new Seance();
        $instanceModel = new Instance();
        $instances = $instanceModel->getAll();

        // Récupération des filtres depuis l'URL
        $searchInstance = $_GET['search_instance'] ?? '';
        $searchDate = $_GET['search_date'] ?? '';

        $toutesSeances = [];
        foreach ($instances as $inst) {
            if (!empty($searchInstance) && $inst['id'] != $searchInstance) continue;

            $instSeances = $seanceModel->getByInstance($inst['id']);
            foreach ($instSeances as $s) {
                if (!empty($searchDate) && $s['date_seance'] != $searchDate) continue;
                $s['instance_nom'] = $inst['nom'];
                $toutesSeances[] = $s;
            }
        }

        // Répartition en deux onglets : "À venir" et "Archives"
        $seancesFutures = [];
        $seancesPassees = [];
        $today = date('Y-m-d');

        foreach ($toutesSeances as $s) {
            // Une séance terminée passe directement aux archives, peu importe la date
            if ($s['statut'] === 'terminee' || $s['date_seance'] < $today) {
                $seancesPassees[] = $s;
            } else {
                $seancesFutures[] = $s;
            }
        }

        // Tri chronologique : le plus proche en haut pour les futures, le plus récent en haut pour les passées
        usort($seancesFutures, fn($a, $b) => strtotime($a['date_seance']) - strtotime($b['date_seance']));
        usort($seancesPassees, fn($a, $b) => strtotime($b['date_seance']) - strtotime($a['date_seance']));

        $this->render('seances/index', [
            'title' => 'Gestion des Séances',
            'instances' => $instances,
            'seances_futures' => $seancesFutures,
            'seances_passees' => $seancesPassees,
            'search_instance' => $searchInstance,
            'search_date' => $searchDate
        ]);
    }

    /**
     * Planifie une nouvelle séance (Brouillon par défaut)
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $instanceId = $_POST['instance_id'] ?? null;
            $date = $_POST['date_seance'] ?? null;
            $heure = $_POST['heure_debut'] ?? null;
            $lieu = trim($_POST['lieu'] ?? '');

            if ($instanceId && $date && $heure) {
                $seanceModel = new Seance();
                $newId = $seanceModel->create($instanceId, $date, $heure, $lieu);

                if ($newId) {
                    Log::add('CREATE_SEANCE', "Planification d'une séance le $date pour l'instance ID $instanceId");
                    setToast("La séance a été planifiée.", "success");
                    $this->redirect('seances/edit/' . $newId);
                    return;
                } else {
                    setToast("Erreur technique lors de la création.", "danger");
                }
            } else {
                setToast("Veuillez remplir les informations logistiques de base.", "danger");
            }
        }
        $this->redirect('seances');
    }

    /**
     * Supprime définitivement une séance et ses composants
     */
    public function delete($id) {
        $seanceModel = new Seance();
        if ($seanceModel->getById($id)) {
            $seanceModel->delete($id);
            Log::add('DELETE_SEANCE', "Suppression de la séance ID $id");
            setToast("Séance supprimée.", "success");
        }
        $this->redirect('seances');
    }

    /**
     * Moteur principal du workflow d'une séance (Avancement d'étape)
     * Gère les actions automatiques déclenchées lors des changements d'état
     */
    public function changeStatut($seanceId) {
        $statut = $_GET['statut'] ?? null;
        $statutsValides = ['ajournee', 'brouillon', 'date_fixee', 'odj_valide', 'dossier_disponible', 'en_cours', 'finalisation', 'terminee'];

        if (!in_array($statut, $statutsValides)) {
            $this->redirect("seances/edit/$seanceId");
            return;
        }

        $seanceModel = new Seance();
        $seanceActuelle = $seanceModel->getById($seanceId);

        // Protection : pas de clôture sans document PV rattaché
        if ($statut === 'terminee' && empty($seanceActuelle['proces_verbal_path'])) {
            setToast("Dépôt du PV signé requis avant clôture.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // Application du nouveau statut
        $seanceModel->updateStatut($seanceId, $statut);
        Log::add('UPDATE_SEANCE_STATUT', "Séance ID $seanceId passe à l'étape $statut");
        setToast("Étape validée.", "success");

        // Action auto : Envoi des convocations si la case a été cochée lors de la validation de l'ODJ
        if ($statut === 'odj_valide' && isset($_GET['send_convocs']) && $_GET['send_convocs'] == '1') {
            $this->envoyerConvocations($seanceId);
        }

        // Action auto : Notification d'ajournement si la séance était déjà avancée
        if ($statut === 'ajournee') {
            $etapesAvancees = ['odj_valide', 'dossier_disponible', 'en_cours'];
            if (in_array($seanceActuelle['statut'], $etapesAvancees) && User::can('manage_convocations')) {
                $this->notifierAjournement($seanceId);
            }
        }

        // Action auto : Envoi du PV final lors de la clôture
        if ($statut === 'terminee' && empty($seanceActuelle['pv_envoye'])) {
            $this->envoyerPvEtDeposer($seanceId);
        }

        if ($statut === 'en_cours') {
            $this->redirect('seances/live/' . $seanceId);
            return;
        }

        $this->redirect('seances/edit/' . $seanceId);
    }

    /**
     * Interface de consultation pour les membres (lecture seule)
     */
    public function view($id) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($id);

        if (!$seance) {
            setToast("Séance introuvable.", "danger");
            $this->redirect('seances');
            return;
        }

        $pointModel = new PointOdj();
        $instanceModel = new Instance();
        $docModel = new Document();

        $this->render('seances/view', [
            'title' => 'Consultation de la séance',
            'seance' => $seance,
            'points' => $pointModel->getBySeance($id),
            'membres' => $instanceModel->getMembres($seance['instance_id']),
            'documents' => $docModel->getBySeance($id)
        ]);
    }

    /**
     * Interface de gestion pour les administrateurs (préparation)
     */
    public function edit($id) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($id);

        if (!$seance) {
            $this->redirect('seances');
            return;
        }

        // VÉRIFICATION DES DROITS : L'utilisateur doit être gestionnaire de l'instance
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT 1 FROM instance_managers WHERE instance_id = ? AND user_id = ?");
        $stmt->execute([$seance['instance_id'], $_SESSION['user_id']]);

        if (!$stmt->fetch() && !User::isSuperAdmin($_SESSION['user_id'])) {
            setToast("Accès refusé : vous n'êtes pas gestionnaire de cette instance.", "danger");
            $this->redirect('seances');
            return;
        }

        $pointModel = new PointOdj();
        $instanceModel = new Instance();
        $docModel = new Document();

        $points = $pointModel->getBySeance($id);
        
        // Extraction des votes pour l'affichage des résultats côté gestionnaire
        $votes = [];
        foreach ($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }

        $this->render('seances/edit', [
            'title' => 'Préparation de la séance',
            'seance' => $seance,
            'points' => $points,
            'membres' => $instanceModel->getMembres($seance['instance_id']),
            'documents' => $docModel->getBySeance($id),
            'votes' => $votes
        ]);
    }

    /**
     * Interface de direct (bureau de séance, quorum, votes)
     */
    public function live($id) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($id);

        if (!$seance || $seance['statut'] !== 'en_cours') {
            setToast("La séance n'est pas ouverte au direct.", "warning");
            $this->redirect('seances/view/' . $id);
            return;
        }

        $pointModel = new PointOdj();
        $instanceModel = new Instance();
        $presenceModel = new Presence();

        $points = $pointModel->getBySeance($id);
        $votes = [];
        foreach ($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }

        $this->render('seances/live', [
            'title' => 'Séance en direct',
            'seance' => $seance,
            'points' => $points,
            'membres' => $instanceModel->getMembres($seance['instance_id']),
            'presences' => $presenceModel->getBySeance($id),
            'votes' => $votes
        ]);
    }

    /* =========================================================================
       MESSAGERIE ET NOTIFICATIONS
       ========================================================================= */

    /**
     * Générateur de gabarit e-mail KronoInstances
     */
    private function getEmailTemplate($title, $content) {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f6f8; padding: 30px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius: 8px; overflow:hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);'>
                            <!-- En-tête bleu -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #0d6efd, #0a58ca); padding: 25px 40px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin:0; font-size: 20px; font-weight: bold; letter-spacing: 1px;'>{$title}</h1>
                                </td>
                            </tr>
                            <!-- Contenu -->
                            <tr>
                                <td style='padding: 35px 40px;'>
                                    {$content}
                                </td>
                            </tr>
                            <!-- Pied de page -->
                            <tr>
                                <td style='background-color: #f8f9fa; padding: 18px 40px; text-align: center; border-top: 1px solid #e9ecef;'>
                                    <p style='color: #aaaaaa; font-size: 12px; margin: 0;'>
                                        KronoInstances &bull; Message automatique généré par la plateforme.<br>
                                        Merci de ne pas répondre directement à cet e-mail.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    /**
     * Diffuse officiellement la convocation et l'ODJ (via email et dépôt espace personnel)
     * Raccourci public pour le bouton manuel
     */
    public function envoyerConvocations($seanceId) {
        if (!User::can('manage_convocations')) {
            setToast("Droits insuffisants pour convoquer.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);

        if (isset($seance['convocations_envoyees']) && $seance['convocations_envoyees'] == 1) {
            setToast("Les convocations ont déjà été envoyées.", "warning");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        // Vérifie qu'on a bien le PDF rattaché
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT chemin_fichier FROM documents WHERE seance_id = ? AND type_doc = 'convocation' LIMIT 1");
        $stmt->execute([$seanceId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            setToast("Dépôt du PDF de convocation requis avant envoi.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $pointModel = new PointOdj();
        $points = $pointModel->getBySeance($seanceId);
        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);
        $userModel = new User();

        $dateObj = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        $dateFormatee = $dateObj->format('d/m/Y à H\hi');
        $lienConsultation = URLROOT . '/seances/view/' . $seanceId;

        // Préparation du bloc ODJ HTML
        $listeOdj = "";
        foreach ($points as $i => $pt) {
            if ($pt['retire']) continue;
            $couleur = ['information'=>'#17a2b8', 'deliberation'=>'#0d6efd', 'vote'=>'#dc3545'][$pt['type_point']] ?? '#6c757d';
            $listeOdj .= "<tr>
                <td style='padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #333;'>
                    <strong style='color: #0d6efd;'>" . ($i + 1) . ".</strong> " . htmlspecialchars($pt['titre']) . "
                </td>
                <td style='padding: 10px 12px; border-bottom: 1px solid #f0f0f0; text-align: right;'>
                    <span style='background-color: {$couleur}; color:#fff; font-size:11px; padding: 3px 8px; border-radius: 4px;'>" . ucfirst($pt['type_point']) . "</span>
                </td>
            </tr>";
        }

        if (empty($listeOdj)) {
            $listeOdj = "<tr><td style='padding: 12px; color: #999; font-size: 13px; font-style: italic;'>L'ordre du jour complet vous sera communiqué ultérieurement.</td></tr>";
        }

        $nbEnvoyes = 0;
        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $nomComplet = strtoupper($membre['nom']) . ' ' . $membre['prenom'];
            $subject = "Convocation • " . $seance['instance_nom'] . " du " . $dateObj->format('d/m/Y');

            $corpsMail = "
                <p style='color: #333; font-size: 15px; margin-top:0;'>Madame, Monsieur <strong>{$nomComplet}</strong>,</p>
                <p style='color: #555; font-size: 14px; line-height: 1.7;'>Vous êtes convié(e) à la réunion de l'instance <strong>" . htmlspecialchars($seance['instance_nom']) . "</strong>.</p>
                
                <table width='100%' cellpadding='0' cellspacing='0' style='margin: 20px 0;'>
                    <tr>
                        <td style='background-color: #f0f5ff; border-left: 4px solid #0d6efd; border-radius: 4px; padding: 15px 20px;'>
                            <p style='margin:0; font-size: 18px; font-weight: bold; color: #0d6efd;'>{$dateFormatee}</p>
                            " . (!empty($seance['lieu']) ? "<p style='margin: 5px 0 0 0; font-size: 13px; color: #555;'><img src='https://cdn-icons-png.flaticon.com/512/2838/2838912.png' width='12' style='vertical-align:middle; margin-right:5px;'> " . htmlspecialchars($seance['lieu']) . "</p>" : "") . "
                        </td>
                    </tr>
                </table>

                <p style='color: #333; font-size: 14px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;'>Ordre du Jour</p>
                <table width='100%' cellpadding='0' cellspacing='0' style='border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden;'>
                    {$listeOdj}
                </table>

                <p style='color: #555; font-size: 14px; line-height: 1.7; margin-top: 25px;'>La convocation officielle ainsi que les documents associés sont disponibles sur la plateforme.</p>
                
                <table cellpadding='0' cellspacing='0' style='margin: 15px 0;'>
                    <tr>
                        <td style='background-color:#0d6efd; border-radius: 6px; padding: 12px 28px;'>
                            <a href='{$lienConsultation}' style='color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;'>Accéder au dossier de séance</a>
                        </td>
                    </tr>
                </table>
            ";

            $bodyHtml = $this->getEmailTemplate("CONVOCATION", $corpsMail);

            // Dépôt dans le coffre-fort si l'utilisateur existe
            $userMatch = $userModel->findByEmail($membre['email']);
            if ($userMatch) {
                UserDocument::deposer(
                    $userMatch['id'], 
                    "Convocation - " . $seance['instance_nom'], 
                    $doc['chemin_fichier'], 
                    "Système", 
                    false
                );
                Notification::add(
                    $userMatch['id'], 
                    "info", 
                    "Convocation déposée pour la séance du " . date('d/m/Y', strtotime($seance['date_seance'])), 
                    $lienConsultation
                );
            }

            if (Mailer::send($membre['email'], $subject, $bodyHtml)) {
                $nbEnvoyes++;
            }
        }

        if ($nbEnvoyes > 0) {
            $db->prepare("UPDATE seances SET convocations_envoyees = 1 WHERE id = ?")->execute([$seanceId]);
            Log::add('CONVOCATIONS_ENVOYEES', "Envoi massif pour séance $seanceId ($nbEnvoyes succès)");
            setToast("Convocations diffusées à $nbEnvoyes membre(s).", "success");
        } else {
            setToast("Aucun envoi n'a pu aboutir.", "warning");
        }

        $this->redirect('seances/edit/' . $seanceId);
    }

    /**
     * Prévient les membres si une séance planifiée est annulée
     */
    private function notifierAjournement($seanceId) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);
        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);

        $dateObj = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        $dateFormatee = $dateObj->format('d/m/Y');
        $subject = "REPORT • " . $seance['instance_nom'] . " du " . $dateFormatee;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $nomComplet = strtoupper($membre['nom']) . ' ' . $membre['prenom'];
            
            $corpsMail = "
                <p style='color: #333; font-size: 15px; margin-top:0;'>Madame, Monsieur <strong>{$nomComplet}</strong>,</p>
                <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 15px;'>
                        Veuillez noter que la séance de <strong>" . htmlspecialchars($seance['instance_nom']) . "</strong> 
                        initialement prévue le <strong>{$dateFormatee}</strong> a été <strong>ajournée</strong>.
                    </p>
                </div>
                <p style='color: #555; font-size: 14px;'>Une nouvelle convocation vous sera adressée ultérieurement dès qu'une date de report aura été fixée.</p>
            ";

            $bodyHtml = $this->getEmailTemplate("SÉANCE AJOURNÉE", $corpsMail);
            Mailer::send($membre['email'], $subject, $bodyHtml);
        }
    }

    /**
     * Publie le compte-rendu dans les espaces des membres à la clôture
     */
    private function envoyerPvEtDeposer($seanceId) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);
        $membres = $seanceModel->getMembresAvecEmail($seance['instance_id']);
        $userModel = new User();
        
        $nbEnvoyes = 0;
        $dateStr = date('d/m/Y', strtotime($seance['date_seance']));
        $subject = "Procès-Verbal disponible • " . $seance['instance_nom'];
        $lien = URLROOT . '/seances/view/' . $seanceId;

        foreach ($membres as $membre) {
            if (empty($membre['email'])) continue;

            $userMatch = $userModel->findByEmail($membre['email']);
            if ($userMatch) {
                UserDocument::deposer(
                    $userMatch['id'], 
                    "Procès-Verbal - " . $seance['instance_nom'], 
                    $seance['proces_verbal_path'], 
                    "KronoInstances", 
                    true
                );
            }

            $corpsMail = "
                <p style='color: #333; font-size: 15px; margin-top:0;'>Madame, Monsieur,</p>
                <p style='color: #555; font-size: 14px; line-height: 1.7;'>Le procès-verbal définitif de la séance du <strong>{$dateStr}</strong> est désormais consultable.</p>
                <table cellpadding='0' cellspacing='0' style='margin: 15px 0;'>
                    <tr>
                        <td style='background-color:#0d6efd; border-radius: 6px; padding: 12px 28px;'>
                            <a href='{$lien}' style='color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;'>Accéder à mon espace</a>
                        </td>
                    </tr>
                </table>
            ";

            $bodyHtml = $this->getEmailTemplate("PROCÈS-VERBAL", $corpsMail);

            if (Mailer::send($membre['email'], $subject, $bodyHtml)) {
                $nbEnvoyes++;
            }
        }

        \app\core\Database::getConnection()->prepare("UPDATE seances SET pv_envoye = 1 WHERE id = ?")->execute([$seanceId]);
    }

    /* =========================================================================
       GESTION DES POINTS A L'ORDRE DU JOUR
       ========================================================================= */

    public function addPoint($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $type = $_POST['type_point'] ?? 'information';
            
            if (!empty($titre)) {
                $pointModel = new PointOdj();
                $pointModel->create($seanceId, $titre, $desc, $type, '');
                setToast("Point ajouté à l'ordre du jour.", "success");
            } else {
                setToast("Le titre du point est obligatoire.", "warning");
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

    public function deletePoint($pointId) {
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT seance_id FROM points_odj WHERE id = ?");
        $stmt->execute([$pointId]);
        $row = $stmt->fetch();
        $seanceId = $row['seance_id'] ?? null;

        $pointModel = new PointOdj();
        $pointModel->delete($pointId);
        setToast("Point supprimé de l'ordre du jour.", "success");

        if ($seanceId) {
            $this->redirect('seances/edit/' . $seanceId);
        } else {
            $this->redirect('seances');
        }
    }

    public function toggleRetirePoint($pointId) {
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("SELECT retire, seance_id FROM points_odj WHERE id = ?");
        $stmt->execute([$pointId]);
        $pt = $stmt->fetch();

        if ($pt) {
            $newStatus = $pt['retire'] ? 0 : 1;
            $db->prepare("UPDATE points_odj SET retire = ? WHERE id = ?")->execute([$newStatus, $pointId]);
            setToast($newStatus ? "Le point a été rayé." : "Le point a été rétabli.", "success");
            $this->redirect('seances/edit/' . $pt['seance_id']);
        }
    }


    /* =========================================================================
       GESTION DES DOCUMENTS (ANNEXES & PV)
       ========================================================================= */

    public function uploadDoc($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
            $nom = trim($_POST['nom'] ?? '');
            $pointId = !empty($_POST['point_odj_id']) ? $_POST['point_odj_id'] : null;
            $typeDoc = $_POST['type_doc'] ?? 'annexe';
            $file = $_FILES['fichier'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                if (empty($nom)) {
                    $nom = pathinfo($file['name'], PATHINFO_FILENAME);
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $nom) . '.' . $ext;
                $uploadDir = 'uploads/seances/' . $seanceId . '/';

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $destPath = $uploadDir . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $docModel = new Document();
                    $docModel->create($seanceId, $pointId, $nom, $destPath, $typeDoc);
                    setToast("Document ajouté avec succès.", "success");
                } else {
                    setToast("Erreur système lors du dépôt du fichier.", "danger");
                }
            } else {
                setToast("Veuillez sélectionner un fichier valide.", "danger");
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

    public function deleteDoc($docId) {
        $docModel = new Document();
        $doc = $docModel->getById($docId);

        if ($doc) {
            if (file_exists($doc['chemin_fichier'])) unlink($doc['chemin_fichier']);
            $docModel->delete($docId);
            setToast("Document supprimé.", "success");
            $this->redirect('seances/edit/' . $doc['seance_id']);
        } else {
            $this->redirect('seances');
        }
    }

    public function uploadPv($seanceId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pv_signe'])) {
            $file = $_FILES['pv_signe'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf', 'odt', 'docx'])) {
                    setToast("Seuls les formats PDF, ODT ou DOCX sont acceptés.", "danger");
                    $this->redirect('seances/edit/' . $seanceId);
                    return;
                }

                $safeName = 'PV_signe_' . $seanceId . '_' . uniqid() . '.' . $ext;
                $uploadDir = 'uploads/seances/' . $seanceId . '/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $destPath = $uploadDir . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $seanceModel = new Seance();
                    $seanceModel->updatePvPath($seanceId, $destPath);
                    Log::add('UPLOAD_PV', "Document PV uploadé pour la séance ID $seanceId");
                    setToast("Procès-verbal rattaché avec succès.", "success");
                }
            }
        }
        $this->redirect('seances/edit/' . $seanceId);
    }

    public function deletePv($seanceId) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);

        if ($seance && !empty($seance['proces_verbal_path'])) {
            if (file_exists($seance['proces_verbal_path'])) unlink($seance['proces_verbal_path']);
            $seanceModel->updatePvPath($seanceId, null);
            Log::add('DELETE_PV', "PV supprimé de la séance ID $seanceId");
            setToast("Procès-verbal supprimé.", "success");
        }
        $this->redirect('seances/edit/' . $seanceId);
    }


    /* =========================================================================
       ROUTES AJAX : COLLABORATION ET ENREGISTREMENTS AUTO
       ========================================================================= */

    public function updateOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['order']) && is_array($data['order'])) {
                $pointModel = new PointOdj();
                foreach ($data['order'] as $index => $id) {
                    $pointModel->updateOrdre($id, $index + 1);
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }

    public function updateDescription($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $pointModel = new PointOdj();
            $pointModel->updateDescription($pointId, $data['description'] ?? '');
            echo json_encode(['success' => true]);
            exit;
        }
    }

    public function updateNoteInterne($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $db = \app\core\Database::getConnection();
            $db->prepare("UPDATE points_odj SET note_interne = ? WHERE id = ?")
               ->execute([$data['note_interne'] ?? '', $pointId]);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    public function updatePointMeta($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $titre = trim($data['titre'] ?? '');
            $typePoint = trim($data['type_point'] ?? 'information');
            
            if (empty($titre)) {
                http_response_code(422);
                echo json_encode(['error' => 'Titre invalide']);
                exit;
            }

            $pointModel = new PointOdj();
            $pointModel->updateMeta($pointId, $titre, $typePoint);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    public function getPointData($pointId) {
        $db = \app\core\Database::getConnection();
        $pt = $db->prepare("SELECT description, note_interne FROM points_odj WHERE id = ?");
        $pt->execute([(int)$pointId]);
        $pt = $pt->fetch(\PDO::FETCH_ASSOC);

        if (!$pt) {
            http_response_code(404);
            echo json_encode(['error' => 'Introuvable']);
            exit;
        }

        $docs = $db->prepare("SELECT id, chemin_fichier, nom FROM documents WHERE point_odj_id = ?");
        $docs->execute([(int)$pointId]);
        $docs = $docs->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'description' => $pt['description'] ?? '',
            'note_interne' => $pt['note_interne'] ?? '',
            'documents' => $docs
        ]);
        exit;
    }

    public function autoSaveDebats($pointId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $pointModel = new PointOdj();
            $pointModel->updateDebats($pointId, $data['debats'] ?? '');
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    /* =========================================================================
       ROUTES AJAX : GESTION DES VERROUS (EDITION COLLABORATIVE)
       ========================================================================= */

    public function lockPoint($pointId) {
        $userId = (int)$_SESSION['user_id'];
        $db = \app\core\Database::getConnection();

        // Purge des verrous fantômes
        $db->prepare("DELETE FROM point_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)")->execute();

        // Vérification de blocage
        $stmt = $db->prepare("SELECT user_id, user_name FROM point_locks WHERE point_odj_id = ? LIMIT 1");
        $stmt->execute([(int)$pointId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing && (int)$existing['user_id'] !== $userId) {
            http_response_code(423);
            header('Content-Type: application/json');
            echo json_encode(['locked' => true, 'user_name' => $existing['user_name'] ?? 'Un collaborateur']);
            exit;
        }

        // Poser ou renouveler le verrou
        $stmt = $db->prepare("SELECT prenom, nom FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));

        $db->prepare("
            INSERT INTO point_locks (point_odj_id, user_id, user_name, locked_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), user_name=VALUES(user_name), locked_at=NOW()
        ")->execute([(int)$pointId, $userId, $userName]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function unlockPoint($pointId) {
        $db = \app\core\Database::getConnection();
        $db->prepare("DELETE FROM point_locks WHERE point_odj_id = ? AND user_id = ?")->execute([$pointId, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    public function checkLocks($seanceId) {
        $db = \app\core\Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.point_odj_id, l.user_id, l.user_name 
            FROM point_locks l 
            JOIN points_odj p ON p.id = l.point_odj_id 
            WHERE p.seance_id = ? AND l.locked_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
        ");
        $stmt->execute([(int)$seanceId]);
        
        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $lock) {
            $result[$lock['point_odj_id']] = ['user_id' => $lock['user_id'], 'user_name' => $lock['user_name']];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }


    /* =========================================================================
       ROUTES AJAX : LIVE (QUORUM, PRÉSENCE, VOTES)
       ========================================================================= */

    public function togglePresence() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $seanceId = $data['seance_id'] ?? 0;
            $membreId = $data['membre_id'] ?? 0;
            $estPresent = $data['est_present'] ?? false;
            $remplacePar = $data['remplace_par'] ?? null;

            $presenceModel = new Presence();
            $presenceModel->update($seanceId, $membreId, $estPresent, $remplacePar);

            $seanceModel = new Seance();
            $seance = $seanceModel->getById($seanceId);
            $presences = $presenceModel->getBySeance($seanceId);
            
            $nbPresents = 0;
            foreach($presences as $p) {
                if ($p['est_present'] || !empty($p['remplace_par_id'])) $nbPresents++;
            }
            $quorumAtteint = ($nbPresents >= $seance['quorum_requis']) ? 1 : 0;
            $seanceModel->updateQuorum($seanceId, $quorumAtteint);

            http_response_code(200);
            echo json_encode(['success' => true, 'quorum_atteint' => $quorumAtteint, 'presents' => $nbPresents]);
            exit;
        }
    }

    public function quorum($seanceId) {
        $attained = isset($_GET['attained']) ? (int)$_GET['attained'] : 0;
        $seanceModel = new Seance();
        $seanceModel->updateQuorum($seanceId, $attained);
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }

    public function saveVote() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $pointModel = new PointOdj();
            $pointModel->saveVotes(
                $data['point_id'] ?? 0, 
                $data['college'] ?? 'administration', 
                $data['pour'] ?? 0, 
                $data['contre'] ?? 0, 
                $data['abstention'] ?? 0, 
                $data['refus'] ?? 0
            );
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        }
    }

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
                setToast("Les votes ont été consignés.", "success");
                $this->redirect('seances/edit/' . $pt['seance_id']);
                return;
            }
        }
        $this->redirect('seances');
    }

    public function getLiveState($seanceId) {
        $pointModel = new PointOdj();
        $presenceModel = new Presence();

        $points = $pointModel->getBySeance($seanceId);
        $presences = $presenceModel->getBySeance($seanceId);
        $votes = [];
        
        foreach($points as $pt) {
            $votes[$pt['id']] = $pointModel->getVotes($pt['id']);
        }

        header('Content-Type: application/json');
        echo json_encode(['points' => $points, 'presences' => $presences, 'votes' => $votes]);
        exit;
    }


    /* =========================================================================
       GÉNÉRATEURS DE DOCUMENTS (CONVOCATION & PROCES-VERBAL)
       ========================================================================= */

    public function generateConvocation($seanceId) {
        $seanceModel = new Seance();
        $seance = $seanceModel->getById($seanceId);

        if (!$seance) {
            $this->redirect('seances');
            return;
        }

        $modelePath = dirname(dirname(__DIR__)) . '/uploads/modeles/modele_instance_' . $seance['instance_id'] . '.odt';
        if (!file_exists($modelePath)) {
            setToast("Modèle de matrice de convocation ODT introuvable sur le serveur.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'KRONO_');
        if (!copy($modelePath, $tempFile)) {
            setToast("Erreur système de lecture du modèle ODT.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($tempFile) !== true) {
            setToast("Archive ODT corrompue.", "danger");
            unlink($tempFile);
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $content = $zip->getFromName('content.xml');
        if ($content === false) {
            setToast("Fichier XML manquant dans le modèle ODT.", "danger");
            $zip->close(); unlink($tempFile);
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $dateStr = date('d/m/Y', strtotime($seance['date_seance']));
        $heureStr = date('H\hi', strtotime($seance['heure_debut']));

        $pointModel = new PointOdj();
        $points = $pointModel->getBySeance($seanceId);
        $listeOdj = "";

        if (!empty($points)) {
            foreach ($points as $index => $pt) {
                if ($index > 0) $listeOdj .= '<text:tab/><text:line-break/>';
                $listeOdj .= ($index + 1) . '. ' . htmlspecialchars($pt['titre'], ENT_QUOTES, 'UTF-8');
            }
        } else {
            $listeOdj = "Aucun point n'a encore été inscrit à l'ordre du jour.";
        }

        $replacements = [
            '[INSTANCE]' => htmlspecialchars($seance['instance_nom'] ?? '', ENT_QUOTES, 'UTF-8'),
            '[DATE]' => htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8'),
            '[HEURE]' => htmlspecialchars($heureStr, ENT_QUOTES, 'UTF-8'),
            '[LIEU]' => htmlspecialchars($seance['lieu'] ?? '', ENT_QUOTES, 'UTF-8'),
            '[ODJ]' => $listeOdj
        ];

        foreach ($replacements as $tag => $value) {
            $regex = '/';
            foreach (str_split($tag) as $char) {
                $regex .= preg_quote($char, '/') . '(<[^>]+>)*?';
            }
            $regex .= '/u';
            $content = preg_replace($regex, $value, $content);
        }

        $zip->addFromString('content.xml', $content);
        $zip->close();

        // Envoi propre au navigateur
        while (ob_get_level()) ob_end_clean();
        clearstatcache(true, $tempFile);

        $safeName = preg_replace('/[^a-zA-Z0-9-]/', '_', $seance['instance_nom']);
        $safeDate = str_replace('/', '-', $dateStr);
        $filename = "Convocation_{$safeName}_{$safeDate}.odt";

        header('Content-Type: application/vnd.oasis.opendocument.text');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tempFile));

        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    public function generatePv($seanceId) {
        $seanceModel = new Seance();
        $pointModel = new PointOdj();
        $instanceModel = new Instance();
        $presenceModel = new Presence();

        $seance = $seanceModel->getById($seanceId);
        if (!$seance) {
            $this->redirect('seances');
            return;
        }

        $points = $pointModel->getBySeance($seanceId);
        $membres = $instanceModel->getMembres($seance['instance_id']);
        $presences = $presenceModel->getBySeance($seanceId);

        // -- 1. GESTION DU XML --
        $xmlContent = "";
        $pStyle = '<text:p text:style-name="P1">';
        $h1Style = '<text:p text:style-name="H1">';
        $h2Style = '<text:p text:style-name="H2">';
        $boldStyleStart = '<text:span text:style-name="T1">';
        $boldStyleEnd = '</text:span>';
        $endP = '</text:p>';

        $dateObj = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
        
        $xmlContent .= $h1Style . "PROCÈS-VERBAL" . $endP;
        $xmlContent .= $pStyle . $boldStyleStart . htmlspecialchars($seance['instance_nom'], ENT_XML1, 'UTF-8') . $boldStyleEnd . $endP;
        $xmlContent .= $pStyle . "Séance du " . htmlspecialchars($dateObj->format('d/m/Y à H\hi'), ENT_XML1, 'UTF-8') . $endP;
        if (!empty($seance['lieu'])) {
            $xmlContent .= $pStyle . "Lieu : " . htmlspecialchars($seance['lieu'], ENT_XML1, 'UTF-8') . $endP;
        }

        $xmlContent .= '<text:p text:style-name="Standard"/>';

        $presents = []; $excuses = []; $remplaces = [];
        foreach ($membres as $m) {
            $presenceInfo = null;
            foreach ($presences as $p) {
                if ($p['membre_id'] == $m['id']) { $presenceInfo = $p; break; }
            }
            
            $nomComplet = strtoupper($m['nom']) . ' ' . $m['prenom'];
            $college = ucfirst($m['college']);

            if ($presenceInfo) {
                if ($presenceInfo['est_present']) {
                    $presents[] = "$nomComplet ($college)";
                } elseif (!empty($presenceInfo['remplace_par_id'])) {
                    $suppleantNom = "";
                    foreach ($membres as $sup) {
                        if ($sup['id'] == $presenceInfo['remplace_par_id']) {
                            $suppleantNom = strtoupper($sup['nom']) . ' ' . $sup['prenom'];
                            break;
                        }
                    }
                    $remplaces[] = "$nomComplet ($college), représenté(e) par $suppleantNom";
                } else {
                    $excuses[] = "$nomComplet ($college)";
                }
            } else {
                $excuses[] = "$nomComplet ($college)";
            }
        }

        $xmlContent .= $h2Style . "MEMBRES PRÉSENTS" . $endP;
        foreach ($presents as $p) { $xmlContent .= $pStyle . htmlspecialchars($p, ENT_XML1, 'UTF-8') . $endP; }

        if (!empty($remplaces)) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . "MEMBRES REPRÉSENTÉS" . $endP;
            foreach ($remplaces as $r) { $xmlContent .= $pStyle . htmlspecialchars($r, ENT_XML1, 'UTF-8') . $endP; }
        }

        if (!empty($excuses)) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . "MEMBRES EXCUSÉS" . $endP;
            foreach ($excuses as $e) { $xmlContent .= $pStyle . htmlspecialchars($e, ENT_XML1, 'UTF-8') . $endP; }
        }

        $xmlContent .= '<text:p text:style-name="SautDePage"/>';
        $xmlContent .= $h1Style . "ORDRE DU JOUR ET DÉBATS" . $endP;

        foreach ($points as $index => $pt) {
            $xmlContent .= '<text:p text:style-name="Standard"/>';
            $xmlContent .= $h2Style . ($index + 1) . ". " . htmlspecialchars($pt['titre'], ENT_XML1, 'UTF-8') . $endP;
            
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

            if (in_array($pt['type_point'], ['deliberation', 'vote'])) {
                $xmlContent .= '<text:p text:style-name="Standard"/>';
                $xmlContent .= $pStyle . $boldStyleStart . "Résultat de la délibération :" . $boldStyleEnd . $endP;
                
                $votes = $pointModel->getVotes($pt['id']);
                
                foreach (['administration', 'personnel'] as $college) {
                    $v = array_filter($votes, fn($vote) => $vote['college'] === $college);
                    $v = reset($v);
                    
                    if ($v) {
                        $unanimite = ($v['pour'] > 0 && $v['contre'] == 0 && $v['abstention'] == 0);
                        $nomCollege = ucfirst($college);
                        
                        if ($unanimite) {
                            $xmlContent .= $pStyle . "Collège $nomCollege : Avis favorable à l'unanimité." . $endP;
                        } else {
                            $details = [];
                            if ($v['pour'] > 0) $details[] = $v['pour'] . " favorables";
                            if ($v['contre'] > 0) $details[] = $v['contre'] . " défavorables";
                            if ($v['abstention'] > 0) $details[] = $v['abstention'] . " abstentions";
                            if ($v['refus'] > 0) $details[] = $v['refus'] . " refus de vote";
                            
                            $txtVote = "Collège $nomCollege : " . implode(", ", $details) . ".";
                            $xmlContent .= $pStyle . htmlspecialchars($txtVote, ENT_XML1, 'UTF-8') . $endP;
                        }
                    }
                }
                $xmlContent .= $pStyle . "" . $endP;
            }
        }

        // -- 2. CONSTRUCTION DU ODT XML --
        $contentXml = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" office:version="1.2">
            <office:automatic-styles>
                <style:style style:name="SautDePage" style:family="paragraph" style:parent-style-name="Standard"><style:paragraph-properties fo:break-before="page"/></style:style>
                <style:style style:name="P1" style:family="paragraph" style:parent-style-name="Standard"><style:text-properties fo:font-size="11pt" style:font-name="Arial"/><style:paragraph-properties fo:margin-bottom="0.2cm"/></style:style>
                <style:style style:name="H1" style:family="paragraph" style:parent-style-name="Standard"><style:text-properties fo:font-size="14pt" fo:font-weight="bold" style:font-name="Arial"/><style:paragraph-properties fo:text-align="center" fo:margin-bottom="0.4cm"/></style:style>
                <style:style style:name="H2" style:family="paragraph" style:parent-style-name="Standard"><style:text-properties fo:font-size="12pt" fo:font-weight="bold" style:text-underline-style="solid" style:font-name="Arial"/><style:paragraph-properties fo:margin-bottom="0.3cm"/></style:style>
                <style:style style:name="T1" style:family="text"><style:text-properties fo:font-weight="bold"/></style:style>
                <style:style style:name="T2" style:family="text"><style:text-properties fo:font-style="italic" fo:color="#666666"/></style:style>
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

        $tempFile = tempnam(sys_get_temp_dir(), 'PV_') . '.odt';
        $zip = new \ZipArchive();

        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            setToast("Impossible de compresser l'archive ODT.", "danger");
            $this->redirect('seances/edit/' . $seanceId);
            return;
        }

        $zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
        $zip->addFromString('content.xml', $contentXml);
        $zip->addEmptyDir('META-INF');
        $zip->addFromString('META-INF/manifest.xml', $manifestXml);
        $zip->close();

        // -- 3. TELECHARGEMENT --
        while (ob_get_level()) ob_end_clean();
        clearstatcache(true, $tempFile);

        $safeName = preg_replace('/[^a-zA-Z0-9-]/', '_', $seance['instance_nom']);
        $safeDate = str_replace('/', '', $dateObj->format('Y-m-d'));
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
}