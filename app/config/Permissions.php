<?php
namespace app\config;

class Permissions {
    public const LIST = [
        // --- GESTION MÉTIER ---
        'manage_instances' => [
            'nom' => 'Gérer les instances',
            'desc' => 'Créer, modifier, supprimer des instances (inclut le suivi des présences)',
            'cat' => 'Gestion métier'
        ],
        'manage_membres' => [
            'nom' => 'Gérer les membres',
            'desc' => 'Ajouter, modifier ou supprimer des membres d\'une instance',
            'cat' => 'Gestion métier'
        ],
        'create_seances' => [
            'nom' => 'Créer des séances',
            'desc' => 'Créer de nouvelles séances et préparer l\'Ordre du Jour',
            'cat' => 'Gestion métier'
        ],
        'manage_convocations' => [
            'nom' => 'Gérer les convocations',
            'desc' => 'Générer les ODT et gérer l\'envoi des convocations',
            'cat' => 'Gestion métier'
        ],
        'depot_document' => [
            'nom' => 'Déposer des documents',
            'desc' => 'Autorise le dépôt manuel de documents dans l\'espace personnel des membres',
            'cat' => 'Gestion métier'
        ],


        // --- WORKFLOW DES SÉANCES ---
        'avancer_etapes' => [
            'nom' => 'Avancer le statut',
            'desc' => 'Faire progresser une séance vers l\'étape suivante',
            'cat' => 'Workflow'
        ],
        'retrograder_etapes' => [
            'nom' => 'Rétrograder le statut',
            'desc' => 'Faire revenir une séance à une étape précédente',
            'cat' => 'Workflow'
        ],

        // --- ADMINISTRATION ---
        'view_logs' => [
            'nom' => 'Voir les logs',
            'desc' => 'Consulter le journal d\'audit technique',
            'cat' => 'Administration'
        ],
        'manage_users' => [
            'nom' => 'Gérer les utilisateurs',
            'desc' => 'Créer, modifier, supprimer des comptes et leurs permissions',
            'cat' => 'Administration'
        ],
        'manage_system' => [
            'nom' => 'Administration Système',
            'desc' => 'Accès super-admin (Configuration globale, mises à jour, BDD)',
            'cat' => 'Administration'
        ]
    ];

    public static function getAll() {
        return self::LIST;
    }
}
