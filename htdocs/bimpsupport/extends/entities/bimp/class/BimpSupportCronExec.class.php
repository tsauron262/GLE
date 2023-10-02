<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpSupportCronExec extends BimpCron
{
    public function sendEcologic($sendMail = false)
    {
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        
        $savs = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_SAV', array('status_ecologic' => 1));
        $mail = '';
        foreach($savs as $sav){
            $suc = '';
            $result = $sav->actionSendDemandeEcologic(array(), $suc);
            if (1) {
                $msg = '';
                $msg .= $sav->getLink();
                if(count($result['errors'])){
                    $msg .= '<br/>'.implode('<br/>', $result['errors']);
                }
                if(count($result['warnings'])){
                    $msg .= '<br/>'.implode('<br/>', $result['warnings']);
                }
                $this->output .= ($this->output ? '<br/><br/>' : '').$msg;
                if(count($result['errors']) || count($result['warnings'])){
                    $mail .= ($mail != '' ? '<br/><br/>' : '').$msg;
                }
            }
        }
        if($mail != '' && $sendMail){
            mailSyn2('Probléme envoie ecologic', 'tommy@bimp.fr,jc.cannet@bimp.fr', null, $mail);
            $this->output .= ($this->output ? '<br/><br/>' : '').'Mail envoyé';
        }
        return 0;
    }
}