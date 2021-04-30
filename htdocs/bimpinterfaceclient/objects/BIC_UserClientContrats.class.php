<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/objects/BIC_UserClient.class.php';

class BIC_UserClientContrats extends BimpObject
{

    public function canView()
    {
        if (BimpCore::isContextPublic()) {
            return 1;
        }

        return parent::canView();
    }

    public function canCreate()
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;
            if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
                return 1;
            }
            return 0;
        }

        return parent::canCreate();
    }

    public function canDelete()
    {
        if (BimpCore::isContextPublic()) {
            global $userClient;
            if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
                return 1;
            }
            return 0;
        }

        return 1;
    }

    public function getContrats()
    {
        global $userClient;
        $return = array();
        if (!isset($userClient))
            $userClient = $this->getParentInstance();
        if (isset($userClient)) {
            foreach ($userClient->getContratsVisibles(true) as $id_contrat => $contrat) {
                $return[$id_contrat] = $contrat->getData('ref');
            }
        }
        return $return;
    }

    public function getFilterAssoContrat()
    {
        return Array(
            Array(
                'name'   => 'id_user',
                'filter' => $_REQUEST['id']
            )
        );
    }
    
//    public function create(&$warnings = array(), $force_create = false) {
//        
//        $id_contrat = BimpTools::getValue('id_contrat');
//        $id_user = BimpTools::getValue('id_user');
//        if ($this->getList(array('id_contrat' => $id_contrat))) {
//            if($this->getList(array('id_user' => $id_user))){
//                return 'Ce contrat est déjà associé à cet utilisateur';
//            }
//        } else {
//            if($id_contrat == 0) {
//                return "Il n'y à pas de contrat à associer";
//            } else {
//                return parent::create($warnings, $force_create);
//            }
//        }
//        
//    }
}
