<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpSupportCronExec extends BimpCron
{
    public static function sendAlertesClients()
    {
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        $result = BS_SAV::sendAlertesClientsUnrestituteSav();
        if ($result) {
            $this->output .= ($this->output ? '<br/><br/>' : '') . '---------- Alertes clients SAV non restitués ----------<br/><br/>';
            $this->output .= $result;
        }
        return 0;
    }
}