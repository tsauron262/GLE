<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RelancesClientsProcess extends BDSProcess
{

    // Init opérations:

    public function initRelances(&$data, &$errors = array())
    {
        $data['steps'] = array(
            'send_relmances' => array(
                'label'    => 'Traitement des relancesss',
                'on_error' => 'continue'
            )
        );
    }

    // Exec opérations:

    public function executeRelances($step_name, &$errors = array())
    {
        switch ($step_name) {
            case 'send_relmances':
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
                
                $warnings = array();
                $pdf_url = '';
//                relancePaiements($clients = array(), $mode = 'global', &$warnings = array(), &$pdf_url = '', $date_prevue = null, $send_emails = true, $bds_process = null)
                $errors = $client->relancePaiements(array(), 'cron', $warnings, $pdf_url, null, false, $this);
                break;
        }

        return array();
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'RelancesClients',
                    'title'       => 'Relances des paiements clients',
                    'description' => '',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Traiter les relances à effectuer à date',
                        'name'          => 'relances',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);
        }
    }
}
