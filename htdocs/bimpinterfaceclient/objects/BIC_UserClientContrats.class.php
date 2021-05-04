<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/objects/BIC_UserClient.class.php';

class BIC_UserClientContrats extends BimpObject
{

    // Froit user: 

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

    // Getters: 

    public function getUserClientAvailableContratsArray()
    {
        $userClient = $this->getParentInstance();

        $contrats = array();

        if (BimpObject::objectLoaded($userClient)) {
            $current_contrats = $userClient->getAssociatedContratsList();
            foreach ($userClient->getContratsVisibles(true) as $id_contrat => $contrat) {
                if (!in_array($id_contrat, $current_contrats)) {
                    $contrats[$id_contrat] = $contrat->getData('ref');
                }
            }
        }

        return $contrats;
    }
}
