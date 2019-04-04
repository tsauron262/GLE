<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BIC_UserClientContrats extends BimpObject {
    
    CONST USER_CLIENT_ROLE_ADMIN = 1;
    CONST USER_CLIENT_ROLE_USER = 0;
    CONST USER_CLIENT_STATUS_ACTIF = 1;
    CONST USER_CLIENT_STATUS_INACTIF = 2;
    CONST CONTRAT_NON_VALIDE = 0;
    CONST CONTRAT_VALIDE = 1;
    
    public static $etat_contrat = Array(
        self::CONTRAT_NON_VALIDE => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'times'),
        self::CONTRAT_VALIDE => Array('label' => 'Ouvert', 'classes' => Array('success'), 'icon' => 'check')
    );
    
    public function etatContrat() {
        global $couverture;
        
        return self::$etat_contrat[1];
    }
    
    public function getCouverture() {
        global $couverture;
        return $couverture;
    }
    
    public function hasAdmin() {
        $parent = $this->getParentInstance();
        return $parent->id;
    }
    
    public function getNomContrat() {
        global $db;
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $contrat = new Contrat($db);
        $contrat->fetch($this->getData('id_contrat'));
        return 'ok';
    }
    
    public function canClientView() {
        return true;
    }
    
}