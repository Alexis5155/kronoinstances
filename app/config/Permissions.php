<?php
namespace app\config;

class Permissions {
    // Liste exhaustive des permissions codées en dur
    public const LIST = [
        // --- GESTION MÉTIER ---
        'create_acte' => [
            'nom' => 'Créer des actes',
            'desc' => 'Permet de générer un nouveau numéro',
            'cat' => 'Gestion métier'
        ],
        'edit_acte_title' => [
            'nom' => 'Modifier titre',
            'desc' => 'Correction mineure du titre uniquement',
            'cat' => 'Gestion métier'
        ],
        
        // --- SUPERVISION DU SERVICE ---
        'view_service_actes' => [
            'nom' => 'Voir les actes du service',
            'desc' => 'Consulter les actes de son service',
            'cat' => 'Supervision du service'
        ],
        'edit_service_actes' => [
            'nom' => 'Éditer les actes du service',
            'desc' => 'Modifier les actes de son service',
            'cat' => 'Supervision du service'
        ],

        // --- REGISTRE GLOBAL ---
        'view_all_actes' => [
            'nom' => 'Voir registre global',
            'desc' => 'Accès en lecture à tout le registre',
            'cat' => 'Registre Global'
        ],
        'edit_all_actes' => [
            'nom' => 'Éditer registre global',
            'desc' => 'Modifier n\'importe quel acte',
            'cat' => 'Registre Global'
        ],
        'delete_acte' => [
            'nom' => 'Supprimer acte',
            'desc' => 'Suppression définitive (Archives + BDD)',
            'cat' => 'Registre Global'
        ],
        'export_registre' => [
            'nom' => 'Exporter',
            'desc' => 'Accès aux exports CSV/Excel',
            'cat' => 'Registre Global'
        ],

        // --- ADMINISTRATION ---
        'view_logs' => [
            'nom' => 'Voir les logs',
            'desc' => 'Audit des actions techniques',
            'cat' => 'Administration'
        ],
        'manage_users' => [
            'nom' => 'Gérer utilisateurs',
            'desc' => 'Créer, modifier, supprimer des comptes',
            'cat' => 'Administration'
        ],
        'manage_roles' => [
            'nom' => 'Gérer rôles',
            'desc' => 'Créer des rôles et changer les permissions',
            'cat' => 'Administration'
        ],
        'manage_services' => [
            'nom' => 'Gérer services',
            'desc' => 'Ajouter/Supprimer des services',
            'cat' => 'Administration'
        ],
        'manage_signataires' => [
            'nom' => 'Gérer signataires',
            'desc' => 'Ajouter/Supprimer des signataires',
            'cat' => 'Administration'
        ],
        'manage_system' => [
            'nom' => 'Admin Système',
            'desc' => 'Mises à jour, config technique, backups',
            'cat' => 'Administration'
        ]
    ];

    // Helper pour récupérer toutes les permissions
    public static function getAll() {
        return self::LIST;
    }
}