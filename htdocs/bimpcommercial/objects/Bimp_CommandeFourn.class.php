<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class Bimp_CommandeFourn extends BimpComm
{

    public static $comm_type = 'commande_fourn';
    public static $status_list = array(
//        0 => array('label' => 'Brouillon', 'icon' => 'file-text', 'classes' => array('warning')),
//        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
//        2 => array('label' => 'Signée (A facturer)', 'icon' => 'check', 'classes' => array('info')),
//        3 => array('label' => 'Non signée (fermée)', 'icon' => 'exclamation-circle', 'classes' => array('important')),
//        4 => array('label' => 'Facturée (fermée)', 'icon' => 'check', 'classes' => array('success')),
    );

    // Getters - overrides BimpComm

    public function getModelsPdfArray()
    {
//        if (!class_exists('ModelePDFPropales')) {
//            require_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
//        }
//
//        return ModelePDFPropales::liste_modeles($this->db->db);
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->fournisseur->commande->dir_output;
    }

    public function getListFilters()
    {
        return array();
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $buttons = array();

        if ($this->isLoaded()) {
            
        }

        return $buttons;
    }

    // Gestion des droits - overrides BimpObject: 

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        return 1;
    }
}
