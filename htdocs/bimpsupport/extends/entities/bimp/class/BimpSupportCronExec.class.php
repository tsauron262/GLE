<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpSupportCronExec extends BimpCron
{
    public function sendEcologic()
    {
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        
        $savs = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', array('status_ecologic' => 1));
        foreach($savs as $sav){
            $suc = '';
            $result = $sav->actionSendDemandeEcologic(array(), $suc);
            if (1) {
                $this->output .= ($this->output ? '<br/><br/>' : '');
                $this->output .= $sav->getLink();
                if(count($result['errors']))
                    $this->output .= implode('<br/>', $result['errors']);
                if(count($result['warnings']))
                    $this->output .= implode('<br/>', $result['warnings']);
            }
        }
        return 0;
    }
}