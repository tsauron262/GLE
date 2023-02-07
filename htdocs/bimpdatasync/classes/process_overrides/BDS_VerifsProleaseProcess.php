<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_VerifsProleaseProcess extends BDSProcess
{

    public static $current_version = 1;
    public static $default_public_title = 'Vérifications et corrections diverses';

    // Vérifs marges factures : 

    public function initCheckFacsReventePA(&$data, &$errors = array())
    {
        $date_from = $this->getOption('date_from', '');
        $date_to = $this->getOption('date_to', '');
        $nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);

        if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
            $errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
        }

        if ($date_from && $date_to && $date_from > $date_to) {
            $errors[] = 'La date de début doit être inférieure à la date de fin';
        }

        if (!count($errors)) {
            $where = 'df.id > 0';
            if ($date_from) {
                $where .= ' AND f.datec >= \'' . $date_from . ' 00:00:00\'';
            }
            if ($date_to) {
                $where .= ($where ? ' AND ' : '') . 'f.datec <= \'' . $date_to . ' 23:59:59\'';
            }

            $rows = $this->db->getRows('facture f', $where, null, 'array', array('rowid'), 'rowid', 'desc', array(
                'df' => array(
                    'alias' => 'df',
                    'table' => 'bf_demande',
                    'on'    => 'df.id_facture_cli_rev = f.rowid'
                )
            ));
            $elements = array();

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $elements[] = (int) $r['rowid'];
                }
            }

            if (empty($elements)) {
                $errors[] = 'Aucune facture a traiter trouvée';
            } else {
                $data['steps'] = array(
                    'check_margins' => array(
                        'label'                  => 'Vérifications des Prix d\'achat',
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => (int) $nbElementsPerIteration
                    )
                );
            }
        }
    }

    public function executeCheckFacsReventePA($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'check_margins':
                if (!empty($this->references)) {
                    $this->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
                    foreach ($this->references as $id_fac) {
                        $this->incProcessed();
                        $fac_errors = array();
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

                        if (BimpObject::objectLoaded($fac)) {
                            $demande = BimpCache::findBimpObjectInstance('bimpfinancement', 'BF_Demande', array(
                                        'id_facture_cli_rev' => $id_fac
                                            ), true);

                            if (BimpObject::objectLoaded($demande)) {
                                $total_rachat_ht = (float) $demande->getData('total_rachat_ht');

                                if (!$total_rachat_ht) {
                                    $fac_errors[] = 'Total Rachat HT non défini pour la demande de location d\'origine';
                                } else {
                                    $lines = $demande->getLines('only_prod');
                                    $total_achat = 0;

                                    foreach ($lines as $line) {
                                        $total_achat += ($line->getData('pa_ht') * $line->getData('qty'));
                                    }

                                    if ($total_achat) {
                                        $pourcentage_achat = $total_rachat_ht / $total_achat;

                                        foreach ($lines as $line) {
                                            $id_fac_line = (int) $this->db->getValue('bimp_facture_line', 'id_line', 'linked_object_name = \'bf_demande_line\' AND linked_id_object = ' . $line->id);
                                            if ($id_fac_line) {
//                                                if ($this->db->update('facturedet', array(
//                                                            'buy_price_ht' => $line->getData('pa_ht') * $pourcentage_achat
//                                                                ), 'rowid = ' . $id_fac_line) <= 0) {
//                                                    $fac_errors[] = 'Echec màj ligne #' . $id_fac_line . ' - ' . $this->db->err();
//                                                }
                                            }
                                        }
                                    } else {
                                        $fac_errors[] = 'Total achat initial nul';
                                    }
                                }
                            } else {
                                $fac_errors[] = 'Demande de location d\'origine absente';
                            }
                        } else {
                            $fac_errors[] = 'Fac #' . $id_fac . ' non trouvée';
                        }

                        if (count($fac_errors)) {
                            $this->incIgnored();
                            $this->Error(BimpTools::getMsgFromArray($fac_errors, 'Fac #' . $id_fac), $fac, $id_fac);
                        } else {
                            $this->incUpdated();
                            $this->Success('Vérif marges OK', $fac, $id_fac);
                        }
                    }
                }
                break;
        }

        return $result;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'VerifsProlease',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => '',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'A partir du',
                        'name'          => 'date_from',
                        'info'          => '',
                        'type'          => 'date',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['date_from'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Jusqu\'au',
                        'name'          => 'date_to',
                        'info'          => '',
                        'type'          => 'date',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['date_to'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nb éléments par itérations',
                        'name'          => 'nb_elements_per_iterations',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '100',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['nb_elements_per_iterations'] = (int) $opt->id;
            }

            // Opérations: 
            // Vérifs PA facs reventes clients: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Vérifier les PA des factures de revente',
                        'name'        => 'checkFacsReventePA',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0,
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $op_options = array();

                if (isset($options['date_from'])) {
                    $op_options[] = $options['date_from'];
                }
                if (isset($options['date_to'])) {
                    $op_options[] = $options['date_to'];
                }
                if (isset($options['nb_elements_per_iterations'])) {
                    $op_options[] = $options['nb_elements_per_iterations'];
                }

                $warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
            }
        }
    }

    public static function updateProcess($id_process, $cur_version, &$warnings = array())
    {
        $errors = array();

        if ($cur_version < 2) {
            // Opération "Reconstruction des docs signés": 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $id_process,
                        'title'         => 'Reconstruction des docs signés',
                        'name'          => 'correctSignedDoc',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 15
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $errors = array_merge($errors, $op->addOptions(array('date_from')));
            }
        }

        return $errors;
    }
}
