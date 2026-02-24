-- ==========================================
-- SCHEMA DE LA BASE DE DONNÉES KRONOINSTANCES
-- ==========================================

-- 1. Table des Rôles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

INSERT INTO roles (id, nom, description) VALUES 
(1, 'Administrateur', 'Accès total et configuration technique'),
(2, 'Gestionnaire RH', 'Pilotage des instances, création de séances et convocations'),
(3, 'Membre Instance', 'Consultation des dossiers et convocations (Élus/Syndicats)');

-- 2. Table des Utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    role_id INT NOT NULL DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- 3. Table des Paramètres Système
CREATE TABLE IF NOT EXISTS settings (
    s_key VARCHAR(50) PRIMARY KEY,
    s_value TEXT
) ENGINE=InnoDB;

-- ==========================================
-- COEUR DU MÉTIER : INSTANCES & SÉANCES
-- ==========================================

-- 4. Types d'Instances (CST, CAP A, CAP B...)
CREATE TABLE IF NOT EXISTS instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    nb_titulaires INT DEFAULT 0,
    nb_suppleants INT DEFAULT 0,
    quorum_requis INT DEFAULT 0
) ENGINE=InnoDB;

-- 5. Membres des instances (Elus / Représentants)
CREATE TABLE IF NOT EXISTS membres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    user_id INT NULL, -- Lien optionnel vers un compte de connexion
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    qualite VARCHAR(100), -- Ex: "Président", "Représentant CGT", "DGS"
    college ENUM('administration', 'personnel') NOT NULL,
    type_mandat ENUM('titulaire', 'suppleant') DEFAULT 'titulaire',
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Séances (Réunions)
CREATE TABLE IF NOT EXISTS seances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instance_id INT NOT NULL,
    date_seance DATE NOT NULL,
    heure_debut TIME,
    lieu VARCHAR(255),
    statut ENUM('planifiee', 'convoquee', 'en_cours', 'close', 'annulee') DEFAULT 'planifiee',
    quorum_atteint BOOLEAN DEFAULT FALSE,
    proces_verbal_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Points à l'Ordre du Jour
CREATE TABLE IF NOT EXISTS points_odj (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seance_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT, -- Exposé des motifs
    direction_origine VARCHAR(100) NULL, -- Ex: "Direction Finances", pour info
    type_point ENUM('avis', 'information') DEFAULT 'avis',
    ordre_affichage INT DEFAULT 0,
    statut ENUM('brouillon', 'valide', 'reporte', 'refuse') DEFAULT 'brouillon',
    resultat_vote VARCHAR(255) NULL, -- Ex: "Unanimité favorable"
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Documents (GED)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seance_id INT NULL,
    point_odj_id INT NULL,
    nom VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    type_doc ENUM('convocation', 'pv', 'annexe', 'autre') DEFAULT 'annexe',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    FOREIGN KEY (point_odj_id) REFERENCES points_odj(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Votes détaillés (Optionnel, pour le mode Live)
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    point_odj_id INT NOT NULL,
    college ENUM('administration', 'personnel', 'tous') NOT NULL,
    nb_pour INT DEFAULT 0,
    nb_contre INT DEFAULT 0,
    nb_abstention INT DEFAULT 0,
    nb_refus_vote INT DEFAULT 0,
    FOREIGN KEY (point_odj_id) REFERENCES points_odj(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Présences (Feuille d'émargement numérique)
CREATE TABLE IF NOT EXISTS presences (
    seance_id INT NOT NULL,
    membre_id INT NOT NULL,
    est_present BOOLEAN DEFAULT FALSE,
    est_excusé BOOLEAN DEFAULT FALSE,
    remplace_par_id INT NULL, -- Si un titulaire est remplacé par un suppléant
    PRIMARY KEY (seance_id, membre_id),
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Logs Système
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    target_id INT NULL,
    target_type VARCHAR(50),
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 12. Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    link VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- INITIALISATION DES DONNÉES PAR DÉFAUT
-- ==========================================================

INSERT INTO settings (s_key, s_value) VALUES ('collectivite_nom', 'Ma Collectivité');

-- Instances par défaut
INSERT INTO instances (nom, description, nb_titulaires, nb_suppleants, quorum_requis) VALUES 
('Comité Social Territorial (CST)', 'Instance de dialogue social pour les sujets d''organisation', 5, 5, 3),
('CAP - Catégorie C', 'Commission Administrative Paritaire pour les agents de catégorie C', 4, 4, 3);
