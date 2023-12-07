<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpContratCronProcess extends BimpCron
{

    public function dailyChecks()
    {
        $this->current_cron_name = 'Vérifs quotidiennes contrats v2';

        // Vérif abonnemens fermés: 
        $lines = BimpCache::getBimpObjectObjects('bimpcontrat', 'BCT_ContratLine', array(
                    'statut'            => 4,
                    'date_fin_validite' => array(
                        'operator' => '<',
                        'value'    => date('Y-m-d') . ' 00:00:00'
                    )
        ));

        if (!empty($lines)) {
            foreach ($lines as $line) {
                $line->checkStatus();
            }

            $this->output .= 'Statut vérifié pour ' . count($lines) . ' ligne(s)';
        }

        return 0;
    }
}
