<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/objects/BIC_UserClient.class.php';

class BIC_UserClientContrat extends BimpObject
{

    // Froit user: 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            $parentUser = $this->getParentInstance();

            if (BimpObject::objectLoaded($parentUser)) {
                if ($userClient->isAdmin()) {
                    if ((int) $userClient->getData('id_client') === (int) $parentUser->getData('id_client')) {
                        return 1;
                    }
                } else {
                    if ((int) $userClient->id === (int) $parentUser->id) {
                        return 1;
                    }
                }
            }

            echo 'ici'; exit;
            return 0;
        }

        return 1;
    }

    public function canClientCreate()
    {
        global $userClient;
        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }
        return 0;
    }
    
    public function canClientEdit()
    {
        return  $this->canClientCreate();
    }

    public function canClientDelete()
    {
        return $this->canClientCreate();
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
