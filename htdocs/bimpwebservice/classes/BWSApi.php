<?php

class BWSApi
{

    public static $requests = array(
        'getObjectData'  => array(
            'desc'   => 'Retourne toutes les données d\'un objet',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet'),
                'ref'         => array('label' => 'Référence de l\'objet')
            )
        ),
        'getObjectValue' => array(
            'desc'   => 'Retourne la valeur d\'un champ objet',
            'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'id'          => array('label' => 'ID de l\'objet'),
                'ref'         => array('label' => 'Référence de l\'objet'),
                'field'       => array('label' => 'Champ')
            )
        ),
        'getObjectsList' => array(
            'desc'   => 'Retourne une liste de données d\'objets selon les filtres indiqués',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'filters'     => array('label' => 'Filtres')
            )
        ),
        'createObject'   => array(
            'desc'   => 'Ajoute un objet',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'data'        => array('label' => 'Données')
            )
        ),
        'updateObject'   => array(
            'desc'   => 'Met à jour un objet selon les données indiquées',
            'info'   => 'Fournir au moins l\'un des deux éléments parmi: ID, Référence',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'data'        => array('label' => 'Données')
            )
        ),
        'deleteObject'   => array(
            'desc'   => 'Supprime un objet',
            'info'   => 'ID de l\'objet obligatoire pour cette opération',
            'params' => array(
                'module'      => array('label' => 'Nom du module', 'require' => 1),
                'object_name' => array('label' => 'Nom de l\'objet', 'required' => 1),
                'ID'          => array('label' => 'ID de l\'objet', 'required' => 1)
            )
        )
    );

    // Getters statics: 
    
    public static function getRequestsArray()
    {
        $requests = array();

        foreach (self::$requests as $name => $data) {
            $requests[$name] = $name;
        }

        return $requests;
    }

    public function authenticate()
    {
        
    }
}
