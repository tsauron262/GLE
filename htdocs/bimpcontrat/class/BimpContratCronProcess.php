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
            $infos = '';
            foreach ($lines as $line) {
                $line->checkStatus($infos);
            }

            $this->output .= 'Statut vérifié pour ' . count($lines) . ' ligne(s)<br/><br/>';
            $this->output .= $infos;
        }

        return 0;
    }
}
