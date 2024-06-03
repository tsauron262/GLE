<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpCommercialCronExec extends BimpCron
{

    public function sendRappelsCommandes()
    {
        $this->current_cron_name = 'Envoi des rappels pour les commandes';
        
        $commande_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande', $commande_class);
        $this->output = $commande_class::sendRappels();
        
        return 0;
    }

    public function sendRappelsFactures()
    {
        $this->current_cron_name = 'Envoi des rappels pour les factures';
        
        $facture_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture', $facture_class);
        $this->output = $facture_class::sendRappels();
        
        return 0;
    }
    
    public function hourlyChecks()
    {
        $this->current_cron_name = 'Vérifs toutes les heures des pièces commerciales';
        
        $this->output .= '***** Vérifs Marges + reval OK des dernières factures mises à jour *****<br/>';
        $facture_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture', $facture_class);
        $this->output .= $facture_class::checkMargesRevalAll();
        
        $this->output .= '***** Vérifs Marges commandes mises à jour *****<br/>';
        $commande_class = '';
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande', $commande_class);
        $this->output .= $commande_class::checkMargesAll();
        
        return 0;
    }
}
