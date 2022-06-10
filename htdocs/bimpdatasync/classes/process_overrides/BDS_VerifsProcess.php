<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_VerifsProcess extends BDSProcess
{

    // Vérifs marges factures : 

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
                $where .= ($where ? ' AND ' : '') . 'datec <= \'' . $date_to . ' 00:00:00\'';
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
                        'on_error'               => 'retry',
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

            // Opérations: 

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
        }
    }
}
