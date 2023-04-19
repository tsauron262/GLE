<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommercialCronExec extends BimpCron
{

    public function sendRappelsCommandes()
    {
        $this->current_cron_name = 'Envoi des rappels pour les commandes';
        
        $commande_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande', $commande_class);
        $this->output = $commande_class::checkLinesEcheances(60);
        
        return 0;
    }

    public function sendRappelsFactures()
    {
        $facture_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture', $facture_class);
        $this->output = $facture_class::sendRappels();
        
        return 0;
    }
}
