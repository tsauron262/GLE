<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpCron.php';

class BimpContratCronProcess extends BimpCron
{

    public function dailyChecks()
    {
        $this->current_cron_name = 'Vérifs quotidiennes contrats v2';
        
        // Vérif abonnemens fermés: 
        $rows = $this->db->getRows();
    }
}
