<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RelancesClientsProcess extends BDSProcess
{

    // Init opérations:

    public function initRelances(&$data, &$errors = array())
    {
        if ((int) BimpTools::getArrayValueFromPath($this->options, 'process_notifs', 1)) {
            $data['steps'] = array(
                'process_notifs' => array(
                    'label'    => 'Traitement des notifications à envoyer aux commerciaux',
                    'on_error' => 'continue'
                )
            );
        }

        if ((int) BimpTools::getArrayValueFromPath($this->options, 'process_clients', 1)) {
            if ((int) BimpTools::getArrayValueFromPath($this->options, 'multiple_iterations', 1)) {
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
                $clients = $client->getFacturesToRelanceByClients(array(
                    'to_process_only' => true,
                    'display_mode'    => 'clients_list'
                ));

                if (!empty($clients)) {
                    $this->Info('Clients à traiter: ' . implode(', ', $clients));
                    $relance = $this->createRelance($errors);
                    if (BimpObject::objectLoaded($relance)) {
                        $data['data'] = array(
                            'id_relance' => $relance->id
                        );

                        $data['steps'] = array(
                            'process_relance' => array(
                                'label'                  => 'Traitement des relances',
                                'on_error'               => 'continue',
                                'elements'               => $clients,
                                'nbElementsPerIteration' => 10
                            )
                        );
                    }
                } else {
                    $data['result_html'] = BimpRender::renderAlerts('Il n\'y a aucune facture impayée à relancer', 'warning');
                    $this->Alert('Aucun client à relancer');
                }
            } else {
                $data['steps'] = array(
                    'process_relance' => array(
                        'label'    => 'Traitement des relances',
                        'on_error' => 'continue'
                    )
                );
            }
        }
    }

    // Exec opérations:

    public function executeRelances($step_name, &$errors = array(), $extra_data = array())
    {
        switch ($step_name) {
            case 'process_notifs':
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
                $clients = $client->getFacturesToRelanceByClients(array(
                    'display_mode' => 'notif_commerciaux'
                ));

                $this->DebugData($clients, 'Clients');
                break;

            case 'process_relance':
                $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
                if (!(int) BimpTools::getArrayValueFromPath($this->options, 'multiple_iterations', 1)) {
                    $this->references = $client->getFacturesToRelanceByClients(array(
                        'to_process_only' => true,
                        'display_mode'    => 'clients_list'
                    ));

                    if (empty($this->references)) {
                        $this->Info('Aucun client à relancer');
                    } else {
                        $this->info('Clients à traiter (une seule itération): ' . implode(', ', $this->references));
                        $relance = $this->createRelance($errors);

                        if (BimpObject::objectLoaded($relance)) {
                            $id_relance = (int) $relance->id;
                        } else {
                            return array();
                        }
                    }
                } else {
                    $id_relance = (int) BimpTools::getArrayValueFromPath($extra_data, 'operation/id_relance', 0);

                    if (!$id_relance) {
                        $errors[] = 'ID de la relance non transmis';
                        return array();
                    }
                }

                if (!empty($this->references)) {
                    $warnings = array();
                    $pdf_url = '';
                    $errors = $client->relancePaiements($this->references, 'cron', $warnings, $pdf_url, null, true, $this, $id_relance);
                }
                break;
        }

        return array();
    }

    // Traitements: 

    public function createRelance(&$errors = array())
    {
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

        if (count($create_warnings)) {
            $this->Alert(BimpTools::getMsgFromArray($create_warnings, 'Erreurs suite à la création de la relance'));
        }

        if (!BimpObject::objectLoaded($relance) || count($create_errors)) {
            $this->incIgnored();
            $msg = BimpTools::getMsgFromArray($create_errors, 'Echec de la création de la relance');
            $this->Error($msg);
            $errors[] = $msg;

            return null;
        }

        $this->incCreated();
        return $relance;
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
            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les notifications à envoyer aux commerciaux',
                        'name'          => 'process_notifs',
                        'info'          => 'Envoi d\'une alerte 2 jours avant la première relance',
                        'type'          => 'toggle',
                        'default_value' => 1,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter les clients à relancer',
                        'name'          => 'process_clients',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => 1,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Itérations mutliples',
                        'name'          => 'multiple_iterations',
                        'info'          => 'Effectuer les relances par paquet de 10 clients',
                        'type'          => 'toggle',
                        'default_value' => 1,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

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

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
