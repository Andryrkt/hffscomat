<?php

namespace App\Service\navigation;

class MenuGroupe
{
    /**=========================================================================
     * 
     * DONNÉES STATIQUES — MENUS PRINCIPAUX
     * 
     * Structure d'un groupe :
     * [
     *   'route'    => string|null   // route pour filtrage (null = toujours visible)
     *   'label'    => string        // texte affiché
     *   'icon'     => string        // icône FontAwesome (sans "fa-")
     *   'link'     => string        // lien externe ou '#' (si absent, on génère depuis 'route')
     *   'target'   => string        // '_blank' optionnel
     *   'params'   => array         // paramètres de route optionnels
     *   'modal_id' => string|null   // id du modal à ouvrir
     *   'is_modal' => bool          // true si déclencheur de modal
     *   'subitems' => array         // sous-items (même structure récursive)
     * ]
     * 
     * Si link
     *==========================================================================*/

    public static function documentationGroupes(): array
    {
        return [
            [
                'route'    => 'documentation_interne',
                'label'    => 'Documentation interne',
                'icon'     => 'folder-tree',
            ],
        ];
    }

    public static function atelierGroupes(): array
    {
        return [
            [
                'label'    => "Demande d'intervention",
                'icon'     => 'toolbox',
                'subitems' => [
                    ['label' => 'Nouvelle demande',            'icon' => 'plus-circle', 'route' => 'dit_new'],
                    ['label' => 'Consultation',                'icon' => 'search',      'route' => 'dit_liste'],
                    ['label' => 'Dossier DIT',                 'icon' => 'folder',      'route' => 'dit_dossier_intervention_atelier'],
                    ['label' => 'Matrice des responsabilités', 'icon' => 'table',       'route' => 'dit_new', 'link'  => '{basePath}/documentation/MATRICE DE RESPONSABILITES OR v9.xlsx'],
                ],
            ],
            [
                'route'    => 'dit_new',
                'label'    => 'Glossaire OR',
                'icon'     => 'book',
                'link'     => '{basePath}/dit/glossaire_or/Glossaire_OR.pdf',
                'target'   => '_blank',
            ],
            [
                'route'    => 'planning_vue',
                'label'    => 'Planning',
                'icon'     => 'calendar-alt',
                'params'   => ['action' => 'oui'],
            ],
            [
                'route'    => 'liste_planning',
                'label'    => 'Planning détaillé',
                'icon'     => 'calendar-day',
                'params'   => ['action' => 'oui'],
            ],
            [
                'route'    => 'planningAtelier_vue',
                'label'    => 'Planning interne Atelier',
                'icon'     => 'calendar-alt',
            ],
            [
                'route'    => 'planning_vue',
                'label'    => 'Planning',
                'icon'     => 'calendar-alt',
                'params'   => ['action' => 'oui'],
            ],
            [
                'route'    => 'liste_planning',
                'label'    => 'Planning détaillé',
                'icon'     => 'calendar-day',
                'params'   => ['action' => 'oui'],
            ],
            [
                'route'    => 'planningAtelier_vue',
                'label'    => 'Planning interne Atelier',
                'icon'     => 'calendar-alt',
            ],
        ];
    }

    public static function magasinGroupes(): array
    {
        return [
            [
                'label'    => 'OR',
                'icon'     => 'warehouse',
                'subitems' => [
                    ['label' => 'Liste à traiter', 'icon' => 'tasks',        'route' => 'magasinListe_index'],
                    ['label' => 'Liste à livrer',  'icon' => 'truck-loading', 'route' => 'magasinListe_or_Livrer'],
                ],
            ],
            [
                'label'    => 'DEMATERIALISATION',
                'icon'     => 'cloud-arrow-up',
                'subitems' => [
                    ['label' => 'Devis',                        'icon' => 'file-invoice', 'route' => 'liste_devis_neg'],
                    ['label' => 'Planning de commande Magasin', 'icon' => 'calendar-alt', 'route' => 'interface_planningMag'],
                ],
            ],

        ];
    }

    /**
     * Définition statique des groupes et liens du menu Admin.
     * Modifiez ici pour ajouter / réordonner des entrées admin.
     */
    public static function adminMenuGroupes(): array
    {
        return [
            [
                'header' => 'Accès & Sécurité',
                'icon'   => 'fa-user-shield',
                'links'  => [
                    ['label' => 'Utilisateurs',              'icon' => 'fa-user',        'route' => 'utilisateur_index'],
                    ['label' => 'Profils ( ~ Applications)', 'icon' => 'fa-users-gear',  'route' => 'profil_index'],
                    ['label' => 'Droits et permissions',     'icon' => 'fa-key',         'route' => 'permission_index'],
                ],
            ],
            [
                'header' => 'Applications & Intégrations',
                'icon'   => 'fa-cubes',
                'links'  => [
                    ['label' => 'Pages',                       'icon' => 'fa-globe',       'route' => 'page_hff_index'],
                    ['label' => 'Applications ( ~ Pages)',     'icon' => 'fa-layer-group', 'route' => 'application_index'],
                    ['label' => 'Vignettes ( ~ Applications)', 'icon' => 'fa-clone',       'route' => 'vignette_index'],
                ],
            ],
            [
                'header' => 'Organisation',
                'icon'   => 'fa-sitemap',
                'links'  => [
                    ['label' => 'Sociétés',              'icon' => 'fa-building',     'route' => 'societte_index'],
                    ['label' => 'Services',              'icon' => 'fa-briefcase',    'route' => 'service_index'],
                    ['label' => 'Agences ( ~ Services)', 'icon' => 'fa-city',         'route' => 'agence_index'],
                    ['label' => 'Personnels',            'icon' => 'fa-id-card',      'route' => 'personnel_index'],
                ],
            ],
            [
                'header' => 'Historique',
                'icon'   => 'fa-clock-rotate-left',
                'links'  => [
                    ['label' => 'Consultation de pages',     'icon' => 'fa-eye',              'route' => 'consultation_page_index'],
                    ['label' => 'Historique des opérations', 'icon' => 'fa-file-circle-check', 'route' => 'operation_document_index'],
                ],
            ]
        ];
    }
}
