<?php
namespace app\core;

use app\models\Arrete;
use app\models\Notification; // Modèle statique existant
use PDO;

class WorkflowEngine {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * DÉMARRAGE DU WORKFLOW
     */
    public function start($arreteId, $workflowId, $userId) {
        // 1. Vérif si déjà démarré
        $stmt = $this->db->prepare("SELECT id FROM workflow_instances WHERE arrete_id = ?");
        $stmt->execute([$arreteId]);
        if ($stmt->fetch()) return false;

        // 2. Récupérer première étape
        $firstStep = $this->getFirstStep($workflowId);
        if (!$firstStep) return false;

        // 3. Créer l'instance
        $stmt = $this->db->prepare("INSERT INTO workflow_instances (arrete_id, workflow_id, status, current_step_id, started_by, started_at) VALUES (?, ?, 'in_progress', ?, ?, NOW())");
        $stmt->execute([$arreteId, $workflowId, $firstStep['id'], $userId]);
        $instanceId = $this->db->lastInsertId();

        // 4. Update statut global
        $this->updateArreteStatus($arreteId, 'en_validation', $workflowId);

        // 5. Notifier les validateurs (TYPE: INFO)
        $this->notifyValidators($firstStep['id'], $arreteId, "Validation requise pour l'acte #$arreteId", 'info');

        return $instanceId;
    }

    /**
     * ENREGISTRER UNE DÉCISION
     */
    public function submitDecision($instanceId, $userId, $decision, $comment = '') {
        $instance = $this->getInstance($instanceId);
        if (!$instance || $instance['status'] !== 'in_progress') return ['success' => false, 'message' => "Workflow inactif"];

        $currentStepId = $instance['current_step_id'];

        if (!$this->canUserValidate($currentStepId, $userId)) {
            return ['success' => false, 'message' => "Non autorisé pour cette étape"];
        }

        // Enregistrer la décision
        $stmt = $this->db->prepare("INSERT INTO workflow_validations (instance_id, step_id, user_id, decision, commentaire, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$instanceId, $currentStepId, $userId, $decision, $comment]);

        // CAS 1 : REJET
        if ($decision === 'reject') {
            $this->setInstanceStatus($instanceId, 'rejected');
            $this->updateArreteStatus($instance['arrete_id'], 'rejete');
            
            // Notifier l'auteur (TYPE: DANGER)
            Notification::add($instance['started_by'], 'danger', "Votre arrêté #{$instance['arrete_id']} a été rejeté.", "/arretes/view/{$instance['arrete_id']}");
            
            return ['success' => true, 'action' => 'rejected'];
        }
        
        // CAS 2 : DEMANDE DE MODIF
        if ($decision === 'request_changes') {
             // Logique à définir (retour brouillon ?). Pour l'instant on notifie juste.
             Notification::add($instance['started_by'], 'warning', "Modifications demandées pour l'arrêté #{$instance['arrete_id']}.", "/arretes/edit/{$instance['arrete_id']}");
             return ['success' => true, 'action' => 'changes_requested'];
        }

        // CAS 3 : APPROBATION
        // Vérifier si l'étape est finie (ici policy='any' => 1 validation suffit)
        return $this->advanceStep($instanceId, $instance);
    }

    /**
     * PASSAGE ÉTAPE SUIVANTE
     */
    private function advanceStep($instanceId, $instance) {
        $currentStep = $this->getStep($instance['current_step_id']);
        $nextStep = $this->getNextStep($instance['workflow_id'], $currentStep['step_order']);

        if ($nextStep) {
            // Update étape courante
            $stmt = $this->db->prepare("UPDATE workflow_instances SET current_step_id = ? WHERE id = ?");
            $stmt->execute([$nextStep['id'], $instanceId]);
            
            // Notifier les prochains validateurs (TYPE: INFO)
            $this->notifyValidators($nextStep['id'], $instance['arrete_id'], "À valider : Étape '{$nextStep['nom']}' pour l'acte #{$instance['arrete_id']}", 'info');
            
            return ['success' => true, 'action' => 'advanced', 'step' => $nextStep['nom']];
        } else {
            // FIN DU WORKFLOW
            return $this->finalizeWorkflow($instanceId, $instance);
        }
    }

    /**
     * FINALISATION (Génération Numéro)
     */
    private function finalizeWorkflow($instanceId, $instance) {
        try {
            $this->db->beginTransaction();

            // Marquer comme terminé
            $stmt = $this->db->prepare("UPDATE workflow_instances SET status = 'approved', finished_at = NOW(), current_step_id = NULL WHERE id = ?");
            $stmt->execute([$instanceId]);

            // Générer le numéro définitif via le Modèle Arrete
            $arreteModel = new Arrete();
            $newNum = $arreteModel->generateDefinitiveNumber($instance['arrete_id']);

            // Update statut final
            $this->updateArreteStatus($instance['arrete_id'], 'valide');

            $this->db->commit();

            // Notifier l'auteur du succès (TYPE: SUCCESS)
            Notification::add($instance['started_by'], 'success', "Arrêté validé ! Numéro officiel : $newNum", "/arretes/view/{$instance['arrete_id']}");

            return ['success' => true, 'action' => 'finished', 'numero' => $newNum];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => "Erreur finale : " . $e->getMessage()];
        }
    }

    // --- Helpers ---

    private function canUserValidate($stepId, $userId) {
        $stmt = $this->db->prepare("SELECT 1 FROM workflow_step_assignees WHERE step_id = ? AND user_id = ?");
        $stmt->execute([$stepId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    private function getFirstStep($workflowId) {
        $stmt = $this->db->prepare("SELECT * FROM workflow_steps WHERE workflow_id = ? ORDER BY step_order ASC LIMIT 1");
        $stmt->execute([$workflowId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getNextStep($workflowId, $currentOrder) {
        $stmt = $this->db->prepare("SELECT * FROM workflow_steps WHERE workflow_id = ? AND step_order > ? ORDER BY step_order ASC LIMIT 1");
        $stmt->execute([$workflowId, $currentOrder]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateArreteStatus($id, $status, $workflowId = null) {
        $sql = "UPDATE arretes SET statut_workflow = ?";
        $params = [$status];
        if ($workflowId) {
            $sql .= ", workflow_id = ?";
            $params[] = $workflowId;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $this->db->prepare($sql)->execute($params);
    }
    
    private function setInstanceStatus($id, $status) {
        $this->db->prepare("UPDATE workflow_instances SET status = ? WHERE id = ?")->execute([$status, $id]);
    }

    private function getInstance($id) {
        $stmt = $this->db->prepare("SELECT * FROM workflow_instances WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getStep($id) {
        $stmt = $this->db->prepare("SELECT * FROM workflow_steps WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function notifyValidators($stepId, $arreteId, $msg, $type = 'info') {
        $stmt = $this->db->prepare("SELECT user_id FROM workflow_step_assignees WHERE step_id = ?");
        $stmt->execute([$stepId]);
        $validators = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($validators as $uid) {
            Notification::add($uid, $type, $msg, "/validation/detail/$arreteId");
        }
    }
}
