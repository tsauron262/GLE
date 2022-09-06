<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php';

class BDSSyncProcess extends BDSProcess {
    public function getListExtraButtons() {
        $buttons = array();
        
        if ($this->isActionAllowed('checkSynchro') && $this->canSetAction('checkSynchro')) {
            $buttons[] = array(
                'label'   => 'Vérifier la synchronisation',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('checkSynchro', array(), array())
            );
        }
        
        return $buttons;
    }
}