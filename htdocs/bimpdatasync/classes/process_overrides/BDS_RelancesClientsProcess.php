<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_RelancesClientsProcess extends BDSProcess
{

    public static $default_public_title = 'Relances des paiements clients';

    // Init opérations:

    public function initRelances(&$data, &$errors = array())
    {
        $data['steps'] = array();
        $data['data'] = array();

        if ((int) BimpTools::getArrayValueFromPath($this->options, 'process_notifs', 1)) {
            $data['steps']['process_notifs'] = array(
                'label'    => 'Traitement des notifications à envoyer aux commerciaux',
                'on_error' => 'continue'
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
                        $data['data']['id_relance'] = $relance->id;

                        $data['steps']['process_relance'] = array(
                            'label'                  => 'Traitement des relances',
                            'on_error'               => 'continue',
                            'elements'               => $clients,
                            'nbElementsPerIteration' => 10
                        );
                    }
                } else {
                    $data['result_html'] = BimpRender::renderAlerts('Il n\'y a aucune facture impayée à relancer', 'warning');
                    $this->Alert('Aucun client à relancer');
                }
            } else {
                $data['steps']['process_relance'] = array(
                    'label'    => 'Traitement des relances',
                    'on_error' => 'continue'
                );
            }
        }
    }

    // Exec opérations:

    public function executeRelances($step_name, &$errors = array(), $extra_data = array())
    {
        switch ($step_name) {
            case 'process_notifs':
                $this->processNotifsCommerciaux($errors);
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

    public function processNotifsCommerciaux(&$errors = array())
    {
        $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
        $clients = $client->getFacturesToRelanceByClients(array(
            'display_mode' => 'notif_commerciaux'
        ));

        $this->DebugData($clients, 'Clients');

        // Trie par commerciaux:
        $data = array();

        if (is_array($clients) && !empty($clients)) {
            foreach ($clients as $id_client => $factures) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);
                if (!BimpObject::objectLoaded($client)) {
                    continue;
                }
//                $email_comm_client = $client->getCommercialEmail(false, true);
				$comm_client = $client->getCommercial(false);
				$id_comm_client = 0;
				if (BimpObject::objectLoaded($comm_client)) {
					$id_comm_client = $comm_client->id;
				}

                foreach ($factures as $id_fac => $fac_data) {
                    $relance_idx = (int) $fac_data['relance_idx'];

                    if (!isset($data[$relance_idx])) {
                        $data[$relance_idx] = array();
                    }

                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

                    if (BimpObject::objectLoaded($facture)) {
                        $comm_fac = $facture->getCommercial();
						$comm_fac_id = (int) $comm_fac['id'];

                        if (BimpObject::objectLoaded($comm_fac)) {
//                            $email_comm_fac = $comm_fac->getData('email');

                            if ($comm_fac_id) {
                                if (!isset($data[$relance_idx][$comm_fac_id])) {
                                    $data[$relance_idx][$comm_fac_id] = array();
                                }

                                if (!isset($data[$relance_idx][$comm_fac_id][$id_client])) {
                                    $data[$relance_idx][$comm_fac_id][$id_client] = array();
                                }

                                $data[$relance_idx][$comm_fac_id][$id_client][$id_fac] = $fac_data;
                                continue;
                            }
                        }

                        if ($id_comm_client) {
                            if (!isset($data[$relance_idx][$id_comm_client])) {
                                $data[$relance_idx][$id_comm_client] = array();
                            }

                            if (!isset($data[$relance_idx][$id_comm_client][(int) $id_client])) {
                                $data[$relance_idx][$id_comm_client][(int) $id_client] = array();
                            }

                            $data[$relance_idx][$id_comm_client][$id_client][$id_fac] = $fac_data;
                        }
                    }
                }
            }
        }

        $this->DebugData($data, 'Trie par commerciaux');

        if (!empty($data)) {
            $dt_relance = new DateTime();
            $dt_relance->add(new DateInterval('P5D'));
            $dt_relance = $dt_relance->format('d / m / Y');

            foreach ($data as $relance_idx => $relance_data) {
                foreach ($relance_data as $idComm => $clients) {
//                    $email = BimpTools::cleanEmailsStr($email);

                    foreach ($clients as $id_client => $factures) {
                        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $id_client);

                        if (BimpObject::objectLoaded($client)) {
                            $html = 'Bonjour,<br/><br/>';

                            $html .= 'le client ' . $client->getLink() . ' va recevoir sous 5 jours ';

                            if ($relance_idx < 4) {
                                $subject = 'Lettre de rappel sous 5 jours au client ' . $client->getRef() . ' - ' . $client->getName();
                                $html .= 'une <b>lettre de rappel</b>';
                            } else {
                                $subject = 'ATTENTION : Mise en demeure sous 5 jours du client ' . $client->getRef() . ' - ' . $client->getName();
                                $html .= 'une <b>mise en demeure</b>';
                            }

                            $html .= ' concernant les retards de réglement ci-après.<br/><br/>';
                            $html .= '<b>Si vous pensez que cette ' . ($relance_idx < 4 ? 'relance' : 'mise en demeure');
                            $html .= ' n\'a pas lieu d\'être, merci d\'en informer immédiatement ';
                            $html .= '<a href="mailto:'.BimpCore::getConf('rappels_factures_financement_impayees_emails', null, 'bimpcommercial').'">Recouvrement</a>';
                            $html .= ' en justifiant votre demande (par exemple : règlement en notre possession, litige client, etc.)</b>';

                            $html .= '<br/><br/>';
                            $html .= '<table>';
                            $html .= '<thead>';
                            $html .= '<tr>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Date facture</th>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Facture</th>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Total TTC</th>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Reste à payer</th>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">Echéance</th>';
                            $html .= '<th style="padding: 5px; font-weight: bold; border-bottom: 1px solid #000; background-color: #DCDCDC">JR</th>';
                            $html .= '</tr>';
                            $html .= '</thead>';

                            $html .= '<tbody>';

                            $facs_refs = '';
                            foreach ($factures as $id_facture => $fac_data) {
                                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

                                if (BimpObject::objectLoaded($facture)) {
                                    $facs_refs .= ($facs_refs ? ' - ' : '') . $facture->getRef();
                                    $html .= '<tr>';
                                    $html .= '<td style="padding: 5px">';
                                    $html .= date('d / m / Y', strtotime($facture->getData('datef')));
                                    $html .= '</td>';

                                    $html .= '<td style="padding: 5px; width: 300px">';
                                    $html .= $facture->getLink() . '<br/>';
                                    $html .= $facture->getData('libelle');
                                    $html .= '</td>';

                                    $html .= '<td style="padding: 5px">';
                                    $html .= BimpTools::displayMoneyValue($fac_data['total_ttc']);
                                    $html .= '</td>';

                                    $html .= '<td style="padding: 5px">';
                                    $html .= BimpTools::displayMoneyValue($fac_data['remain_to_pay']);
                                    $html .= '</td>';

                                    $html .= '<td style="padding: 5px">';
                                    $html .= date('d / m / Y', strtotime($fac_data['date_lim']));
                                    $html .= '</td>';

                                    $html .= '<td style="padding: 5px">';
                                    $html .= $fac_data['retard'];
                                    $html .= '</td>';
                                    $html .= '</tr>';
                                }
                            }

                            $html .= '</tbody>';
                            $html .= '</table>';


							$code = 'notif_commercial_courrier_retard_regl';
							if (!count(BimpUserMsg::envoiMsg($code, $subject, $html, $idComm))) {
                                $this->Success('Envoi alerte au commercial OK', $client, $facs_refs);
                            } else {
                                $this->Error('Echec envoi alerte au commercial', $client, $facs_refs);
                            }
                        }
                    }
                }
            }
        }
    }

    // Install:

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'RelancesClients',
                    'title'       => ($title ? $title : static::$default_public_title),
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
                        'info'          => 'Envoi d\'une alerte 5 jours avant la première relance',
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
