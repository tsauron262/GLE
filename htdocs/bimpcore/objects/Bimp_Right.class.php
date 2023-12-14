<?php

class Bimp_Right extends BimpObject
{

    // Droits users: 

    public function canCreate()
    {
        global $user;

        return (int) $user->admin;
    }

    public function canEdit()
    {
        return 1;
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    // Getters booléens: 

    public function isEditable($force_edit = false, &$errors = []): int
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        if (!$force_edit) {
            return 0;
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        return 1;
    }

    // Getters filters

    /* Commentaire à suppr. 
     * Les fonctions suivantes servent à pouvoir effectuer des recherche sur chacune des colonnes custom dans la barre de recherche des listes
     * Syntaxe du nom des fonctions: getNomColonneSearchFilters() 
     */

    public function getRight_moduleSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $def_alias = $main_alias . '___right_def';

        // ajout de la jointure sur la table rights_def si elle n'a pas déjà été ajoutée: 
        if (!isset($joins[$def_alias])) {
            $joins[$def_alias] = array(
                'alias' => $def_alias,
                'table' => 'rights_def',
                'on'    => $main_alias . '.fk_id = ' . $def_alias . '.id'
            );
        }

        // Ajout du filtre sur le champ module de la table jointe
        $filters[$def_alias . '.module'] = array(
            'part_type' => 'middle',
            'part'      => $value
        ); // Permet de générérer : LIKE %$value%
    }

    public function getLibelleSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $def_alias = $main_alias . '___right_def';

        // ajout de la jointure si elle n'a pas déjà été ajoutée: 
        if (!isset($joins[$def_alias])) {
            $joins[$def_alias] = array(
                'alias' => $def_alias,
                'table' => 'rights_def',
                'on'    => $main_alias . '.fk_id = ' . $def_alias . '.id'
            );
        }

        // Ajout du filtre sur le champ module de la table jointe
        $filters[$def_alias . '.libelle'] = array(
            'part_type' => 'middle',
            'part'      => $value
        ); // Permet de générérer : LIKE %$value%
    }

    public function getCodeSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $def_alias = $main_alias . '___right_def';

        // ajout de la jointure si elle n'a pas déjà été ajoutée: 
        if (!isset($joins[$def_alias])) {
            $joins[$def_alias] = array(
                'alias' => $def_alias,
                'table' => 'rights_def',
                'on'    => $main_alias . '.fk_id = ' . $def_alias . '.id'
            );
        }

        // Ajout du filtre sur le champ module OU perms OU subperms de la table jointe
        // Utiliser $main_alias en préfixe de l'identifiant du filtre de type "or" permet d'éviter un éventuel conflit de nom
        $filters[$main_alias . '_or_code'] = array(
            'or' => array(
                $def_alias . '.module'   => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $def_alias . '.perms'    => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $def_alias . '.subperms' => array(
                    'part_type' => 'middle',
                    'part'      => $value
                )
            )
        );
    }
    /* Commentaire à suppr. 
     * La fonction suivante sers à rendre fonctionnel les filtres personnalisés (custom) dans le panneau filtres à gauche des listes. 
     */

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = [], $excluded = false)
    {
        switch ($field_name) {
            case 'right_module':
                // ajout de la jointure sur la table rights_def si elle n'a pas déjà été ajoutée: 
                $def_alias = $main_alias . '___right_def'; // on utilise le même alias que pour les recherches sur les colonnes custom pour éviter d'avoir plusieurs fois la même jointure
                if (!isset($joins[$def_alias])) {
                    $joins[$def_alias] = array(
                        'alias' => $def_alias,
                        'table' => 'rights_def',
                        'on'    => $main_alias . '.fk_id = ' . $def_alias . '.id'
                    );
                }

                // Ajout du filtre:
                $filters[$def_alias . '.module'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                return;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    // Overrides: 
    
    public function validate()
    {
        $errors = parent::validate();

        if (!(int) $this->getData('entity')) {
            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                $errors[] = 'Aucune entité spécifiée';
            } else {
                $this->set('entity', 1);
            }
        }

        return $errors;
    }
}
