<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_VerifsProcess extends BDSProcess
{

    // Vérifs marges factures:

    public function initCheckFacsMargin(&$data, &$errors = array())
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
            $where = '';
            if ($date_from) {
                $where .= 'datec >= \'' . $date_from . ' 00:00:00\'';
            }
            if ($date_to) {
                $where .= ($where ? ' AND ' : '') . 'datec <= \'' . $date_to . ' 23:59:59\'';
            }

            $rows = $this->db->getRows('facture', $where, null, 'array', array('rowid'), 'rowid', 'desc');
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
                        'label'                  => 'Vérifications des marges',
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => (int) $nbElementsPerIteration
                    )
                );
            }
        }
    }

    public function executeCheckFacsMargin($step_name, &$errors = array(), $extra_data = array())
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
                            $fac_errors = $fac->checkMargin(false);
                            $fac_errors = BimpTools::merge_array($fac_errors, $fac->checkTotalAchat(false));
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

    // Vérifs Restes à payer factures: 

    public function initCheckFacsRtp(&$data, &$errors = array())
    {
        $date_from = $this->getOption('date_from', '');
        $date_to = $this->getOption('date_to', '');
        $nbElementsPerIteration = $this->getOption('nb_elements_per_iterations', 100);
        $not_classified_only = $this->getOption('not_classified_only', 1);
        $zero_only = $this->getOption('rtp_zero_only', 0);

        if (!preg_match('/^[0-9]+$/', $nbElementsPerIteration) || !(int) $nbElementsPerIteration) {
            $errors[] = 'Le nombre d\'élements par itération doit être un nombre entier positif';
        }

        if ($date_from && $date_to && $date_from > $date_to) {
            $errors[] = 'La date de début doit être inférieure à la date de fin';
        }

        if (!count($errors)) {
            $where = 'fk_statut > 0';

            if ($date_from) {
                $where .= ' AND date_valid >= \'' . $date_from . ' 00:00:00\'';
            }
            if ($date_to) {
                $where .= ' AND date_valid <= \'' . $date_to . ' 23:59:59\'';
            }

            if ($not_classified_only) {
                $where .= ' AND paye = 0';
            }

            if ($zero_only) {
                $where .= ' and remain_to_pay = 0';
            }

            $rows = $this->db->getRows('facture', $where, null, 'array', array('rowid'), 'rowid', 'desc');
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
                    'check_rtp' => array(
                        'label'                  => 'Vérifications des restes à payer',
                        'on_error'               => 'continue',
                        'elements'               => $elements,
                        'nbElementsPerIteration' => (int) $nbElementsPerIteration
                    )
                );
            }
        }
    }

    public function executeCheckFacsRtp($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'check_rtp':
                if (!empty($this->references)) {
                    $this->setCurrentObjectData('bimpcommercial', 'Bimp_Facture');
                    foreach ($this->references as $id_fac) {
                        $this->incProcessed();
                        $fac_errors = array();
                        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_fac);

                        if (BimpObject::objectLoaded($fac)) {
                            $fac_errors = $fac->checkIsPaid(false);
                        } else {
                            $fac_errors[] = 'Fac #' . $id_fac . ' non trouvée';
                        }

                        if (count($fac_errors)) {
                            $this->incIgnored();
                            $this->Error(BimpTools::getMsgFromArray($fac_errors, 'Fac #' . $id_fac), $fac, $id_fac);
                        } else {
                            $this->incUpdated();
                            $this->Success('Vérif Reste à payer OK', $fac, $id_fac);
                        }
                    }
                }
                break;
        }

        return $result;
    }

    // Vérifs réceptions: 

    public function initCheckReceptions(&$data, &$errors = array())
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
            $where = 'status = 1';

            if ($date_from) {
                $where .= ' AND date_received >= \'' . $date_from . ' 00:00:00\'';
            }

            if ($date_to) {
                $where .= ' AND date_received <= \'' . $date_from . ' 23:59:59\'';
            }

            $rows = $this->db->getRows('bl_commande_fourn_reception', $where, null, 'array', array('id'), 'id', 'asc');
            if (is_array($rows)) {
                $elements = array();
                foreach ($rows as $r) {
                    $elements[] = (int) $r['id'];
                }
                if (empty($elements)) {
                    $errors[] = 'Aucune réception a traiter trouvée';
                } else {
                    $data['steps'] = array(
                        'check_receptions' => array(
                            'label'                  => 'Vérifications des réceptions',
                            'on_error'               => 'continue',
                            'elements'               => $elements,
                            'nbElementsPerIteration' => (int) $nbElementsPerIteration
                        )
                    );
                }
            } else {
                $errors[] = $this->db->err();
            }
        }
    }

    public function executeCheckReceptions($step_name, &$errors = array(), $extra_data = array())
    {
        if (!empty($this->references)) {
            $prod_instance = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            $this->setCurrentObject(BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception'));

            $entrepots = BimpCache::getEntrepotsArray(false, false, true);

            foreach ($this->references as $id_r) {
                $where = 'inventorycode LIKE \'%_RECEP' . $id_r . '\'';
                $mvts = $this->db->getRows('stock_mouvement a', $where, null, 'array', array('a.*', 'p.serialisable'), null, null, array(
                    array(
                        'alias' => 'p',
                        'table' => 'product_extrafields',
                        'on'    => 'p.fk_object = a.fk_product'
                    )
                ));

                if (!empty($mvts)) {
                    $lines = array();

                    // Trie par ligne: 
                    foreach ($mvts as $m) {
                        if ((int) $m['serialisable']) {
                            continue;
                        }
                        $prod_instance->id = (int) $m['fk_product'];

                        if ($m['inventorycode']) {
                            if (preg_match('/^(ANNUL_)?CMDF(\d+)_LN(\d+)_RECEP' . $id_r . '$/', $m['inventorycode'], $matches)) {
                                $id_cmd = (int) $matches[2];
                                $id_line = (int) $matches[3];

                                if (!isset($lines[$id_cmd])) {
                                    $lines[$id_cmd] = array();
                                }
                                if (!isset($lines[$id_cmd][$id_line])) {
                                    $lines[$id_cmd][$id_line] = array(
                                        'recep'   => array(),
                                        'annul'   => array(),
                                        'id_prod' => (int) $m['fk_product']
                                    );
                                }

                                if ($matches[1]) {
                                    $lines[$id_cmd][$id_line]['annul'][] = $m;
                                } else {
                                    $lines[$id_cmd][$id_line]['recep'][] = $m;
                                }
                            } else {
                                $this->Alert('RECEP #' . $id_r . ' - MVT #' . $m['rowid'] . ': CODE INCORRECT: ' . $m['inventorycode'], $prod_instance);
                            }
                        } else {
                            $this->Alert('RECEP #' . $id_r . ' - MVT #' . $m['rowid'] . ': AUCUN CODE', $prod_instance);
                        }
                    }

                    if (!empty($lines)) {
                        $this->incProcessed();
                        foreach ($lines as $id_comm => $comm_lines) {
                            foreach ($comm_lines as $id_line => $line) {
                                $diff = count($line['recep']) - count($line['annul']);
                                if ($diff != 1) {
                                    $prod_instance->id = (int) $line['id_prod'];
                                    $title = '<a target="_blank" href="' . DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $id_comm . '">';
                                    $title .= (isset($entrepots[(int) $m['fk_entrepot']]) ? $entrepots[(int) $m['fk_entrepot']] : 'Entrepôt #' . $m['fk_entrepot']) . ' - ';
                                    $title .= 'Réception #' . $id_r . ': ';
                                    $title .= '</a>';
                                    $html = '<br/><br/>';
                                    foreach (array('recep' => 'réception(s)', 'annul' => 'annulation(s)') as $code => $label) {
                                        if (count($line[$code])) {
                                            $html .= '<b>' . count($line[$code]) . ' ' . $label . '</b><br/>';
                                            foreach ($line[$code] as $m) {
                                                $html .= '   - <b>' . ((int) $m['type_mouvement'] ? 'Sortie' : 'Entrée') . '</b> : ' . $m['value'] . '<br/>';
                                            }
                                        }
                                    }

                                    if (!$diff) {
                                        $this->Alert($title . 'tous les mouvements annulés.' . $html, $prod_instance, $m['inventorycode']);
                                    } else {
                                        $this->Error($title . 'incohérence trouvée.' . $html, $prod_instance, $m['inventorycode']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'Verifs',
                    'title'       => 'Vérifications',
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

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Vérifier seulement les factures non classées payées',
                        'name'          => 'not_classified_only',
                        'info'          => '',
                        'type'          => 'bool',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['not_classified_only'] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Restes à payer à 0 seulement',
                        'name'          => 'rtp_zero_only',
                        'info'          => '',
                        'type'          => 'bool',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options['rtp_zero_only'] = (int) $opt->id;
            }

            // Opérations: 
            // Vérifs marges factures: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Vérifier les marges + revals OK des factures',
                        'name'        => 'checkFacsMargin',
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

            // Vérifs restes à payer factures: 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Vérifier les restes à payer des factures',
                        'name'        => 'checkFacsRtp',
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
                if (isset($options['not_classified_only'])) {
                    $op_options[] = $options['not_classified_only'];
                }
                if (isset($options['rtp_zero_only'])) {
                    $op_options[] = $options['rtp_zero_only'];
                }

                $warnings = array_merge($warnings, $op->addAssociates('options', $op_options));
            }
        }
    }
}
