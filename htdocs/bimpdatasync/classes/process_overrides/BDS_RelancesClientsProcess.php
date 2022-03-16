<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RelancesClientsProcess extends BDSProcess
{

    // Init opérations:

    public function initRelances(&$data, &$errors = array())
    {
        $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
        $clients = $client->getFacturesToRelanceByClients(true, null, array(), null, false, 'clients_list');

        if (!empty($clients)) {            
            $this->setCurrentObjectData('bimpcommercial', 'BimpRelanceClients');
            $this->incProcessed();

            global $user;
            $create_errors = array();
            $create_warnings = array();

            $relance = BimpObject::createBimpObject('bimpcommercial', 'BimpRelanceClients', array(
                        'id_user'     => (BimpObject::objectLoaded($user) ? (int) $user->id : 1),
                        'date'        => date('Y-m-d H:i:s'),
                        'mode'        => 'cron',
                        'date_prevue' => date('Y-m-d')
                            ), true, $create_errors, $create_warnings);

            if (BimpObject::objectLoaded($relance)) {
                $this->incCreated();
                $data['steps'] = array(
                    'process_relance_' . $relance->id => array(
                        'label'                  => 'Traitement des relances',
                        'on_error'               => 'continue',
                        'elements'               => $clients,
                        'nbElementsPerIteration' => 10
                    )
                );
            } else {
                $this->incIgnored();
                $msg = BimpTools::getMsgFromArray($create_errors, 'Echec de la création de la relance');
                $this->Error($msg);
                $errors[] = $msg;
            }
        } else {
            $data['result_html'] = BimpRender::renderAlerts('Il n\'y a aucune facture impayée à relancer', 'warning');
            $this->Alert('Aucun client à relancer');
        }
    }

    // Exec opérations:

    public function executeRelances($step_name, &$errors = array())
    {
        if (preg_match('/^process_relance_(\d+)$/', $step_name, $matches)) {
            $id_relance = (int) $matches[1];

            $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');

            $warnings = array();
            $pdf_url = '';
            $errors = $client->relancePaiements($this->references, 'cron', $warnings, $pdf_url, null, true, $this, $id_relance);
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
