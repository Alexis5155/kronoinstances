-- ==========================================
-- SCHEMA BDD KRONOACTES - WORKFLOW + COMMENTAIRES + NUMÉROTATION
-- (Étapes affectées à des users, pas à des rôles)
-- ==========================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. Rôles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    power INT DEFAULT 0,
    is_immutable TINYINT(1) DEFAULT 0
) ENGINE=InnoDB;

-- 3. Liaison Rôle <-> Permissions
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_slug VARCHAR(50) NOT NULL,
    PRIMARY KEY (role_id, permission_slug),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    role_id INT NOT NULL,
    service_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_users_service (service_id),
    INDEX idx_users_role (role_id)
) ENGINE=InnoDB;

-- 5. Paramètres
CREATE TABLE IF NOT EXISTS settings (
    s_key VARCHAR(50) PRIMARY KEY,
    s_value TEXT
) ENGINE=InnoDB;

-- 6. Signataires
CREATE TABLE IF NOT EXISTS signataires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    qualite VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 7. Arrêtés
-- Numéro définitif: num_index/num_complet NULL tant que pas validé définitivement + génération explicite
-- Numéro temporaire: l'ID de la ligne (arretes.id) => rien à stocker de plus
CREATE TABLE IF NOT EXISTS arretes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Numérotation définitive (NULL tant que non générée)
    num_index INT NULL COMMENT 'Index définitif (NULL tant que non généré)',
    num_complet VARCHAR(50) NULL COMMENT 'Numéro complet (NULL tant que non généré)',
    annee INT NULL COMMENT 'Année de numérotation (renseignée au moment de la génération du numéro)',

    type_acte ENUM('réglementaire', 'individuel') NOT NULL,
    titre VARCHAR(255) NOT NULL,

    -- Date de prise/signature (souvent connue seulement quand validé)
    date_prise DATE NULL,
    signataire VARCHAR(100) DEFAULT NULL,

    -- Statuts
    statut_workflow ENUM('brouillon', 'en_validation', 'valide', 'publie', 'rejete', 'annule') DEFAULT 'brouillon',
    -- Legacy (si tu veux garder compat avec l’existant pendant la transition)
    statut ENUM('en cours', 'achevé', 'annulé') DEFAULT NULL,

    commentaire TEXT COMMENT 'Legacy/notes générales',

    -- Gestion documentaire
    mode_creation ENUM('upload', 'editeur') DEFAULT 'upload',
    fichier_path VARCHAR(255) DEFAULT NULL COMMENT 'Legacy',
    fichier_original_path VARCHAR(255) DEFAULT NULL,
    fichier_pdf_path VARCHAR(255) DEFAULT NULL,
    fichier_original_name VARCHAR(255) DEFAULT NULL,
    fichier_original_mime VARCHAR(100) DEFAULT NULL,
    contenu_html LONGTEXT DEFAULT NULL,

    -- Workflow
    workflow_id INT NULL,

    -- Auteur principal
    user_id INT NOT NULL,
    service_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Traçabilité numérotation définitive
    numero_genere_at TIMESTAMP NULL,
    numero_genere_by INT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (numero_genere_by) REFERENCES users(id) ON DELETE SET NULL,

    -- Unicité : OK car num_index et annee sont NULL tant que brouillon/en_validation
    UNIQUE KEY unique_index_annuel (annee, type_acte, num_index),

    INDEX idx_recherche (annee, statut_workflow, service_id),
    INDEX idx_user_actes (user_id),
    INDEX idx_service_actes (service_id),
    INDEX idx_numero (num_complet)
) ENGINE=InnoDB;

-- 8. Auteurs secondaires (co-rédacteurs)
CREATE TABLE IF NOT EXISTS arrete_auteurs (
    arrete_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('principal', 'secondaire') NOT NULL DEFAULT 'secondaire',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by INT NULL COMMENT 'Qui a ajouté ce co-auteur',
    PRIMARY KEY (arrete_id, user_id),
    FOREIGN KEY (arrete_id) REFERENCES arretes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_arrete_auteurs_user (user_id)
) ENGINE=InnoDB;

-- 9. Commentaires "post" sur un arrêté
CREATE TABLE IF NOT EXISTS arrete_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arrete_id INT NOT NULL,
    user_id INT NOT NULL,

    body TEXT NOT NULL,
    parent_id INT NULL COMMENT 'Optionnel: réponses',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    edited TINYINT(1) DEFAULT 0,
    deleted TINYINT(1) DEFAULT 0 COMMENT 'Soft delete',

    FOREIGN KEY (arrete_id) REFERENCES arretes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES arrete_comments(id) ON DELETE CASCADE,

    INDEX idx_comments_arrete (arrete_id, created_at),
    INDEX idx_comments_parent (parent_id),
    INDEX idx_comments_user (user_id, created_at)
) ENGINE=InnoDB;

-- 10. Mentions (optionnel)
CREATE TABLE IF NOT EXISTS arrete_comment_mentions (
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES arrete_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentions_user (user_id)
) ENGINE=InnoDB;

-- ==========================================
-- WORKFLOW (circuits par service, étapes assignées à des users)
-- ==========================================

-- 11. Circuits de validation (rattachés à un service)
CREATE TABLE IF NOT EXISTS workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY uq_workflow_service_nom (service_id, nom),
    INDEX idx_workflows_service_actif (service_id, actif),
    INDEX idx_workflows_default (service_id, is_default)
) ENGINE=InnoDB;

-- 12. Étapes d’un workflow
CREATE TABLE IF NOT EXISTS workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    step_order INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,

    -- Concurrence : si plusieurs assignees et policy='any', une seule validation suffit pour passer à l'étape suivante
    approval_policy ENUM('any', 'all') DEFAULT 'any',

    -- Numérotation définitive: possible de “marquer” une étape qui autorise/génère le numéro
    genere_numero TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    UNIQUE KEY uq_steps_workflow_order (workflow_id, step_order),
    INDEX idx_steps_workflow (workflow_id)
) ENGINE=InnoDB;

-- 13. Affectation des validateurs d'une étape (users uniquement)
CREATE TABLE IF NOT EXISTS workflow_step_assignees (
    step_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (step_id, user_id),
    FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_wsa_user (user_id)
) ENGINE=InnoDB;

-- 14. Instance de workflow pour un arrêté
CREATE TABLE IF NOT EXISTS workflow_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arrete_id INT NOT NULL,
    workflow_id INT NOT NULL,

    status ENUM('draft', 'in_progress', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
    current_step_id INT NULL,

    started_by INT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,

    FOREIGN KEY (arrete_id) REFERENCES arretes(id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE RESTRICT,
    FOREIGN KEY (current_step_id) REFERENCES workflow_steps(id) ON DELETE SET NULL,
    FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY uq_instance_arrete (arrete_id),
    INDEX idx_instances_status (status),
    INDEX idx_instances_current_step (current_step_id),
    INDEX idx_instances_workflow (workflow_id)
) ENGINE=InnoDB;

-- 15. Historique des validations
CREATE TABLE IF NOT EXISTS workflow_validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    step_id INT NOT NULL,
    user_id INT NOT NULL,

    decision ENUM('approve', 'reject', 'request_changes') NOT NULL,
    commentaire TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,

    UNIQUE KEY uq_validation_once (instance_id, step_id, user_id),
    INDEX idx_validations_instance (instance_id, created_at),
    INDEX idx_validations_user (user_id, created_at)
) ENGINE=InnoDB;

-- FK arretes.workflow_id une fois workflows créés
ALTER TABLE arretes
    ADD CONSTRAINT fk_arretes_workflow
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE SET NULL;

-- ==========================================
-- Notifications (compatible app/models/Notification.php)
-- ==========================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read_date (user_id, is_read, created_at)
) ENGINE=InnoDB;

-- 16. Logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    target_id INT NULL,
    target_type VARCHAR(50) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_logs_target (target_type, target_id),
    INDEX idx_logs_user (user_id, created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- SEEDER (DONNÉES INITIALES)
-- ==========================================================

-- A. Rôles
INSERT IGNORE INTO roles (id, nom, description, power, is_immutable) VALUES
(1, 'Administrateur', 'Accès complet au système', 100, 1),
(2, 'Responsable', 'Responsable d''un service', 50, 0),
(3, 'Agent', 'Utilisateur standard', 10, 0);

-- B. Permissions (on garde l'idée : droits de gérer workflows / valider / commenter)
INSERT IGNORE INTO role_permissions (role_id, permission_slug) VALUES
-- Admin
(1, 'create_acte'), (1, 'edit_acte_title'), (1, 'delete_acte'),
(1, 'view_service_actes'), (1, 'edit_service_actes'),
(1, 'view_all_actes'), (1, 'edit_all_actes'), (1, 'export_registre'),
(1, 'view_logs'), (1, 'manage_users'), (1, 'manage_roles'),
(1, 'manage_services'), (1, 'manage_signataires'), (1, 'manage_system'),
(1, 'manage_workflows'), (1, 'start_workflow'), (1, 'validate_workflow_step'), (1, 'cancel_workflow'),
(1, 'generate_numero'),
(1, 'comment_on_acte'), (1, 'edit_own_comments'), (1, 'delete_own_comments'), (1, 'delete_any_comment'),

-- Responsable
(2, 'create_acte'), (2, 'view_service_actes'), (2, 'edit_service_actes'),
(2, 'export_registre'), (2, 'manage_signataires'),
(2, 'manage_workflows'), (2, 'start_workflow'), (2, 'validate_workflow_step'),
(2, 'generate_numero'),
(2, 'comment_on_acte'), (2, 'edit_own_comments'), (2, 'delete_own_comments'),

-- Agent
(3, 'create_acte'), (3, 'edit_acte_title'), (3, 'view_service_actes'),
(3, 'start_workflow'),
(3, 'comment_on_acte'), (3, 'edit_own_comments'), (3, 'delete_own_comments');

-- C. Données initiales
INSERT IGNORE INTO settings (s_key, s_value) VALUES
('collectivite_nom', 'Ma Collectivité'),
('update_track', 'main'),
('workflow_enabled', '1');

INSERT IGNORE INTO services (id, nom) VALUES (1, 'Administration générale');
INSERT IGNORE INTO signataires (nom, prenom, qualite) VALUES ('DUPONT', 'Martin', 'Maire');

-- D. Exemple de workflow par défaut (sans assignation de user ici)
INSERT IGNORE INTO workflows (id, service_id, nom, description, actif, is_default) VALUES
(1, 1, 'Validation standard', '2 étapes (validateurs utilisateurs). Dernière étape = autorise/génère le numéro.', 1, 1);

INSERT IGNORE INTO workflow_steps (workflow_id, step_order, nom, description, approval_policy, genere_numero) VALUES
(1, 1, 'Validation', 'Validation par un ou plusieurs validateurs (concurrente)', 'any', 0),
(1, 2, 'Validation finale / Numéro', 'Étape finale (concurrente possible) + génération du numéro', 'any', 1);

-- NB: l'affectation des users à chaque étape se fait via workflow_step_assignees
-- (step_id, user_id), à faire après création de tes users.
