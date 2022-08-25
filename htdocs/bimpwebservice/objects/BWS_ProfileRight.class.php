<?php

class BWS_ProfileRight extends BimpObject
{

    // Getters params: 

    public function getProfileListTitle()
    {
        $profile = $this->getParentInstance();

        if (BimpObject::objectLoaded($profile)) {
            return 'Droits du profile webservice "' . $profile->getData('name') . '"';
        }

        return 'Droits profiles webservice';
    }

    // Getters array: 

    public function getRequestsArray()
    {
        if (!defined('BWS_LIB_INIT')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpwebservice/BWS_Lib.php';
        }

        return BWSApi::getRequestsArray();
    }
}
