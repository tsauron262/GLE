<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpContratCronProcess extends BimpCron
{

    public function dailyChecks()
    {
        $this->current_cron_name = 'Vérifs quotidiennes contrats v2';

        // Vérif abonnemens fermés: 
        $lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                    'c.version'           => 2,
                    'a.statut'            => 4,
                    'a.date_fin_validite' => array(
                        'operator' => '<',
                        'value'    => date('Y-m-d') . ' 00:00:00'
                    )
                        ), 'rowid', 'asc', array(
                    'c' => array('table' => 'contrat', 'on' => 'c.rowid = a.fk_contrat')
        ));

        if (!empty($lines)) {
            $infos_str = '';
            foreach ($lines as $line) {
                $infos = array();
                $line->checkStatus($infos);

                if (!empty($infos)) {
                    $infos_str .= BimpTools::getMsgFromArray($infos, 'Contrat #' . $line->getData('fk_contrat') . ' - ligne n°' . $line->getData('rang'));
                }
            }

            $this->output .= '<br/><br/>***** Vérif statuts *****<br/>';
            $this->output .= 'Statut vérifié pour ' . count($lines) . ' ligne(s)<br/>';
            $this->output .= $infos_str;
        }

        BimpObject::loadClass('bimpcontrat', 'BCT_Contrat');
        
        // Renouvellements auto : 
        $this->output .= '<br/><br/>***** Renouvellements automatiques *****<br/>';
        $this->output .= BCT_Contrat::RenouvAuto();

        // Tâches renouvellements manuels: 
        $this->output .= '<br/><br/>***** Tâches Renouvellements *****<br/>';
        $this->output .= BCT_Contrat::createRenouvTasks();
        
        // Tâches renouvellements manuels: 
        $this->output .= '<br/><br/>***** Alerte ligne inactive *****<br/>';
        $this->output .= BCT_Contrat::checkInactivesLines();
        
        // Alertes factures impayées: 
        $this->output .= '<br/><br/>***** Alertes factures impayées *****<br/>';
        $this->output .= BCT_Contrat::sendAlertUnpaidFacsAbo();

        return 0;
    }
}
