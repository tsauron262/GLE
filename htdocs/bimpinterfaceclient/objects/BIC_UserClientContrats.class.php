<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/objects/BIC_UserClient.class.php';

class BIC_UserClientContrats extends BimpObject {

    public function canClientView() {
        return true;
    }

    public function canClientEdit() {
        global $userClient;
        if ($userClient->it_is_admin()) {
            return true;
        }
        return false;
    }

    public function canClientCreate() {
        return $this->canClientEdit();
    }
        
    public function canClientDelete() {
        global $userClient;
        if(isset($userClient) && $userClient->it_is_admin()) {
            return 1;
        }
        return 0;
    }
    
    public function canDelete() {
        return 1;
    }

    public function getContrats() {
        global $userClient;
        if (isset($userClient)) {
            foreach ($userClient->getContratVisible(true) as $id_contrat => $contrat) {
                $return[$id_contrat] = $contrat->getName();
            }
            return $return;
        }
    }

    public function getFilterAssoContrat() {
        return Array(
            Array(
                'name' => 'id_user',
                'filter' => $_REQUEST['id']
            )
        );
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        $id_contrat = BimpTools::getValue('id_contrat');
        $id_user = BimpTools::getValue('id_user');
        if ($this->getList(array('id_contrat' => $id_contrat))) {
            if($this->getList(array('id_user' => $id_user))){
                return 'Ce contrat est déjà associé à cet utilisateur';
            }
        } else {
            parent::create($warnings, $force_create);
        }
        
    }

}
